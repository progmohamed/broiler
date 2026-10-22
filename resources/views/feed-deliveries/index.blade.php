<x-app-shell title="كل توريدات العلف" active="inventory">
    <header class="ios-page-header">
        <a class="mb-1 text-sm font-semibold text-ios-blue" href="{{ route('finance.index') }}#feed-purchase-form">المصروفات ←</a>
        <p class="ios-eyebrow">المخزون والمشتريات</p>
        <h1 class="ios-large-title">كل توريدات العلف</h1>
        <p class="text-sm text-slate-500">سجل كامل بكل كميات العلف الواردة وتكلفتها.</p>
    </header>

    @if (session('status'))
        <div class="ios-status" role="status">{{ session('status') }}</div>
    @endif

    <section class="flex flex-col gap-2" aria-labelledby="feed-deliveries-summary-title">
        <div class="flex items-baseline justify-between px-1">
            <h2 class="ios-section-title p-0" id="feed-deliveries-summary-title">الإجمالي</h2>
            <a class="text-xs font-semibold text-ios-blue" href="{{ route('finance.index') }}#feed-purchase-form">إضافة شراء علف</a>
        </div>
        <dl class="ios-card grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 py-4 text-center">
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">الوزن</dt><dd class="mt-1 text-xl font-bold">{{ number_format($totalWeightTons, 3) }}</dd><dd class="text-xs text-slate-400">طن</dd></div>
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">ما يعادل</dt><dd class="mt-1 text-xl font-bold text-ios-blue">{{ number_format($totalSacks, 1) }}</dd><dd class="text-xs text-slate-400">شيكارة</dd></div>
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">التكلفة</dt><dd class="mt-1 text-xl font-bold text-ios-red">{{ number_format($totalCost, 2) }}</dd><dd class="text-xs text-slate-400">شاملة الإضافات</dd></div>
        </dl>
    </section>

    <form class="ios-card flex items-end gap-3 p-4" method="GET" action="{{ route('feed-deliveries.index') }}">
        <div class="min-w-0 flex-1">
            <label class="ios-label" for="filter_payment_method">طريقة الدفع</label>
            <select class="ios-input mt-1.5" id="filter_payment_method" name="payment_method">
                <option value="">كل طرق الدفع</option>
                @foreach ($paymentMethods as $paymentMethod)
                    <option value="{{ $paymentMethod->value }}" @selected(($filters['payment_method'] ?? '') === $paymentMethod->value)>{{ $paymentMethod->label() }}</option>
                @endforeach
            </select>
        </div>
        <button class="shrink-0 rounded-xl bg-ios-blue px-4 py-3 text-sm font-semibold text-white" type="submit">تطبيق</button>
        @if ($filters !== [])
            <a class="shrink-0 rounded-xl bg-slate-100 px-3 py-3 text-sm font-semibold text-slate-600" href="{{ route('feed-deliveries.index') }}">مسح</a>
        @endif
    </form>

    <section class="flex flex-col gap-3" aria-labelledby="all-feed-deliveries-title">
        <div class="flex items-baseline justify-between px-1">
            <h2 class="ios-section-title p-0" id="all-feed-deliveries-title">التوريدات</h2>
            <span class="text-xs text-slate-400">{{ $deliveries->total() }} توريد</span>
        </div>

        @forelse ($deliveries as $delivery)
            <article class="ios-card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="font-semibold">{{ $delivery->feed_type }}</h3>
                        <p class="mt-1 truncate text-xs text-slate-400">{{ $delivery->supplier }} · {{ $delivery->delivered_on->translatedFormat('j F Y') }}</p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1.5">
                        <p class="font-bold text-ios-red">{{ number_format($delivery->totalCost(), 2) }}</p>
                        <span class="rounded-full px-2 py-0.5 text-[0.65rem] font-semibold {{ $delivery->payment_method->value === 'credit' ? 'bg-orange-50 text-ios-orange' : 'bg-slate-100 text-slate-500' }}">{{ $delivery->payment_method->label() }}</span>
                    </div>
                </div>
                <dl class="mt-4 grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 text-center">
                    <div class="px-1"><dt class="text-[0.65rem] text-slate-400">الوزن</dt><dd class="mt-1 font-semibold">{{ number_format((float) $delivery->weight_tons, 3) }} طن</dd></div>
                    <div class="px-1"><dt class="text-[0.65rem] text-slate-400">الشكاير</dt><dd class="mt-1 font-semibold text-ios-blue">{{ number_format($delivery->sackCount(), 1) }}</dd></div>
                    <div class="px-1"><dt class="text-[0.65rem] text-slate-400">سعر الطن</dt><dd class="mt-1 font-semibold">{{ number_format((float) $delivery->price_per_ton, 2) }}</dd></div>
                </dl>
                @if ($delivery->invoice_number || $delivery->notes)
                    <div class="mt-3 flex flex-col gap-1 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-500">
                        @if ($delivery->invoice_number)<p>فاتورة: {{ $delivery->invoice_number }}</p>@endif
                        @if ($delivery->notes)<p>{{ $delivery->notes }}</p>@endif
                    </div>
                @endif
                <div class="mt-3 flex gap-2 border-t border-slate-100 pt-3">
                    <a class="flex-1 rounded-xl bg-blue-50 px-3 py-2 text-center text-sm font-semibold text-ios-blue" href="{{ route('feed-deliveries.edit', $delivery) }}">تعديل</a>
                    <form class="flex-1" method="POST" action="{{ route('feed-deliveries.destroy', $delivery) }}" data-confirm-delete data-confirm-message="هل أنت متأكد من حذف التوريد ومصروفه المرتبط؟">
                        @csrf
                        @method('DELETE')
                        <button class="w-full rounded-xl bg-red-50 px-3 py-2 text-sm font-semibold text-ios-red" type="submit">حذف</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="ios-card px-5 py-10 text-center text-sm text-slate-400">لا توجد توريدات علف حتى الآن.</div>
        @endforelse

        @if ($deliveries->hasPages())
            <nav class="ios-card flex items-center justify-between gap-3 p-2" aria-label="صفحات توريدات العلف">
                @if ($deliveries->onFirstPage())<span class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-300">السابق</span>@else<a class="rounded-xl px-3 py-2 text-sm font-semibold text-ios-blue" href="{{ $deliveries->previousPageUrl() }}" rel="prev">السابق</a>@endif
                <span class="text-xs text-slate-400">صفحة {{ $deliveries->currentPage() }} من {{ $deliveries->lastPage() }}</span>
                @if ($deliveries->hasMorePages())<a class="rounded-xl px-3 py-2 text-sm font-semibold text-ios-blue" href="{{ $deliveries->nextPageUrl() }}" rel="next">التالي</a>@else<span class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-300">التالي</span>@endif
            </nav>
        @endif
    </section>
</x-app-shell>
