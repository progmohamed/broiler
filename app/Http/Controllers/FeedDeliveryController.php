<?php

namespace App\Http\Controllers;

use App\ExpensePaymentMethod;
use App\Http\Requests\StoreFeedDeliveryRequest;
use App\Models\FeedDelivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeedDeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'payment_method' => ['nullable', Rule::enum(ExpensePaymentMethod::class)],
        ]);
        $deliveriesQuery = FeedDelivery::query();

        if (isset($filters['payment_method'])) {
            $deliveriesQuery->where('payment_method', $filters['payment_method']);
        }

        $deliveries = (clone $deliveriesQuery)
            ->latest('delivered_on')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $totals = (clone $deliveriesQuery)
            ->toBase()
            ->selectRaw('COALESCE(SUM(weight_tons), 0) AS total_weight_tons')
            ->selectRaw('COALESCE(SUM((weight_tons * price_per_ton) + shipping_cost + tip), 0) AS total_cost')
            ->first();
        $totalWeightTons = (float) $totals->total_weight_tons;

        return view('feed-deliveries.index', [
            'deliveries' => $deliveries,
            'totalWeightTons' => $totalWeightTons,
            'totalSacks' => $totalWeightTons * FeedDelivery::KILOGRAMS_PER_TON / FeedDelivery::SACK_WEIGHT_KILOGRAMS,
            'totalCost' => (float) $totals->total_cost,
            'filters' => $filters,
            'paymentMethods' => ExpensePaymentMethod::cases(),
        ]);
    }

    public function store(StoreFeedDeliveryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $delivery = FeedDelivery::query()->create($request->validated());
            $delivery->syncExpense();
        });

        return redirect()->to(route('finance.index').'#recent-expenses')->with('status', 'تم حفظ شراء العلف وإضافته إلى المصروفات.');
    }

    public function edit(FeedDelivery $feedDelivery): View
    {
        return view('feed-deliveries.edit', [
            'delivery' => $feedDelivery,
            'paymentMethods' => ExpensePaymentMethod::cases(),
        ]);
    }

    public function update(StoreFeedDeliveryRequest $request, FeedDelivery $feedDelivery): RedirectResponse
    {
        DB::transaction(function () use ($request, $feedDelivery): void {
            $feedDelivery->update($request->validated());
            $feedDelivery->syncExpense();
        });

        return to_route('feed-deliveries.index')->with('status', 'تم تحديث شراء العلف والمصروف المرتبط به.');
    }

    public function destroy(FeedDelivery $feedDelivery): RedirectResponse
    {
        $feedDelivery->delete();

        return to_route('feed-deliveries.index')->with('status', 'تم حذف شراء العلف والمصروف المرتبط به.');
    }
}
