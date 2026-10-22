<?php

namespace Tests\Feature;

use App\FarmReportBuilder;
use App\Models\DailyRecord;
use App\Models\EggSale;
use App\Models\Expense;
use App\Models\FarmSetting;
use App\Models\FeedDelivery;
use App\Models\OtherSale;
use App\Models\WorkerAdvance;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FarmReportBuilderTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_production_uses_herd_count_and_recorded_days_and_inclusive_date_boundaries(): void
    {
        $this->createSettings();
        DailyRecord::factory()->count(4)->sequence(
            ['recorded_on' => '2026-09-01', 'production' => 9000],
            ['recorded_on' => '2026-09-02', 'production' => 800],
            ['recorded_on' => '2026-09-04', 'production' => 900],
            ['recorded_on' => '2026-09-05', 'production' => 9000],
        )->create();

        $report = (new FarmReportBuilder)->build($this->plan());
        $metrics = array_column($report['metrics'], 'value', 'key');
        $this->assertSame(1700, $metrics['production']);
        $this->assertSame(85.0, $metrics['production_rate']);
        $this->assertSame(2, $metrics['recorded_days']);
        $this->assertCount(2, $report['tables'][0]['rows']);
        $this->assertStringContainsString('2 يوم من 3 يوم', $report['notes'][0]);
        $this->assertSame('2026-09-04', $report['charts'][0]['points'][1]['date']->toDateString());
    }

    public function test_feed_efficiency_uses_kilograms_to_grams_and_weighted_ratios(): void
    {
        $this->createSettings();
        DailyRecord::factory()->count(2)->sequence(
            ['recorded_on' => '2026-09-02', 'production' => 800, 'feed' => 100],
            ['recorded_on' => '2026-09-04', 'production' => 1200, 'feed' => 200],
        )->create();

        $report = (new FarmReportBuilder)->build($this->plan(['report' => 'feed_efficiency']));
        $metrics = array_column($report['metrics'], 'value', 'key');
        $this->assertSame(300, $metrics['feed']);
        $this->assertSame(150.0, $metrics['feed_per_hen']);
        $this->assertSame(150.0, $metrics['feed_per_egg']);
        $this->assertCount(2, $report['charts']);
        $this->assertSame([100, 200], array_column($report['charts'][0]['points'], 'value'));
        $this->assertSame([800, 1200], array_column($report['charts'][1]['points'], 'value'));
    }

    public function test_feed_purchases_can_be_filtered_by_supplier_with_delivery_details_and_totals(): void
    {
        FeedDelivery::factory()->count(4)->sequence(
            [
                'delivered_on' => '2026-09-02', 'supplier' => 'الحاج صبحي معروف', 'feed_type' => 'علف بياض',
                'weight_tons' => 2, 'price_per_ton' => 1000, 'shipping_cost' => 100, 'tip' => 10, 'payment_method' => 'credit',
            ],
            [
                'delivered_on' => '2026-09-04', 'supplier' => 'الحاج صبحي معروف', 'feed_type' => 'علف نامي',
                'weight_tons' => 1.5, 'price_per_ton' => 1200, 'shipping_cost' => 50, 'tip' => 0, 'payment_method' => 'cash',
            ],
            ['delivered_on' => '2026-09-03', 'supplier' => 'مورد آخر', 'weight_tons' => 20],
            ['delivered_on' => '2026-09-05', 'supplier' => 'الحاج صبحي معروف', 'weight_tons' => 30],
        )->create();

        $report = (new FarmReportBuilder)->build($this->plan([
            'report' => 'feed_purchases',
            'supplier' => 'صبحي معروف',
            'payment_method' => 'credit',
            'result_mode' => 'list',
        ]));
        $metrics = array_column($report['metrics'], 'value', 'key');

        $this->assertSame(1, $metrics['feed_delivery_count']);
        $this->assertSame(2.0, $metrics['feed_purchased_tons']);
        $this->assertSame(2110.0, $metrics['feed_purchase_cost']);
        $this->assertSame(40.0, $metrics['feed_purchased_sacks']);
        $this->assertSame([2.0], array_column($report['charts'][0]['points'], 'value'));
        $this->assertCount(1, $report['tables'][0]['rows']);
        $this->assertSame('الحاج صبحي معروف', $report['tables'][0]['rows'][0][0]);
        $this->assertSame('الحاج صبحي معروف', $report['tables'][1]['rows'][0][1]);
        $this->assertSame('أسماء موردي العلف: الحاج صبحي معروف.', $report['answer']);
        $this->assertStringContainsString('صبحي معروف', implode(' ', $report['notes']));
        $this->assertStringContainsString('آجل', implode(' ', $report['notes']));
    }

    public function test_sales_reports_search_buyers_and_products_without_mixing_records(): void
    {
        EggSale::factory()->create(['sold_on' => '2026-09-02', 'buyer' => 'أحمد علي', 'cartons' => 10, 'price_per_carton' => 100]);
        EggSale::factory()->create(['sold_on' => '2026-09-03', 'buyer' => 'مشتري آخر', 'cartons' => 50, 'price_per_carton' => 80]);
        OtherSale::factory()->create(['sold_on' => '2026-09-03', 'custom_product' => 'كرتون مستعمل', 'product_type' => 'other', 'quantity' => 2, 'unit_price' => 75]);

        $eggReport = (new FarmReportBuilder)->build($this->plan(['report' => 'egg_sales', 'result_mode' => 'ranking']));
        $eggMetrics = array_column($eggReport['metrics'], 'value', 'key');
        $this->assertSame(2, $eggMetrics['egg_sale_count']);
        $this->assertSame(5000.0, $eggMetrics['egg_sale_revenue']);
        $this->assertSame('مشتري آخر', $eggReport['tables'][0]['rows'][0][0]);
        $this->assertStringContainsString('أكثر مشتري للبيض هو مشتري آخر', $eggReport['answer']);

        $otherReport = (new FarmReportBuilder)->build($this->plan(['report' => 'other_sales', 'target' => 'كرتون مستعمل']));
        $this->assertSame(150.0, array_column($otherReport['metrics'], 'value', 'key')['other_sale_revenue']);
        $this->assertSame('كرتون مستعمل', $otherReport['tables'][0]['rows'][0][2]);
    }

    public function test_expense_and_worker_reports_support_text_filters(): void
    {
        Expense::factory()->create(['spent_on' => '2026-09-02', 'category' => 'medicines', 'amount' => 250, 'payee' => 'الصيدلية']);
        Expense::factory()->create(['spent_on' => '2026-09-03', 'category' => 'fuel', 'amount' => 900]);
        WorkerAdvance::factory()->create(['advanced_on' => '2026-09-03', 'worker_name' => 'محمد حسن', 'amount' => 300]);
        WorkerAdvance::factory()->create(['advanced_on' => '2026-09-04', 'worker_name' => 'عامل آخر', 'amount' => 800]);

        $expenseReport = (new FarmReportBuilder)->build($this->plan(['report' => 'expenses', 'target' => 'أدوية']));
        $this->assertSame(250.0, array_column($expenseReport['metrics'], 'value', 'key')['expense_total']);
        $this->assertSame('أدوية', $expenseReport['tables'][0]['rows'][0][1]);

        $advanceReport = (new FarmReportBuilder)->build($this->plan(['report' => 'worker_advances', 'target' => 'محمد']));
        $this->assertSame(300.0, array_column($advanceReport['metrics'], 'value', 'key')['worker_advance_total']);
        $this->assertSame('محمد حسن', $advanceReport['tables'][0]['rows'][0][1]);
    }

    public function test_inventory_report_uses_all_movements_through_the_requested_date(): void
    {
        FeedDelivery::factory()->create(['delivered_on' => '2026-09-01', 'weight_tons' => 1]);
        DailyRecord::factory()->create(['recorded_on' => '2026-09-02', 'feed' => 100, 'production' => 600]);
        EggSale::factory()->create(['sold_on' => '2026-09-03', 'cartons' => 10]);

        $report = (new FarmReportBuilder)->build($this->plan(['report' => 'inventory']));
        $metrics = array_column($report['metrics'], 'value', 'key');

        $this->assertSame(900.0, $metrics['feed_stock']);
        $this->assertSame(18.0, $metrics['feed_stock_sacks']);
        $this->assertSame(300, $metrics['egg_stock']);
        $this->assertSame([], $report['charts']);
    }

    public function test_general_question_returns_only_the_validated_model_answer(): void
    {
        $report = (new FarmReportBuilder)->build($this->plan([
            'report' => 'general',
            'answer' => 'حافظ على المياه النظيفة والتهوية المناسبة.',
        ]));

        $this->assertSame('حافظ على المياه النظيفة والتهوية المناسبة.', $report['answer']);
        $this->assertSame([], $report['metrics']);
        $this->assertSame([], $report['tables']);
    }

    public function test_zero_production_does_not_divide_by_zero_or_invent_feed_per_egg(): void
    {
        $this->createSettings();
        DailyRecord::factory()->create(['recorded_on' => '2026-09-02', 'production' => 0, 'feed' => 100]);
        $report = (new FarmReportBuilder)->build($this->plan(['report' => 'feed_efficiency']));
        $metrics = array_column($report['metrics'], 'value', 'key');
        $this->assertNull($metrics['feed_per_egg']);
        $this->assertSame(100.0, $metrics['feed_per_hen']);
        $this->assertSame(0, $report['charts'][1]['points'][0]['height']);
    }

    public function test_missing_and_zero_herd_counts_leave_ratios_unavailable(): void
    {
        DailyRecord::factory()->create(['recorded_on' => '2026-09-02', 'production' => 800, 'feed' => 100]);
        $report = (new FarmReportBuilder)->build($this->plan());
        $this->assertNull(array_column($report['metrics'], 'value', 'key')['production_rate']);

        $this->createSettings(0);
        $report = (new FarmReportBuilder)->build($this->plan(['report' => 'feed_efficiency']));
        $this->assertNull(array_column($report['metrics'], 'value', 'key')['feed_per_hen']);
        $this->assertSame(125.0, array_column($report['metrics'], 'value', 'key')['feed_per_egg']);
    }

    public function test_missing_records_are_unknown_rather_than_zero_production(): void
    {
        $this->createSettings();
        $report = (new FarmReportBuilder)->build($this->plan());
        $this->assertNull(array_column($report['metrics'], 'value', 'key')['production']);
        $this->assertNull(array_column($report['metrics'], 'value', 'key')['production_rate']);
        $this->assertSame([], $report['tables'][0]['rows']);
        $this->assertSame([], $report['charts'][0]['points']);
    }

    public function test_mortality_totals_are_reported_without_inventing_causes(): void
    {
        $this->createSettings();
        DailyRecord::factory()->count(2)->sequence(
            ['recorded_on' => '2026-09-02', 'deaths' => 3],
            ['recorded_on' => '2026-09-04', 'deaths' => 2],
        )->create();
        $report = (new FarmReportBuilder)->build($this->plan(['report' => 'mortality', 'missing_data' => 'mortality_causes']));
        $metrics = array_column($report['metrics'], 'value', 'key');
        $this->assertSame(5, $metrics['deaths']);
        $this->assertSame(0.5, $metrics['mortality_rate']);
        $this->assertStringContainsString('أسباب النافق غير مسجلة', implode(' ', $report['notes']));
        $this->assertSame([3, 2], array_column($report['charts'][0]['points'], 'value'));
    }

    public function test_weekly_groups_start_on_monday_and_only_include_requested_days(): void
    {
        $this->createSettings();
        DailyRecord::factory()->count(3)->sequence(
            ['recorded_on' => '2026-09-06', 'production' => 100],
            ['recorded_on' => '2026-09-07', 'production' => 200],
            ['recorded_on' => '2026-09-08', 'production' => 300],
        )->create();
        $report = (new FarmReportBuilder)->build($this->plan(['start_date' => '2026-09-06', 'end_date' => '2026-09-08', 'group_by' => 'week']));
        $this->assertSame([
            ['2026-08-31', 1, 100, '10.0'],
            ['2026-09-07', 2, 500, '25.0'],
        ], $report['tables'][0]['rows']);
    }

    public function test_monthly_groups_do_not_include_data_outside_the_requested_range(): void
    {
        $this->createSettings();
        DailyRecord::factory()->count(3)->sequence(
            ['recorded_on' => '2026-08-30', 'production' => 9000],
            ['recorded_on' => '2026-08-31', 'production' => 500],
            ['recorded_on' => '2026-09-01', 'production' => 600],
        )->create();
        $report = (new FarmReportBuilder)->build($this->plan(['start_date' => '2026-08-31', 'end_date' => '2026-09-01', 'group_by' => 'month']));
        $this->assertSame([
            ['2026-08-01', 1, 500, '50.0'],
            ['2026-09-01', 1, 600, '60.0'],
        ], $report['tables'][0]['rows']);
    }

    public function test_previous_period_comparison_uses_an_equal_length_period_and_percentage_points(): void
    {
        $this->createSettings();
        DailyRecord::factory()->count(2)->sequence(
            ['recorded_on' => '2026-08-30', 'production' => 700],
            ['recorded_on' => '2026-09-04', 'production' => 900],
        )->create();
        $report = (new FarmReportBuilder)->build($this->plan(['comparison' => 'previous_period']));
        $comparison = $report['tables'][1];
        $this->assertSame('مقارنة الفترة السابقة: 2026-08-30 — 2026-09-01', $comparison['title']);
        $this->assertSame(['متوسط نسبة الإنتاج اليومية', '70.0', '90.0', '20.0 نقطة مئوية'], $comparison['rows'][2]);
    }

    public function test_comparison_with_no_previous_records_has_no_invented_difference(): void
    {
        $this->createSettings();
        DailyRecord::factory()->create(['recorded_on' => '2026-09-02', 'production' => 900]);
        $report = (new FarmReportBuilder)->build($this->plan(['comparison' => 'previous_period']));
        $this->assertSame(['إجمالي الإنتاج', '—', '900', '—'], $report['tables'][1]['rows'][1]);
    }

    public function test_finance_includes_credit_sales_and_linked_expenses_once(): void
    {
        EggSale::factory()->create(['sold_on' => '2026-09-02', 'cartons' => 10, 'price_per_carton' => 100, 'payment_status' => 'pending']);
        OtherSale::factory()->create(['sold_on' => '2026-09-04', 'quantity' => 2, 'unit_price' => 50]);
        Expense::factory()->create(['spent_on' => '2026-09-04', 'amount' => 200]);
        $delivery = FeedDelivery::factory()->create(['delivered_on' => '2026-09-02', 'weight_tons' => 1, 'price_per_ton' => 500, 'shipping_cost' => 10, 'tip' => 5]);
        $delivery->syncExpense();
        $advance = WorkerAdvance::factory()->create(['advanced_on' => '2026-09-04', 'amount' => 50]);
        $advance->syncExpense();
        Expense::factory()->create(['spent_on' => '2026-09-05', 'amount' => 9999]);
        EggSale::factory()->create(['sold_on' => '2026-09-01', 'cartons' => 100, 'price_per_carton' => 100]);

        $report = (new FarmReportBuilder)->build($this->plan(['report' => 'finance']));
        $metrics = array_column($report['metrics'], 'value', 'key');
        $this->assertSame(1100.0, $metrics['revenue']);
        $this->assertSame(765.0, $metrics['expenses']);
        $this->assertSame(335.0, $metrics['net_profit']);
        $this->assertSame([
            ['2026-09-02', '1,000.00', '515.00', '485.00'],
            ['2026-09-04', '100.00', '250.00', '-150.00'],
        ], $report['tables'][0]['rows']);
    }

    public function test_financial_loss_preserves_negative_value_and_decimal_precision(): void
    {
        EggSale::factory()->create(['sold_on' => '2026-09-02', 'cartons' => 1, 'price_per_carton' => 100.25]);
        Expense::factory()->create(['spent_on' => '2026-09-02', 'amount' => 150.75]);
        $report = (new FarmReportBuilder)->build($this->plan(['report' => 'finance']));
        $this->assertSame(-50.5, array_column($report['metrics'], 'value', 'key')['net_profit']);
        $this->assertSame(100.25, $report['charts'][0]['points'][0]['value']);
    }

    public function test_empty_finance_has_zero_recorded_totals_and_explicit_missing_transactions_note(): void
    {
        $report = (new FarmReportBuilder)->build($this->plan(['report' => 'finance']));
        $this->assertSame(0.0, array_column($report['metrics'], 'value', 'key')['net_profit']);
        $this->assertStringContainsString('لا توجد معاملات مالية مسجلة', implode(' ', $report['notes']));
    }

    public function test_summary_combines_production_mortality_feed_and_finance(): void
    {
        $this->createSettings();
        DailyRecord::factory()->create(['recorded_on' => '2026-09-02', 'production' => 800, 'feed' => 100, 'deaths' => 2]);
        $report = (new FarmReportBuilder)->build($this->plan(['report' => 'summary']));
        $metrics = array_column($report['metrics'], 'value', 'key');
        $this->assertSame(800, $metrics['production']);
        $this->assertSame(2, $metrics['deaths']);
        $this->assertSame(100, $metrics['feed']);
        $this->assertSame(0.0, $metrics['net_profit']);
        $this->assertCount(2, $report['tables']);
    }

    private function createSettings(int $count = 1000): void
    {
        FarmSetting::query()->create(['herd_birth_date' => '2026-01-01', 'total_herd_count' => $count]);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array{report: string, scope: string, target: null, supplier: null, payment_method: null, payment_status: null, result_mode: string, ranking_direction: string, answer: null, start_date: string, end_date: string, group_by: string, comparison: string, missing_data: string}
     */
    private function plan(array $overrides = []): array
    {
        return array_replace(['report' => 'production', 'scope' => 'farm', 'target' => null, 'supplier' => null, 'payment_method' => null, 'payment_status' => null, 'result_mode' => 'details', 'ranking_direction' => 'desc', 'answer' => null, 'start_date' => '2026-09-02', 'end_date' => '2026-09-04', 'group_by' => 'day', 'comparison' => 'none', 'missing_data' => 'none'], $overrides);
    }
}
