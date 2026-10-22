<?php

namespace App\Http\Controllers;

use App\ExpenseCategory;
use App\ExpensePaymentMethod;
use App\Http\Requests\SaveExpenseRequest;
use App\Models\EggSale;
use App\Models\Expense;
use App\Models\OtherSale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'category' => ['nullable', Rule::enum(ExpenseCategory::class)],
        ], [
            'from.date_format' => 'تاريخ البداية غير صحيح.',
            'to.date_format' => 'تاريخ النهاية غير صحيح.',
            'to.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية أو مساويًا له.',
            'category.enum' => 'تصنيف المصروف غير صحيح.',
        ]);

        $datedExpenseQuery = Expense::query();
        $eggSaleQuery = EggSale::query();
        $otherSaleQuery = OtherSale::query();

        if (isset($filters['from'])) {
            $datedExpenseQuery->whereDate('spent_on', '>=', $filters['from']);
            $eggSaleQuery->whereDate('sold_on', '>=', $filters['from']);
            $otherSaleQuery->whereDate('sold_on', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $datedExpenseQuery->whereDate('spent_on', '<=', $filters['to']);
            $eggSaleQuery->whereDate('sold_on', '<=', $filters['to']);
            $otherSaleQuery->whereDate('sold_on', '<=', $filters['to']);
        }

        $expenseQuery = clone $datedExpenseQuery;

        if (isset($filters['category'])) {
            $expenseQuery->where('category', $filters['category']);
        }

        $totals = (clone $expenseQuery)
            ->toBase()
            ->selectRaw('COUNT(*) AS transaction_count')
            ->selectRaw('COALESCE(SUM(amount), 0) AS total_amount')
            ->selectRaw('COALESCE(SUM(CASE WHEN payment_method = ? THEN amount ELSE 0 END), 0) AS credit_amount', [ExpensePaymentMethod::Credit->value])
            ->first();

        $expenses = (clone $expenseQuery)
            ->latest('spent_on')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $revenueTotals = (clone $eggSaleQuery)
            ->toBase()
            ->selectRaw('COALESCE(SUM(cartons * price_per_carton), 0) AS total_revenue')
            ->first();
        $otherRevenueTotals = (clone $otherSaleQuery)
            ->toBase()
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) AS total_revenue')
            ->first();
        $otherRevenue = (float) $otherRevenueTotals->total_revenue;
        $totalRevenue = (float) $revenueTotals->total_revenue + $otherRevenue;
        $financialExpenseTotal = (float) (clone $datedExpenseQuery)->sum('amount');

        return view('finance.index', [
            'categoriesByGroup' => collect(ExpenseCategory::cases())->groupBy(fn (ExpenseCategory $category): string => $category->group()),
            'manualCategoriesByGroup' => collect(ExpenseCategory::cases())
                ->reject(fn (ExpenseCategory $category): bool => in_array($category, [ExpenseCategory::Feed, ExpenseCategory::WorkerAdvance], true))
                ->groupBy(fn (ExpenseCategory $category): string => $category->group()),
            'paymentMethods' => ExpensePaymentMethod::cases(),
            'expenses' => $expenses,
            'filters' => $filters,
            'transactionCount' => (int) $totals->transaction_count,
            'totalAmount' => (float) $totals->total_amount,
            'creditAmount' => (float) $totals->credit_amount,
            'totalRevenue' => $totalRevenue,
            'financialExpenseTotal' => $financialExpenseTotal,
            'netProfit' => $totalRevenue - $financialExpenseTotal,
        ]);
    }

    public function store(SaveExpenseRequest $request): RedirectResponse
    {
        Expense::query()->create($request->validated());

        return to_route('finance.index')->with('status', 'تم حفظ المصروف بنجاح.');
    }

    public function edit(Expense $expense): View|RedirectResponse
    {
        if ($expense->feed_delivery_id !== null) {
            return to_route('feed-deliveries.edit', $expense->feed_delivery_id)
                ->with('status', 'عدّل التوريد ليتم تحديث المصروف تلقائيًا.');
        }

        if ($expense->worker_advance_id !== null) {
            return to_route('worker-advances.edit', $expense->worker_advance_id)
                ->with('status', 'عدّل السلفة ليتم تحديث المصروف تلقائيًا.');
        }

        return view('finance.edit', [
            'expense' => $expense,
            'categoriesByGroup' => collect(ExpenseCategory::cases())
                ->reject(fn (ExpenseCategory $category): bool => in_array($category, [ExpenseCategory::Feed, ExpenseCategory::WorkerAdvance], true))
                ->groupBy(fn (ExpenseCategory $category): string => $category->group()),
            'paymentMethods' => ExpensePaymentMethod::cases(),
        ]);
    }

    public function update(SaveExpenseRequest $request, Expense $expense): RedirectResponse
    {
        if ($expense->feed_delivery_id !== null) {
            return to_route('feed-deliveries.edit', $expense->feed_delivery_id)
                ->with('status', 'عدّل التوريد ليتم تحديث المصروف تلقائيًا.');
        }

        if ($expense->worker_advance_id !== null) {
            return to_route('worker-advances.edit', $expense->worker_advance_id)
                ->with('status', 'عدّل السلفة ليتم تحديث المصروف تلقائيًا.');
        }

        $expense->update($request->validated());

        return to_route('finance.index')->with('status', 'تم تحديث المصروف بنجاح.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        if ($expense->feed_delivery_id !== null) {
            return to_route('finance.index')->with('status', 'لا يمكن حذف مصروف العلف منفردًا؛ احذف التوريد المرتبط به.');
        }

        if ($expense->worker_advance_id !== null) {
            return to_route('finance.index')->with('status', 'لا يمكن حذف مصروف السلفة منفردًا؛ احذف السلفة المرتبطة به.');
        }

        $expense->delete();

        return to_route('finance.index')->with('status', 'تم حذف المصروف.');
    }
}
