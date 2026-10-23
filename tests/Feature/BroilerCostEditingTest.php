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

class BroilerCostEditingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_chick_cost_can_be_corrected_even_after_closing_without_changing_birds(): void
    {
        $cycle = BroilerCycle::factory()->create(['closed_on' => today()]);
        $this->put(route('broiler.chick-cost.update', $cycle), ['chick_cost' => 1250.50, 'initial_birds' => 1])->assertSessionHasNoErrors()->assertRedirect(route('broiler.show', $cycle));
        $this->assertSame('1250.50', $cycle->fresh()->chick_cost);
        $this->assertSame(1000, $cycle->fresh()->initial_birds);
        $this->assertSame(-1250.50, app(BroilerLedger::class)->summary($cycle->fresh())['profit']);
    }

    #[TestWith(['expense'])]
    #[TestWith(['medicine'])]
    public function test_editing_cost_updates_profit_and_category_without_duplicate_entries(string $kind): void
    {
        $cycle = BroilerCycle::factory()->create();
        $entry = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => $kind, 'amount' => 10, 'category' => $kind === 'expense' ? 'heating' : 'medicines']);
        $this->put(route('broiler.entries.cost.update', [$cycle, $entry]), ['amount' => 600, 'description' => 'تصحيح', 'category' => 'bedding', 'kind' => 'sale', 'birds' => 999])->assertSessionHasNoErrors();
        $summary = app(BroilerLedger::class)->summary($cycle);
        $this->assertSame(1600.0, $summary['costs']);
        $this->assertSame(600.0, $summary['expenses'][$kind === 'expense' ? 'bedding' : 'medicines']);
        $this->assertSame($kind, $entry->fresh()->kind);
        $this->assertDatabaseCount('broiler_entries', 1);
    }

    public function test_feed_price_correction_recalculates_cost_without_changing_stock(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $entry = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'feed_delivery', 'feed_kg' => 1250, 'amount' => 100]);
        $this->put(route('broiler.entries.cost.update', [$cycle, $entry]), ['price_per_ton' => 20000, 'shipping_cost' => 500, 'payment_status' => 'credit', 'feed_kg' => 1, 'amount' => 1])->assertSessionHasNoErrors();
        $this->assertSame('25500.00', $entry->fresh()->amount);
        $this->assertSame('credit', $entry->fresh()->payment_status);
        $this->assertSame(1250.0, app(BroilerLedger::class)->summary($cycle)['feed_stock']);
        $this->get(route('broiler.show', $cycle))->assertOk()->assertSee('تعديل المصروف')->assertSee('تعديل ثمن الكتاكيت');
    }

    public function test_other_cycle_and_non_cost_entries_cannot_be_edited(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $other = BroilerEntry::factory()->create(['kind' => 'expense']);
        $this->put(route('broiler.entries.cost.update', [$cycle, $other]), ['amount' => 5, 'description' => 'test'])->assertNotFound();
        $sale = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'sale']);
        $this->put(route('broiler.entries.cost.update', [$cycle, $sale]), ['amount' => 5])->assertNotFound();
    }

    #[TestWith([-1])]
    #[TestWith([1.111])]
    #[TestWith([null])]
    public function test_invalid_chick_cost_does_not_change_data(mixed $amount): void
    {
        $cycle = BroilerCycle::factory()->create();
        $this->put(route('broiler.chick-cost.update', $cycle), ['chick_cost' => $amount])->assertSessionHasErrors('chick_cost');
        $this->assertSame('1000.00', $cycle->fresh()->chick_cost);
    }

    public function test_invalid_expense_fields_are_rejected(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $entry = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'expense', 'amount' => 10]);
        $this->put(route('broiler.entries.cost.update', [$cycle, $entry]), ['amount' => -5, 'category' => 'fake', 'description' => ''])->assertSessionHasErrors(['amount', 'category', 'description']);
        $this->assertSame('10.00', $entry->fresh()->amount);
    }

    public function test_app_lock_protects_cost_edits(): void
    {
        $setting = new FarmSetting;
        $setting->herd_birth_date = today();
        $setting->total_herd_count = 0;
        $setting->app_pin = Hash::make('1234');
        $setting->save();
        $cycle = BroilerCycle::factory()->create();
        $entry = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'expense']);
        $this->put(route('broiler.chick-cost.update', $cycle), ['chick_cost' => 0])->assertRedirect(route('app-lock.create'));
        $this->put(route('broiler.entries.cost.update', [$cycle, $entry]), ['amount' => 0])->assertRedirect(route('app-lock.create'));
    }
}
