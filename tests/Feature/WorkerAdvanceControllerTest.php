<?php

namespace Tests\Feature;

use App\ExpenseCategory;
use App\ExpensePaymentMethod;
use App\Models\Expense;
use App\Models\WorkerAdvance;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WorkerAdvanceControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_worker_advance_is_saved_with_a_linked_expense(): void
    {
        $this->post(route('worker-advances.store'), [
            'advanced_on' => '2026-09-03',
            'worker_name' => 'أحمد محمد',
            'amount' => 750,
            'payment_method' => ExpensePaymentMethod::Cash->value,
            'notes' => 'سلفة أول الشهر',
        ])->assertRedirect(route('worker-advances.index'))
            ->assertSessionHas('status', 'تم حفظ السلفة وإضافتها للمصروفات.');

        $advance = WorkerAdvance::query()->firstOrFail();
        $this->assertDatabaseHas('expenses', [
            'worker_advance_id' => $advance->id,
            'category' => ExpenseCategory::WorkerAdvance->value,
            'amount' => 750,
            'payee' => 'أحمد محمد',
        ]);
    }

    public function test_worker_advance_requires_core_data(): void
    {
        $this->from(route('worker-advances.index'))->post(route('worker-advances.store'), [])
            ->assertRedirect(route('worker-advances.index'))
            ->assertInvalid(['advanced_on', 'worker_name', 'amount', 'payment_method']);
    }

    public function test_updating_an_advance_updates_the_same_expense(): void
    {
        $advance = WorkerAdvance::factory()->create();
        $advance->syncExpense();
        $expenseId = Expense::query()->value('id');

        $this->put(route('worker-advances.update', $advance), [
            'advanced_on' => '2026-09-02',
            'worker_name' => 'العامل الجديد',
            'amount' => 900,
            'payment_method' => ExpensePaymentMethod::BankTransfer->value,
            'notes' => null,
        ])->assertRedirect(route('worker-advances.index'));

        $this->assertDatabaseCount('expenses', 1);
        $this->assertDatabaseHas('expenses', ['id' => $expenseId, 'amount' => 900, 'payee' => 'العامل الجديد']);
    }

    public function test_deleting_an_advance_deletes_the_linked_expense(): void
    {
        $advance = WorkerAdvance::factory()->create();
        $advance->syncExpense();

        $this->delete(route('worker-advances.destroy', $advance))
            ->assertRedirect(route('worker-advances.index'));

        $this->assertDatabaseCount('worker_advances', 0);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_advance_list_can_be_filtered_by_worker(): void
    {
        WorkerAdvance::factory()->create(['worker_name' => 'أحمد العامل']);
        WorkerAdvance::factory()->create(['worker_name' => 'محمود العامل']);

        $this->get(route('worker-advances.index', ['worker_name' => 'أحمد']))
            ->assertOk()
            ->assertSee('أحمد العامل')
            ->assertDontSee('محمود العامل');
    }
}
