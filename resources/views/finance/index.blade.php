<x-app-shell title="المالية" active="finance">
    <header class="ios-page-header">
        <p class="ios-eyebrow">الإيرادات والمصروفات</p>
        <h1 class="ios-large-title">المالية</h1>
        <p class="text-sm text-slate-500">سجّل مصروفات المزرعة وتابع إجمالياتها.</p>
    </header>

    @if (session('status'))
        <div class="ios-status" role="status">{{ session('status') }}</div>
    @endif

    <nav class="grid grid-cols-2 rounded-xl bg-slate-200/70 p-1 text-center text-sm font-semibold" aria-label="أقسام المالية">
        <span class="rounded-lg bg-white px-3 py-2 text-slate-950 shadow-sm" aria-current="page">المصروفات</span>
        <a class="rounded-lg px-3 py-2 text-slate-500 transition active:bg-white/60" href="{{ route('sales.index') }}">الإيرادات</a>
    </nav>

    <section class="flex flex-col gap-2" aria-labelledby="finance-summary-title">
        <div class="flex items-baseline justify-between px-1">
            <h2 class="ios-section-title p-0" id="finance-summary-title">النظرة المالية</h2>
            <span class="text-[0.65rem] text-slate-400">كل المبيعات والمصروفات شاملة العلف</span>
        </div>
        <dl class="ios-card grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 py-4 text-center">
            <div class="px-2">
                <dt class="text-[0.68rem] text-slate-400">الإيرادات</dt>
                <dd class="mt-1 text-xl font-bold text-ios-green">{{ number_format($totalRevenue, 2) }}</dd>
                <dd class="text-xs text-slate-400">كل المبيعات</dd>
            </div>
            <div class="px-2">
                <dt class="text-[0.68rem] text-slate-400">المصروفات</dt>
                <dd class="mt-1 text-xl font-bold text-ios-red">{{ number_format($financialExpenseTotal, 2) }}</dd>
                <dd class="text-xs text-slate-400">اليدوية + العلف</dd>
            </div>
            <div class="px-2">
                <dt class="text-[0.68rem] text-slate-400">الصافي الحالي</dt>
                <dd class="mt-1 text-xl font-bold {{ $netProfit < 0 ? 'text-ios-red' : 'text-ios-blue' }}">{{ number_format($netProfit, 2) }}</dd>
                <dd class="text-xs text-slate-400">بعد تكلفة العلف</dd>
            </div>
        </dl>
    </section>

    <section class="flex flex-col gap-2" aria-labelledby="expense-summary-title">
        <h2 class="ios-section-title" id="expense-summary-title">ملخص المصروفات</h2>
        <dl class="ios-card grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 py-4 text-center">
            <div class="px-2">
                <dt class="text-[0.68rem] text-slate-400">إجمالي المصروفات</dt>
                <dd class="mt-1 text-xl font-bold text-ios-red">{{ number_format($totalAmount, 2) }}</dd>
                <dd class="text-xs text-slate-400">للفترة المعروضة</dd>
            </div>
            <div class="px-2">
                <dt class="text-[0.68rem] text-slate-400">المصروفات الآجلة</dt>
                <dd class="mt-1 text-xl font-bold text-ios-orange">{{ number_format($creditAmount, 2) }}</dd>
                <dd class="text-xs text-slate-400">غير مدفوعة</dd>
            </div>
            <div class="px-2">
                <dt class="text-[0.68rem] text-slate-400">عدد العمليات</dt>
                <dd class="mt-1 text-xl font-bold">{{ number_format($transactionCount) }}</dd>
                <dd class="text-xs text-slate-400">عملية</dd>
            </div>
        </dl>
    </section>

    <section class="flex flex-col gap-2" aria-labelledby="add-expense-title">
        <div class="flex items-start justify-between gap-3 px-1">
            <div>
                <h2 class="ios-section-title p-0" id="add-expense-title">تسجيل مصروف</h2>
                <p class="mt-1 text-xs text-slate-400">اختر شراء علف لتحديث المخزون والتكلفة معًا، أو أضف أي مصروف آخر.</p>
            </div>
            <a class="shrink-0 text-xs font-semibold text-ios-blue" href="{{ route('feed-deliveries.index') }}">كل التوريدات</a>
        </div>

        @include('finance.partials.feed-purchase-form')
    </section>

    <details class="ios-card group scroll-mt-4" id="expense-form" @if (old('_form') === 'expense') open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-4">
            <span>
                <span class="block font-semibold" id="expense-form-title">مصروف آخر</span>
                <span class="mt-1 block text-xs font-normal text-slate-400">معدات وصيانة وكهرباء وعمال ومصروفات نثرية</span>
            </span>
            <svg aria-hidden="true" class="size-5 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg>
        </summary>

        <form class="flex flex-col gap-4 border-t border-slate-200/80 p-4" method="POST" action="{{ route('expenses.store') }}">
            @csrf
            <input name="_form" type="hidden" value="expense">

            <div class="ios-form-group">
                <div class="ios-form-row">
                    <label class="ios-label" for="spent_on">تاريخ المصروف</label>
                    <input class="ios-input @error('spent_on') ios-input-error @enderror" id="spent_on" name="spent_on" type="date" dir="ltr" value="{{ old('spent_on', today()->toDateString()) }}" required>
                    @error('spent_on') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="category">التصنيف</label>
                    <select class="ios-input @error('category') ios-input-error @enderror" id="category" name="category" required>
                        <option value="">اختر التصنيف</option>
                        @foreach ($manualCategoriesByGroup as $group => $categories)
                            <optgroup label="{{ $group }}">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ $category->label() }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('category') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 border-b border-slate-200/80">
                    <div class="flex flex-col gap-1.5 px-4 py-3">
                        <label class="ios-label" for="amount">المبلغ</label>
                        <input class="ios-input @error('amount') ios-input-error @enderror" id="amount" name="amount" type="number" min="0.01" step="0.01" inputmode="decimal" value="{{ old('amount') }}" placeholder="0.00" required>
                        @error('amount') <p class="ios-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5 px-4 py-3">
                        <label class="ios-label" for="payment_method">طريقة الدفع</label>
                        <select class="ios-input @error('payment_method') ios-input-error @enderror" id="payment_method" name="payment_method" required>
                            @foreach ($paymentMethods as $paymentMethod)
                                <option value="{{ $paymentMethod->value }}" @selected(old('payment_method', 'cash') === $paymentMethod->value)>{{ $paymentMethod->label() }}</option>
                            @endforeach
                        </select>
                        @error('payment_method') <p class="ios-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="payee">الجهة أو العامل <span class="font-normal text-slate-300">اختياري</span></label>
                    <input class="ios-input @error('payee') ios-input-error @enderror" id="payee" name="payee" type="text" maxlength="150" value="{{ old('payee') }}" placeholder="اسم الجهة المستلمة للمبلغ">
                    @error('payee') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="invoice_number">رقم الفاتورة <span class="font-normal text-slate-300">اختياري</span></label>
                    <input class="ios-input @error('invoice_number') ios-input-error @enderror" id="invoice_number" name="invoice_number" type="text" maxlength="100" value="{{ old('invoice_number') }}" placeholder="—">
                    @error('invoice_number') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="notes">ملاحظات <span class="font-normal text-slate-300">اختياري</span></label>
                    <textarea class="ios-input min-h-20 resize-none @error('notes') ios-input-error @enderror" id="notes" name="notes" maxlength="1000" placeholder="تفاصيل المصروف">{{ old('notes') }}</textarea>
                    @error('notes') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <button class="ios-primary-button" type="submit">حفظ المصروف</button>
        </form>
    </details>

    <details class="ios-card group" @if ($filters !== []) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 font-semibold">
            <span>تصفية المصروفات</span>
            <svg aria-hidden="true" class="size-5 text-slate-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg>
        </summary>
        <form class="flex flex-col gap-3 border-t border-slate-200/80 p-4" method="GET" action="{{ route('finance.index') }}">
            <div class="grid grid-cols-2 gap-3">
                <div><label class="ios-label" for="from">من تاريخ</label><input class="mt-1.5 w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5 outline-none focus:ring-2 focus:ring-ios-blue" id="from" name="from" type="date" dir="ltr" value="{{ $filters['from'] ?? '' }}"></div>
                <div><label class="ios-label" for="to">إلى تاريخ</label><input class="mt-1.5 w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5 outline-none focus:ring-2 focus:ring-ios-blue" id="to" name="to" type="date" dir="ltr" value="{{ $filters['to'] ?? '' }}"></div>
            </div>
            <div>
                <label class="ios-label" for="filter_category">التصنيف</label>
                <select class="mt-1.5 w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5 outline-none focus:ring-2 focus:ring-ios-blue" id="filter_category" name="category">
                    <option value="">كل التصنيفات</option>
                    @foreach ($categoriesByGroup as $group => $categories)
                        <optgroup label="{{ $group }}">
                            @foreach ($categories as $category)
                                <option value="{{ $category->value }}" @selected(($filters['category'] ?? '') === $category->value)>{{ $category->label() }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <button class="rounded-xl bg-ios-blue px-4 py-2.5 text-sm font-semibold text-white" type="submit">تطبيق</button>
                <a class="rounded-xl bg-slate-100 px-4 py-2.5 text-center text-sm font-semibold text-slate-600" href="{{ route('finance.index') }}">مسح الفلاتر</a>
            </div>
        </form>
    </details>

    <section class="flex scroll-mt-4 flex-col gap-3" id="recent-expenses" aria-labelledby="recent-expenses-title">
        <div class="flex items-baseline justify-between px-1">
            <h2 class="ios-section-title p-0" id="recent-expenses-title">المصروفات الأخيرة</h2>
            <span class="text-xs text-slate-400">{{ $expenses->total() }} عملية</span>
        </div>

        @forelse ($expenses as $expense)
            <article class="ios-card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold">{{ $expense->category->label() }}</h3>
                        <p class="mt-1 text-xs text-slate-400">{{ $expense->spent_on->translatedFormat('j F Y') }}@if ($expense->payee) · {{ $expense->payee }}@endif</p>
                    </div>
                    <div class="text-left">
                        <p class="font-bold text-ios-red">{{ number_format((float) $expense->amount, 2) }}</p>
                        <p class="mt-1 text-[0.65rem] {{ $expense->payment_method->value === 'credit' ? 'text-ios-orange' : 'text-slate-400' }}">{{ $expense->payment_method->label() }}</p>
                    </div>
                </div>
                @if ($expense->invoice_number || $expense->notes)
                    <div class="mt-3 flex flex-col gap-1 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-500">
                        @if ($expense->invoice_number)<p>فاتورة: {{ $expense->invoice_number }}</p>@endif
                        @if ($expense->notes)<p>{{ $expense->notes }}</p>@endif
                    </div>
                @endif
                <div class="mt-3 flex gap-2 border-t border-slate-100 pt-3">
                    @if ($expense->feed_delivery_id)
                        <span class="flex-1 rounded-xl bg-orange-50 px-3 py-2 text-center text-xs font-semibold text-ios-orange">تلقائي من توريد العلف</span>
                        <a class="flex-1 rounded-xl bg-blue-50 px-3 py-2 text-center text-sm font-semibold text-ios-blue" href="{{ route('feed-deliveries.edit', $expense->feed_delivery_id) }}">تعديل التوريد</a>
                    @elseif ($expense->worker_advance_id)
                        <span class="flex-1 rounded-xl bg-orange-50 px-3 py-2 text-center text-xs font-semibold text-ios-orange">تلقائي من سلفة عامل</span>
                        <a class="flex-1 rounded-xl bg-blue-50 px-3 py-2 text-center text-sm font-semibold text-ios-blue" href="{{ route('worker-advances.edit', $expense->worker_advance_id) }}">تعديل السلفة</a>
                    @else
                        <a class="flex-1 rounded-xl bg-blue-50 px-3 py-2 text-center text-sm font-semibold text-ios-blue" href="{{ route('expenses.edit', $expense) }}">تعديل</a>
                        <form class="flex-1" method="POST" action="{{ route('expenses.destroy', $expense) }}" data-confirm-delete data-confirm-message="هل أنت متأكد من حذف هذا المصروف؟">
                            @csrf
                            @method('DELETE')
                            <button class="w-full rounded-xl bg-red-50 px-3 py-2 text-sm font-semibold text-ios-red" type="submit">حذف</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="ios-card px-5 py-10 text-center text-sm text-slate-400">لا توجد مصروفات مطابقة حتى الآن.</div>
        @endforelse

        @if ($expenses->hasPages())
            <nav class="ios-card flex items-center justify-between gap-3 p-2" aria-label="صفحات المصروفات">
                @if ($expenses->onFirstPage())<span class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-300">السابق</span>@else<a class="rounded-xl px-3 py-2 text-sm font-semibold text-ios-blue" href="{{ $expenses->previousPageUrl() }}" rel="prev">السابق</a>@endif
                <span class="text-xs text-slate-400">صفحة {{ $expenses->currentPage() }} من {{ $expenses->lastPage() }}</span>
                @if ($expenses->hasMorePages())<a class="rounded-xl px-3 py-2 text-sm font-semibold text-ios-blue" href="{{ $expenses->nextPageUrl() }}" rel="next">التالي</a>@else<span class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-300">التالي</span>@endif
            </nav>
        @endif
    </section>
</x-app-shell>
