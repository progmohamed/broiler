<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEggSaleRequest;
use App\Models\EggSale;
use App\SalePaymentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EggSaleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'payment_status' => ['nullable', Rule::enum(SalePaymentStatus::class)],
        ]);
        $salesQuery = EggSale::query();

        if (isset($filters['payment_status'])) {
            $salesQuery->where('payment_status', $filters['payment_status']);
        }

        $sales = (clone $salesQuery)
            ->latest('sold_on')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $totals = (clone $salesQuery)
            ->toBase()
            ->selectRaw('COALESCE(SUM(cartons), 0) AS total_cartons')
            ->selectRaw('COALESCE(SUM(cartons * price_per_carton), 0) AS total_revenue')
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN cartons * price_per_carton ELSE 0 END), 0) AS pending_revenue")
            ->first();

        return view('egg-sales.index', [
            'sales' => $sales,
            'totalCartons' => (int) $totals->total_cartons,
            'totalRevenue' => (float) $totals->total_revenue,
            'pendingRevenue' => (float) $totals->pending_revenue,
            'filters' => $filters,
            'paymentStatuses' => SalePaymentStatus::cases(),
        ]);
    }

    public function store(StoreEggSaleRequest $request): RedirectResponse
    {
        EggSale::query()->create($request->validated());

        return redirect()->to(route('sales.index').'#egg-sales-list')->with('status', 'تم حفظ عملية بيع البيض بنجاح.');
    }

    public function edit(EggSale $eggSale): View
    {
        return view('egg-sales.edit', ['sale' => $eggSale]);
    }

    public function update(StoreEggSaleRequest $request, EggSale $eggSale): RedirectResponse
    {
        $eggSale->update($request->validated());

        return to_route('egg-sales.index')->with('status', 'تم تحديث عملية بيع البيض.');
    }

    public function destroy(EggSale $eggSale): RedirectResponse
    {
        $eggSale->delete();

        return to_route('egg-sales.index')->with('status', 'تم حذف عملية بيع البيض.');
    }
}
