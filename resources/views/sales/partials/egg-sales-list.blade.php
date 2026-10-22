<section class="flex scroll-mt-4 flex-col gap-3" id="egg-sales-list" aria-labelledby="egg-sales-title">
    <div class="flex items-baseline justify-between px-1">
        <h2 class="ios-section-title p-0" id="egg-sales-title">مبيعات البيض الأخيرة</h2>
        <a class="text-xs font-semibold text-ios-blue" href="{{ route('egg-sales.index') }}">عرض الكل</a>
    </div>

    @forelse ($eggSales as $sale)
        <article class="ios-card p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="font-semibold">{{ $sale->buyer }}</h3>
                    <p class="mt-1 text-xs text-slate-400">{{ $sale->sold_on->translatedFormat('j F Y') }}</p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-[0.65rem] font-semibold {{ $sale->payment_status === 'paid' ? 'bg-green-50 text-ios-green' : 'bg-orange-50 text-ios-orange' }}">
                    {{ $sale->payment_status === 'paid' ? 'تم الدفع' : 'مبلغ مستحق' }}
                </span>
            </div>
            <dl class="mt-4 grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 text-center">
                <div class="px-1"><dt class="text-[0.65rem] text-slate-400">الكمية</dt><dd class="mt-1 font-semibold">{{ number_format($sale->cartons) }} كرتونة</dd></div>
                <div class="px-1"><dt class="text-[0.65rem] text-slate-400">سعر الكرتونة</dt><dd class="mt-1 font-semibold text-ios-blue">{{ number_format((float) $sale->price_per_carton, 2) }}</dd></div>
                <div class="px-1"><dt class="text-[0.65rem] text-slate-400">الإجمالي</dt><dd class="mt-1 font-semibold text-ios-green">{{ number_format($sale->totalAmount(), 2) }}</dd></div>
            </dl>
            @if ($sale->invoice_number || $sale->notes)
                <div class="mt-4 flex flex-col gap-2 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-500">
                    @if ($sale->invoice_number)<p>رقم الفاتورة: {{ $sale->invoice_number }}</p>@endif
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
</section>
