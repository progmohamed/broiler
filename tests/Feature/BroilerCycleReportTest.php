<?php

namespace Tests\Feature;

use App\BroilerLedger;
use App\Models\BroilerCycle;
use App\Models\BroilerEntry;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BroilerCycleReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_report_uses_initial_chicks_and_only_selected_expenses_in_this_cycle(): void
    {
        $cycle = BroilerCycle::factory()->create(['initial_birds' => 1000]);
        foreach (['heating' => 1000, 'bedding' => 500, 'salaries' => 2000, 'miscellaneous' => 250, 'other' => 900] as $category => $amount) {
            $this->post(route('broiler.entries.store', $cycle), ['kind' => 'expense', 'recorded_on' => today()->toDateString(), 'category' => $category, 'amount' => $amount, 'description' => 'مصروف'])->assertSessionHasNoErrors();
        }
        BroilerEntry::factory()->create(['kind' => 'expense', 'category' => 'heating', 'amount' => 99999]);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'daily', 'birds' => 100, 'amount' => 0]);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'sale', 'birds' => 100, 'amount' => 10000]);
        $summary = app(BroilerLedger::class)->summary($cycle);
        $this->assertSame(['heating' => 1.0, 'bedding' => 0.5, 'salaries' => 2.0, 'miscellaneous' => 0.25], $summary['chick_costs']);
        $this->assertSame(3.75, $summary['chick_cost_total']);
        $this->get(route('broiler.show', $cycle))->assertOk()->assertSee('تقرير نصيب الكتكوت')->assertSee('3.750')->assertSee('1,000 كتكوت عند بداية الدورة');
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
        $cycle = BroilerCycle::factory()->create();
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'feed_delivery', 'payment_status' => null]);
        $this->assertSame(0.0, app(BroilerLedger::class)->summary($cycle)['chick_cost_total']);
        $this->get(route('broiler.show', $cycle))->assertOk()->assertSee('طريقة الدفع غير محددة');
    }
}
