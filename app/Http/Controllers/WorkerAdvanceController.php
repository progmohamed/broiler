<?php

namespace App\Http\Controllers;

use App\ExpensePaymentMethod;
use App\Http\Requests\SaveWorkerAdvanceRequest;
use App\Models\WorkerAdvance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WorkerAdvanceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'worker_name' => ['nullable', 'string', 'max:150'],
        ]);

        $advancesQuery = WorkerAdvance::query();

        if (isset($filters['from'])) {
            $advancesQuery->whereDate('advanced_on', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $advancesQuery->whereDate('advanced_on', '<=', $filters['to']);
        }

        if (isset($filters['worker_name'])) {
            $advancesQuery->where('worker_name', 'like', '%'.$filters['worker_name'].'%');
        }

        return view('worker-advances.index', [
            'advances' => (clone $advancesQuery)->latest('advanced_on')->latest('id')->paginate(10)->withQueryString(),
            'totalAmount' => (float) (clone $advancesQuery)->sum('amount'),
            'transactionCount' => (clone $advancesQuery)->count(),
            'filters' => $filters,
            'paymentMethods' => ExpensePaymentMethod::cases(),
        ]);
    }

    public function store(SaveWorkerAdvanceRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $advance = WorkerAdvance::query()->create($request->validated());
            $advance->syncExpense();
        });

        return to_route('worker-advances.index')->with('status', 'تم حفظ السلفة وإضافتها للمصروفات.');
    }

    public function edit(WorkerAdvance $workerAdvance): View
    {
        return view('worker-advances.edit', [
            'advance' => $workerAdvance,
            'paymentMethods' => ExpensePaymentMethod::cases(),
        ]);
    }

    public function update(SaveWorkerAdvanceRequest $request, WorkerAdvance $workerAdvance): RedirectResponse
    {
        DB::transaction(function () use ($request, $workerAdvance): void {
            $workerAdvance->update($request->validated());
            $workerAdvance->syncExpense();
        });

        return to_route('worker-advances.index')->with('status', 'تم تحديث السلفة والمصروف المرتبط بها.');
    }

    public function destroy(WorkerAdvance $workerAdvance): RedirectResponse
    {
        $workerAdvance->delete();

        return to_route('worker-advances.index')->with('status', 'تم حذف السلفة والمصروف المرتبط بها.');
    }
}
