<?php

namespace Tests\Feature;

use App\ExpensePaymentMethod;
use App\Models\FeedDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FeedDeliveryControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_feed_purchase_form_is_displayed_in_finance_with_today_as_the_default_date(): void
    {
        $this->travelTo('2026-08-31 12:00:00');

        $this->get(route('finance.index'))
            ->assertOk()
            ->assertSee('شراء علف')
            ->assertSee('value="2026-08-31"', false)
            ->assertSee('يدخل المخزون ويُسجّل تلقائيًا كمصروف');

        $this->get(route('feed-deliveries.index'))
            ->assertOk()
            ->assertSee('كل توريدات العلف')
            ->assertSee('لا توجد توريدات علف حتى الآن.');
    }

    public function test_a_valid_feed_delivery_is_saved(): void
    {
        $this->post(route('feed-deliveries.store'), [
            'delivered_on' => '2026-08-30',
            'feed_type' => 'علف بياض 18%',
            'weight_tons' => 2.5,
            'price_per_ton' => 1000,
            'shipping_cost' => 150,
            'tip' => 25,
            'supplier' => 'شركة الأعلاف',
            'payment_method' => ExpensePaymentMethod::Cash->value,
            'invoice_number' => 'INV-100',
            'notes' => 'تم الاستلام بحالة جيدة',
        ])->assertRedirect(route('finance.index').'#recent-expenses')
            ->assertSessionHas('status', 'تم حفظ شراء العلف وإضافته إلى المصروفات.');

        $this->assertDatabaseHas('feed_deliveries', [
            'feed_type' => 'علف بياض 18%',
            'weight_tons' => 2.5,
            'supplier' => 'شركة الأعلاف',
            'invoice_number' => 'INV-100',
        ]);

        $delivery = FeedDelivery::query()->firstOrFail();

        $this->assertSame('2026-08-30', $delivery->delivered_on->toDateString());
    }

    public function test_finance_page_displays_the_feed_purchase_as_an_expense(): void
    {
        $delivery = FeedDelivery::factory()->create([
            'delivered_on' => '2026-08-30',
            'feed_type' => 'علف بياض',
            'weight_tons' => 2,
            'price_per_ton' => 1000,
            'shipping_cost' => 150,
            'tip' => 25,
            'supplier' => 'المورد الأول',
        ]);
        $delivery->syncExpense();

        $this->get(route('finance.index'))
            ->assertOk()
            ->assertSee('2,175.00')
            ->assertSee('المورد الأول')
            ->assertSee('تلقائي من توريد العلف');
    }

    public function test_all_feed_deliveries_page_is_paginated(): void
    {
        FeedDelivery::factory()->count(16)->create();

        $this->get(route('feed-deliveries.index'))
            ->assertOk()
            ->assertSee('16 توريد')
            ->assertSee('صفحة 1 من 2')
            ->assertSee('التالي');
    }

    public function test_feed_deliveries_can_be_filtered_by_payment_method(): void
    {
        FeedDelivery::factory()->create([
            'supplier' => 'مورد نقدي',
            'payment_method' => ExpensePaymentMethod::Cash,
        ]);
        FeedDelivery::factory()->create([
            'supplier' => 'مورد آجل',
            'payment_method' => ExpensePaymentMethod::Credit,
        ]);

        $this->get(route('feed-deliveries.index', ['payment_method' => ExpensePaymentMethod::Credit->value]))
            ->assertSee('مورد آجل')
            ->assertSee('آجل')
            ->assertDontSee('مورد نقدي')
            ->assertSee('value="credit" selected', false);
    }

    public function test_feed_delivery_filter_rejects_an_invalid_payment_method(): void
    {
        $this->get(route('feed-deliveries.index', ['payment_method' => 'unknown']))
            ->assertSessionHasErrors('payment_method');
    }

    public function test_feed_delivery_requires_its_core_data(): void
    {
        $this->from(route('finance.index'))
            ->post(route('feed-deliveries.store'), [])
            ->assertRedirect(route('finance.index'))
            ->assertInvalid([
                'delivered_on' => 'تاريخ التوريد مطلوب.',
                'feed_type' => 'نوع العلف مطلوب.',
                'weight_tons' => 'وزن العلف مطلوب.',
                'price_per_ton' => 'سعر الطن مطلوب.',
                'shipping_cost' => 'تكلفة الشحن مطلوبة، ويمكن إدخال صفر.',
                'tip' => 'الإكرامية مطلوبة، ويمكن إدخال صفر.',
                'supplier' => 'اسم المورد مطلوب.',
                'payment_method' => 'طريقة الدفع مطلوبة.',
            ]);

        $this->assertDatabaseCount('feed_deliveries', 0);
    }
}
