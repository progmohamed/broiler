<?php

namespace Tests\Feature;

use App\ExpenseCategory;
use App\Models\DailyRecord;
use App\Models\EggSale;
use App\Models\Expense;
use App\Models\FeedDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BreakEvenControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_debt_forecast_uses_the_manual_business_scenario(): void
    {
        $this->travelTo('2026-10-09 12:00:00');

        $this->get(route('break-even.index', [
            'current_debt' => 300000,
            'egg_inventory_cartons' => 100,
            'average_sale_price' => 90,
            'daily_production_cartons' => 215,
            'feed_stock_days' => 7,
            'daily_feed_kilograms' => 500,
            'feed_delivery_tons' => 5,
            'feed_delivery_cost' => 50000,
            'daily_expenses' => 1000,
            'collection_percentage' => 100,
        ]))
            ->assertOk()
            ->assertSee('متى تصبح المديونية صفرًا؟')
            ->assertSee('30 أكتوبر 2026')
            ->assertSee('16 أكتوبر 2026')
            ->assertSee('50,000.00')
            ->assertSee('194,200.00')
            ->assertSee('شراء علف');
    }

    public function test_debt_forecast_loads_defaults_from_farm_data(): void
    {
        $this->travelTo('2026-10-09 12:00:00');
        DailyRecord::factory()->create(['recorded_on' => '2026-10-09', 'production' => 3000, 'feed' => 100]);
        FeedDelivery::factory()->create([
            'delivered_on' => '2026-10-01',
            'weight_tons' => 1,
            'price_per_ton' => 10000,
            'shipping_cost' => 500,
            'tip' => 0,
        ])->syncExpense();
        EggSale::factory()->create([
            'sold_on' => '2026-10-09',
            'cartons' => 40,
            'price_per_carton' => 100,
            'payment_status' => 'paid',
        ]);
        Expense::factory()->create(['spent_on' => '2026-10-09', 'category' => ExpenseCategory::Medicines, 'amount' => 3000]);

        $this->get(route('break-even.index'))
            ->assertOk()
            ->assertSee('name="current_debt"', false)
            ->assertSee('value="9500"', false)
            ->assertSee('value="60"', false)
            ->assertSee('value="100"', false)
            ->assertSee('value="10500"', false);
    }

    public function test_debt_forecast_rejects_invalid_inputs(): void
    {
        $this->get(route('break-even.index', [
            'current_debt' => -1,
            'feed_stock_days' => -1,
            'collection_percentage' => 101,
        ]))->assertSessionHasErrors(['current_debt', 'feed_stock_days', 'collection_percentage']);
    }

    public function test_debt_forecast_warns_when_debt_cannot_be_paid_within_two_years(): void
    {
        $this->get(route('break-even.index', [
            'current_debt' => 300000,
            'average_sale_price' => 0,
            'daily_production_cartons' => 0,
            'feed_stock_days' => 7,
            'daily_feed_kilograms' => 500,
            'feed_delivery_tons' => 5,
            'feed_delivery_cost' => 50000,
            'daily_expenses' => 1000,
        ]))
            ->assertOk()
            ->assertSee('لن تصل المديونية إلى صفر خلال عامين');
    }
}
