<?php

namespace Tests\Feature;

use App\BroilerLedger;
use App\Models\BroilerCycle;
use App\Models\BroilerEntry;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BroilerFeedInventoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_delivery_adds_stock_and_expense_and_daily_only_reduces_stock(): void
    {
        $cycle = BroilerCycle::factory()->create(['chick_cost' => 100]);
        $this->post(route('broiler.entries.store', $cycle), $this->delivery(['weight_tons' => 1, 'price_per_ton' => 20000, 'shipping_cost' => 500, 'amount' => 1, 'category' => 'salaries']))->assertSessionHasNoErrors()->assertRedirect(route('broiler.show', $cycle));
        $this->assertDatabaseHas('broiler_entries', ['broiler_cycle_id' => $cycle->id, 'kind' => 'feed_delivery', 'amount' => 20500, 'category' => 'feed']);
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'daily', 'recorded_on' => today()->toDateString(), 'birds' => 0, 'feed_sacks' => 2, 'amount' => 1000, 'unit_price' => 100])->assertSessionHasNoErrors();
        $summary = app(BroilerLedger::class)->summary($cycle);
        $this->assertSame(900.0, $summary['feed_stock']);
        $this->assertSame(20600.0, $summary['costs']);
        $this->assertSame(20500.0, $summary['expenses']['feed']);
        $this->assertSame(100.0, $summary['feed']);
        $this->assertDatabaseHas('broiler_entries', ['kind' => 'daily', 'amount' => 0, 'unit_price' => 0]);
        $this->get(route('broiler.show', $cycle))->assertOk()->assertSee('رصيد المخزن')->assertSee('20,500.00')->assertSee('نقلة علف');
    }

    public function test_fractional_tons_and_sacks_preserve_price_precision_and_stock(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $this->post(route('broiler.entries.store', $cycle), $this->delivery(['weight_tons' => 1.25, 'price_per_ton' => 27555.55, 'shipping_cost' => 400, 'feed_kg' => 1, 'unit_price' => 1]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('broiler_entries', ['kind' => 'feed_delivery', 'feed_kg' => 1250, 'price_per_ton' => 27555.55, 'amount' => 34844.44]);
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'daily', 'recorded_on' => today()->toDateString(), 'birds' => 0, 'feed_sacks' => 2.5, 'feed_kg' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('broiler_entries', ['kind' => 'daily', 'feed_kg' => 125, 'amount' => 0]);
        $this->assertSame(1125.0, app(BroilerLedger::class)->summary($cycle)['feed_stock']);
        $this->get(route('broiler.show', $cycle))->assertSee('1.125')->assertSee('شيكارة')->assertSee('27,555.55');
    }

    public function test_prevents_overdrawn_stock_and_does_not_use_another_cycles_delivery(): void
    {
        $other = BroilerCycle::factory()->create();
        $this->post(route('broiler.entries.store', $other), $this->delivery())->assertSessionHasNoErrors();
        $cycle = BroilerCycle::factory()->create();
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'daily', 'recorded_on' => today()->toDateString(), 'birds' => 0, 'feed_sacks' => 0.02])->assertSessionHasErrors('feed_sacks');
        $this->assertDatabaseMissing('broiler_entries', ['broiler_cycle_id' => $cycle->id]);
    }

    public function test_later_delivery_cannot_fund_earlier_consumption(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $this->post(route('broiler.entries.store', $cycle), $this->delivery())->assertSessionHasNoErrors();
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'daily', 'recorded_on' => today()->subDay()->toDateString(), 'birds' => 0, 'feed_sacks' => 0.02])->assertSessionHasErrors('feed_sacks');
        $this->assertDatabaseCount('broiler_entries', 1);
    }

    public function test_backdated_consumption_preserves_stock_for_already_recorded_days(): void
    {
        $cycle = BroilerCycle::factory()->create();
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'feed_delivery', 'recorded_on' => today()->subDays(3), 'feed_kg' => 100]);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'daily', 'recorded_on' => today()->subDay(), 'daily_on' => today()->subDay(), 'feed_kg' => 80, 'amount' => 0]);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'feed_delivery', 'recorded_on' => today(), 'feed_kg' => 100]);
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'daily', 'recorded_on' => today()->subDays(2)->toDateString(), 'birds' => 0, 'feed_sacks' => 1])->assertSessionHasErrors('feed_sacks');
        $this->assertDatabaseCount('broiler_entries', 3);
    }

    public function test_fractional_deliveries_can_be_consumed_exactly(): void
    {
        $cycle = BroilerCycle::factory()->create();
        foreach ([0.001, 0.002] as $quantity) {
            $this->post(route('broiler.entries.store', $cycle), $this->delivery(['weight_tons' => $quantity]))->assertSessionHasNoErrors();
        }
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'daily', 'recorded_on' => today()->toDateString(), 'birds' => 0, 'feed_sacks' => 0.06])->assertSessionHasNoErrors();
        $this->assertSame(0.0, app(BroilerLedger::class)->summary($cycle)['feed_stock']);
    }

    #[TestWith(['disinfection'])]
    #[TestWith(['heating'])]
    #[TestWith(['salaries'])]
    #[TestWith(['electricity'])]
    #[TestWith(['maintenance'])]
    #[TestWith(['medicines'])]
    #[TestWith(['other'])]
    public function test_expense_categories_are_saved_and_totaled(string $category): void
    {
        $cycle = BroilerCycle::factory()->create();
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'expense', 'recorded_on' => today()->toDateString(), 'category' => $category, 'amount' => 125.50, 'description' => 'مصروف الدورة'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('broiler_entries', ['broiler_cycle_id' => $cycle->id, 'category' => $category, 'amount' => 125.50]);
        $summary = app(BroilerLedger::class)->summary($cycle);
        $this->assertSame(125.50, $summary['expenses'][$category]);
        $this->assertSame(1125.50, $summary['costs']);
    }

    public function test_medicine_form_automatically_uses_medicines_category(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'medicine', 'recorded_on' => today()->toDateString(), 'amount' => 50, 'description' => 'تحصين', 'category' => 'salaries'])->assertSessionHasNoErrors();
        $this->assertSame(50.0, app(BroilerLedger::class)->summary($cycle)['expenses']['medicines']);
        $this->assertDatabaseHas('broiler_entries', ['kind' => 'medicine', 'category' => 'medicines']);
    }

    public function test_rejects_missing_or_unknown_expense_category(): void
    {
        $cycle = BroilerCycle::factory()->create();
        foreach ([null, 'invalid', 'feed'] as $category) {
            $this->post(route('broiler.entries.store', $cycle), ['kind' => 'expense', 'category' => $category, 'recorded_on' => today()->toDateString(), 'description' => 'مصروف', 'amount' => 1])->assertSessionHasErrors('category');
        }
        $this->assertDatabaseCount('broiler_entries', 0);
    }

    #[TestWith(['weight_tons', 0])]
    #[TestWith(['weight_tons', -1])]
    #[TestWith(['weight_tons', 0.0001])]
    #[TestWith(['price_per_ton', -1])]
    #[TestWith(['price_per_ton', 1.111])]
    #[TestWith(['shipping_cost', -1])]
    #[TestWith(['feed_type', ''])]
    public function test_rejects_invalid_delivery_values(string $field, mixed $value): void
    {
        $cycle = BroilerCycle::factory()->create();
        $this->post(route('broiler.entries.store', $cycle), $this->delivery([$field => $value]))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('broiler_entries', 0);
    }

    public function test_closed_cycle_rejects_deliveries(): void
    {
        $cycle = BroilerCycle::factory()->create(['closed_on' => today()]);
        $this->post(route('broiler.entries.store', $cycle), $this->delivery())->assertSessionHasErrors('cycle');
        $this->assertDatabaseCount('broiler_entries', 0);
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    private function delivery(array $overrides = []): array
    {
        return array_replace(['kind' => 'feed_delivery', 'recorded_on' => today()->toDateString(), 'weight_tons' => 0.1, 'price_per_ton' => 20000, 'shipping_cost' => 0, 'feed_type' => 'بادئ'], $overrides);
    }
}
