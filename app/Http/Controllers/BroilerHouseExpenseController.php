<?php

namespace App\Http\Controllers;

use App\BroilerHouseCosts;
use App\Http\Requests\StoreBroilerHouseExpenseRequest;
use App\Models\BroilerHouse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class BroilerHouseExpenseController extends Controller
{
    public function index(BroilerHouse $house, BroilerHouseCosts $costs): View
    {
        $expenses = $house->expenses()->latest('starts_on')->get();
        $cycles = $house->cycles()->orderBy('started_on')->get();
        $allocations = [];
        foreach ($expenses as $expense) {
            foreach ($cycles as $cycle) {
                $allocations[$expense->id][$cycle->id] = $costs->allocation($expense, $cycle);
            }
        }

        return view('broiler.house-expenses', compact('house', 'expenses', 'cycles', 'allocations'));
    }

    public function store(StoreBroilerHouseExpenseRequest $request, BroilerHouse $house): RedirectResponse
    {
        DB::transaction(function () use ($request, $house): void {
            BroilerHouse::query()->lockForUpdate()->findOrFail($house->id)->expenses()->create($request->validated());
        });

        return to_route('broiler.houses.expenses', $house)->with('status', 'تم تسجيل المصروف على العنبر.');
    }

    public function update(StoreBroilerHouseExpenseRequest $request, BroilerHouse $house, int $expense): RedirectResponse
    {
        DB::transaction(function () use ($request, $house, $expense): void {
            BroilerHouse::query()->lockForUpdate()->findOrFail($house->id)->expenses()->findOrFail($expense)->update($request->validated());
        });

        return to_route('broiler.houses.expenses', $house)->with('status', 'تم تعديل مصروف العنبر وتوزيعه.');
    }
}
