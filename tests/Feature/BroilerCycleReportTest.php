<?php

namespace Tests\Feature;

use App\BroilerLedger;
use App\Models\BroilerCycle;
use App\Models\BroilerEntry;
use App\Models\BroilerHouseExpense;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BroilerCycleReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_report_uses_initial_chicks_and_all_recorded_costs_in_this_cycle(): void
    {
        $cycle = BroilerCycle::factory()->create(['initial_birds' => 1000]);
        foreach (['heating' => 1000, 'bedding' => 500, 'salaries' => 2000, 'miscellaneous' => 250, 'other' => 900] as $category => $amount) {
            $this->post(route('broiler.entries.store', $cycle), ['kind' => 'expense', 'recorded_on' => today()->toDateString(), 'category' => $category, 'amount' => $amount, 'description' => 'مصروف'])->assertSessionHasNoErrors();
        }
        BroilerEntry::factory()->create(['kind' => 'expense', 'category' => 'heating', 'amount' => 99999]);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'daily', 'birds' => 100, 'amount' => 0]);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'sale', 'birds' => 100, 'amount' => 10000]);
        $summary = app(BroilerLedger::class)->summary($cycle);
        $this->assertSame(1.0, $summary['chick_costs']['heating']);
        $this->assertSame(0.5, $summary['chick_costs']['bedding']);
        $this->assertSame(2.0, $summary['chick_costs']['salaries']);
        $this->assertSame(0.25, $summary['chick_costs']['miscellaneous']);
        $this->assertSame(0.9, $summary['chick_costs']['other']);
        $this->assertSame(1.0, $summary['chick_costs']['chicks']);
        $this->assertSame(5.65, $summary['chick_cost_total']);
        $this->get(route('broiler.show', $cycle))->assertOk()->assertSee('تقرير نصيب الكتكوت')->assertSee('5.650')->assertSee('1,000 كتكوت عند بداية الدورة');
    }

    #[TestWith(['cash', 'كاش'])]
    #[TestWith(['credit', 'آجل'])]
    public function test_payment_mode_is_saved_without_changing_stock_or_expense(string $mode, string $label): void
    {
        $cycle = BroilerCycle::factory()->create(['chick_cost' => 0]);
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'feed_delivery', 'recorded_on' => today()->toDateString(), 'weight_tons' => 1, 'price_per_ton' => 20000, 'shipping_cost' => 500, 'feed_type' => 'بادئ', 'payment_status' => $mode])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('broiler_entries', ['broiler_cycle_id' => $cycle->id, 'payment_status' => $mode, 'amount' => 20500]);
        $summary = app(BroilerLedger::class)->summary($cycle);
        $this->assertSame(20500.0, $summary['costs']);
        $this->assertSame(1000.0, $summary['feed_stock']);
        $this->get(route('broiler.show', $cycle))->assertOk()->assertSee($label);
    }

    public function test_legacy_delivery_payment_is_unspecified_and_empty_report_is_zero(): void
    {
        $cycle = BroilerCycle::factory()->create(['chick_cost' => 0]);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'feed_delivery', 'payment_status' => null, 'amount' => 0]);
        $this->assertSame(0.0, app(BroilerLedger::class)->summary($cycle)['chick_cost_total']);
        $this->get(route('broiler.show', $cycle))->assertOk()->assertSee('طريقة الدفع غير محددة');
    }

    public function test_medicines_vaccinations_feed_and_house_allocation_are_counted_once(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 2));
        $cycle = BroilerCycle::factory()->create(['initial_birds' => 1000, 'chick_cost' => 1000, 'started_on' => '2026-11-01']);
        BroilerHouseExpense::factory()->create(['broiler_house_id' => $cycle->broiler_house_id, 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-10', 'amount' => 1000]);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'medicine', 'amount' => 300]);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'expense', 'category' => 'medicines', 'amount' => 200]);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'feed_delivery', 'feed_kg' => 1000, 'amount' => 20500, 'shipping_cost' => 500]);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'daily', 'amount' => 0, 'medicines' => 'دواء مسجل كملاحظة']);

        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'expense', 'category' => 'vaccinations', 'recorded_on' => '2026-11-02', 'amount' => 400, 'description' => 'لقاح وأجرة التحصين'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('broiler_entries', ['broiler_cycle_id' => $cycle->id, 'category' => 'vaccinations', 'amount' => 400]);
        $summary = app(BroilerLedger::class)->summary($cycle);
        $this->assertSame(0.5, $summary['chick_costs']['medicines']);
        $this->assertSame(0.4, $summary['chick_costs']['vaccinations']);
        $this->assertSame(20.5, $summary['chick_costs']['feed']);
        $this->assertSame(0.2, $summary['chick_costs']['house']);
        $this->assertSame(22.6, $summary['chick_cost_total']);
        $this->assertSame(22600.0, array_sum($summary['chick_cost_amounts']));
        $this->assertSame(22600.0, $summary['costs']);
        $this->get(route('broiler.show', [$cycle, 'tab' => 'reports']))->assertOk()->assertSee('22.600')->assertSee('تحصينات')->assertSee('إجمالي التكلفة المسجلة');
    }
}
