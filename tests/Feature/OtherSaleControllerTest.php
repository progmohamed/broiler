<?php

namespace Tests\Feature;

use App\Models\EggSale;
use App\Models\OtherSale;
use App\SalePaymentStatus;
use App\SaleProductType;
use App\SaleUnit;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OtherSaleControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_revenue_page_displays_form_with_today_as_default(): void
    {
        $this->travelTo('2026-09-03 12:00:00');

        $this->get(route('sales.index'))
            ->assertOk()
            ->assertSee('إضافة بيع آخر')
            ->assertSee('شكاير فارغة')
            ->assertSee('فراخ لحم')
            ->assertSee('value="2026-09-03"', false);
    }

    public function test_valid_other_sale_is_saved(): void
    {
        $this->post(route('other-sales.store'), [
            'sold_on' => '2026-09-03',
            'product_type' => SaleProductType::Manure->value,
            'custom_product' => 'يجب حذفه',
            'quantity' => 2.5,
            'unit' => SaleUnit::Ton->value,
            'unit_price' => 350,
            'buyer' => 'مزرعة النور',
            'payment_status' => SalePaymentStatus::Pending->value,
            'invoice_number' => 'SALE-100',
            'notes' => 'يتم التحصيل الأسبوع القادم',
        ])->assertRedirect(route('sales.index'))
            ->assertSessionHas('status', 'تم حفظ عملية البيع بنجاح.');

        $this->assertDatabaseHas('other_sales', [
            'product_type' => SaleProductType::Manure->value,
            'custom_product' => null,
            'quantity' => 2.5,
            'unit' => SaleUnit::Ton->value,
            'unit_price' => 350,
            'buyer' => 'مزرعة النور',
            'payment_status' => SalePaymentStatus::Pending->value,
        ]);
    }

    public function test_other_product_requires_a_custom_name(): void
    {
        $this->from(route('sales.index'))->post(route('other-sales.store'), [
            'sold_on' => '2026-09-03',
            'product_type' => SaleProductType::Other->value,
            'quantity' => 1,
            'unit' => SaleUnit::Unit->value,
            'unit_price' => 100,
            'buyer' => 'عميل',
            'payment_status' => SalePaymentStatus::Paid->value,
        ])->assertRedirect(route('sales.index'))
            ->assertInvalid(['custom_product' => 'اكتب اسم المبيع الآخر.']);

        $this->assertDatabaseCount('other_sales', 0);
    }

    public function test_sale_rejects_invalid_core_data(): void
    {
        $this->from(route('sales.index'))->post(route('other-sales.store'), [
            'sold_on' => 'bad-date',
            'product_type' => 'unknown',
            'quantity' => 0,
            'unit' => 'unknown',
            'unit_price' => -1,
            'buyer' => '',
            'payment_status' => 'unknown',
        ])->assertRedirect(route('sales.index'))
            ->assertInvalid(['sold_on', 'product_type', 'quantity', 'unit', 'unit_price', 'buyer', 'payment_status']);
    }

    public function test_revenue_summary_combines_egg_and_other_sales_and_pending_amounts(): void
    {
        EggSale::factory()->create([
            'sold_on' => '2026-01-10',
            'cartons' => 10,
            'price_per_carton' => 100,
            'payment_status' => 'paid',
        ]);
        OtherSale::factory()->create([
            'sold_on' => '2026-01-12',
            'quantity' => 2,
            'unit_price' => 250,
            'payment_status' => SalePaymentStatus::Pending,
            'notes' => 'بيع داخل الفترة',
        ]);
        OtherSale::factory()->create([
            'sold_on' => '2026-02-01',
            'quantity' => 10,
            'unit_price' => 100,
            'notes' => 'بيع خارج الفترة',
        ]);

        $this->get(route('sales.index', ['from' => '2026-01-01', 'to' => '2026-01-31']))
            ->assertOk()
            ->assertSee('1,500.00')
            ->assertSee('500.00')
            ->assertSee('بيع داخل الفترة')
            ->assertDontSee('بيع خارج الفترة');
    }

    public function test_other_sales_can_be_filtered_by_product_type(): void
    {
        OtherSale::factory()->create(['product_type' => SaleProductType::Manure, 'notes' => 'بيع سبلة']);
        OtherSale::factory()->create(['product_type' => SaleProductType::Broilers, 'notes' => 'بيع فراخ']);

        $this->get(route('sales.index', ['product_type' => SaleProductType::Manure->value]))
            ->assertOk()
            ->assertSee('بيع سبلة')
            ->assertDontSee('بيع فراخ');
    }

    public function test_other_sale_can_be_edited_and_updated(): void
    {
        $otherSale = OtherSale::factory()->create([
            'product_type' => SaleProductType::EmptySacks,
            'buyer' => 'المشتري القديم',
        ]);

        $this->get(route('other-sales.edit', $otherSale))
            ->assertOk()
            ->assertSee('تعديل عملية البيع')
            ->assertSee('المشتري القديم');

        $this->put(route('other-sales.update', $otherSale), [
            'sold_on' => '2026-09-01',
            'product_type' => SaleProductType::Other->value,
            'custom_product' => 'حديد مستعمل',
            'quantity' => 3,
            'unit' => SaleUnit::Unit->value,
            'unit_price' => 125,
            'buyer' => 'المشتري الجديد',
            'payment_status' => SalePaymentStatus::Paid->value,
            'invoice_number' => null,
            'notes' => null,
        ])->assertRedirect(route('sales.index'))
            ->assertSessionHas('status', 'تم تحديث عملية البيع بنجاح.');

        $this->assertDatabaseHas('other_sales', [
            'id' => $otherSale->id,
            'product_type' => SaleProductType::Other->value,
            'custom_product' => 'حديد مستعمل',
            'buyer' => 'المشتري الجديد',
        ]);
    }

    public function test_other_sale_can_be_deleted(): void
    {
        $otherSale = OtherSale::factory()->create();

        $this->delete(route('other-sales.destroy', $otherSale))
            ->assertRedirect(route('sales.index'))
            ->assertSessionHas('status', 'تم حذف عملية البيع.');

        $this->assertModelMissing($otherSale);
    }

    public function test_revenue_page_escapes_user_content(): void
    {
        OtherSale::factory()->create([
            'product_type' => SaleProductType::Other,
            'custom_product' => '<script>alert("product")</script>',
            'buyer' => '<script>alert("buyer")</script>',
            'notes' => '<img src=x onerror=alert("notes")>',
        ]);

        $this->get(route('sales.index'))
            ->assertSeeText('<script>alert("product")</script>')
            ->assertSeeText('<script>alert("buyer")</script>')
            ->assertSeeText('<img src=x onerror=alert("notes")>')
            ->assertDontSee('<script>alert("product")</script>', false)
            ->assertDontSee('<script>alert("buyer")</script>', false)
            ->assertDontSee('<img src=x onerror=alert("notes")>', false);
    }
}
