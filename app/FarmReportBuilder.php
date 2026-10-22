<?php

namespace App;

use App\Models\DailyRecord;
use App\Models\EggSale;
use App\Models\Expense;
use App\Models\FarmSetting;
use App\Models\FeedDelivery;
use App\Models\OtherSale;
use App\Models\WorkerAdvance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type Plan from FarmReportPlanner
 *
 * @phpstan-type Metric array{key: string, label: string, value: int|float|null, unit: string, precision: int}
 * @phpstan-type Table array{title: string, headings: list<string>, rows: list<list<string|int|float|null>>}
 * @phpstan-type Chart array{title: string, unit: string, tone: string, points: list<array{date: Carbon, value: int|float, height: int}>}
 * @phpstan-type Report array{title: string, period: string, answer: string, notes: list<string>, metrics: list<Metric>, tables: list<Table>, charts: list<Chart>}
 */
class FarmReportBuilder
{
    /**
     * @param  Plan  $plan
     * @return Report
     */
    public function build(array $plan): array
    {
        $from = Carbon::parse($plan['start_date'])->startOfDay();
        $to = Carbon::parse($plan['end_date'])->endOfDay();
        $report = [
            'title' => match ($plan['report']) {
                'production' => 'تقرير إنتاج البيض',
                'mortality' => 'تقرير النافق',
                'feed_efficiency' => 'كفاءة استهلاك العلف',
                'feed_purchases' => 'تقرير مشتريات العلف',
                'egg_sales' => 'تقرير مبيعات البيض',
                'other_sales' => 'تقرير المبيعات الأخرى',
                'expenses' => 'تقرير المصروفات',
                'worker_advances' => 'تقرير سلف العمال',
                'inventory' => 'تقرير المخزون',
                'finance' => 'التقرير المالي',
                'summary' => 'ملخص أداء القطيع',
                'general' => 'إجابة المساعد',
                default => 'بيانات غير متاحة',
            },
            'period' => $from->toDateString().' — '.$to->toDateString(),
            'answer' => '', 'notes' => [], 'metrics' => [], 'tables' => [], 'charts' => [],
        ];

        if ($plan['report'] === 'general') {
            $report['answer'] = filled($plan['answer']) ? $plan['answer'] : 'أعد صياغة سؤالك بتفاصيل أكثر لأتمكن من مساعدتك.';

            return $report;
        }

        if ($plan['scope'] !== 'farm') {
            $report['answer'] = 'التطبيق يسجل قطيعًا واحدًا على مستوى المزرعة ولا يربط السجلات بعنابر أو قطعان متعددة. لا يمكن تحديد إنتاج عنبر بعينه أو مقارنة العنابر أو ترتيب أدائها من البيانات الحالية.';

            return $report;
        }

        if ($plan['report'] === 'unsupported' || in_array($plan['missing_data'], ['prolapse', 'other'], true)) {
            $report['answer'] = $plan['missing_data'] === 'prolapse'
                ? 'لا توجد سجلات منظمة لعدد حالات انقلاب الرحم. الملاحظات والأدوية لا تكفي لاستخراج عدد موثوق، ولا يعني غياب البيانات عدم وجود حالات.'
                : 'أستطيع الإجابة عن كل البيانات المسجلة: الإنتاج والنافق والعلف والمخزون والمبيعات والمصروفات والسلف والمالية، إضافة إلى الأسئلة الإرشادية العامة. البيانات غير المسجلة لا يمكن حسابها، وتعديل السجلات غير متاح من المحادثة.';

            return $report;
        }

        $setting = FarmSetting::query()->first();
        $herdCount = $setting?->total_herd_count;
        $herdCount = $herdCount > 0 ? $herdCount : null;
        $data = $this->periodData($plan, $from, $to, $herdCount);
        $report = array_replace($report, $data);

        if ($plan['missing_data'] === 'mortality_causes') {
            $report['notes'][] = 'أسباب النافق غير مسجلة في حقول منظمة؛ الأعداد المعروضة إجمالية ولا تثبت سببًا طبيًا.';
        }

        if (in_array($plan['report'], ['production', 'mortality', 'feed_efficiency', 'summary'], true)) {
            $report['notes'][] = $herdCount === null
                ? 'عدد القطيع غير محدد أو يساوي صفرًا؛ تعذّر حساب النسب واستهلاك العلف لكل دجاجة.'
                : 'النسب محسوبة على عدد القطيع المسجل في الإعدادات ('.number_format($herdCount).')، وهو عدد ثابت؛ لا يوجد تاريخ لتغير حجم القطيع.';
        }

        if ($plan['comparison'] === 'previous_period') {
            $days = (int) $from->diffInDays($to->copy()->startOfDay()) + 1;
            $previousTo = $from->copy()->subDay()->endOfDay();
            $previousFrom = $from->copy()->subDays($days);
            $previous = $this->periodData($plan, $previousFrom, $previousTo, $herdCount);
            $previousMetrics = collect($previous['metrics'])->keyBy('key');
            $rows = [];

            foreach ($report['metrics'] as $metric) {
                $previousValue = $previousMetrics->get($metric['key'])['value'] ?? null;
                $difference = $metric['value'] === null || $previousValue === null ? null : $metric['value'] - $previousValue;
                $unit = $metric['unit'] === '%' ? 'نقطة مئوية' : $metric['unit'];
                $rows[] = [
                    $metric['label'],
                    $this->format($previousValue, $metric['precision']),
                    $this->format($metric['value'], $metric['precision']),
                    $difference === null ? '—' : $this->format($difference, $metric['precision']).' '.$unit,
                ];
            }

            $report['tables'][] = [
                'title' => 'مقارنة الفترة السابقة: '.$previousFrom->toDateString().' — '.$previousTo->toDateString(),
                'headings' => ['المؤشر', 'السابقة', 'الحالية', 'الفرق'],
                'rows' => $rows,
            ];
            $report['notes'][] = 'المقارنة بفترة سابقة مساوية في عدد الأيام. راجع اكتمال التسجيل في الفترتين قبل تفسير الفرق.';
            $report['notes'] = array_values(array_unique(array_merge($report['notes'], array_map(
                fn (string $note): string => 'الفترة السابقة: '.$note,
                $previous['notes'],
            ))));
        }

        $report['answer'] = $this->reportAnswer($plan, $report);

        return $report;
    }

    /**
     * @param  Plan  $plan
     * @return array{metrics: list<Metric>, tables: list<Table>, charts: list<Chart>, notes: list<string>}
     */
    private function periodData(array $plan, Carbon $from, Carbon $to, ?int $herdCount): array
    {
        $data = ['metrics' => [], 'tables' => [], 'charts' => [], 'notes' => []];

        if (in_array($plan['report'], ['production', 'mortality', 'feed_efficiency', 'summary'], true)) {
            $records = DailyRecord::query()->whereDate('recorded_on', '>=', $from)->whereDate('recorded_on', '<=', $to)
                ->oldest('recorded_on')->get(['recorded_on', 'production', 'feed', 'deaths']);
            $days = $records->count();
            $expectedDays = (int) $from->diffInDays($to->copy()->startOfDay()) + 1;
            $production = $days === 0 ? null : (int) $records->sum('production');
            $feed = $days === 0 ? null : (int) $records->sum('feed');
            $deaths = $days === 0 ? null : (int) $records->sum('deaths');
            $data['notes'][] = "توجد بيانات لـ {$days} يوم من {$expectedDays} يوم. الأيام غير المسجلة لا تُحسب أصفارًا؛ المتوسطات تخص الأيام المسجلة فقط.";
            $data['metrics'][] = $this->metric('recorded_days', 'أيام مسجلة', $days, 'يوم');

            if (in_array($plan['report'], ['production', 'summary'], true)) {
                $data['metrics'][] = $this->metric('production', 'إجمالي الإنتاج', $production, 'بيضة');
                $data['metrics'][] = $this->metric('production_rate', 'متوسط نسبة الإنتاج اليومية', $this->ratio($production, $herdCount === null ? null : $herdCount * $days, 100), '%', 1);
            }

            if (in_array($plan['report'], ['mortality', 'summary'], true)) {
                $data['metrics'][] = $this->metric('deaths', 'إجمالي النافق', $deaths, 'دجاجة');
                $data['metrics'][] = $this->metric('mortality_rate', 'نسبة النافق خلال الفترة', $this->ratio($deaths, $herdCount, 100), '%', 2);
            }

            if (in_array($plan['report'], ['feed_efficiency', 'summary'], true)) {
                $data['metrics'][] = $this->metric('feed', 'العلف المستهلك', $feed, 'كجم', 1);
                $data['metrics'][] = $this->metric('feed_per_hen', 'متوسط العلف لكل دجاجة يوميًا', $this->ratio($feed, $herdCount === null ? null : $herdCount * $days, 1000), 'جرام', 1);
                $data['metrics'][] = $this->metric('feed_per_egg', 'العلف لكل بيضة', $this->ratio($feed, $production, 1000), 'جرام', 1);
                $data['notes'][] = 'استهلاك الدجاجة اليومي = العلف بالجرام ÷ (عدد القطيع × الأيام المسجلة). استهلاك البيضة = العلف بالجرام ÷ الإنتاج؛ يظهر — إذا كان الإنتاج صفرًا.';
            }

            $groups = $records->groupBy(fn (DailyRecord $record): string => $this->groupDate($record->recorded_on, $plan['group_by'])->toDateString());
            $rows = [];
            $productionPoints = [];
            $feedPoints = [];
            $deathPoints = [];

            foreach ($groups as $date => $group) {
                $groupProduction = (int) $group->sum('production');
                $groupFeed = (int) $group->sum('feed');
                $groupDeaths = (int) $group->sum('deaths');
                $row = [$date, $group->count()];

                if (in_array($plan['report'], ['production', 'summary'], true)) {
                    $row[] = $groupProduction;
                    $row[] = $this->format($this->ratio($groupProduction, $herdCount === null ? null : $herdCount * $group->count(), 100), 1);
                }

                if (in_array($plan['report'], ['mortality', 'summary'], true)) {
                    $row[] = $groupDeaths;
                }

                if (in_array($plan['report'], ['feed_efficiency', 'summary'], true)) {
                    $row[] = $groupFeed;
                    $row[] = $this->format($this->ratio($groupFeed, $groupProduction, 1000), 1);
                    $row[] = $this->format($this->ratio($groupFeed, $herdCount === null ? null : $herdCount * $group->count(), 1000), 1);

                    if ($plan['report'] === 'feed_efficiency') {
                        $row[] = $groupProduction;
                    }
                }

                $rows[] = $row;
                $productionPoints[] = ['date' => Carbon::parse($date), 'value' => $groupProduction];
                $feedPoints[] = ['date' => Carbon::parse($date), 'value' => $groupFeed];
                $deathPoints[] = ['date' => Carbon::parse($date), 'value' => $groupDeaths];
            }

            $headings = ['بداية الفترة', 'أيام مسجلة'];

            if (in_array($plan['report'], ['production', 'summary'], true)) {
                $headings = array_merge($headings, ['بيض', 'متوسط الإنتاج %']);
            }

            if (in_array($plan['report'], ['mortality', 'summary'], true)) {
                $headings[] = 'نافق';
                $data['charts'][] = $this->chart('تطور النافق', 'دجاجة', 'red', $deathPoints);
            }

            if (in_array($plan['report'], ['feed_efficiency', 'summary'], true)) {
                $headings = array_merge($headings, ['علف كجم', 'جرام/بيضة', 'جرام/دجاجة/يوم']);
                $data['charts'][] = $this->chart('استهلاك العلف', 'كجم', 'orange', $feedPoints);

                if ($plan['report'] === 'feed_efficiency') {
                    $headings[] = 'بيض';
                }
            }

            if ($plan['report'] !== 'mortality') {
                $data['charts'][] = $this->chart('تطور إنتاج البيض', 'بيضة', 'green', $productionPoints);
            }

            $data['tables'][] = ['title' => 'تفاصيل القطيع المسجل', 'headings' => $headings, 'rows' => $rows];
        }

        if ($plan['report'] === 'feed_purchases') {
            $feedPurchases = $this->feedPurchasesData($from, $to, $plan['group_by'], $plan['supplier'], $plan['payment_method']);

            foreach (array_keys($data) as $key) {
                $data[$key] = array_merge($data[$key], $feedPurchases[$key]);
            }
        }

        $searchReports = [
            'egg_sales' => fn (): array => $this->eggSalesData($from, $to, $plan['group_by'], $plan['target'], $plan['payment_status']),
            'other_sales' => fn (): array => $this->otherSalesData($from, $to, $plan['group_by'], $plan['target'], $plan['payment_status']),
            'expenses' => fn (): array => $this->expensesData($from, $to, $plan['group_by'], $plan['target'], $plan['payment_method']),
            'worker_advances' => fn (): array => $this->workerAdvancesData($from, $to, $plan['group_by'], $plan['target'], $plan['payment_method']),
            'inventory' => fn (): array => $this->inventoryData($to),
        ];

        if (isset($searchReports[$plan['report']])) {
            $reportData = $searchReports[$plan['report']]();

            foreach (array_keys($data) as $key) {
                $data[$key] = array_merge($data[$key], $reportData[$key]);
            }
        }

        if (in_array($plan['report'], ['finance', 'summary'], true)) {
            $financialData = $this->financialData($from, $to, $plan['group_by']);

            foreach (array_keys($data) as $key) {
                $data[$key] = array_merge($data[$key], $financialData[$key]);
            }
        }

        if ($plan['report'] !== 'inventory') {
            $data['notes'][] = match ($plan['group_by']) {
                'week' => 'التجميع أسبوعي بدايةً من الاثنين، مع احتساب الأيام داخل الفترة المطلوبة فقط.',
                'month' => 'التجميع شهري، مع احتساب الأيام داخل الفترة المطلوبة فقط.',
                default => 'التجميع يومي حسب تاريخ السجل.',
            };
        }

        return $data;
    }

    /** @return array{metrics: list<Metric>, tables: list<Table>, charts: list<Chart>, notes: list<string>} */
    private function feedPurchasesData(Carbon $from, Carbon $to, string $groupBy, ?string $supplier, ?string $paymentMethod): array
    {
        $deliveries = FeedDelivery::query()
            ->whereDate('delivered_on', '>=', $from)
            ->whereDate('delivered_on', '<=', $to)
            ->oldest('delivered_on')
            ->oldest('id')
            ->get([
                'id', 'delivered_on', 'feed_type', 'weight_tons', 'price_per_ton', 'shipping_cost',
                'tip', 'supplier', 'payment_method', 'invoice_number',
            ]);

        $supplier = is_string($supplier) && trim($supplier) !== '' ? trim($supplier) : null;

        if ($supplier !== null) {
            $needle = Str::lower($supplier);
            $deliveries = $deliveries
                ->filter(fn (FeedDelivery $delivery): bool => str_contains(Str::lower($delivery->supplier), $needle))
                ->values();
        }

        if ($paymentMethod !== null) {
            $deliveries = $deliveries->where('payment_method', ExpensePaymentMethod::from($paymentMethod))->values();
        }

        $weightTons = round((float) $deliveries->sum(fn (FeedDelivery $delivery): float => (float) $delivery->weight_tons), 3);
        $sackCount = round((float) $deliveries->sum(fn (FeedDelivery $delivery): float => $delivery->sackCount()), 1);
        $totalCost = round((float) $deliveries->sum(fn (FeedDelivery $delivery): float => $delivery->totalCost()), 2);
        $groups = $deliveries->groupBy(fn (FeedDelivery $delivery): string => $this->groupDate($delivery->delivered_on, $groupBy)->toDateString());
        $weightPoints = [];

        foreach ($groups as $date => $group) {
            $weightPoints[] = [
                'date' => Carbon::parse($date),
                'value' => round((float) $group->sum(fn (FeedDelivery $delivery): float => (float) $delivery->weight_tons), 3),
            ];
        }

        $rows = $deliveries->map(fn (FeedDelivery $delivery): array => [
            $delivery->delivered_on->toDateString(),
            $delivery->supplier,
            $delivery->feed_type,
            $this->format((float) $delivery->weight_tons, 3),
            $this->format($delivery->sackCount(), 1),
            $this->format((float) $delivery->price_per_ton, 2),
            $this->format((float) $delivery->shipping_cost, 2),
            $this->format((float) $delivery->tip, 2),
            $this->format($delivery->totalCost(), 2),
            $delivery->payment_method->label(),
            $delivery->invoice_number,
        ])->all();
        $supplierRows = $deliveries->groupBy('supplier')->map(function ($group, string $supplier): array {
            return [
                $supplier,
                $group->count(),
                $this->format((float) $group->sum(fn (FeedDelivery $delivery): float => (float) $delivery->weight_tons), 3),
                $this->format((float) $group->sum(fn (FeedDelivery $delivery): float => $delivery->totalCost()), 2),
            ];
        })->sortByDesc(fn (array $row): float => (float) str_replace(',', '', $row[2]))->values()->all();

        return [
            'metrics' => [
                $this->metric('feed_delivery_count', 'عدد النقلات', $deliveries->count(), 'نقلة'),
                $this->metric('feed_purchased_tons', 'كمية العلف المشتراة', $weightTons, 'طن', 3),
                $this->metric('feed_purchase_cost', 'إجمالي تكلفة المشتريات', $totalCost, 'جنيه', 2),
                $this->metric('feed_purchased_sacks', 'ما يعادل شكائر 50 كجم', $sackCount, 'شكارة', 1),
            ],
            'tables' => [
                ['title' => 'ملخص الموردين', 'headings' => ['المورد', 'عدد النقلات', 'الوزن (طن)', 'التكلفة'], 'rows' => $supplierRows],
                [
                    'title' => $supplier === null ? 'تفاصيل نقلات العلف' : 'تفاصيل نقلات المورد '.$supplier,
                    'headings' => ['التاريخ', 'المورد', 'نوع العلف', 'الوزن (طن)', 'شكائر 50 كجم', 'سعر الطن', 'الشحن', 'الإكرامية', 'الإجمالي', 'الدفع', 'الفاتورة'],
                    'rows' => $rows,
                ],
            ],
            'charts' => [$this->chart('كميات العلف المشتراة', 'طن', 'orange', $weightPoints)],
            'notes' => [
                $supplier === null ? 'يعرض التقرير كل الموردين خلال الفترة المطلوبة.' : 'تمت مطابقة اسم المورد جزئيًا مع: '.$supplier.'.',
                $paymentMethod === null ? 'يعرض التقرير كل طرق الدفع.' : 'طريقة الدفع المطلوبة: '.ExpensePaymentMethod::from($paymentMethod)->label().'.',
                'إجمالي التكلفة = كمية الأطنان × سعر الطن + الشحن + الإكرامية. عدد الشكائر محسوب على وزن 50 كجم للشكارة.',
                $deliveries->isEmpty() ? 'لا توجد نقلات علف مطابقة خلال هذه الفترة.' : 'تُعرض كل نقلة مسجلة داخل الفترة المطلوبة.',
            ],
        ];
    }

    /** @return array{metrics: list<Metric>, tables: list<Table>, charts: list<Chart>, notes: list<string>} */
    private function eggSalesData(Carbon $from, Carbon $to, string $groupBy, ?string $search, ?string $paymentStatus): array
    {
        $sales = EggSale::query()->whereDate('sold_on', '>=', $from)->whereDate('sold_on', '<=', $to)
            ->oldest('sold_on')->oldest('id')->get();
        $sales = $sales->filter(fn (EggSale $sale): bool => $this->matches($search, [
            $sale->buyer, $sale->invoice_number, $sale->notes,
        ]))->values();

        if ($paymentStatus !== null) {
            $sales = $sales->where('payment_status', $paymentStatus)->values();
        }
        $cartons = (int) $sales->sum('cartons');
        $revenue = round((float) $sales->sum(fn (EggSale $sale): float => $sale->totalAmount()), 2);
        $pending = round((float) $sales->filter(fn (EggSale $sale): bool => $sale->payment_status === SalePaymentStatus::Pending->value)
            ->sum(fn (EggSale $sale): float => $sale->totalAmount()), 2);
        $points = $sales->groupBy(fn (EggSale $sale): string => $this->groupDate($sale->sold_on, $groupBy)->toDateString())
            ->map(fn ($group, string $date): array => ['date' => Carbon::parse($date), 'value' => round((float) $group->sum(fn (EggSale $sale): float => $sale->totalAmount()), 2)])
            ->values()->all();
        $buyerRows = $sales->groupBy('buyer')->map(function ($group, string $buyer): array {
            return [
                $buyer,
                (int) $group->sum('cartons'),
                $this->format((float) $group->sum(fn (EggSale $sale): float => $sale->totalAmount()), 2),
                $group->count(),
            ];
        })->sortByDesc(fn (array $row): int => $row[1])->values()->all();

        return [
            'metrics' => [
                $this->metric('egg_sale_count', 'عدد عمليات البيع', $sales->count(), 'عملية'),
                $this->metric('egg_sale_cartons', 'الكرتونات المباعة', $cartons, 'كرتونة'),
                $this->metric('egg_sale_revenue', 'قيمة مبيعات البيض', $revenue, 'جنيه', 2),
                $this->metric('egg_sale_pending', 'المبيعات الآجلة', $pending, 'جنيه', 2),
            ],
            'tables' => [
                ['title' => 'ترتيب مشتري البيض', 'headings' => ['المشتري', 'الكرتونات', 'القيمة', 'عدد العمليات'], 'rows' => $buyerRows],
                [
                    'title' => 'تفاصيل مبيعات البيض',
                    'headings' => ['التاريخ', 'المشتري', 'الكرتونات', 'سعر الكرتونة', 'الإجمالي', 'الحالة', 'الفاتورة'],
                    'rows' => $sales->map(fn (EggSale $sale): array => [
                        $sale->sold_on->toDateString(), $sale->buyer, $sale->cartons,
                        $this->format((float) $sale->price_per_carton, 2), $this->format($sale->totalAmount(), 2),
                        SalePaymentStatus::tryFrom($sale->payment_status)?->label() ?? $sale->payment_status, $sale->invoice_number,
                    ])->all(),
                ],
            ],
            'charts' => [$this->chart('قيمة مبيعات البيض', 'جنيه', 'green', $points)],
            'notes' => [$this->filterNote($search), $this->saleStatusNote($paymentStatus), $sales->isEmpty() ? 'لا توجد مبيعات بيض مطابقة خلال الفترة.' : 'تشمل القيمة العمليات المطابقة المسجلة.'],
        ];
    }

    /** @return array{metrics: list<Metric>, tables: list<Table>, charts: list<Chart>, notes: list<string>} */
    private function otherSalesData(Carbon $from, Carbon $to, string $groupBy, ?string $search, ?string $paymentStatus): array
    {
        $sales = OtherSale::query()->whereDate('sold_on', '>=', $from)->whereDate('sold_on', '<=', $to)
            ->oldest('sold_on')->oldest('id')->get();
        $sales = $sales->filter(fn (OtherSale $sale): bool => $this->matches($search, [
            $sale->buyer, $sale->productLabel(), $sale->invoice_number, $sale->notes,
        ]))->values();

        if ($paymentStatus !== null) {
            $sales = $sales->where('payment_status', SalePaymentStatus::from($paymentStatus))->values();
        }
        $revenue = round((float) $sales->sum(fn (OtherSale $sale): float => $sale->totalAmount()), 2);
        $pending = round((float) $sales->filter(fn (OtherSale $sale): bool => $sale->payment_status === SalePaymentStatus::Pending)
            ->sum(fn (OtherSale $sale): float => $sale->totalAmount()), 2);
        $points = $sales->groupBy(fn (OtherSale $sale): string => $this->groupDate($sale->sold_on, $groupBy)->toDateString())
            ->map(fn ($group, string $date): array => ['date' => Carbon::parse($date), 'value' => round((float) $group->sum(fn (OtherSale $sale): float => $sale->totalAmount()), 2)])
            ->values()->all();

        return [
            'metrics' => [
                $this->metric('other_sale_count', 'عدد عمليات البيع', $sales->count(), 'عملية'),
                $this->metric('other_sale_revenue', 'قيمة المبيعات الأخرى', $revenue, 'جنيه', 2),
                $this->metric('other_sale_pending', 'المبيعات الآجلة', $pending, 'جنيه', 2),
            ],
            'tables' => [[
                'title' => 'تفاصيل المبيعات الأخرى',
                'headings' => ['التاريخ', 'المشتري', 'المنتج', 'الكمية', 'الوحدة', 'سعر الوحدة', 'الإجمالي', 'الحالة', 'الفاتورة'],
                'rows' => $sales->map(fn (OtherSale $sale): array => [
                    $sale->sold_on->toDateString(), $sale->buyer, $sale->productLabel(),
                    $this->format((float) $sale->quantity, 3), $sale->unit->label(), $this->format((float) $sale->unit_price, 2),
                    $this->format($sale->totalAmount(), 2), $sale->payment_status->label(), $sale->invoice_number,
                ])->all(),
            ]],
            'charts' => [$this->chart('قيمة المبيعات الأخرى', 'جنيه', 'green', $points)],
            'notes' => [$this->filterNote($search), $this->saleStatusNote($paymentStatus), $sales->isEmpty() ? 'لا توجد مبيعات أخرى مطابقة خلال الفترة.' : 'تشمل القيمة العمليات المطابقة المسجلة.'],
        ];
    }

    /** @return array{metrics: list<Metric>, tables: list<Table>, charts: list<Chart>, notes: list<string>} */
    private function expensesData(Carbon $from, Carbon $to, string $groupBy, ?string $search, ?string $paymentMethod): array
    {
        $expenses = Expense::query()->whereDate('spent_on', '>=', $from)->whereDate('spent_on', '<=', $to)
            ->oldest('spent_on')->oldest('id')->get();
        $expenses = $expenses->filter(fn (Expense $expense): bool => $this->matches($search, [
            $expense->category->label(), $expense->payee, $expense->invoice_number, $expense->notes,
        ]))->values();

        if ($paymentMethod !== null) {
            $expenses = $expenses->where('payment_method', ExpensePaymentMethod::from($paymentMethod))->values();
        }
        $total = round((float) $expenses->sum('amount'), 2);
        $credit = round((float) $expenses->filter(fn (Expense $expense): bool => $expense->payment_method === ExpensePaymentMethod::Credit)->sum('amount'), 2);
        $points = $expenses->groupBy(fn (Expense $expense): string => $this->groupDate($expense->spent_on, $groupBy)->toDateString())
            ->map(fn ($group, string $date): array => ['date' => Carbon::parse($date), 'value' => round((float) $group->sum('amount'), 2)])
            ->values()->all();

        return [
            'metrics' => [
                $this->metric('expense_count', 'عدد المصروفات', $expenses->count(), 'عملية'),
                $this->metric('expense_total', 'إجمالي المصروفات', $total, 'جنيه', 2),
                $this->metric('expense_credit', 'المصروفات الآجلة', $credit, 'جنيه', 2),
            ],
            'tables' => [[
                'title' => 'تفاصيل المصروفات',
                'headings' => ['التاريخ', 'التصنيف', 'المبلغ', 'طريقة الدفع', 'جهة الدفع', 'الفاتورة'],
                'rows' => $expenses->map(fn (Expense $expense): array => [
                    $expense->spent_on->toDateString(), $expense->category->label(), $this->format((float) $expense->amount, 2),
                    $expense->payment_method->label(), $expense->payee, $expense->invoice_number,
                ])->all(),
            ]],
            'charts' => [$this->chart('المصروفات المسجلة', 'جنيه', 'orange', $points)],
            'notes' => [$this->filterNote($search), $this->paymentMethodNote($paymentMethod), $expenses->isEmpty() ? 'لا توجد مصروفات مطابقة خلال الفترة.' : 'تشمل النتائج المصروفات اليدوية والمصروفات المرتبطة بالعلف والسلف مرة واحدة.'],
        ];
    }

    /** @return array{metrics: list<Metric>, tables: list<Table>, charts: list<Chart>, notes: list<string>} */
    private function workerAdvancesData(Carbon $from, Carbon $to, string $groupBy, ?string $search, ?string $paymentMethod): array
    {
        $advances = WorkerAdvance::query()->whereDate('advanced_on', '>=', $from)->whereDate('advanced_on', '<=', $to)
            ->oldest('advanced_on')->oldest('id')->get();
        $advances = $advances->filter(fn (WorkerAdvance $advance): bool => $this->matches($search, [$advance->worker_name, $advance->notes]))->values();

        if ($paymentMethod !== null) {
            $advances = $advances->where('payment_method', ExpensePaymentMethod::from($paymentMethod))->values();
        }
        $total = round((float) $advances->sum('amount'), 2);
        $points = $advances->groupBy(fn (WorkerAdvance $advance): string => $this->groupDate($advance->advanced_on, $groupBy)->toDateString())
            ->map(fn ($group, string $date): array => ['date' => Carbon::parse($date), 'value' => round((float) $group->sum('amount'), 2)])
            ->values()->all();

        return [
            'metrics' => [
                $this->metric('worker_advance_count', 'عدد السلف', $advances->count(), 'سلفة'),
                $this->metric('worker_advance_total', 'إجمالي السلف', $total, 'جنيه', 2),
            ],
            'tables' => [[
                'title' => 'تفاصيل سلف العمال',
                'headings' => ['التاريخ', 'العامل', 'المبلغ', 'طريقة الدفع'],
                'rows' => $advances->map(fn (WorkerAdvance $advance): array => [
                    $advance->advanced_on->toDateString(), $advance->worker_name, $this->format((float) $advance->amount, 2), $advance->payment_method->label(),
                ])->all(),
            ]],
            'charts' => [$this->chart('سلف العمال', 'جنيه', 'orange', $points)],
            'notes' => [$this->filterNote($search), $this->paymentMethodNote($paymentMethod), $advances->isEmpty() ? 'لا توجد سلف مطابقة خلال الفترة.' : 'كل سلفة تدخل ضمن المصروفات المالية مرة واحدة.'],
        ];
    }

    /** @return array{metrics: list<Metric>, tables: list<Table>, charts: list<Chart>, notes: list<string>} */
    private function inventoryData(Carbon $asOf): array
    {
        $deliveredFeed = (float) FeedDelivery::query()->whereDate('delivered_on', '<=', $asOf)->sum('weight_tons') * FeedDelivery::KILOGRAMS_PER_TON;
        $dailyRecords = DailyRecord::query()->whereDate('recorded_on', '<=', $asOf);
        $usedFeed = (float) (clone $dailyRecords)->sum('feed');
        $remainingFeed = $deliveredFeed - $usedFeed;
        $recentFeed = (clone $dailyRecords)->where('feed', '>', 0)->latest('recorded_on')->limit(7)->pluck('feed');
        $averageFeed = $recentFeed->isEmpty() ? 0.0 : round((float) $recentFeed->average(), 1);
        $feedDays = $remainingFeed > 0 && $averageFeed > 0 ? round($remainingFeed / $averageFeed, 1) : null;
        $producedEggs = (int) (clone $dailyRecords)->sum('production');
        $soldEggs = (int) EggSale::query()->whereDate('sold_on', '<=', $asOf)->sum('cartons') * EggSale::EGGS_PER_CARTON;

        return [
            'metrics' => [
                $this->metric('feed_stock', 'رصيد العلف', round($remainingFeed, 1), 'كجم', 1),
                $this->metric('feed_stock_sacks', 'رصيد شكائر 50 كجم', round($remainingFeed / FeedDelivery::SACK_WEIGHT_KILOGRAMS, 1), 'شكارة', 1),
                $this->metric('feed_days_remaining', 'أيام العلف المتوقعة', $feedDays, 'يوم', 1),
                $this->metric('average_feed', 'متوسط الاستهلاك الحديث', $averageFeed, 'كجم/يوم', 1),
                $this->metric('egg_stock', 'رصيد البيض', $producedEggs - $soldEggs, 'بيضة'),
                $this->metric('egg_stock_cartons', 'رصيد كرتونات البيض', round(($producedEggs - $soldEggs) / EggSale::EGGS_PER_CARTON, 1), 'كرتونة', 1),
            ],
            'tables' => [],
            'charts' => [],
            'notes' => [
                'المخزون محسوب حتى '.$asOf->toDateString().' من إجمالي الوارد والاستهلاك أو الإنتاج والمبيعات المسجلة.',
                'أيام العلف المتوقعة تعتمد على متوسط آخر 7 أيام مسجلة باستهلاك أكبر من صفر.',
            ],
        ];
    }

    /** @param list<string|null> $values */
    private function matches(?string $search, array $values): bool
    {
        $search = is_string($search) ? trim($search) : '';

        if ($search === '') {
            return true;
        }

        $needle = Str::lower($search);

        return collect($values)->contains(fn (?string $value): bool => $value !== null && str_contains(Str::lower($value), $needle));
    }

    private function filterNote(?string $search): string
    {
        return filled($search) ? 'تم البحث عن العبارة: '.trim($search).'.' : 'يعرض التقرير كل السجلات خلال الفترة المطلوبة.';
    }

    private function paymentMethodNote(?string $paymentMethod): string
    {
        return $paymentMethod === null
            ? 'يعرض التقرير كل طرق الدفع.'
            : 'طريقة الدفع المطلوبة: '.ExpensePaymentMethod::from($paymentMethod)->label().'.';
    }

    private function saleStatusNote(?string $paymentStatus): string
    {
        return $paymentStatus === null
            ? 'يعرض التقرير المبيعات المدفوعة والآجلة.'
            : 'حالة الدفع المطلوبة: '.SalePaymentStatus::from($paymentStatus)->label().'.';
    }

    /** @return array{metrics: list<Metric>, tables: list<Table>, charts: list<Chart>, notes: list<string>} */
    private function financialData(Carbon $from, Carbon $to, string $groupBy): array
    {
        $eggSales = EggSale::query()->whereDate('sold_on', '>=', $from)->whereDate('sold_on', '<=', $to)
            ->selectRaw('DATE(sold_on) AS date, SUM(cartons * price_per_carton) AS revenue')
            ->groupByRaw('DATE(sold_on)')->toBase()->get();
        $otherSales = OtherSale::query()->whereDate('sold_on', '>=', $from)->whereDate('sold_on', '<=', $to)
            ->selectRaw('DATE(sold_on) AS date, SUM(quantity * unit_price) AS revenue')
            ->groupByRaw('DATE(sold_on)')->toBase()->get();
        $expenses = Expense::query()->whereDate('spent_on', '>=', $from)->whereDate('spent_on', '<=', $to)
            ->selectRaw('DATE(spent_on) AS date, SUM(amount) AS expense')
            ->groupByRaw('DATE(spent_on)')->toBase()->get();
        $groups = $eggSales->concat($otherSales)->concat($expenses)
            ->groupBy(fn (object $row): string => $this->groupDate(Carbon::parse($row->date), $groupBy)->toDateString())
            ->sortKeys();
        $revenue = round((float) $eggSales->sum('revenue') + (float) $otherSales->sum('revenue'), 2);
        $expense = round((float) $expenses->sum('expense'), 2);
        $rows = [];
        $revenuePoints = [];
        $expensePoints = [];

        foreach ($groups as $date => $group) {
            $groupRevenue = round((float) $group->sum('revenue'), 2);
            $groupExpense = round((float) $group->sum('expense'), 2);
            $rows[] = [$date, $this->format($groupRevenue, 2), $this->format($groupExpense, 2), $this->format($groupRevenue - $groupExpense, 2)];
            $revenuePoints[] = ['date' => Carbon::parse($date), 'value' => $groupRevenue];
            $expensePoints[] = ['date' => Carbon::parse($date), 'value' => $groupExpense];
        }

        return [
            'metrics' => [
                $this->metric('revenue', 'إجمالي الإيرادات', $revenue, 'جنيه', 2),
                $this->metric('expenses', 'إجمالي المصروفات', $expense, 'جنيه', 2),
                $this->metric('net_profit', 'صافي الربح المسجل', round($revenue - $expense, 2), 'جنيه', 2),
            ],
            'tables' => [['title' => 'تفاصيل المالية', 'headings' => ['بداية الفترة', 'الإيرادات (جنيه)', 'المصروفات (جنيه)', 'الصافي (جنيه)'], 'rows' => $rows]],
            'charts' => [$this->chart('الإيرادات المسجلة', 'جنيه', 'green', $revenuePoints), $this->chart('المصروفات المسجلة', 'جنيه', 'orange', $expensePoints)],
            'notes' => [
                'صافي الربح المسجل = مبيعات البيض + المبيعات الأخرى − المصروفات، ويشمل الآجل وفق صفحة المالية؛ ليس رصيدًا نقديًا أو ربحًا محاسبيًا شاملًا لتقييم المخزون والإهلاك.',
                'توريدات العلف وسلف العمال تدخل من المصروفات المرتبطة بها مرة واحدة فقط.',
                $groups->isEmpty() ? 'لا توجد معاملات مالية مسجلة خلال هذه الفترة.' : 'تُحتسب المعاملات داخل الفترة المطلوبة فقط؛ الأيام الخالية من المعاملات لا تظهر في الجدول.',
            ],
        ];
    }

    /**
     * @param  Plan  $plan
     * @param  Report  $report
     */
    private function reportAnswer(array $plan, array $report): string
    {
        if (in_array($plan['report'], ['feed_purchases', 'egg_sales'], true)
            && in_array($plan['result_mode'], ['list', 'ranking'], true)) {
            $rows = $report['tables'][0]['rows'] ?? [];

            if ($rows === []) {
                return $plan['report'] === 'feed_purchases'
                    ? 'لا توجد أسماء موردي علف مطابقة خلال الفترة المطلوبة.'
                    : 'لا توجد مبيعات بيض مطابقة خلال الفترة المطلوبة.';
            }

            if ($plan['result_mode'] === 'list') {
                $names = array_values(array_unique(array_column($rows, 0)));

                return ($plan['report'] === 'feed_purchases' ? 'أسماء موردي العلف: ' : 'أسماء مشتري البيض: ')
                    .implode('، ', $names).'.';
            }

            $top = $plan['ranking_direction'] === 'asc' ? $rows[array_key_last($rows)] : $rows[0];

            return $plan['report'] === 'feed_purchases'
                ? ($plan['ranking_direction'] === 'asc'
                    ? "أقل مورد علف هو {$top[0]} بعدد {$top[1]} نقلة ووزن {$top[2]} طن."
                    : "أكثر مورد علف هو {$top[0]} بعدد {$top[1]} نقلة ووزن {$top[2]} طن.")
                : ($plan['ranking_direction'] === 'asc'
                    ? "أقل مشتري للبيض هو {$top[0]} بإجمالي {$top[1]} كرتونة وقيمة {$top[2]} جنيه."
                    : "أكثر مشتري للبيض هو {$top[0]} بإجمالي {$top[1]} كرتونة وقيمة {$top[2]} جنيه.");
        }

        return implode('، ', array_map(
            fn (array $metric): string => $metric['label'].': '.$this->format($metric['value'], $metric['precision']).' '.$metric['unit'],
            array_slice($report['metrics'], 0, 3),
        )).'.';
    }

    private function groupDate(Carbon $date, string $groupBy): Carbon
    {
        return match ($groupBy) {
            'week' => $date->copy()->startOfWeek(Carbon::MONDAY),
            'month' => $date->copy()->startOfMonth(),
            default => $date->copy()->startOfDay(),
        };
    }

    private function ratio(?int $numerator, ?int $denominator, int $scale): ?float
    {
        return $numerator === null || $denominator === null || $denominator <= 0 ? null : $numerator / $denominator * $scale;
    }

    /** @return Metric */
    private function metric(string $key, string $label, int|float|null $value, string $unit, int $precision = 0): array
    {
        return compact('key', 'label', 'value', 'unit', 'precision');
    }

    private function format(int|float|null $value, int $precision): string
    {
        return $value === null ? '—' : number_format($value, $precision);
    }

    /**
     * @param  list<array{date: Carbon, value: int|float}>  $points
     * @return Chart
     */
    private function chart(string $title, string $unit, string $tone, array $points): array
    {
        $maximum = max(1, (float) collect($points)->max('value'));

        return [
            'title' => $title, 'unit' => $unit, 'tone' => $tone,
            'points' => array_map(fn (array $point): array => $point + [
                'height' => $point['value'] == 0 ? 0 : max(1, (int) round($point['value'] / $maximum * 100)),
            ], $points),
        ];
    }
}
