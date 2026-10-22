<?php

namespace Tests\Feature;

use App\ExpenseCategory;
use App\ExpensePaymentMethod;
use App\Models\EggSale;
use App\Models\Expense;
use App\Models\OtherSale;
use App\SalePaymentStatus;
use App\SaleProductType;
use App\SaleUnit;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ExpenseControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_finance_page_displays_expense_form_with_today_as_default(): void
    {
        $this->travelTo('2026-09-03 12:00:00');

        $this->get(route('finance.index'))
            ->assertOk()
            ->assertSee('إضافة مصروف')
            ->assertSee('معدات')
            ->assertSee('مصروفات نثرية')
            ->assertSee('value="2026-09-03"', false);
    }

    public function test_valid_expense_is_saved(): void
    {
        $this->post(route('expenses.store'), [
            'spent_on' => '2026-09-03',
            'category' => ExpenseCategory::Maintenance->value,
            'amount' => 450.75,
            'payment_method' => ExpensePaymentMethod::Cash->value,
            'payee' => 'فني الصيانة',
            'invoice_number' => 'EXP-100',
            'notes' => 'صيانة المولد',
        ])->assertRedirect(route('finance.index'))
            ->assertSessionHas('status', 'تم حفظ المصروف بنجاح.');

        $this->assertDatabaseHas('expenses', [
            'category' => ExpenseCategory::Maintenance->value,
            'amount' => 450.75,
            'payment_method' => ExpensePaymentMethod::Cash->value,
            'payee' => 'فني الصيانة',
            'invoice_number' => 'EXP-100',
            'notes' => 'صيانة المولد',
        ]);

        $expense = Expense::query()->firstOrFail();

        $this->assertSame('2026-09-03', $expense->spent_on->toDateString());
    }

    public function test_expense_requires_its_core_data(): void
    {
        $this->from(route('finance.index'))
            ->post(route('expenses.store'), [])
            ->assertRedirect(route('finance.index'))
            ->assertInvalid([
                'spent_on' => 'تاريخ المصروف مطلوب.',
                'category' => 'تصنيف المصروف مطلوب.',
                'amount' => 'مبلغ المصروف مطلوب.',
                'payment_method' => 'طريقة الدفع مطلوبة.',
            ]);

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_expense_rejects_invalid_category_payment_method_and_amount(): void
    {
        $this->from(route('finance.index'))
            ->post(route('expenses.store'), [
                'spent_on' => '2026-09-03',
                'category' => 'unknown',
                'amount' => 0,
                'payment_method' => 'unknown',
            ])
            ->assertRedirect(route('finance.index'))
            ->assertInvalid([
                'category' => 'تصنيف المصروف غير صحيح.',
                'amount' => 'يجب أن يكون مبلغ المصروف أكبر من صفر.',
                'payment_method' => 'طريقة الدفع غير صحيحة.',
            ]);

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_finance_summary_and_list_respect_date_filters(): void
    {
        Expense::factory()->create([
            'spent_on' => '2026-01-10',
            'amount' => 100,
            'payment_method' => ExpensePaymentMethod::Cash,
            'notes' => 'مصروف يناير الأول',
        ]);
        Expense::factory()->create([
            'spent_on' => '2026-01-20',
            'amount' => 250,
            'payment_method' => ExpensePaymentMethod::Credit,
            'notes' => 'مصروف يناير الآجل',
        ]);
        Expense::factory()->create([
            'spent_on' => '2026-02-01',
            'amount' => 400,
            'notes' => 'مصروف فبراير',
        ]);

        $this->get(route('finance.index', ['from' => '2026-01-01', 'to' => '2026-01-31']))
            ->assertOk()
            ->assertSee('350.00')
            ->assertSee('250.00')
            ->assertSee('مصروف يناير الأول')
            ->assertSee('مصروف يناير الآجل')
            ->assertDontSee('مصروف فبراير');
    }

    public function test_financial_overview_uses_all_sales_and_manual_expenses_for_selected_dates(): void
    {
        EggSale::factory()->create([
            'sold_on' => '2026-01-10',
            'cartons' => 10,
            'price_per_carton' => 100,
        ]);
        OtherSale::factory()->create([
            'sold_on' => '2026-01-12',
            'product_type' => SaleProductType::Manure,
            'quantity' => 2,
            'unit' => SaleUnit::Ton,
            'unit_price' => 200,
            'payment_status' => SalePaymentStatus::Paid,
        ]);
        EggSale::factory()->create([
            'sold_on' => '2026-02-01',
            'cartons' => 5,
            'price_per_carton' => 100,
        ]);
        Expense::factory()->create([
            'spent_on' => '2026-01-15',
            'amount' => 350,
        ]);

        $this->get(route('finance.index', ['from' => '2026-01-01', 'to' => '2026-01-31']))
            ->assertOk()
            ->assertSee('1,400.00')
            ->assertSee('350.00')
            ->assertSee('1,050.00')
            ->assertSee('بعد تكلفة العلف');
    }

    public function test_finance_list_can_be_filtered_by_category(): void
    {
        Expense::factory()->create([
            'category' => ExpenseCategory::Electricity,
            'notes' => 'فاتورة الكهرباء',
        ]);
        Expense::factory()->create([
            'category' => ExpenseCategory::Maintenance,
            'notes' => 'إصلاح المعدات',
        ]);

        $this->get(route('finance.index', ['category' => ExpenseCategory::Electricity->value]))
            ->assertOk()
            ->assertSee('فاتورة الكهرباء')
            ->assertDontSee('إصلاح المعدات');
    }

    public function test_expense_can_be_edited_and_updated(): void
    {
        $expense = Expense::factory()->create([
            'category' => ExpenseCategory::Maintenance,
            'amount' => 100,
            'payee' => 'الجهة القديمة',
        ]);

        $this->get(route('expenses.edit', $expense))
            ->assertOk()
            ->assertSee('تعديل المصروف')
            ->assertSee('الجهة القديمة');

        $this->put(route('expenses.update', $expense), [
            'spent_on' => '2026-09-01',
            'category' => ExpenseCategory::Electricity->value,
            'amount' => 275,
            'payment_method' => ExpensePaymentMethod::BankTransfer->value,
            'payee' => 'شركة الكهرباء',
            'invoice_number' => null,
            'notes' => null,
        ])->assertRedirect(route('finance.index'))
            ->assertSessionHas('status', 'تم تحديث المصروف بنجاح.');

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'category' => ExpenseCategory::Electricity->value,
            'amount' => 275,
            'payment_method' => ExpensePaymentMethod::BankTransfer->value,
            'payee' => 'شركة الكهرباء',
        ]);
    }

    public function test_expense_can_be_deleted(): void
    {
        $expense = Expense::factory()->create();

        $this->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('finance.index'))
            ->assertSessionHas('status', 'تم حذف المصروف.');

        $this->assertModelMissing($expense);
    }

    public function test_finance_page_escapes_payee_and_notes_content(): void
    {
        Expense::factory()->create([
            'payee' => '<script>alert("payee")</script>',
            'notes' => '<img src=x onerror=alert("notes")>',
        ]);

        $this->get(route('finance.index'))
            ->assertSeeText('<script>alert("payee")</script>')
            ->assertSeeText('<img src=x onerror=alert("notes")>')
            ->assertDontSee('<script>alert("payee")</script>', false)
            ->assertDontSee('<img src=x onerror=alert("notes")>', false);
    }
}
