<x-app-shell title="المخزون" active="inventory">
    <header class="ios-page-header">
        <p class="ios-eyebrow">الكميات والحركة</p>
        <h1 class="ios-large-title">المخزون</h1>
        <p class="text-sm text-slate-500">الأرصدة الحالية وآخر حركات العلف والبيض.</p>
    </header>

    @if ($remainingFeedKilograms < 0 || $remainingEggs < 0 || ($feedDaysRemaining !== null && $feedDaysRemaining <= 3))
        <section class="flex flex-col gap-2" aria-labelledby="inventory-alerts-title">
            <h2 class="ios-section-title" id="inventory-alerts-title">تنبيهات المخزون</h2>
            @if ($remainingFeedKilograms < 0)<div class="rounded-2xl bg-red-50 px-4 py-3 text-sm font-semibold text-ios-red">استهلاك العلف المسجل أكبر من التوريدات بمقدار {{ number_format(abs($remainingFeedKilograms)) }} كجم.</div>@elseif ($feedDaysRemaining !== null && $feedDaysRemaining <= 3)<div class="rounded-2xl bg-orange-50 px-4 py-3 text-sm font-semibold text-ios-orange">رصيد العلف يكفي تقريبًا {{ number_format($feedDaysRemaining, 1) }} يوم فقط.</div>@endif
            @if ($remainingEggs < 0)<div class="rounded-2xl bg-red-50 px-4 py-3 text-sm font-semibold text-ios-red">مبيعات البيض أكبر من الإنتاج المسجل بمقدار {{ number_format(abs($remainingEggs)) }} بيضة.</div>@endif
        </section>
    @endif

    <section class="flex flex-col gap-2" aria-labelledby="feed-stock-title">
        <div class="flex items-baseline justify-between px-1"><h2 class="ios-section-title p-0" id="feed-stock-title">مخزون العلف</h2><a class="text-xs font-semibold text-ios-blue" href="#feed-movements">حركات العلف ←</a></div>
        <dl class="ios-card grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 py-4 text-center">
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">الرصيد</dt><dd class="mt-1 text-xl font-bold {{ $remainingFeedKilograms < 0 ? 'text-ios-red' : 'text-ios-green' }}">{{ number_format($remainingFeedKilograms) }}</dd><dd class="text-xs text-slate-400">كجم</dd></div>
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">ما يعادل</dt><dd class="mt-1 text-xl font-bold text-ios-blue">{{ number_format($remainingFeedSacks, 1) }}</dd><dd class="text-xs text-slate-400">شيكارة</dd></div>
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">يكفي لمدة</dt><dd class="mt-1 text-xl font-bold text-ios-orange">{{ $feedDaysRemaining === null ? '—' : number_format($feedDaysRemaining, 1) }}</dd><dd class="text-xs text-slate-400">يوم تقريبي</dd></div>
        </dl>
        <div class="ios-card grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 px-2 py-3 text-center text-xs"><div><span class="block text-slate-400">الوارد</span><strong class="mt-1 block">{{ number_format($deliveredFeedKilograms) }} كجم</strong></div><div><span class="block text-slate-400">المستهلك</span><strong class="mt-1 block">{{ number_format($usedFeedKilograms) }} كجم</strong></div><div><span class="block text-slate-400">متوسط 7 سجلات</span><strong class="mt-1 block">{{ number_format($averageDailyFeedKilograms) }} كجم/يوم</strong></div></div>
    </section>

    <section class="flex flex-col gap-2" aria-labelledby="egg-stock-title">
        <div class="flex items-baseline justify-between px-1"><h2 class="ios-section-title p-0" id="egg-stock-title">مخزون البيض</h2><a class="text-xs font-semibold text-ios-blue" href="#egg-movements">حركات البيض ←</a></div>
        <dl class="ios-card grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 py-4 text-center">
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">الرصيد</dt><dd class="mt-1 text-xl font-bold {{ $remainingEggs < 0 ? 'text-ios-red' : 'text-ios-green' }}">{{ number_format($remainingEggs) }}</dd><dd class="text-xs text-slate-400">بيضة</dd></div>
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">ما يعادل</dt><dd class="mt-1 text-xl font-bold text-ios-blue">{{ number_format($remainingEggCartons, 1) }}</dd><dd class="text-xs text-slate-400">كرتونة</dd></div>
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">الإنتاج / البيع</dt><dd class="mt-1 text-base font-bold">{{ number_format($producedEggs) }}</dd><dd class="text-xs text-slate-400">بيع {{ number_format($soldEggs) }}</dd></div>
        </dl>
    </section>

    <section class="flex scroll-mt-6 flex-col gap-3" id="feed-movements" aria-labelledby="feed-movements-title">
        <div class="flex items-baseline justify-between px-1"><h2 class="ios-section-title p-0" id="feed-movements-title">آخر حركة للعلف</h2><a class="text-xs font-semibold text-ios-blue" href="{{ route('feed-deliveries.index') }}">كل التوريدات</a></div>
        <div class="ios-card divide-y divide-slate-200/80">
            @forelse ($feedMovements as $movement)<div class="flex items-center gap-3 px-4 py-3"><span class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $movement['direction'] === 'in' ? 'bg-green-50 text-ios-green' : 'bg-red-50 text-ios-red' }}">{{ $movement['direction'] === 'in' ? '+' : '−' }}</span><div class="min-w-0 flex-1"><p class="font-semibold">{{ $movement['title'] }}</p><p class="mt-0.5 truncate text-xs text-slate-400">{{ $movement['occurred_on']->translatedFormat('j F Y') }} · {{ $movement['description'] }}</p></div><strong class="text-sm {{ $movement['direction'] === 'in' ? 'text-ios-green' : 'text-ios-red' }}">{{ number_format($movement['amount']) }} كجم</strong></div>@empty<div class="px-4 py-8 text-center text-sm text-slate-400">لا توجد حركة علف حتى هذا التاريخ.</div>@endforelse
        </div>
    </section>

    <section class="flex scroll-mt-6 flex-col gap-3" id="egg-movements" aria-labelledby="egg-movements-title">
        <div class="flex items-baseline justify-between px-1"><h2 class="ios-section-title p-0" id="egg-movements-title">آخر حركة للبيض</h2><a class="text-xs font-semibold text-ios-blue" href="{{ route('egg-sales.index') }}">كل المبيعات</a></div>
        <div class="ios-card divide-y divide-slate-200/80">
            @forelse ($eggMovements as $movement)<div class="flex items-center gap-3 px-4 py-3"><span class="flex size-9 shrink-0 items-center justify-center rounded-full {{ $movement['direction'] === 'in' ? 'bg-green-50 text-ios-green' : 'bg-red-50 text-ios-red' }}">{{ $movement['direction'] === 'in' ? '+' : '−' }}</span><div class="min-w-0 flex-1"><p class="font-semibold">{{ $movement['title'] }}</p><p class="mt-0.5 truncate text-xs text-slate-400">{{ $movement['occurred_on']->translatedFormat('j F Y') }} · {{ $movement['description'] }}</p></div><strong class="text-sm {{ $movement['direction'] === 'in' ? 'text-ios-green' : 'text-ios-red' }}">{{ number_format($movement['amount']) }} بيضة</strong></div>@empty<div class="px-4 py-8 text-center text-sm text-slate-400">لا توجد حركة بيض حتى هذا التاريخ.</div>@endforelse
        </div>
    </section>
</x-app-shell>
