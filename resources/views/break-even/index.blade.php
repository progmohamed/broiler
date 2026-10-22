<x-app-shell title="توقع سداد المديونية" active="more">
    <header class="ios-page-header gap-2">
        <a class="text-sm font-semibold text-ios-blue" href="{{ route('reports.index') }}">التقارير ←</a>
        <p class="ios-eyebrow">محاكاة السيولة</p>
        <h1 class="ios-large-title">متى تصبح المديونية صفرًا؟</h1>
        <p class="text-sm leading-6 text-slate-500">توقع يومي يجمع تحصيل مبيعات البيض مع شراء العلف والمصروفات حتى موعد السداد.</p>
    </header>

    <form class="ios-card flex flex-col gap-4 p-4" method="GET" action="{{ route('break-even.index') }}">
        <div class="grid grid-cols-2 gap-3">
            <div><label class="ios-label" for="current_debt">الدين الحالي</label><input class="ios-input mt-1.5 @error('current_debt') ios-input-error @enderror" id="current_debt" name="current_debt" type="number" min="0" step="0.01" value="{{ old('current_debt', $inputs['current_debt']) }}">@error('current_debt')<p class="ios-error">{{ $message }}</p>@enderror</div>
            <div><label class="ios-label" for="egg_inventory_cartons">مخزون البيض <span class="font-normal text-slate-400">كرتونة</span></label><input class="ios-input mt-1.5" id="egg_inventory_cartons" name="egg_inventory_cartons" type="number" min="0" step="0.1" value="{{ old('egg_inventory_cartons', $inputs['egg_inventory_cartons']) }}"></div>
            <div><label class="ios-label" for="average_sale_price">متوسط سعر الكرتونة</label><input class="ios-input mt-1.5" id="average_sale_price" name="average_sale_price" type="number" min="0" step="0.01" value="{{ old('average_sale_price', $inputs['average_sale_price']) }}"></div>
            <div><label class="ios-label" for="daily_production_cartons">الإنتاج اليومي <span class="font-normal text-slate-400">كرتونة</span></label><input class="ios-input mt-1.5" id="daily_production_cartons" name="daily_production_cartons" type="number" min="0" step="0.1" value="{{ old('daily_production_cartons', $inputs['daily_production_cartons']) }}"></div>
        </div>

        <div class="rounded-2xl bg-orange-50 p-3 ring-1 ring-orange-100">
            <p class="mb-3 text-xs font-bold text-orange-700">العلف والنقلة القادمة</p>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="ios-label" for="feed_stock_days">المخزون يكفي <span class="font-normal text-slate-400">يوم</span></label><input class="ios-input mt-1.5" id="feed_stock_days" name="feed_stock_days" type="number" min="0" step="0.1" value="{{ old('feed_stock_days', $inputs['feed_stock_days']) }}"></div>
                <div><label class="ios-label" for="daily_feed_kilograms">الاستهلاك اليومي <span class="font-normal text-slate-400">كجم</span></label><input class="ios-input mt-1.5" id="daily_feed_kilograms" name="daily_feed_kilograms" type="number" min="0" step="0.1" value="{{ old('daily_feed_kilograms', $inputs['daily_feed_kilograms']) }}"></div>
                <div><label class="ios-label" for="feed_delivery_tons">كمية النقلة <span class="font-normal text-slate-400">طن</span></label><input class="ios-input mt-1.5" id="feed_delivery_tons" name="feed_delivery_tons" type="number" min="0" step="0.001" value="{{ old('feed_delivery_tons', $inputs['feed_delivery_tons']) }}"></div>
                <div><label class="ios-label" for="feed_delivery_cost">تكلفة النقلة</label><input class="ios-input mt-1.5" id="feed_delivery_cost" name="feed_delivery_cost" type="number" min="0" step="0.01" value="{{ old('feed_delivery_cost', $inputs['feed_delivery_cost']) }}"></div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div><label class="ios-label" for="daily_expenses">مصروفات يومية بخلاف العلف</label><input class="ios-input mt-1.5" id="daily_expenses" name="daily_expenses" type="number" min="0" step="0.01" value="{{ old('daily_expenses', $inputs['daily_expenses']) }}"></div>
            <div><label class="ios-label" for="collection_percentage">نسبة التحصيل النقدي</label><div class="relative mt-1.5"><input class="ios-input ps-9" id="collection_percentage" name="collection_percentage" type="number" min="0" max="100" step="0.1" value="{{ old('collection_percentage', $inputs['collection_percentage']) }}"><span class="absolute start-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">%</span></div>@error('collection_percentage')<p class="ios-error">{{ $message }}</p>@enderror</div>
        </div>
        @if ($errors->any() && ! $errors->has('current_debt') && ! $errors->has('collection_percentage'))<p class="ios-error">{{ $errors->first() }}</p>@endif
        <button class="ios-primary-button" type="submit">احسب موعد السداد</button>
        <p class="text-center text-[0.65rem] text-slate-400">القيم معبأة تلقائيًا من آخر 30 يومًا ويمكن تعديلها.</p>
    </form>

    @if ($report['payoffDate'] !== null)
        <section class="ios-card overflow-hidden">
            <div class="bg-linear-to-l from-emerald-600 to-emerald-500 p-5 text-white">
                <p class="text-xs text-emerald-100">التاريخ المتوقع لوصول الدين إلى صفر</p>
                <h2 class="mt-1 text-3xl font-bold">{{ \Illuminate\Support\Carbon::parse($report['payoffDate'])->translatedFormat('j F Y') }}</h2>
                <p class="mt-1 text-sm text-emerald-100">بعد {{ number_format($report['daysToPayoff']) }} يوم · بناءً على استمرار الافتراضات الحالية</p>
            </div>
        </section>
    @else
        <div class="rounded-2xl bg-red-50 px-4 py-4 text-sm leading-6 text-red-800 ring-1 ring-red-200">لن تصل المديونية إلى صفر خلال عامين بهذه الأرقام. المتبقي المتوقع {{ number_format($report['remainingDebt'], 2) }}. عدّل السعر أو الإنتاج أو المصروفات أو نسبة التحصيل.</div>
    @endif

    <section class="grid grid-cols-2 gap-2">
        <div class="ios-card p-4"><p class="text-xs text-slate-400">العلف الحالي ينتهي</p><p class="mt-1 font-bold text-ios-orange">{{ \Illuminate\Support\Carbon::parse($report['nextFeedDepletionDate'])->translatedFormat('j F Y') }}</p><p class="text-xs text-slate-400">بعد {{ number_format($inputs['feed_stock_days'], 1) }} يوم</p></div>
        <div class="ios-card p-4"><p class="text-xs text-slate-400">النقلة القادمة</p><p class="mt-1 text-xl font-bold text-ios-red">{{ number_format($inputs['feed_delivery_cost'], 2) }}</p><p class="text-xs text-slate-400">تكفي {{ number_format($report['deliveryCoverageDays'], 1) }} يوم</p></div>
        <div class="ios-card p-4"><p class="text-xs text-slate-400">تحصيل يومي متوقع</p><p class="mt-1 text-xl font-bold text-ios-green">{{ number_format($report['dailyCollections'], 2) }}</p><p class="text-xs text-slate-400">من الإنتاج اليومي</p></div>
        <div class="ios-card p-4"><p class="text-xs text-slate-400">قيمة مخزون البيض</p><p class="mt-1 text-xl font-bold text-ios-blue">{{ number_format($report['openingInventoryValue'], 2) }}</p><p class="text-xs text-slate-400">تحصيل أول يوم</p></div>
    </section>

    @if ($report['firstFeedPurchaseDate'] !== null)
        <section class="rounded-2xl bg-orange-50 p-4 ring-1 ring-orange-100">
            <p class="text-xs font-semibold text-orange-700">أول نقلة علف متوقعة</p>
            <p class="mt-1 text-lg font-bold text-slate-900">{{ \Illuminate\Support\Carbon::parse($report['firstFeedPurchaseDate'])->translatedFormat('j F Y') }}</p>
            <p class="mt-1 text-sm text-slate-600">بعد شراء النقلة والمصروف والتحصيل في هذا اليوم، يصبح الدين المتوقع <strong>{{ number_format($report['debtAfterFirstFeedPurchase'], 2) }}</strong>.</p>
        </section>
    @endif

    <section class="flex flex-col gap-2">
        <h2 class="ios-section-title">ملخص فترة التوقع</h2>
        <dl class="ios-card divide-y divide-slate-100 px-4 text-sm">
            <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">إجمالي التحصيلات</dt><dd class="font-bold text-ios-green">{{ number_format($report['totalCollections'], 2) }}</dd></div>
            <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">نقلات العلف</dt><dd class="font-semibold">{{ number_format($report['feedPurchases']) }} نقلة · {{ number_format($report['totalFeedPurchases'], 2) }}</dd></div>
            <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">المصروفات الأخرى</dt><dd class="font-semibold">{{ number_format($report['totalOperatingExpenses'], 2) }}</dd></div>
            <div class="flex justify-between gap-3 py-3"><dt class="text-slate-500">صافي النقد اليومي المتوسط</dt><dd class="font-bold {{ $report['averageDailyNetCash'] >= 0 ? 'text-ios-green' : 'text-ios-red' }}">{{ number_format($report['averageDailyNetCash'], 2) }}</dd></div>
        </dl>
    </section>

    <section class="flex flex-col gap-2">
        <h2 class="ios-section-title">حركة المديونية</h2>
        <div class="ios-card overflow-x-auto">
            <table class="min-w-full text-right text-xs">
                <thead class="bg-slate-50 text-slate-400"><tr><th class="p-3">اليوم</th><th class="p-3">تحصيل</th><th class="p-3">علف</th><th class="p-3">مصروف</th><th class="p-3">الدين</th></tr></thead>
                <tbody class="divide-y divide-slate-100">@foreach ($report['rows'] as $row)<tr class="{{ $row['isPayoffDay'] ? 'bg-emerald-50' : ($row['isFeedPurchaseDay'] ? 'bg-orange-50' : '') }}"><td class="whitespace-nowrap p-3 font-semibold">{{ \Illuminate\Support\Carbon::parse($row['date'])->translatedFormat('j M') }}@if($row['isFeedPurchaseDay'])<span class="block text-[0.6rem] text-orange-600">شراء علف</span>@endif</td><td class="p-3 text-ios-green">{{ number_format($row['collections']) }}</td><td class="p-3 text-ios-orange">{{ $row['feedPurchase'] > 0 ? number_format($row['feedPurchase']) : '—' }}</td><td class="p-3">{{ number_format($row['operatingExpenses']) }}</td><td class="p-3 font-bold {{ $row['closingDebt'] <= 0 ? 'text-ios-green' : 'text-ios-red' }}">{{ number_format($row['closingDebt']) }}</td></tr>@endforeach</tbody>
            </table>
        </div>
        <p class="px-1 text-[0.65rem] text-slate-400">يعرض الجدول أول 14 يومًا، ثم أيام شراء العلف ويوم السداد.</p>
    </section>

    <section class="rounded-2xl bg-slate-100 px-4 py-4 text-xs leading-6 text-slate-500">
        <h2 class="font-semibold text-slate-700">افتراضات التوقع</h2>
        <ul class="mt-1 list-disc space-y-1 pe-4">@foreach ($report['notes'] as $note)<li>{{ $note }}</li>@endforeach</ul>
    </section>
</x-app-shell>
