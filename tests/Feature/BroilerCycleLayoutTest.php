<?php

namespace Tests\Feature;

use App\Models\BroilerCycle;
use App\Models\BroilerEntry;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BroilerCycleLayoutTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_daily_tab_is_default_and_requested_tabs_are_restored(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $this->get(route('broiler.show', $cycle))->assertOk()->assertViewHas('activeTab', 'daily');
        $this->get(route('broiler.show', [$cycle, 'tab' => 'costs']))->assertViewHas('activeTab', 'costs')->assertSee('فلترة حسب التصنيف');
        $this->get(route('broiler.show', [$cycle, 'tab' => ['bad'], 'category' => ['bad']]))->assertOk()->assertViewHas('activeTab', 'daily')->assertViewHas('category', '');
    }

    public function test_cost_filter_is_scoped_to_cycle_and_does_not_change_financial_totals(): void
    {
        $cycle = BroilerCycle::factory()->create(['chick_cost' => 0]);
        $bedding = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'category' => 'bedding', 'amount' => 100]);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'category' => 'heating', 'amount' => 200]);
        BroilerEntry::factory()->create(['category' => 'bedding', 'amount' => 999]);
        $this->get(route('broiler.show', [$cycle, 'tab' => 'costs', 'category' => 'bedding']))->assertOk()
            ->assertViewHas('costEntries', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $bedding->id)
            ->assertViewHas('summary', fn ($summary) => $summary['costs'] === 300.0);
    }

    #[TestWith(['medicine', null, 'medicines'])]
    #[TestWith(['feed_delivery', 'feed', 'feed'])]
    #[TestWith(['expense', null, 'other'])]
    public function test_special_and_legacy_cost_categories_remain_filterable(string $kind, ?string $category, string $filter): void
    {
        $cycle = BroilerCycle::factory()->create();
        $entry = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => $kind, 'category' => $category]);
        $this->get(route('broiler.show', [$cycle, 'tab' => 'costs', 'category' => $filter]))->assertOk()->assertViewHas('costEntries', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $entry->id);
    }

    public function test_pagination_keeps_cost_category_and_does_not_page_daily_records(): void
    {
        $cycle = BroilerCycle::factory()->create();
        BroilerEntry::factory()->count(21)->create(['broiler_cycle_id' => $cycle->id, 'category' => 'heating']);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'daily', 'amount' => 0]);
        $response = $this->get(route('broiler.show', [$cycle, 'tab' => 'costs', 'category' => 'heating', 'cost_page' => 2]))->assertOk();
        $response->assertViewHas('costEntries', fn ($rows) => $rows->count() === 1 && str_contains($rows->previousPageUrl(), 'category=heating') && str_contains($rows->previousPageUrl(), 'tab=costs'));
        $response->assertViewHas('dailyEntries', fn ($rows) => $rows->count() === 1 && $rows->currentPage() === 1);
    }

    public function test_failed_submission_opens_its_tab_and_keeps_the_form_values(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $url = route('broiler.show', $cycle);
        $this->from($url)->post(route('broiler.entries.store', $cycle), ['kind' => 'expense', 'category' => 'bedding', 'description' => 'فرشة جديدة', 'amount' => -1, 'recorded_on' => today()->toDateString()])->assertSessionHasErrors('amount');
        $this->get($url)->assertOk()->assertViewHas('activeTab', 'costs')->assertSee('فرشة جديدة');
    }

    public function test_successful_expense_save_returns_to_costs(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'expense', 'category' => 'bedding', 'description' => 'فرشة', 'amount' => 20, 'recorded_on' => today()->toDateString()])->assertSessionHasNoErrors()->assertSessionHas('cycle_tab', 'costs');
        $this->get(route('broiler.show', $cycle))->assertViewHas('activeTab', 'costs');
    }

    public function test_closed_cycles_hide_add_forms_but_keep_cost_correction_and_reports(): void
    {
        $cycle = BroilerCycle::factory()->create(['closed_on' => today()]);
        $this->get(route('broiler.show', $cycle))->assertOk()->assertDontSee('name="kind"', false)->assertSee('تعديل ثمن الكتاكيت')->assertSee('تقارير الدورة والنتائج المالية');
    }

    public function test_feed_and_sales_have_separate_histories_and_tabs(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $feed = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'feed_delivery']);
        $sale = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'sale']);
        $expense = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'expense']);
        BroilerEntry::factory()->create(['kind' => 'feed_delivery']);

        $this->get(route('broiler.show', [$cycle, 'tab' => 'feed']))
            ->assertOk()->assertViewHas('activeTab', 'feed')
            ->assertViewHas('feedEntries', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $feed->id)
            ->assertViewHas('saleEntries', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $sale->id)
            ->assertViewHas('costEntries', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $expense->id);
        $this->get(route('broiler.show', [$cycle, 'tab' => 'sales']))->assertOk()->assertViewHas('activeTab', 'sales');
    }
}
