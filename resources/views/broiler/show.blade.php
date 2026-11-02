<x-broiler-shell :title="$cycle->name">
<div data-cycle-layout data-initial-tab="{{ $activeTab }}" data-restore-tab="{{ $errors->any() || session('cycle_tab') ? 'true' : 'false' }}" class="flex flex-col gap-5 pb-28">
<header class="flex flex-wrap items-start justify-between gap-4">
    <div class="ios-page-header"><p class="ios-eyebrow">{{ $cycle->house->name }} · {{ $cycle->closed_on ? 'دورة مغلقة' : 'دورة جارية' }}</p><h1 class="ios-large-title">{{ $cycle->name }}</h1><p class="text-sm text-slate-500">من {{ $cycle->started_on->format('Y-m-d') }} · {{ number_format($cycle->initial_birds) }} كتكوت · عمر الدورة {{ (int) $cycle->started_on->diffInDays($cycle->closed_on ?? today()) }} يوم</p></div>
    @if(!$cycle->closed_on)<details class="max-w-full"><summary class="cursor-pointer rounded-xl border border-slate-200 bg-white px-4 py-3 font-semibold">إدارة الدورة</summary><div class="mt-3 max-w-sm">@include('broiler.close-cycle')</div></details>@endif
</header>
<section class="ios-metrics grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="مؤشرات الدورة">
@foreach(['remaining' => 'الطيور المتبقية', 'deaths' => 'النفوق', 'feed_stock' => 'رصيد المخزن (طن)', 'profit' => 'الربح حسب المسجل'] as $key => $label)
    <div class="ios-card p-4"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-xl sm:text-2xl font-bold {{ $key === 'profit' && $summary[$key] < 0 ? 'text-red-700' : 'text-emerald-900' }}">{{ number_format($key === 'feed_stock' ? $summary[$key] / 1000 : $summary[$key], in_array($key, ['remaining', 'deaths']) ? 0 : ($key === 'feed_stock' ? 3 : 2)) }}</p>@if($key === 'deaths')<p class="mt-1 text-xs text-slate-500">نسبة النفوق {{ number_format($summary['mortality'], 2) }}%</p>@endif</div>
@endforeach
</section>
<noscript><style>[data-cycle-panel][hidden]{display:block!important}</style></noscript>
<section id="cycle-panel-daily" role="tabpanel" aria-labelledby="cycle-tab-daily" data-cycle-panel="daily" @if($activeTab !== 'daily') hidden @endif>
<div class="flex flex-col gap-4">
<h2 class="text-lg font-bold">متابعة اليوم</h2>
@if(!$cycle->closed_on)<div class="grid items-start gap-4 md:grid-cols-2">@include('broiler.daily-form') @include('broiler.weight-form')</div>@endif
<div class="ios-card grid gap-4 p-5 sm:grid-cols-2"><div><p class="ios-label">متوسط وزن آخر عينة (كجم/طائر)</p><p class="text-xl sm:text-2xl font-bold">{{ $summary['average_weight'] === null ? 'غير متاح' : number_format($summary['average_weight'], 3) }}</p><p class="text-xs text-slate-500">{{ $summary['weight_date'] }}</p></div><div><p class="ios-label">معامل التحويل الغذائي FCR</p><p class="text-xl sm:text-2xl font-bold">{{ $summary['fcr'] === null ? 'غير متاح' : number_format($summary['fcr'], 3) }}</p></div></div>
@include('broiler.daily-history')
</div></section>
<section id="cycle-panel-feed" role="tabpanel" aria-labelledby="cycle-tab-feed" data-cycle-panel="feed" @if($activeTab !== 'feed') hidden @endif><div class="flex flex-col gap-4">
<div><h2 class="text-lg font-bold">العلف والمخزون</h2><p class="text-sm text-slate-500">سجّل النقلات وتابع الوارد والاستهلاك والرصيد المتاح.</p></div>
<div class="grid items-start gap-4 md:grid-cols-2"><div class="ios-card p-5"><h3 class="mb-4 font-bold">مخزن العلف</h3><dl class="flex flex-col gap-4"><div class="flex justify-between gap-3"><dt>العلف الوارد (طن)</dt><dd class="font-bold">{{ number_format($summary['feed_received'] / 1000, 3) }}</dd></div><div class="flex justify-between gap-3"><dt>العلف المستهلك (شيكارة)</dt><dd class="font-bold">{{ number_format($summary['feed'] / \App\Models\BroilerEntry::SACK_WEIGHT_KG, 3) }}</dd></div><div class="flex justify-between gap-3"><dt>رصيد المخزن (طن)</dt><dd class="font-bold">{{ number_format($summary['feed_stock'] / 1000, 3) }}</dd></div></dl></div>@if(!$cycle->closed_on) @include('broiler.feed-delivery-form') @endif</div>
@include('broiler.cost-history', ['costEntries' => $feedEntries, 'feedOnly' => true])
</div></section>
<section id="cycle-panel-costs" role="tabpanel" aria-labelledby="cycle-tab-costs" data-cycle-panel="costs" @if($activeTab !== 'costs') hidden @endif>
<div class="flex flex-col gap-4">
<div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-bold">المصروفات والأدوية</h2><p class="text-sm text-slate-500">إجمالي التكاليف {{ number_format($summary['costs'], 2) }}</p></div>
@if(!$cycle->closed_on)@include('broiler.expense-form')@endif
<div class="grid items-start gap-4 lg:grid-cols-2">
<section class="ios-card p-5" aria-label="مصروفات الدورة"><h3 class="mb-4 font-bold">مصروفات الدورة حسب التصنيف</h3><table class="w-full text-right text-sm"><tbody class="divide-y divide-slate-100"><tr><td class="py-3">كتاكيت @include('broiler.chick-cost-form')</td><td class="py-3 text-left">{{ number_format($cycle->chick_cost, 2) }}</td></tr>
@foreach(['feed' => 'علف (النقلات والنقل)', ...\App\Models\BroilerEntry::EXPENSE_CATEGORIES] as $key => $label)
@if($summary['expenses'][$key] != 0)<tr><td class="py-3">{{ $label }}</td><td class="py-3 text-left">{{ number_format($summary['expenses'][$key], 2) }}</td></tr>@endif
@endforeach
<tr><td class="py-3">نصيب الدورة من مصروفات العنبر</td><td class="py-3 text-left">{{ number_format($summary['house_costs'], 2) }}</td></tr></tbody></table><a class="mt-3 inline-block text-sm font-semibold text-ios-blue" href="{{ route('broiler.houses.expenses', $cycle->house) }}">الإيجار والمصروفات التأسيسية ←</a></section>

</div>
@include('broiler.cost-history')
</div></section>
<section id="cycle-panel-sales" role="tabpanel" aria-labelledby="cycle-tab-sales" data-cycle-panel="sales" @if($activeTab !== 'sales') hidden @endif><div class="flex flex-col gap-4">
<div><h2 class="text-lg font-bold">المبيعات والتحصيل</h2><p class="text-sm text-slate-500">سجّل بيع الطيور وراجع المبالغ المحصلة والمستحقة.</p></div>
<div class="ios-card ios-metrics grid grid-cols-3 gap-3 p-5">@foreach(['sold' => 'الطيور المباعة', 'revenue' => 'إجمالي المبيعات', 'receivable' => 'المستحقات'] as $key => $label)<div><p class="ios-label">{{ $label }}</p><p class="text-lg font-bold">{{ number_format($summary[$key], $key === 'sold' ? 0 : 2) }}</p></div>@endforeach</div>
@if(!$cycle->closed_on) @include('broiler.sale-form') @endif
@include('broiler.entry-history', ['entries' => $saleEntries, 'historyTitle' => 'سجل المبيعات'])
</div></section>
<section id="cycle-panel-reports" role="tabpanel" aria-labelledby="cycle-tab-reports" data-cycle-panel="reports" @if($activeTab !== 'reports') hidden @endif><div class="flex flex-col gap-4">
<h2 class="text-lg font-bold">تقارير الدورة والنتائج المالية</h2>
<div class="ios-card ios-metrics grid grid-cols-2 gap-4 p-5 lg:grid-cols-4">@foreach(['sold' => 'الطيور المباعة', 'revenue' => 'إجمالي المبيعات', 'costs' => 'إجمالي التكاليف', 'receivable' => 'المبالغ المستحقة'] as $key => $label)<div><p class="ios-label">{{ $label }}</p><p class="text-xl font-bold">{{ number_format($summary[$key], $key === 'sold' ? 0 : 2) }}</p></div>@endforeach</div>
@include('broiler.chick-cost-report')
@include('broiler.entry-history')
</div></section>
<section id="cycle-panel-vaccinations" role="tabpanel" aria-labelledby="cycle-tab-vaccinations" data-cycle-panel="vaccinations" @if($activeTab !== 'vaccinations') hidden @endif>@include('broiler.vaccinations')</section>
<div class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-6 gap-1 border-t border-emerald-900/10 bg-white/95 px-2 pt-2 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-lg backdrop-blur md:inset-x-6 md:bottom-4 md:mx-auto md:max-w-4xl md:rounded-2xl md:border" role="tablist" aria-label="أقسام الدورة">
@foreach($tabs as $key => $label)
<button type="button" id="cycle-tab-{{ $key }}" role="tab" data-cycle-tab="{{ $key }}" aria-controls="cycle-panel-{{ $key }}" aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}" tabindex="{{ $activeTab === $key ? '0' : '-1' }}" class="flex min-h-14 min-w-0 flex-col items-center justify-center gap-1 rounded-xl px-1 py-2 text-[11px] sm:text-sm font-semibold text-slate-600 aria-selected:bg-emerald-800 aria-selected:text-white focus-visible:outline-2 focus-visible:outline-emerald-600"><svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="{{ ['daily' => 'M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z', 'feed' => 'M12 21V3m0 6C5 9 4 5 4 3c6 0 8 3 8 6Zm0 7c7 0 8-4 8-6-6 0-8 3-8 6Z', 'costs' => 'M3 7h18v13H3V7Zm0 0V4h15v3m-3 6h6m-5 0h1', 'sales' => 'M3 3h2l3 12h11l3-8H6m3 13h1m7 0h1', 'reports' => 'M4 3v18h17M8 16v-4m5 4V8m5 8V5', 'vaccinations' => 'm14 3 7 7m-5-5-4 4m6-2-4 4M5 15l4 4m-6 2 4-4m0-4 6-6 6 6-6 6-6-6Z'][$key] }}" /></svg><span>{{ $label }}</span></button>
@endforeach
</div>
</div>
</x-broiler-shell>
