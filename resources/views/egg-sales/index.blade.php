<x-app-shell title="كل مبيعات البيض" active="finance">
    <header class="ios-page-header">
        <a class="mb-1 text-sm font-semibold text-ios-blue" href="{{ route('sales.index') }}#egg-sale-form">الإيرادات ←</a>
        <p class="ios-eyebrow">المبيعات والتحصيل</p>
        <h1 class="ios-large-title">كل مبيعات البيض</h1>
        <p class="text-sm text-slate-500">سجل كامل بمبيعات الكراتين والمبالغ المحصلة والآجلة.</p>
    </header>

    @if (session('status'))
        <div class="ios-status" role="status">{{ session('status') }}</div>
    @endif

    <section class="flex flex-col gap-2" aria-labelledby="egg-sales-summary-title">
        <div class="flex items-baseline justify-between px-1">
            <h2 class="ios-section-title p-0" id="egg-sales-summary-title">الإجمالي</h2>
            <a class="text-xs font-semibold text-ios-blue" href="{{ route('sales.index') }}#egg-sale-form">إضافة بيع بيض</a>
        </div>
        <dl class="ios-card grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 py-4 text-center">
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">الكراتين</dt><dd class="mt-1 text-xl font-bold">{{ number_format($totalCartons) }}</dd><dd class="text-xs text-slate-400">كرتونة</dd></div>
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">المبيعات</dt><dd class="mt-1 text-xl font-bold text-ios-green">{{ number_format($totalRevenue, 2) }}</dd><dd class="text-xs text-slate-400">إجمالي القيمة</dd></div>
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">غير محصل</dt><dd class="mt-1 text-xl font-bold text-ios-orange">{{ number_format($pendingRevenue, 2) }}</dd><dd class="text-xs text-slate-400">مبلغ مستحق</dd></div>
        </dl>
    </section>

    <form class="ios-card flex items-end gap-3 p-4" method="GET" action="{{ route('egg-sales.index') }}">
        <div class="min-w-0 flex-1">
            <label class="ios-label" for="filter_payment_status">حالة الدفع</label>
            <select class="ios-input mt-1.5" id="filter_payment_status" name="payment_status">
                <option value="">كل حالات الدفع</option>
                @foreach ($paymentStatuses as $paymentStatus)
                    <option value="{{ $paymentStatus->value }}" @selected(($filters['payment_status'] ?? '') === $paymentStatus->value)>{{ $paymentStatus->label() }}</option>
                @endforeach
            </select>
        </div>
        <button class="shrink-0 rounded-xl bg-ios-blue px-4 py-3 text-sm font-semibold text-white" type="submit">تطبيق</button>
        @if ($filters !== [])
            <a class="shrink-0 rounded-xl bg-slate-100 px-3 py-3 text-sm font-semibold text-slate-600" href="{{ route('egg-sales.index') }}">مسح</a>
        @endif
    </form>

    <section class="flex flex-col gap-3" aria-labelledby="all-egg-sales-title">
        <div class="flex items-baseline justify-between px-1">
            <h2 class="ios-section-title p-0" id="all-egg-sales-title">المبيعات</h2>
            <span class="text-xs text-slate-400">{{ $sales->total() }} عملية</span>
        </div>

        @forelse ($sales as $sale)
            <article class="ios-card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold">{{ $sale->buyer }}</h3>
                        <p class="mt-1 text-xs text-slate-400">{{ $sale->sold_on->translatedFormat('j F Y') }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-[0.65rem] font-semibold {{ $sale->payment_status === 'paid' ? 'bg-green-50 text-ios-green' : 'bg-orange-50 text-ios-orange' }}">{{ $sale->payment_status === 'paid' ? 'تم الدفع' : 'مبلغ مستحق' }}</span>
                </div>
                <dl class="mt-4 grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 text-center">
                    <div class="px-1"><dt class="text-[0.65rem] text-slate-400">الكمية</dt><dd class="mt-1 font-semibold">{{ number_format($sale->cartons) }} كرتونة</dd></div>
                    <div class="px-1"><dt class="text-[0.65rem] text-slate-400">سعر الكرتونة</dt><dd class="mt-1 font-semibold text-ios-blue">{{ number_format((float) $sale->price_per_carton, 2) }}</dd></div>
                    <div class="px-1"><dt class="text-[0.65rem] text-slate-400">الإجمالي</dt><dd class="mt-1 font-semibold text-ios-green">{{ number_format($sale->totalAmount(), 2) }}</dd></div>
                </dl>
                @if ($sale->invoice_number || $sale->notes)
                    <div class="mt-3 flex flex-col gap-1 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-500">
                        @if ($sale->invoice_number)<p>فاتورة: {{ $sale->invoice_number }}</p>@endif
                        @if ($sale->notes)<p>{{ $sale->notes }}</p>@endif
                    </div>
                @endif
                <div class="mt-3 flex gap-2 border-t border-slate-100 pt-3">
                    <a class="flex-1 rounded-xl bg-blue-50 px-3 py-2 text-center text-sm font-semibold text-ios-blue" href="{{ route('egg-sales.edit', $sale) }}">تعديل</a>
                    <form class="flex-1" method="POST" action="{{ route('egg-sales.destroy', $sale) }}" data-confirm-delete data-confirm-message="هل أنت متأكد من حذف عملية بيع البيض؟">
                        @csrf
                        @method('DELETE')
                        <button class="w-full rounded-xl bg-red-50 px-3 py-2 text-sm font-semibold text-ios-red" type="submit">حذف</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="ios-card px-5 py-10 text-center text-sm text-slate-400">لا توجد مبيعات بيض حتى الآن.</div>
        @endforelse

        @if ($sales->hasPages())
            <nav class="ios-card flex items-center justify-between gap-3 p-2" aria-label="صفحات مبيعات البيض">
                @if ($sales->onFirstPage())<span class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-300">السابق</span>@else<a class="rounded-xl px-3 py-2 text-sm font-semibold text-ios-blue" href="{{ $sales->previousPageUrl() }}" rel="prev">السابق</a>@endif
                <span class="text-xs text-slate-400">صفحة {{ $sales->currentPage() }} من {{ $sales->lastPage() }}</span>
                @if ($sales->hasMorePages())<a class="rounded-xl px-3 py-2 text-sm font-semibold text-ios-blue" href="{{ $sales->nextPageUrl() }}" rel="next">التالي</a>@else<span class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-300">التالي</span>@endif
            </nav>
        @endif
    </section>
</x-app-shell>
