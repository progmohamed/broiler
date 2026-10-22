<?php

namespace Tests\Feature;

use App\Models\DailyRecord;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reports_page_uses_the_last_thirty_days_by_default(): void
    {
        $this->travelTo('2026-09-04 12:00:00');

        $this->get(route('reports.index'))
            ->assertSee('التقارير')
            ->assertSee('تقرير النافق')
            ->assertSee('تقرير الإنتاج')
            ->assertSee('تقرير استهلاك العلف')
            ->assertSee('data-report-tabs', false)
            ->assertSee('data-report-tab="mortality-report"', false)
            ->assertSee('data-report-tab="production-report"', false)
            ->assertSee('data-report-tab="feed-report"', false)
            ->assertSee('data-report-panel', false)
            ->assertSee('value="2026-08-06"', false)
            ->assertSee('value="2026-09-04"', false)
            ->assertSee('لا توجد تسجيلات خلال هذه الفترة.');
    }

    public function test_reports_only_include_records_within_the_selected_period(): void
    {
        DailyRecord::factory()->create([
            'recorded_on' => '2026-08-10',
            'deaths' => 7,
            'production' => 321,
            'feed' => 150,
        ]);
        DailyRecord::factory()->create([
            'recorded_on' => '2026-08-20',
            'deaths' => 99,
            'production' => 999,
            'feed' => 950,
        ]);

        $this->get(route('reports.index', ['from' => '2026-08-01', 'to' => '2026-08-15']))
            ->assertSee('1 يوم مسجل')
            ->assertSee('data-chart-value="7"', false)
            ->assertSee('data-chart-value="321"', false)
            ->assertSee('data-chart-value="150"', false)
            ->assertDontSee('data-chart-value="99"', false)
            ->assertDontSee('data-chart-value="999"', false)
            ->assertDontSee('data-chart-value="950"', false);
    }

    public function test_report_period_rejects_an_end_date_before_the_start_date(): void
    {
        $this->from(route('reports.index'))
            ->get(route('reports.index', ['from' => '2026-09-04', 'to' => '2026-09-01']))
            ->assertRedirect(route('reports.index'))
            ->assertInvalid(['to' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية أو مساويًا له.']);
    }
}
