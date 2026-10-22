<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveOtherSaleRequest;
use App\Models\EggSale;
use App\Models\OtherSale;
use App\SalePaymentStatus;
use App\SaleProductType;
use App\SaleUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OtherSaleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'product_type' => ['nullable', Rule::enum(SaleProductType::class)],
        ], [
            'from.date_format' => 'تاريخ البداية غير صحيح.',
            'to.date_format' => 'تاريخ النهاية غير صحيح.',
            'to.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية أو مساويًا له.',
            'product_type.enum' => 'نوع المبيع غير صحيح.',
        ]);

        $otherSaleQuery = OtherSale::query();
        $eggSaleQuery = EggSale::query();

        if (isset($filters['from'])) {
            $otherSaleQuery->whereDate('sold_on', '>=', $filters['from']);
            $eggSaleQuery->whereDate('sold_on', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $otherSaleQuery->whereDate('sold_on', '<=', $filters['to']);
            $eggSaleQuery->whereDate('sold_on', '<=', $filters['to']);
        }

        if (isset($filters['product_type'])) {
            $otherSaleQuery->where('product_type', $filters['product_type']);
        }

        $otherSaleTotals = (clone $otherSaleQuery)
            ->toBase()
            ->selectRaw('COUNT(*) AS transaction_count')
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) AS total_revenue')
            ->selectRaw('COALESCE(SUM(CASE WHEN payment_status = ? THEN quantity * unit_price ELSE 0 END), 0) AS pending_revenue', [SalePaymentStatus::Pending->value])
            ->first();

        $eggSaleTotals = (clone $eggSaleQuery)
            ->toBase()
            ->selectRaw('COUNT(*) AS transaction_count')
            ->selectRaw('COALESCE(SUM(cartons * price_per_carton), 0) AS total_revenue')
            ->selectRaw('COALESCE(SUM(CASE WHEN payment_status = ? THEN cartons * price_per_carton ELSE 0 END), 0) AS pending_revenue', [SalePaymentStatus::Pending->value])
            ->first();

        $otherSales = (clone $otherSaleQuery)
            ->latest('sold_on')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $eggSales = (clone $eggSaleQuery)
            ->latest('sold_on')
            ->latest('id')
            ->limit(10)
            ->get();

        $eggRevenue = (float) $eggSaleTotals->total_revenue;
        $otherRevenue = (float) $otherSaleTotals->total_revenue;

        return view('sales.index', [
            'filters' => $filters,
            'eggSales' => $eggSales,
            'otherSales' => $otherSales,
            'productTypes' => SaleProductType::cases(),
            'saleUnits' => SaleUnit::cases(),
            'paymentStatuses' => SalePaymentStatus::cases(),
            'eggRevenue' => $eggRevenue,
            'otherRevenue' => $otherRevenue,
            'totalRevenue' => $eggRevenue + $otherRevenue,
            'pendingRevenue' => (float) $eggSaleTotals->pending_revenue + (float) $otherSaleTotals->pending_revenue,
            'transactionCount' => (int) $eggSaleTotals->transaction_count + (int) $otherSaleTotals->transaction_count,
        ]);
    }

    public function store(SaveOtherSaleRequest $request): RedirectResponse
    {
        OtherSale::query()->create($this->saleData($request));

        return to_route('sales.index')->with('status', 'تم حفظ عملية البيع بنجاح.');
    }

    public function edit(OtherSale $otherSale): View
    {
        return view('sales.edit', [
            'otherSale' => $otherSale,
            'productTypes' => SaleProductType::cases(),
            'saleUnits' => SaleUnit::cases(),
            'paymentStatuses' => SalePaymentStatus::cases(),
        ]);
    }

    public function update(SaveOtherSaleRequest $request, OtherSale $otherSale): RedirectResponse
    {
        $otherSale->update($this->saleData($request));

        return to_route('sales.index')->with('status', 'تم تحديث عملية البيع بنجاح.');
    }

    public function destroy(OtherSale $otherSale): RedirectResponse
    {
        $otherSale->delete();

        return to_route('sales.index')->with('status', 'تم حذف عملية البيع.');
    }

    /**
     * @return array<string, mixed>
     */
    private function saleData(SaveOtherSaleRequest $request): array
    {
        $data = $request->validated();

        if ($data['product_type'] !== SaleProductType::Other->value) {
            $data['custom_product'] = null;
        }

        return $data;
    }
}
