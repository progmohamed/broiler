<?php

namespace Tests\Feature;

use App\Models\DailyRecord;
use App\Models\EggSale;
use App\Models\FeedDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_inventory_page_displays_current_feed_and_egg_balances(): void
    {
        FeedDelivery::factory()->create([
            'delivered_on' => '2026-09-01',
            'feed_type' => 'علف بياض',
            'weight_tons' => 2,
            'supplier' => 'المورد الأول',
        ]);
        DailyRecord::factory()->create([
            'recorded_on' => '2026-09-02',
            'feed' => 500,
            'production' => 300,
        ]);
        EggSale::factory()->create([
            'sold_on' => '2026-09-03',
            'cartons' => 4,
            'buyer' => 'مشتري البيض',
        ]);

        $this->get(route('inventory.index', ['as_of' => '2026-09-03']))
            ->assertOk()
            ->assertSee('1,500')
            ->assertSee('30.0')
            ->assertSee('180')
            ->assertSee('6.0')
            ->assertSee('توريد علف بياض')
            ->assertSee('استهلاك يومي')
            ->assertSee('إنتاج يومي')
            ->assertSee('مشتري البيض');
    }

    public function test_inventory_is_calculated_as_of_the_selected_date(): void
    {
        FeedDelivery::factory()->create(['delivered_on' => '2026-01-01', 'weight_tons' => 1]);
        FeedDelivery::factory()->create(['delivered_on' => '2026-02-01', 'weight_tons' => 5, 'feed_type' => 'توريد مستقبلي']);
        DailyRecord::factory()->create(['recorded_on' => '2026-01-10', 'feed' => 200, 'production' => 90]);
        EggSale::factory()->create(['sold_on' => '2026-02-01', 'cartons' => 1, 'buyer' => 'بيع مستقبلي']);

        $this->get(route('inventory.index', ['as_of' => '2026-01-31']))
            ->assertOk()
            ->assertSee('800')
            ->assertSee('90')
            ->assertDontSee('توريد مستقبلي')
            ->assertDontSee('بيع مستقبلي');
    }

    public function test_inventory_warns_when_feed_is_low_or_stock_is_negative(): void
    {
        FeedDelivery::factory()->create(['delivered_on' => '2026-09-01', 'weight_tons' => 0.5]);
        DailyRecord::factory()->create(['recorded_on' => '2026-09-01', 'feed' => 200, 'production' => 30]);
        DailyRecord::factory()->create(['recorded_on' => '2026-09-02', 'feed' => 200, 'production' => 30]);
        EggSale::factory()->create(['sold_on' => '2026-09-02', 'cartons' => 3]);

        $this->get(route('inventory.index', ['as_of' => '2026-09-03']))
            ->assertOk()
            ->assertSee('رصيد العلف يكفي تقريبًا 0.5 يوم فقط.')
            ->assertSee('مبيعات البيض أكبر من الإنتاج المسجل بمقدار 30 بيضة.');
    }

    public function test_inventory_rejects_an_invalid_date(): void
    {
        $this->get(route('inventory.index', ['as_of' => 'invalid']))
            ->assertSessionHasErrors(['as_of']);
    }
}
