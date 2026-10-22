<x-app-shell title="التقارير" active="more">
    <header class="ios-page-header">
        <p class="ios-eyebrow">تحليل أداء القطيع</p>
        <h1 class="ios-large-title">التقارير</h1>
        <p class="text-sm text-slate-500">تابع النافق والإنتاج واستهلاك العلف خلال الفترة التي تختارها.</p>
        <div class="flex flex-wrap gap-3 text-sm font-semibold text-ios-blue">
            <a href="{{ route('break-even.index') }}">توقع سداد المديونية</a>
            <a href="{{ route('smart-assistant.index') }}">✦ اسأل المساعد الذكي</a>
        </div>
    </header>

    <form class="ios-card flex flex-col gap-3 p-4" method="GET" action="{{ route('reports.index') }}">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="ios-label" for="report_from">من تاريخ</label>
                <input class="mt-1.5 w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5 outline-none focus:ring-2 focus:ring-ios-blue @error('from') ring-2 ring-ios-red @enderror" id="report_from" name="from" type="date" dir="ltr" value="{{ old('from', $from->toDateString()) }}" required>
                @error('from') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="ios-label" for="report_to">إلى تاريخ</label>
                <input class="mt-1.5 w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5 outline-none focus:ring-2 focus:ring-ios-blue @error('to') ring-2 ring-ios-red @enderror" id="report_to" name="to" type="date" dir="ltr" value="{{ old('to', $to->toDateString()) }}" required>
                @error('to') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <button class="rounded-xl bg-ios-blue px-4 py-2.5 text-sm font-semibold text-white" type="submit">عرض التقارير</button>
    </form>

    <div class="flex items-center justify-between px-1 text-xs text-slate-400">
        <span>{{ $from->translatedFormat('j F Y') }} — {{ $to->translatedFormat('j F Y') }}</span>
        <span>{{ $recordedDays }} يوم مسجل</span>
    </div>

    <nav class="grid grid-cols-3 gap-2" aria-label="اختيار التقرير" role="tablist" data-report-tabs>
        <button class="flex min-h-20 flex-col items-center justify-center gap-2 rounded-2xl bg-slate-200/70 px-2 py-3 text-xs font-semibold text-slate-500 transition aria-selected:bg-white aria-selected:text-ios-blue aria-selected:shadow-sm aria-selected:ring-1 aria-selected:ring-slate-900/5" id="mortality-report-tab" type="button" role="tab" aria-controls="mortality-report" aria-selected="true" data-report-tab="mortality-report">
            <span class="flex size-9 items-center justify-center rounded-xl bg-red-50 text-base text-ios-red">↓</span>
            <span>تقرير النافق</span>
        </button>
        <button class="flex min-h-20 flex-col items-center justify-center gap-2 rounded-2xl bg-slate-200/70 px-2 py-3 text-xs font-semibold text-slate-500 transition aria-selected:bg-white aria-selected:text-ios-blue aria-selected:shadow-sm aria-selected:ring-1 aria-selected:ring-slate-900/5" id="production-report-tab" type="button" role="tab" aria-controls="production-report" aria-selected="false" data-report-tab="production-report">
            <span class="flex size-9 items-center justify-center rounded-xl bg-green-50 text-base text-ios-green">●</span>
            <span>تقرير الإنتاج</span>
        </button>
        <button class="flex min-h-20 flex-col items-center justify-center gap-2 rounded-2xl bg-slate-200/70 px-2 py-3 text-xs font-semibold text-slate-500 transition aria-selected:bg-white aria-selected:text-ios-blue aria-selected:shadow-sm aria-selected:ring-1 aria-selected:ring-slate-900/5" id="feed-report-tab" type="button" role="tab" aria-controls="feed-report" aria-selected="false" data-report-tab="feed-report">
            <span class="flex size-9 items-center justify-center rounded-xl bg-orange-50 text-base text-ios-orange">▰</span>
            <span>استهلاك العلف</span>
        </button>
    </nav>

    <x-report-chart
        id="mortality-report"
        title="تقرير النافق"
        description="عدد الطيور النافقة في كل يوم مسجل."
        :points="$mortalityReport['points']"
        unit="طائر"
        tone="red"
        role="tabpanel"
        aria-labelledby="mortality-report-tab"
        data-report-panel
        :metrics="[
            ['label' => 'إجمالي النافق', 'value' => number_format($mortalityReport['total']), 'suffix' => 'طائر'],
            ['label' => 'المتوسط اليومي', 'value' => number_format($mortalityReport['average'], 1), 'suffix' => 'طائر'],
            ['label' => 'أعلى يوم', 'value' => number_format($mortalityReport['maximum']), 'suffix' => 'طائر'],
        ]"
    />

    <x-report-chart
        id="production-report"
        title="تقرير الإنتاج"
        description="إنتاج البيض اليومي خلال الفترة المحددة."
        :points="$productionReport['points']"
        unit="بيضة"
        tone="green"
        role="tabpanel"
        aria-labelledby="production-report-tab"
        data-report-panel
        hidden
        :metrics="[
            ['label' => 'إجمالي الإنتاج', 'value' => number_format($productionReport['total']), 'suffix' => 'بيضة'],
            ['label' => 'المتوسط اليومي', 'value' => number_format($productionReport['average'], 1), 'suffix' => 'بيضة'],
            ['label' => 'أعلى إنتاج', 'value' => number_format($productionReport['maximum']), 'suffix' => 'بيضة'],
        ]"
    />

    <x-report-chart
        id="feed-report"
        title="تقرير استهلاك العلف"
        description="كمية العلف المستهلكة يوميًا بالكيلوجرام."
        :points="$feedReport['points']"
        unit="كجم"
        tone="orange"
        role="tabpanel"
        aria-labelledby="feed-report-tab"
        data-report-panel
        hidden
        :metrics="[
            ['label' => 'إجمالي الاستهلاك', 'value' => number_format($feedReport['total']), 'suffix' => 'كجم'],
            ['label' => 'ما يعادل', 'value' => number_format($feedReport['total'] / \App\Models\DailyRecord::FEED_SACK_WEIGHT_KG, 1), 'suffix' => 'شيكارة'],
            ['label' => 'المتوسط اليومي', 'value' => number_format($feedReport['average'], 1), 'suffix' => 'كجم'],
        ]"
    />
</x-app-shell>
