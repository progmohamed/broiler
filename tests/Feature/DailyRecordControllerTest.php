<?php

namespace Tests\Feature;

use App\Models\DailyRecord;
use App\Models\EggSale;
use App\Models\FarmSetting;
use App\Models\FeedDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DailyRecordControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_records_show_production_changes_compared_with_the_previous_day(): void
    {
        $this->travelTo('2026-01-07 12:00:00');

        FarmSetting::query()->create([
            'herd_birth_date' => '2025-12-01',
            'total_herd_count' => 1000,
        ]);

        DailyRecord::factory()->count(7)->sequence(
            ['recorded_on' => '2026-01-01', 'production' => 100],
            ['recorded_on' => '2026-01-02', 'production' => 120],
            ['recorded_on' => '2026-01-03', 'production' => 90],
            ['recorded_on' => '2026-01-04', 'production' => 90],
            ['recorded_on' => '2026-01-05', 'production' => 0],
            ['recorded_on' => '2026-01-06', 'production' => 0],
            ['recorded_on' => '2026-01-07', 'production' => 50],
        )->create();

        $response = $this->get(route('daily-records.index', ['show' => 'all']))
            ->assertSee('↑')
            ->assertSee('↓')
            ->assertSee('2.0%')
            ->assertSee('3.0%')
            ->assertSee('9.0%')
            ->assertSee('0.0%')
            ->assertSee('دون تغيير')
            ->assertSee('5.0%')
            ->assertSee('عن اليوم السابق');

        $this->assertSame([
            ['difference' => 50, 'percentage' => 5.0],
            ['difference' => 0, 'percentage' => 0.0],
            ['difference' => -90, 'percentage' => -9.0],
            ['difference' => 0, 'percentage' => 0.0],
            ['difference' => -30, 'percentage' => -3.0],
            ['difference' => 20, 'percentage' => 2.0],
            null,
        ], $response['records']->getCollection()->pluck('productionChange')->all());
    }

    public function test_production_comparison_does_not_skip_a_missing_day(): void
    {
        $this->travelTo('2026-01-03 12:00:00');

        FarmSetting::query()->create([
            'herd_birth_date' => '2025-12-01',
            'total_herd_count' => 1000,
        ]);

        DailyRecord::factory()->count(2)->sequence(
            ['recorded_on' => '2026-01-01', 'production' => 100],
            ['recorded_on' => '2026-01-03', 'production' => 150],
        )->create();

        $response = $this->get(route('daily-records.index'))
            ->assertDontSee('فرق نسبة الإنتاج إلى عدد القطيع')
            ->assertDontSee('50.0%');

        $this->assertNull($response['records'][0]['productionChange']);
    }

    public function test_production_comparison_includes_the_previous_day_outside_the_page(): void
    {
        $this->travelTo('2026-01-04 12:00:00');

        FarmSetting::query()->create([
            'herd_birth_date' => '2025-12-01',
            'total_herd_count' => 1000,
        ]);

        DailyRecord::factory()->count(4)->sequence(
            ['recorded_on' => '2026-01-01', 'production' => 80],
            ['recorded_on' => '2026-01-02', 'production' => 100],
            ['recorded_on' => '2026-01-03', 'production' => 100],
            ['recorded_on' => '2026-01-04', 'production' => 100],
        )->create();

        $response = $this->get(route('daily-records.index'))
            ->assertSee('2.0%');

        $this->assertSame(['difference' => 20, 'percentage' => 2.0], $response['records'][2]['productionChange']);
    }

    public function test_production_comparison_is_hidden_without_a_valid_herd_count(): void
    {
        $this->travelTo('2026-01-02 12:00:00');

        DailyRecord::factory()->count(2)->sequence(
            ['recorded_on' => '2026-01-01', 'production' => 100],
            ['recorded_on' => '2026-01-02', 'production' => 150],
        )->create();

        $response = $this->get(route('daily-records.index'))
            ->assertDontSee('فرق نسبة الإنتاج إلى عدد القطيع');

        $this->assertNull($response['records'][0]['productionChange']);

        FarmSetting::query()->create([
            'herd_birth_date' => '2025-12-01',
            'total_herd_count' => 0,
        ]);

        $response = $this->get(route('daily-records.index'))
            ->assertDontSee('فرق نسبة الإنتاج إلى عدد القطيع');

        $this->assertNull($response['records'][0]['productionChange']);
    }

    public function test_dashboard_defaults_to_today_and_displays_recent_daily_records(): void
    {
        $this->travelTo('2026-08-30 12:00:00');

        DailyRecord::factory()->create([
            'recorded_on' => '2026-08-29',
            'production' => 840,
            'feed' => 125,
            'deaths' => 2,
        ]);

        $this->get(route('daily-records.index'))
            ->assertOk()
            ->assertSee('2026-08-30')
            ->assertSee('840')
            ->assertSee('125')
            ->assertSee('2');
    }

    public function test_recent_records_display_production_as_egg_cartons(): void
    {
        DailyRecord::factory()->create([
            'recorded_on' => '2026-08-29',
            'production' => 845,
        ]);

        $this->get(route('daily-records.index'))
            ->assertSee('845')
            ->assertSee('28.2 كرتونة');
    }

    public function test_valid_daily_record_is_saved_for_its_selected_date(): void
    {
        $this->post(route('daily-records.store'), [
            'recorded_on' => '2026-08-29',
            'production' => 840,
            'feed_sacks' => 2.5,
            'deaths' => 2,
            'medicines' => 'فيتامينات 5 مل',
            'notes' => 'حالة القطيع جيدة',
        ])->assertRedirect(route('daily-records.index').'?recorded_on=2026-08-29')
            ->assertSessionHas('status', 'تم حفظ السجل اليومي.');

        $this->assertDatabaseHas('daily_records', [
            'production' => 840,
            'feed' => 125,
            'deaths' => 2,
            'medicines' => 'فيتامينات 5 مل',
            'notes' => 'حالة القطيع جيدة',
        ]);
    }

    public function test_existing_daily_record_is_updated_for_the_selected_date(): void
    {
        DailyRecord::factory()->create([
            'recorded_on' => '2026-08-29',
            'production' => 700,
            'feed' => 100,
            'deaths' => 1,
        ]);

        $this->post(route('daily-records.store'), [
            'recorded_on' => '2026-08-29',
            'production' => 840,
            'feed_sacks' => 2.5,
            'deaths' => 2,
        ])->assertRedirect(route('daily-records.index').'?recorded_on=2026-08-29')
            ->assertSessionHas('status', 'تم تحديث السجل اليومي.');

        $this->assertDatabaseCount('daily_records', 1);
        $this->assertDatabaseHas('daily_records', [
            'production' => 840,
            'feed' => 125,
            'deaths' => 2,
        ]);

        $record = DailyRecord::query()->whereDate('recorded_on', '2026-08-29')->firstOrFail();

        $this->assertSame('2026-08-29', $record->recorded_on->toDateString());
    }

    public function test_dashboard_loads_the_record_for_the_selected_date(): void
    {
        DailyRecord::factory()->create([
            'recorded_on' => '2026-08-29',
            'production' => 840,
            'feed' => 125,
            'deaths' => 2,
        ]);

        $this->get(route('daily-records.index', ['recorded_on' => '2026-08-29']))
            ->assertOk()
            ->assertSee('تحديث السجل اليومي')
            ->assertSee('value="840"', false)
            ->assertSee('value="2.5"', false)
            ->assertSee('value="2"', false);
    }

    public function test_form_and_recent_record_fields_use_the_requested_order(): void
    {
        DailyRecord::factory()->create([
            'recorded_on' => '2026-08-29',
        ]);

        $this->get(route('daily-records.index'))
            ->assertSeeInOrder([
                'for="feed_sacks"',
                'for="deaths"',
                'for="production"',
                'for="medicines"',
                'for="notes"',
            ], false)
            ->assertSeeInOrder([
                '>العلف</dt>',
                '>النافق</dt>',
                '>الإنتاج</span>',
            ], false);
    }

    public function test_dashboard_shows_three_recent_records_and_can_show_all_records(): void
    {
        foreach (range(1, 8) as $day) {
            DailyRecord::factory()->create([
                'recorded_on' => sprintf('2026-01-%02d', $day),
                'medicines' => "دواء السجل {$day}",
                'notes' => "ملاحظة السجل {$day}",
            ]);
        }

        $this->get(route('daily-records.index'))
            ->assertSee('دواء السجل 8')
            ->assertSee('دواء السجل 6')
            ->assertDontSee('دواء السجل 5')
            ->assertSee('آخر 3 سجلات')
            ->assertSee('صفحة 1 من 3')
            ->assertSee('التالي');

        $this->get(route('daily-records.index', ['show' => 'all']))
            ->assertSee('كل السجلات')
            ->assertSee('دواء السجل 1')
            ->assertSee('ملاحظة السجل 1')
            ->assertDontSee('صفحة 1 من');
    }

    public function test_dashboard_displays_only_inventory_summary_and_simplified_weekly_performance(): void
    {
        $this->travelTo('2026-01-07 12:00:00');

        FarmSetting::query()->create([
            'farm_name' => 'مزرعة النور',
            'herd_birth_date' => '2025-12-01',
            'total_herd_count' => 1000,
        ]);
        DailyRecord::factory()->create([
            'recorded_on' => '2026-01-06',
            'production' => 800,
            'feed' => 100,
            'deaths' => 1,
        ]);
        DailyRecord::factory()->create([
            'recorded_on' => '2026-01-07',
            'production' => 840,
            'feed' => 150,
            'deaths' => 2,
        ]);
        FeedDelivery::factory()->create([
            'delivered_on' => '2026-01-01',
            'weight_tons' => 2,
        ]);

        $this->get(route('daily-records.index'))
            ->assertOk()
            ->assertSee('مزرعة النور')
            ->assertSee('لوحة اليوم')
            ->assertSee('المخزون الحالي')
            ->assertSee('العلف المتبقي بالمخزن')
            ->assertSee('كراتين البيض بالمخزن')
            ->assertSee('العمر بالأيام')
            ->assertSee('العمر بالأسابيع')
            ->assertSee('يكفي العلف')
            ->assertSee('أداء آخر 7 أيام')
            ->assertSee('متوسط 820 بيضة')
            ->assertSee('إجمالي النافق')
            ->assertDontSee('84.0% من القطيع')
            ->assertDontSee('5.0% عن أمس')
            ->assertDontSee('متوسط الإنتاج')
            ->assertDontSee('إجراءات سريعة')
            ->assertDontSee('dashboard_recorded_on');
    }

    public function test_dashboard_warns_when_today_has_not_been_recorded(): void
    {
        $this->travelTo('2026-01-07 12:00:00');

        $this->get(route('daily-records.index'))
            ->assertOk()
            ->assertSee('يحتاج انتباهك')
            ->assertSee('لم يتم تسجيل بيانات اليوم بعد.')
            ->assertSee('سجّل الآن');
    }

    public function test_recent_records_display_age_and_production_percentage_from_settings(): void
    {
        FarmSetting::query()->create([
            'herd_birth_date' => '2026-01-01',
            'total_herd_count' => 1200,
        ]);
        DailyRecord::factory()->create([
            'recorded_on' => '2026-01-15',
            'production' => 840,
            'feed' => 125,
            'deaths' => 2,
        ]);

        $this->get(route('daily-records.index', ['recorded_on' => '2026-01-15']))
            ->assertOk()
            ->assertSee('العمر بالأيام')
            ->assertSee('14')
            ->assertSee('العمر بالأسابيع')
            ->assertSee('2')
            ->assertSee('نسبة الإنتاج')
            ->assertSee('70.0%');
    }

    public function test_dashboard_displays_remaining_feed_up_to_the_selected_date(): void
    {
        FeedDelivery::factory()->create([
            'delivered_on' => '2026-01-10',
            'weight_tons' => 2,
        ]);
        FeedDelivery::factory()->create([
            'delivered_on' => '2026-01-20',
            'weight_tons' => 5,
        ]);
        DailyRecord::factory()->create([
            'recorded_on' => '2026-01-11',
            'feed' => 100,
        ]);
        DailyRecord::factory()->create([
            'recorded_on' => '2026-01-16',
            'feed' => 150,
        ]);

        $this->get(route('daily-records.index', ['recorded_on' => '2026-01-15']))
            ->assertOk()
            ->assertSee('العلف المتبقي')
            ->assertSee('38.0')
            ->assertSee('1,900 كجم');
    }

    public function test_dashboard_displays_remaining_egg_cartons_up_to_the_selected_date(): void
    {
        DailyRecord::factory()->create([
            'recorded_on' => '2026-01-10',
            'production' => 900,
        ]);
        DailyRecord::factory()->create([
            'recorded_on' => '2026-01-20',
            'production' => 300,
        ]);
        EggSale::factory()->create([
            'sold_on' => '2026-01-11',
            'cartons' => 10,
        ]);
        EggSale::factory()->create([
            'sold_on' => '2026-01-20',
            'cartons' => 5,
        ]);

        $this->get(route('daily-records.index', ['recorded_on' => '2026-01-15']))
            ->assertOk()
            ->assertSee('كراتين البيض بالمخزن')
            ->assertSee('20.0')
            ->assertSee('600 بيضة');
    }

    public function test_daily_record_requires_all_amounts(): void
    {
        $this->from(route('daily-records.index'))
            ->post(route('daily-records.store'), [])
            ->assertRedirect(route('daily-records.index'))
            ->assertInvalid([
                'recorded_on' => 'اختر تاريخ السجل.',
                'production' => 'أدخل كمية إنتاج البيض.',
                'feed_sacks' => 'أدخل عدد شكاير العلف.',
                'deaths' => 'أدخل عدد النافق.',
            ]);

        $this->assertDatabaseCount('daily_records', 0);
    }

    public function test_feed_sacks_must_be_entered_in_half_sack_increments(): void
    {
        $this->from(route('daily-records.index'))
            ->post(route('daily-records.store'), [
                'recorded_on' => '2026-08-29',
                'production' => 840,
                'feed_sacks' => 2.25,
                'deaths' => 2,
            ])
            ->assertRedirect(route('daily-records.index'))
            ->assertInvalid([
                'feed_sacks' => 'يمكن إدخال عدد كامل أو نصف شيكارة فقط.',
            ]);

        $this->assertDatabaseCount('daily_records', 0);
    }
}
