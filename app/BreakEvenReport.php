<?php

namespace App;

use App\Models\DailyRecord;
use App\Models\EggSale;
use App\Models\Expense;
use App\Models\FeedDelivery;
use App\Models\OtherSale;
use Illuminate\Support\Carbon;

class BreakEvenReport
{
    private const FORECAST_DAYS = 730;

    /** @return array<string, float> */
    public function defaults(Carbon $today): array
    {
        $from = $today->copy()->subDays(29);
        $dailyRecords = DailyRecord::query()
            ->whereDate('recorded_on', '>=', $from->toDateString())
            ->whereDate('recorded_on', '<=', $today->toDateString())
            ->get(['production', 'feed']);
        $eggSales = EggSale::query()
            ->whereDate('sold_on', '>=', $from->toDateString())
            ->whereDate('sold_on', '<=', $today->toDateString())
            ->get(['cartons', 'price_per_carton', 'payment_status']);
        $latestDelivery = FeedDelivery::query()->whereDate('delivered_on', '<=', $today->toDateString())->latest('delivered_on')->latest('id')->first();
        $soldCartons = (float) $eggSales->sum('cartons');
        $salesRevenue = (float) $eggSales->sum(fn (EggSale $sale): float => $sale->totalAmount());
        $paidRevenue = (float) $eggSales->where('payment_status', SalePaymentStatus::Paid->value)->sum(fn (EggSale $sale): float => $sale->totalAmount());
        $recordedDays = max(1, $dailyRecords->count());
        $dailyFeedKilograms = (float) $dailyRecords->sum('feed') / $recordedDays;
        $feedStockKilograms = max(0, $this->feedStockKilograms($today));
        $operatingExpenses = (float) Expense::query()
            ->whereDate('spent_on', '>=', $from->toDateString())
            ->whereDate('spent_on', '<=', $today->toDateString())
            ->whereNotIn('category', [ExpenseCategory::Feed->value, ExpenseCategory::WorkerAdvance->value])
            ->sum('amount');

        return [
            'current_debt' => round(max(0, $this->totalExpenses($today) - $this->totalRevenue($today)), 2),
            'egg_inventory_cartons' => round(max(0, $this->eggInventoryCartons($today)), 1),
            'average_sale_price' => round($soldCartons > 0 ? $salesRevenue / $soldCartons : 0, 2),
            'daily_production_cartons' => round((float) $dailyRecords->sum('production') / $recordedDays / EggSale::EGGS_PER_CARTON, 1),
            'feed_stock_days' => round($dailyFeedKilograms > 0 ? $feedStockKilograms / $dailyFeedKilograms : 0, 1),
            'daily_feed_kilograms' => round($dailyFeedKilograms, 1),
            'feed_delivery_tons' => round((float) ($latestDelivery?->weight_tons ?? 0), 3),
            'feed_delivery_cost' => round($latestDelivery?->totalCost() ?? 0, 2),
            'daily_expenses' => round($operatingExpenses / 30, 2),
            'collection_percentage' => round($salesRevenue > 0 ? $paidRevenue / $salesRevenue * 100 : 100, 1),
        ];
    }

    /**
     * @param  array<string, float>  $inputs
     * @return array<string, mixed>
     */
    public function build(Carbon $today, array $inputs): array
    {
        $debt = $inputs['current_debt'];
        $feedDaysRemaining = $inputs['feed_stock_days'];
        $deliveryCoverageDays = $inputs['daily_feed_kilograms'] > 0
            ? $inputs['feed_delivery_tons'] * FeedDelivery::KILOGRAMS_PER_TON / $inputs['daily_feed_kilograms']
            : 0;
        $dailyCollectedSales = $inputs['daily_production_cartons'] * $inputs['average_sale_price'] * $inputs['collection_percentage'] / 100;
        $openingInventoryCollections = $inputs['egg_inventory_cartons'] * $inputs['average_sale_price'] * $inputs['collection_percentage'] / 100;
        $rows = [];
        $feedPurchases = 0;
        $totalFeedPurchases = 0.0;
        $totalCollections = 0.0;
        $totalOperatingExpenses = 0.0;
        $payoffDate = $debt <= 0 ? $today->toDateString() : null;
        $firstFeedPurchaseDate = null;
        $debtAfterFirstFeedPurchase = null;
        $daysSimulated = 0;

        for ($day = 1; $day <= self::FORECAST_DAYS && $debt > 0; $day++) {
            $date = $today->copy()->addDays($day - 1);
            $feedPurchase = 0.0;
            $needsFeedPurchase = $feedDaysRemaining <= 0;
            $isFeedPurchaseDay = false;

            if ($needsFeedPurchase && $deliveryCoverageDays > 0 && $inputs['feed_delivery_cost'] > 0) {
                $feedPurchase = $inputs['feed_delivery_cost'];
                $feedDaysRemaining += $deliveryCoverageDays;
                $isFeedPurchaseDay = true;
                $feedPurchases++;
                $totalFeedPurchases += $feedPurchase;
                $firstFeedPurchaseDate ??= $date->toDateString();
            }

            $collections = $dailyCollectedSales + ($day === 1 ? $openingInventoryCollections : 0);
            $openingDebt = $debt;
            $debt = max(0, $debt + $feedPurchase + $inputs['daily_expenses'] - $collections);
            $totalCollections += $collections;
            $totalOperatingExpenses += $inputs['daily_expenses'];
            $daysSimulated = $day;
            $feedDaysRemaining = max(0, $feedDaysRemaining - 1);

            if ($isFeedPurchaseDay && $debtAfterFirstFeedPurchase === null) {
                $debtAfterFirstFeedPurchase = $debt;
            }

            $isPayoffDay = $debt <= 0;

            if ($day <= 14 || $isFeedPurchaseDay || $isPayoffDay) {
                $rows[] = [
                    'day' => $day,
                    'date' => $date->toDateString(),
                    'openingDebt' => round($openingDebt, 2),
                    'collections' => round($collections, 2),
                    'feedPurchase' => round($feedPurchase, 2),
                    'operatingExpenses' => round($inputs['daily_expenses'], 2),
                    'closingDebt' => round($debt, 2),
                    'isFeedPurchaseDay' => $isFeedPurchaseDay,
                    'isPayoffDay' => $isPayoffDay,
                ];
            }

            if ($isPayoffDay) {
                $payoffDate = $date->toDateString();
            }
        }

        $dailyNetBeforeFeed = $dailyCollectedSales - $inputs['daily_expenses'];
        $averageDailyFeedPurchaseCost = $deliveryCoverageDays > 0 ? $inputs['feed_delivery_cost'] / $deliveryCoverageDays : 0;
        $notes = [
            'يفترض التوقع بيع متوسط الإنتاج اليومي بنفس متوسط سعر آخر 30 يومًا.',
            'قيمة مخزون البيض الحالي تدخل ضمن تحصيلات أول يوم في المحاكاة.',
            'تضاف نقلة علف جديدة كلما انتهى الرصيد، بنفس كمية وتكلفة النقلة المحددتين.',
            'المبيعات الآجلة لا تدخل نقدًا إلا بنسبة التحصيل المحددة.',
            'النتيجة تقديرية وتتغير عند تغير الإنتاج أو السعر أو المصروفات أو مواعيد التحصيل.',
        ];

        if ($inputs['daily_feed_kilograms'] <= 0 || $deliveryCoverageDays <= 0) {
            $notes[] = 'بيانات استهلاك أو كمية العلف غير كافية لتكرار نقلات العلف داخل التوقع.';
        }

        return [
            'payoffDate' => $payoffDate,
            'daysToPayoff' => $payoffDate === null ? null : $daysSimulated,
            'remainingDebt' => round($debt, 2),
            'daysSimulated' => $daysSimulated,
            'nextFeedDepletionDate' => $today->copy()->addDays((int) ceil($inputs['feed_stock_days']))->toDateString(),
            'firstFeedPurchaseDate' => $firstFeedPurchaseDate,
            'debtAfterFirstFeedPurchase' => $debtAfterFirstFeedPurchase === null ? null : round($debtAfterFirstFeedPurchase, 2),
            'deliveryCoverageDays' => round($deliveryCoverageDays, 1),
            'dailyCollections' => round($dailyCollectedSales, 2),
            'openingInventoryValue' => round($openingInventoryCollections, 2),
            'dailyNetBeforeFeed' => round($dailyNetBeforeFeed, 2),
            'averageDailyFeedPurchaseCost' => round($averageDailyFeedPurchaseCost, 2),
            'averageDailyNetCash' => round($dailyNetBeforeFeed - $averageDailyFeedPurchaseCost, 2),
            'feedPurchases' => $feedPurchases,
            'totalFeedPurchases' => round($totalFeedPurchases, 2),
            'totalCollections' => round($totalCollections, 2),
            'totalOperatingExpenses' => round($totalOperatingExpenses, 2),
            'rows' => $rows,
            'notes' => $notes,
        ];
    }

    private function totalExpenses(Carbon $today): float
    {
        return (float) Expense::query()->whereDate('spent_on', '<=', $today->toDateString())->sum('amount');
    }

    private function totalRevenue(Carbon $today): float
    {
        $eggRevenue = EggSale::query()->whereDate('sold_on', '<=', $today->toDateString())->get(['cartons', 'price_per_carton'])->sum(fn (EggSale $sale): float => $sale->totalAmount());
        $otherRevenue = OtherSale::query()->whereDate('sold_on', '<=', $today->toDateString())->get(['quantity', 'unit_price'])->sum(fn (OtherSale $sale): float => $sale->totalAmount());

        return (float) $eggRevenue + (float) $otherRevenue;
    }

    private function eggInventoryCartons(Carbon $today): float
    {
        $producedEggs = (float) DailyRecord::query()->whereDate('recorded_on', '<=', $today->toDateString())->sum('production');
        $soldEggs = (float) EggSale::query()->whereDate('sold_on', '<=', $today->toDateString())->sum('cartons') * EggSale::EGGS_PER_CARTON;

        return ($producedEggs - $soldEggs) / EggSale::EGGS_PER_CARTON;
    }

    private function feedStockKilograms(Carbon $today): float
    {
        $delivered = FeedDelivery::query()->whereDate('delivered_on', '<=', $today->toDateString())->get(['weight_tons'])->sum(fn (FeedDelivery $delivery): float => $delivery->weightInKilograms());
        $consumed = (float) DailyRecord::query()->whereDate('recorded_on', '<=', $today->toDateString())->sum('feed');

        return (float) $delivered - $consumed;
    }
}
