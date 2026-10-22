<x-app-shell title="سلف العمال" active="more">
    <header class="ios-page-header">
        <p class="ios-eyebrow">العمال والمصروفات</p>
        <h1 class="ios-large-title">سلف العمال</h1>
        <p class="text-sm text-slate-500">سجّل السلفة مرة واحدة لتظهر تلقائيًا ضمن مصروفات العمال.</p>
    </header>
    @if (session('status'))<div class="ios-status" role="status">{{ session('status') }}</div>@endif

    <dl class="ios-card grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 py-4 text-center">
        <div class="px-3"><dt class="text-xs text-slate-400">إجمالي السلف</dt><dd class="mt-1 text-2xl font-bold text-ios-red">{{ number_format($totalAmount, 2) }}</dd></div>
        <div class="px-3"><dt class="text-xs text-slate-400">عدد العمليات</dt><dd class="mt-1 text-2xl font-bold text-ios-blue">{{ number_format($transactionCount) }}</dd></div>
    </dl>

    <section class="flex flex-col gap-2" id="worker-advance-form" aria-labelledby="advance-form-title">
        <h2 class="ios-section-title" id="advance-form-title">إضافة سلفة</h2>
        <form class="flex flex-col gap-4" method="POST" action="{{ route('worker-advances.store') }}">
            @csrf
            <div class="ios-form-group">
                <div class="ios-form-row"><label class="ios-label" for="advanced_on">التاريخ</label><input class="ios-input @error('advanced_on') ios-input-error @enderror" id="advanced_on" name="advanced_on" type="date" dir="ltr" value="{{ old('advanced_on', today()->toDateString()) }}" required>@error('advanced_on')<p class="ios-error">{{ $message }}</p>@enderror</div>
                <div class="ios-form-row"><label class="ios-label" for="worker_name">اسم العامل</label><input class="ios-input @error('worker_name') ios-input-error @enderror" id="worker_name" name="worker_name" type="text" maxlength="150" value="{{ old('worker_name') }}" required>@error('worker_name')<p class="ios-error">{{ $message }}</p>@enderror</div>
                <div class="grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 border-b border-slate-200/80">
                    <div class="flex flex-col gap-1.5 px-4 py-3"><label class="ios-label" for="amount">مبلغ السلفة</label><input class="ios-input @error('amount') ios-input-error @enderror" id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required>@error('amount')<p class="ios-error">{{ $message }}</p>@enderror</div>
                    <div class="flex flex-col gap-1.5 px-4 py-3"><label class="ios-label" for="payment_method">طريقة الدفع</label><select class="ios-input @error('payment_method') ios-input-error @enderror" id="payment_method" name="payment_method" required>@foreach ($paymentMethods as $paymentMethod)<option value="{{ $paymentMethod->value }}" @selected(old('payment_method', 'cash') === $paymentMethod->value)>{{ $paymentMethod->label() }}</option>@endforeach</select>@error('payment_method')<p class="ios-error">{{ $message }}</p>@enderror</div>
                </div>
                <div class="ios-form-row"><label class="ios-label" for="notes">ملاحظات <span class="font-normal text-slate-300">اختياري</span></label><textarea class="ios-input min-h-20 resize-none @error('notes') ios-input-error @enderror" id="notes" name="notes" maxlength="1000">{{ old('notes') }}</textarea>@error('notes')<p class="ios-error">{{ $message }}</p>@enderror</div>
            </div>
            <button class="ios-primary-button" type="submit">حفظ السلفة</button>
        </form>
    </section>

    <details class="ios-card group" @if ($filters !== []) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3.5 font-semibold"><span>تصفية السلف</span><span class="text-ios-blue">⌄</span></summary>
        <form class="flex flex-col gap-3 border-t border-slate-200/80 p-4" method="GET" action="{{ route('worker-advances.index') }}">
            <div class="grid grid-cols-2 gap-3"><div><label class="ios-label" for="from">من</label><input class="mt-1.5 w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5" id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}"></div><div><label class="ios-label" for="to">إلى</label><input class="mt-1.5 w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5" id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}"></div></div>
            <div><label class="ios-label" for="filter_worker_name">اسم العامل</label><input class="mt-1.5 w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5" id="filter_worker_name" name="worker_name" type="search" value="{{ $filters['worker_name'] ?? '' }}"></div>
            <div class="grid grid-cols-2 gap-2"><button class="rounded-xl bg-ios-blue px-4 py-2.5 text-sm font-semibold text-white">تطبيق</button><a class="rounded-xl bg-slate-100 px-4 py-2.5 text-center text-sm font-semibold text-slate-600" href="{{ route('worker-advances.index') }}">مسح</a></div>
        </form>
    </details>

    <section class="flex flex-col gap-3">
        <div class="flex items-baseline justify-between px-1"><h2 class="ios-section-title p-0">آخر السلف</h2><span class="text-xs text-slate-400">{{ $advances->total() }} عملية</span></div>
        @forelse ($advances as $advance)
            <article class="ios-card p-4">
                <div class="flex items-start justify-between gap-3"><div><h3 class="font-semibold">{{ $advance->worker_name }}</h3><p class="mt-1 text-xs text-slate-400">{{ $advance->advanced_on->translatedFormat('j F Y') }} · {{ $advance->payment_method->label() }}</p></div><strong class="text-ios-red">{{ number_format((float) $advance->amount, 2) }}</strong></div>
                @if ($advance->notes)<p class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-500">{{ $advance->notes }}</p>@endif
                <div class="mt-3 flex gap-2 border-t border-slate-100 pt-3"><a class="flex-1 rounded-xl bg-blue-50 px-3 py-2 text-center text-sm font-semibold text-ios-blue" href="{{ route('worker-advances.edit', $advance) }}">تعديل</a><form class="flex-1" method="POST" action="{{ route('worker-advances.destroy', $advance) }}" data-confirm-delete data-confirm-message="هل أنت متأكد من حذف السلفة ومصروفها؟">@csrf @method('DELETE')<button class="w-full rounded-xl bg-red-50 px-3 py-2 text-sm font-semibold text-ios-red">حذف</button></form></div>
            </article>
        @empty
            <div class="ios-card px-5 py-10 text-center text-sm text-slate-400">لا توجد سلف مسجلة.</div>
        @endforelse
        @if ($advances->hasPages())<nav class="ios-card flex items-center justify-between p-2">@if ($advances->onFirstPage())<span class="px-3 py-2 text-slate-300">السابق</span>@else<a class="px-3 py-2 font-semibold text-ios-blue" href="{{ $advances->previousPageUrl() }}">السابق</a>@endif<span class="text-xs text-slate-400">صفحة {{ $advances->currentPage() }} من {{ $advances->lastPage() }}</span>@if ($advances->hasMorePages())<a class="px-3 py-2 font-semibold text-ios-blue" href="{{ $advances->nextPageUrl() }}">التالي</a>@else<span class="px-3 py-2 text-slate-300">التالي</span>@endif</nav>@endif
    </section>
</x-app-shell>
