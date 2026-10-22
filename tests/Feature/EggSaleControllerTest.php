<?php

namespace Tests\Feature;

use App\Models\EggSale;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EggSaleControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_egg_sale_form_is_displayed_in_revenue_with_today_as_the_default_date(): void
    {
        $this->travelTo('2026-09-01 12:00:00');

        $this->get(route('sales.index'))
            ->assertSee('مبيعات البيض')
            ->assertSee('value="2026-09-01"', false)
            ->assertSee('لا توجد مبيعات بيض حتى الآن.');

        $this->get(route('egg-sales.index'))
            ->assertOk()
            ->assertSee('كل مبيعات البيض')
            ->assertSee('لا توجد مبيعات بيض حتى الآن.');
    }

    public function test_valid_egg_sale_is_saved(): void
    {
        $this->post(route('egg-sales.store'), [
            'sold_on' => '2026-09-01',
            'cartons' => 25,
            'price_per_carton' => 120.5,
            'buyer' => 'متجر الخير',
            'payment_status' => 'paid',
            'invoice_number' => 'SALE-100',
            'notes' => 'تم التسليم صباحًا',
        ])->assertRedirect(route('sales.index').'#egg-sales-list')
            ->assertSessionHas('status', 'تم حفظ عملية بيع البيض بنجاح.');

        $this->assertDatabaseHas('egg_sales', [
            'cartons' => 25,
            'price_per_carton' => 120.5,
            'buyer' => 'متجر الخير',
            'payment_status' => 'paid',
            'invoice_number' => 'SALE-100',
        ]);

        $sale = EggSale::query()->firstOrFail();

        $this->assertSame('2026-09-01', $sale->sold_on->toDateString());
    }

    public function test_page_displays_sales_revenue_and_pending_totals(): void
    {
        EggSale::factory()->create([
            'cartons' => 10,
            'price_per_carton' => 100,
            'payment_status' => 'paid',
            'buyer' => 'المشتري الأول',
        ]);
        EggSale::factory()->create([
            'cartons' => 5,
            'price_per_carton' => 120,
            'payment_status' => 'pending',
            'buyer' => 'المشتري الثاني',
        ]);

        $this->get(route('sales.index'))
            ->assertSee('15')
            ->assertSee('1,600.00')
            ->assertSee('600.00')
            ->assertSee('مبلغ مستحق');
    }

    public function test_egg_sale_requires_its_core_data(): void
    {
        $this->from(route('sales.index'))
            ->post(route('egg-sales.store'), [])
            ->assertRedirect(route('sales.index'))
            ->assertInvalid([
                'sold_on' => 'تاريخ البيع مطلوب.',
                'cartons' => 'عدد الكراتين مطلوب.',
                'price_per_carton' => 'سعر الكرتونة مطلوب.',
                'buyer' => 'اسم المشتري مطلوب.',
                'payment_status' => 'حالة الدفع مطلوبة.',
            ]);

        $this->assertDatabaseCount('egg_sales', 0);
    }

    public function test_page_escapes_buyer_and_notes_content(): void
    {
        EggSale::factory()->create([
            'buyer' => '<script>alert("buyer")</script>',
            'notes' => '<img src=x onerror=alert("notes")>',
        ]);

        $this->get(route('sales.index'))
            ->assertSeeText('<script>alert("buyer")</script>')
            ->assertSeeText('<img src=x onerror=alert("notes")>')
            ->assertDontSee('<script>alert("buyer")</script>', false)
            ->assertDontSee('<img src=x onerror=alert("notes")>', false);
    }

    public function test_all_egg_sales_page_is_paginated(): void
    {
        EggSale::factory()->count(16)->create();

        $this->get(route('egg-sales.index'))
            ->assertOk()
            ->assertSee('16 عملية')
            ->assertSee('صفحة 1 من 2')
            ->assertSee('التالي');
    }

    public function test_egg_sales_can_be_filtered_by_payment_status(): void
    {
        EggSale::factory()->create([
            'buyer' => 'مشتري مدفوع',
            'cartons' => 10,
            'price_per_carton' => 100,
            'payment_status' => 'paid',
        ]);
        EggSale::factory()->create([
            'buyer' => 'مشتري آجل',
            'cartons' => 5,
            'price_per_carton' => 120,
            'payment_status' => 'pending',
        ]);

        $this->get(route('egg-sales.index', ['payment_status' => 'pending']))
            ->assertSee('مشتري آجل')
            ->assertSee('5 كرتونة')
            ->assertSee('600.00')
            ->assertDontSee('مشتري مدفوع')
            ->assertSee('value="pending" selected', false);
    }

    public function test_egg_sales_filter_rejects_an_invalid_payment_status(): void
    {
        $this->get(route('egg-sales.index', ['payment_status' => 'unknown']))
            ->assertSessionHasErrors('payment_status');
    }
}
