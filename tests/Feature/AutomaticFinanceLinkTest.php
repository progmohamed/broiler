<?php

namespace Tests\Feature;

use App\ExpenseCategory;
use App\ExpensePaymentMethod;
use App\Models\EggSale;
use App\Models\Expense;
use App\Models\FeedDelivery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AutomaticFinanceLinkTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_saving_a_feed_delivery_creates_one_linked_expense(): void
    {
        $this->post(route('feed-deliveries.store'), $this->feedDeliveryData())
            ->assertRedirect(route('finance.index').'#recent-expenses');

        $delivery = FeedDelivery::query()->firstOrFail();

        $this->assertDatabaseHas('expenses', [
            'feed_delivery_id' => $delivery->id,
            'spent_on' => '2026-09-03 00:00:00',
            'category' => ExpenseCategory::Feed->value,
            'amount' => 2675,
            'payment_method' => ExpensePaymentMethod::Credit->value,
            'payee' => 'شركة الأعلاف',
            'invoice_number' => 'FEED-100',
        ]);
        $this->assertDatabaseCount('expenses', 1);

        $this->get(route('finance.index'))
            ->assertOk()
            ->assertSee('شراء علف')
            ->assertSee('2,675.00')
            ->assertSee('بعد تكلفة العلف')
            ->assertSee('تلقائي من توريد العلف');
    }

    public function test_updating_a_feed_delivery_updates_the_same_expense(): void
    {
        $this->post(route('feed-deliveries.store'), $this->feedDeliveryData());
        $delivery = FeedDelivery::query()->firstOrFail();
        $expenseId = Expense::query()->value('id');

        $this->put(route('feed-deliveries.update', $delivery), [
            ...$this->feedDeliveryData(),
            'weight_tons' => 3,
            'price_per_ton' => 1200,
            'shipping_cost' => 200,
            'tip' => 50,
            'supplier' => 'المورد الجديد',
            'payment_method' => ExpensePaymentMethod::BankTransfer->value,
        ])->assertRedirect(route('feed-deliveries.index'))
            ->assertSessionHas('status', 'تم تحديث شراء العلف والمصروف المرتبط به.');

        $this->assertDatabaseCount('expenses', 1);
        $this->assertDatabaseHas('expenses', [
            'id' => $expenseId,
            'feed_delivery_id' => $delivery->id,
            'amount' => 3850,
            'payment_method' => ExpensePaymentMethod::BankTransfer->value,
            'payee' => 'المورد الجديد',
        ]);
    }

    public function test_deleting_a_feed_delivery_deletes_its_linked_expense(): void
    {
        $this->post(route('feed-deliveries.store'), $this->feedDeliveryData());
        $delivery = FeedDelivery::query()->firstOrFail();

        $this->delete(route('feed-deliveries.destroy', $delivery))
            ->assertRedirect(route('feed-deliveries.index'))
            ->assertSessionHas('status', 'تم حذف شراء العلف والمصروف المرتبط به.');

        $this->assertDatabaseCount('feed_deliveries', 0);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_linked_feed_expense_cannot_be_changed_independently(): void
    {
        $this->post(route('feed-deliveries.store'), $this->feedDeliveryData());
        $delivery = FeedDelivery::query()->firstOrFail();
        $expense = Expense::query()->firstOrFail();

        $this->get(route('expenses.edit', $expense))
            ->assertRedirect(route('feed-deliveries.edit', $delivery));

        $this->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('finance.index'));

        $this->assertModelExists($expense);
    }

    public function test_feed_expense_cannot_be_created_manually(): void
    {
        $this->from(route('finance.index'))->post(route('expenses.store'), [
            'spent_on' => '2026-09-03',
            'category' => ExpenseCategory::Feed->value,
            'amount' => 100,
            'payment_method' => ExpensePaymentMethod::Cash->value,
        ])->assertRedirect(route('finance.index'))
            ->assertInvalid(['category' => 'هذا المصروف يُسجل تلقائيًا من صفحته المخصصة.']);

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_editing_and_deleting_egg_sales_updates_finance_and_inventory_sources(): void
    {
        $sale = EggSale::factory()->create([
            'sold_on' => '2026-09-03',
            'cartons' => 5,
            'price_per_carton' => 100,
            'buyer' => 'المشتري القديم',
        ]);

        $this->get(route('egg-sales.edit', $sale))
            ->assertOk()
            ->assertSee('المشتري القديم');

        $this->put(route('egg-sales.update', $sale), [
            'sold_on' => '2026-09-03',
            'cartons' => 8,
            'price_per_carton' => 125,
            'buyer' => 'المشتري الجديد',
            'payment_status' => 'paid',
            'invoice_number' => null,
            'notes' => null,
        ])->assertRedirect(route('egg-sales.index'));

        $this->get(route('finance.index'))->assertSee('1,000.00');

        $this->delete(route('egg-sales.destroy', $sale))
            ->assertRedirect(route('egg-sales.index'));

        $this->assertDatabaseCount('egg_sales', 0);
        $this->get(route('finance.index'))->assertDontSee('1,000.00');
    }

    /**
     * @return array<string, mixed>
     */
    private function feedDeliveryData(): array
    {
        return [
            'delivered_on' => '2026-09-03',
            'feed_type' => 'علف بياض 18%',
            'weight_tons' => 2.5,
            'price_per_ton' => 1000,
            'shipping_cost' => 150,
            'tip' => 25,
            'supplier' => 'شركة الأعلاف',
            'payment_method' => ExpensePaymentMethod::Credit->value,
            'invoice_number' => 'FEED-100',
            'notes' => 'توريد كامل',
        ];
    }
}
