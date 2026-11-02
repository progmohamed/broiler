<?php

namespace Tests\Feature;

use App\BroilerLedger;
use App\Models\BroilerCycle;
use App\Models\BroilerEntry;
use App\Models\FarmSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BroilerExpenseDeletionTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['expense', false])]
    #[TestWith(['expense', true])]
    #[TestWith(['medicine', false])]
    public function test_deleting_expense_recalculates_costs_and_returns_to_expenses(string $kind, bool $closed): void
    {
        $cycle = BroilerCycle::factory()->create(['closed_on' => $closed ? today() : null]);
        $entry = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => $kind, 'category' => 'medicines', 'amount' => 500]);
        $kept = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'expense', 'category' => 'heating', 'amount' => 200]);

        $this->delete(route('broiler.entries.cost.destroy', [$cycle, $entry]))->assertRedirect(route('broiler.show', [$cycle, 'tab' => 'costs']))->assertSessionHas('cycle_tab', 'costs');

        $this->assertModelMissing($entry);
        $this->assertModelExists($kept);
        $summary = app(BroilerLedger::class)->summary($cycle);
        $this->assertSame(1200.0, $summary['costs']);
        $this->assertSame(-1200.0, $summary['profit']);
        $this->assertSame(1.2, $summary['chick_cost_total']);
        $this->assertSame(0.0, $summary['expenses']['medicines']);
    }

    #[TestWith(['sale'])]
    #[TestWith(['daily'])]
    #[TestWith(['weight'])]
    #[TestWith(['feed_delivery'])]
    public function test_non_expense_records_cannot_be_deleted(string $kind): void
    {
        $entry = BroilerEntry::factory()->create(['kind' => $kind]);
        $this->delete(route('broiler.entries.cost.destroy', [$entry->cycle, $entry]))->assertNotFound();
        $this->assertModelExists($entry);
    }

    public function test_other_cycle_and_missing_records_cannot_be_deleted(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $entry = BroilerEntry::factory()->create();
        $this->delete(route('broiler.entries.cost.destroy', [$cycle, $entry]))->assertNotFound();
        $this->delete(route('broiler.entries.cost.destroy', [$cycle, 99999]))->assertNotFound();
        $this->assertModelExists($entry);
    }

    public function test_lock_blocks_deletion_and_expense_page_has_confirmation(): void
    {
        $entry = BroilerEntry::factory()->create();
        $this->get(route('broiler.show', $entry->cycle))->assertSee('data-confirm-delete', false)->assertSee(route('broiler.entries.cost.destroy', [$entry->cycle, $entry]), false);
        $setting = new FarmSetting;
        $setting->herd_birth_date = today();
        $setting->total_herd_count = 0;
        $setting->app_pin = Hash::make('1234');
        $setting->save();

        $this->delete(route('broiler.entries.cost.destroy', [$entry->cycle, $entry]))->assertRedirect(route('app-lock.create'));
        $this->assertModelExists($entry);
    }
}
