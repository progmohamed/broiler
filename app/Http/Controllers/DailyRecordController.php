<?php

namespace App\Http\Controllers;

use App\ExpensePaymentMethod;
use App\Http\Requests\StoreDailyRecordRequest;
use App\Models\DailyRecord;
use App\Models\EggSale;
use App\Models\Expense;
use App\Models\FarmSetting;
use App\Models\FeedDelivery;
use App\Models\OtherSale;
use App\SalePaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DailyRecordController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'recorded_on' => ['nullable', 'date_format:Y-m-d'],
            'show' => ['nullable', 'in:all'],
        ], [
            'recorded_on.date_format' => 'اختر تاريخًا صحيحًا للسجل.',
            'show.in' => 'خيار عرض السجلات غير صحيح.',
        ]);

        $selectedDate = isset($validated['recorded_on'])
            ? Carbon::createFromFormat('Y-m-d', $validated['recorded_on'])
            : today();
        $setting = FarmSetting::query()->first();
        $record = DailyRecord::query()->whereDate('recorded_on', $selectedDate)->first();
        $ageInDays = $this->calculateAgeInDays($setting, $selectedDate);
        $deliveredFeedKilograms = (float) FeedDelivery::query()
            ->whereDate('delivered_on', '<=', $selectedDate)
            ->sum('weight_tons') * FeedDelivery::KILOGRAMS_PER_TON;
        $dailyTotals = DailyRecord::query()
            ->whereDate('recorded_on', '<=', $selectedDate)
            ->toBase()
            ->selectRaw('COALESCE(SUM(feed), 0) AS used_feed_kilograms')
            ->selectRaw('COALESCE(SUM(production), 0) AS produced_eggs')
            ->first();
        $usedFeedKilograms = (float) $dailyTotals->used_feed_kilograms;
        $remainingFeedKilograms = $deliveredFeedKilograms - $usedFeedKilograms;
        $producedEggs = (int) $dailyTotals->produced_eggs;
        $soldEggs = (int) EggSale::query()
            ->whereDate('sold_on', '<=', $selectedDate)
            ->sum('cartons') * EggSale::EGGS_PER_CARTON;
        $remainingEggs = $producedEggs - $soldEggs;
        $showAllRecords = ($validated['show'] ?? null) === 'all';
        $recentRecords = DailyRecord::query()
            ->latest('recorded_on')
            ->paginate($showAllRecords ? 20 : 3)
            ->withQueryString();
        $previousDates = $recentRecords->getCollection()
            ->map(fn (DailyRecord $dailyRecord): string => $dailyRecord->recorded_on->copy()->subDay()->toDateString());
        $previousRecords = DailyRecord::query()
            ->where(function (Builder $query) use ($previousDates): void {
                $query->whereRaw('1 = 0');

                foreach ($previousDates as $previousDate) {
                    $query->orWhereDate('recorded_on', $previousDate);
                }
            })
            ->get(['recorded_on', 'production'])
            ->keyBy(fn (DailyRecord $dailyRecord): string => $dailyRecord->recorded_on->toDateString());
        $recentRecords->through(function (DailyRecord $dailyRecord) use ($setting, $previousRecords): array {
            $recordAgeInDays = $this->calculateAgeInDays($setting, $dailyRecord->recorded_on);
            $previousRecord = $previousRecords->get($dailyRecord->recorded_on->copy()->subDay()->toDateString());

            return [
                'record' => $dailyRecord,
                'ageInDays' => $recordAgeInDays,
                'ageInWeeks' => $recordAgeInDays === null ? null : intdiv($recordAgeInDays, 7),
                'productionPercentage' => $this->calculateProductionPercentage($dailyRecord, $setting),
                'productionChange' => $this->calculateProductionChange($dailyRecord, $previousRecord, $setting),
            ];
        });
        $weeklyRecords = DailyRecord::query()
            ->whereBetween('recorded_on', [
                $selectedDate->copy()->subDays(6)->startOfDay(),
                $selectedDate->copy()->endOfDay(),
            ])
            ->get()
            ->keyBy(fn (DailyRecord $dailyRecord): string => $dailyRecord->recorded_on->toDateString());
        $productionTrend = $this->buildProductionTrend($selectedDate, $weeklyRecords);
        $weeklyRecordedDays = $weeklyRecords->count();
        $weeklyAverageProduction = $weeklyRecordedDays === 0 ? null : round($weeklyRecords->avg('production'));
        $weeklyAverageFeedKilograms = $weeklyRecordedDays === 0 ? null : round($weeklyRecords->avg('feed'));
        $estimatedFeedDays = $weeklyAverageFeedKilograms === null || $weeklyAverageFeedKilograms <= 0
            ? null
            : max(0, (int) floor($remainingFeedKilograms / $weeklyAverageFeedKilograms));
        $pendingRevenue = $this->calculatePendingRevenue($selectedDate);
        $creditExpenses = (float) Expense::query()
            ->whereDate('spent_on', '<=', $selectedDate)
            ->where('payment_method', ExpensePaymentMethod::Credit->value)
            ->sum('amount');
        $alerts = $this->buildAlerts(
            record: $record,
            selectedDate: $selectedDate,
            setting: $setting,
            remainingFeedKilograms: $remainingFeedKilograms,
            remainingEggs: $remainingEggs,
            estimatedFeedDays: $estimatedFeedDays,
            pendingRevenue: $pendingRevenue,
            creditExpenses: $creditExpenses,
        );

        return view('daily-records.index', [
            'records' => $recentRecords,
            'record' => $record,
            'selectedDate' => $selectedDate,
            'ageInDays' => $ageInDays,
            'ageInWeeks' => $ageInDays === null ? null : intdiv($ageInDays, 7),
            'remainingFeedKilograms' => $remainingFeedKilograms,
            'remainingFeedSacks' => $remainingFeedKilograms / DailyRecord::FEED_SACK_WEIGHT_KG,
            'remainingEggs' => $remainingEggs,
            'remainingEggCartons' => $remainingEggs / EggSale::EGGS_PER_CARTON,
            'setting' => $setting,
            'isToday' => $selectedDate->isSameDay(today()),
            'ageRemainingDays' => $ageInDays === null ? null : $ageInDays % 7,
            'productionTrend' => $productionTrend,
            'weeklyAverageProduction' => $weeklyAverageProduction,
            'weeklyAverageFeedKilograms' => $weeklyAverageFeedKilograms,
            'weeklyTotalDeaths' => (int) $weeklyRecords->sum('deaths'),
            'weeklyRecordedDays' => $weeklyRecordedDays,
            'estimatedFeedDays' => $estimatedFeedDays,
            'pendingRevenue' => $pendingRevenue,
            'creditExpenses' => $creditExpenses,
            'alerts' => $alerts,
            'showAllRecords' => $showAllRecords,
        ]);
    }

    public function store(StoreDailyRecordRequest $request): RedirectResponse
    {
        $recordedOn = $request->string('recorded_on')->toString();
        $record = DailyRecord::query()->whereDate('recorded_on', $recordedOn)->firstOrNew();
        $recordData = $request->safe()->only(['recorded_on', 'production', 'deaths', 'medicines', 'notes']);
        $recordData['feed'] = (int) ($request->float('feed_sacks') * DailyRecord::FEED_SACK_WEIGHT_KG);
        $record->fill($recordData);
        $isNewRecord = ! $record->exists;
        $record->save();

        $status = $isNewRecord ? 'تم حفظ السجل اليومي.' : 'تم تحديث السجل اليومي.';

        return to_route('daily-records.index', ['recorded_on' => $record->recorded_on->toDateString()])
            ->with('status', $status);
    }

    private function calculateAgeInDays(?FarmSetting $setting, Carbon $recordedOn): ?int
    {
        if ($setting === null || $recordedOn->isBefore($setting->herd_birth_date)) {
            return null;
        }

        return (int) $setting->herd_birth_date->diffInDays($recordedOn);
    }

    private function calculateProductionPercentage(DailyRecord $record, ?FarmSetting $setting): ?float
    {
        if ($setting === null || $setting->total_herd_count <= 0) {
            return null;
        }

        return round(($record->production / $setting->total_herd_count) * 100, 1);
    }

    /**
     * @return array{difference: int, percentage: float}|null
     */
    private function calculateProductionChange(DailyRecord $record, ?DailyRecord $previousRecord, ?FarmSetting $setting): ?array
    {
        if ($previousRecord === null || $setting === null || $setting->total_herd_count <= 0) {
            return null;
        }

        $difference = $record->production - $previousRecord->production;

        return [
            'difference' => $difference,
            'percentage' => round(($difference / $setting->total_herd_count) * 100, 1),
        ];
    }

    /**
     * @param  Collection<string, DailyRecord>  $weeklyRecords
     * @return list<array{date: Carbon, production: int, height: int, hasRecord: bool}>
     */
    private function buildProductionTrend(Carbon $selectedDate, Collection $weeklyRecords): array
    {
        $highestProduction = max(1, (int) $weeklyRecords->max('production'));

        return collect(range(6, 0))
            ->map(function (int $daysAgo) use ($selectedDate, $weeklyRecords, $highestProduction): array {
                $date = $selectedDate->copy()->subDays($daysAgo);
                $dailyRecord = $weeklyRecords->get($date->toDateString());
                $production = $dailyRecord?->production ?? 0;

                return [
                    'date' => $date,
                    'production' => $production,
                    'height' => $production === 0 ? 4 : max(10, (int) round(($production / $highestProduction) * 100)),
                    'hasRecord' => $dailyRecord !== null,
                ];
            })
            ->all();
    }

    private function calculatePendingRevenue(Carbon $selectedDate): float
    {
        $pendingEggSales = EggSale::query()
            ->whereDate('sold_on', '<=', $selectedDate)
            ->where('payment_status', SalePaymentStatus::Pending->value)
            ->toBase()
            ->selectRaw('COALESCE(SUM(cartons * price_per_carton), 0) AS total')
            ->value('total');
        $pendingOtherSales = OtherSale::query()
            ->whereDate('sold_on', '<=', $selectedDate)
            ->where('payment_status', SalePaymentStatus::Pending->value)
            ->toBase()
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) AS total')
            ->value('total');

        return (float) $pendingEggSales + (float) $pendingOtherSales;
    }

    /**
     * @return list<array{tone: string, message: string, route: string|null, action: string|null}>
     */
    private function buildAlerts(
        ?DailyRecord $record,
        Carbon $selectedDate,
        ?FarmSetting $setting,
        float $remainingFeedKilograms,
        int $remainingEggs,
        ?int $estimatedFeedDays,
        float $pendingRevenue,
        float $creditExpenses,
    ): array {
        $alerts = [];

        if ($record === null) {
            $alerts[] = [
                'tone' => 'blue',
                'message' => $selectedDate->isSameDay(today()) ? 'لم يتم تسجيل بيانات اليوم بعد.' : 'لا يوجد سجل لهذا التاريخ.',
                'route' => '#daily-entry',
                'action' => 'سجّل الآن',
            ];
        }

        if ($setting === null) {
            $alerts[] = [
                'tone' => 'orange',
                'message' => 'أكمل بيانات القطيع لعرض العمر ونسب الإنتاج بدقة.',
                'route' => route('farm-settings.edit'),
                'action' => 'الإعدادات',
            ];
        }

        if ($remainingFeedKilograms < 0) {
            $alerts[] = [
                'tone' => 'red',
                'message' => 'رصيد العلف بالسالب. راجع التوريدات والاستهلاك.',
                'route' => route('finance.index').'#feed-purchase-form',
                'action' => 'راجع العلف',
            ];
        } elseif ($estimatedFeedDays !== null && $estimatedFeedDays <= 3) {
            $alerts[] = [
                'tone' => 'orange',
                'message' => "العلف الحالي يكفي تقريبًا {$estimatedFeedDays} أيام فقط.",
                'route' => route('finance.index').'#feed-purchase-form',
                'action' => 'أضف توريدًا',
            ];
        }

        if ($remainingEggs < 0) {
            $alerts[] = [
                'tone' => 'red',
                'message' => 'مبيعات البيض أكبر من الإنتاج المسجل حتى هذا التاريخ.',
                'route' => route('sales.index').'#egg-sales-list',
                'action' => 'راجع المبيعات',
            ];
        }

        if ($pendingRevenue > 0 || $creditExpenses > 0) {
            $alerts[] = [
                'tone' => 'slate',
                'message' => 'توجد مبالغ آجلة تحتاج إلى متابعة في المالية.',
                'route' => route('finance.index'),
                'action' => 'عرض المالية',
            ];
        }

        return $alerts;
    }
}
