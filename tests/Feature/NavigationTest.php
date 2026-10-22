<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_inventory_page_links_to_existing_stock_features(): void
    {
        $this->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('المخزون')
            ->assertSee('مخزون العلف')
            ->assertSee('مخزون البيض')
            ->assertSee('href="#feed-movements"', false)
            ->assertSee('href="#egg-movements"', false)
            ->assertSee('id="feed-movements"', false)
            ->assertSee('id="egg-movements"', false)
            ->assertSee('href="'.route('feed-deliveries.index').'"', false)
            ->assertSee('href="'.route('egg-sales.index').'"', false)
            ->assertDontSee('id="as_of"', false)
            ->assertDontSee('تقارير المخزن');
    }

    public function test_finance_page_links_to_current_revenue_feature(): void
    {
        $this->get(route('finance.index'))
            ->assertOk()
            ->assertSee('المالية')
            ->assertSee('الإيرادات')
            ->assertSee('المصروفات')
            ->assertSee('href="'.route('sales.index').'"', false);
    }

    public function test_more_page_links_to_existing_settings(): void
    {
        $this->get(route('more.index'))
            ->assertOk()
            ->assertSee('المزيد')
            ->assertSee('إعدادات المزرعة')
            ->assertSee('href="'.route('farm-settings.edit').'"', false)
            ->assertSee('href="'.route('reports.index').'"', false)
            ->assertSee('href="'.route('break-even.index').'"', false)
            ->assertSee('كل توريدات العلف')
            ->assertSee('كل مبيعات البيض')
            ->assertDontSee('تقارير المخزن');
    }

    public function test_app_shell_displays_new_tabs_and_quick_add_actions(): void
    {
        $this->get(route('inventory.index'))
            ->assertOk()
            ->assertSeeInOrder(['اليومية', 'المخزون', 'إضافة', 'المالية', 'المزيد'])
            ->assertSee('data-quick-add-open', false)
            ->assertSee('data-quick-add-dialog', false)
            ->assertSee('href="'.route('daily-records.index').'#daily-entry"', false)
            ->assertSee('href="'.route('finance.index').'#feed-purchase-form"', false)
            ->assertSee('href="'.route('sales.index').'#egg-sale-form"', false)
            ->assertSee('href="'.route('sales.index').'#other-sale-form"', false)
            ->assertSee('href="'.route('finance.index').'#expense-form"', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_feed_and_egg_record_pages_are_available(): void
    {
        $this->get(route('feed-deliveries.index'))->assertOk()->assertSee('كل توريدات العلف');
        $this->get(route('egg-sales.index'))->assertOk()->assertSee('كل مبيعات البيض');
        $this->get(route('sales.index'))->assertOk();
        $this->get(route('farm-settings.edit'))->assertOk();
    }
}
