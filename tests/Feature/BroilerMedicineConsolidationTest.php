<?php

namespace Tests\Feature;

use App\BroilerLedger;
use App\Models\BroilerCycle;
use App\Models\BroilerEntry;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BroilerMedicineConsolidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_conversion_preserves_every_field_and_financial_totals_and_is_repeatable(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $medicine = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'medicine', 'amount' => 125.75, 'notes' => 'جرعة الدواء']);
        $existing = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'expense', 'category' => 'medicines', 'amount' => 50]);
        $before = $medicine->fresh()->getAttributes();
        $existingBefore = $existing->fresh()->getAttributes();
        $summary = app(BroilerLedger::class)->summary($cycle);
        $migration = require database_path('migrations/2026_11_02_092651_convert_broiler_medicines_to_expenses.php');

        $migration->up();
        $migration->up();

        $this->assertSame(array_replace($before, ['kind' => 'expense', 'category' => 'medicines']), $medicine->fresh()->getAttributes());
        $this->assertSame($existingBefore, $existing->fresh()->getAttributes());
        $this->assertSame($summary, app(BroilerLedger::class)->summary($cycle));
        $this->assertDatabaseCount('broiler_entries', 2);
    }

    public function test_old_medicine_form_submissions_are_saved_as_medicine_expenses(): void
    {
        $cycle = BroilerCycle::factory()->create();

        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'medicine', 'recorded_on' => today()->toDateString(), 'description' => 'دواء', 'amount' => 100])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('broiler_entries', ['broiler_cycle_id' => $cycle->id, 'kind' => 'expense', 'category' => 'medicines', 'amount' => 100]);
        $this->assertDatabaseMissing('broiler_entries', ['kind' => 'medicine']);
        $this->get(route('broiler.show', [$cycle, 'tab' => 'costs']))->assertOk()->assertSee('+ مصروف إضافي')->assertDontSee('+ دواء / تحصين');
    }
}
