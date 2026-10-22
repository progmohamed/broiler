<x-app-shell title="الإيرادات" active="finance">
    <header class="ios-page-header">
        <p class="ios-eyebrow">المبيعات والتحصيل</p>
        <h1 class="ios-large-title">الإيرادات</h1>
        <p class="text-sm text-slate-500">تابع مبيعات البيض وكل المبيعات الأخرى في مكان واحد.</p>
    </header>

    @if (session('status'))
        <div class="ios-status" role="status">{{ session('status') }}</div>
    @endif

    <nav class="grid grid-cols-2 rounded-xl bg-slate-200/70 p-1 text-center text-sm font-semibold" aria-label="أقسام المالية">
        <a class="rounded-lg px-3 py-2 text-slate-500 transition active:bg-white/60" href="{{ route('finance.index') }}">المصروفات</a>
        <span class="rounded-lg bg-white px-3 py-2 text-slate-950 shadow-sm" aria-current="page">الإيرادات</span>
    </nav>

    <section class="flex flex-col gap-2" aria-labelledby="revenue-summary-title">
        <div class="flex items-baseline justify-between px-1">
            <h2 class="ios-section-title p-0" id="revenue-summary-title">ملخص الإيرادات</h2>
            <span class="text-[0.65rem] text-slate-400">حسب الفترة والفلاتر المعروضة</span>
        </div>
        <dl class="ios-card grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 py-4 text-center">
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">إجمالي المبيعات</dt><dd class="mt-1 text-xl font-bold text-ios-green">{{ number_format($totalRevenue, 2) }}</dd><dd class="text-xs text-slate-400">{{ $transactionCount }} عملية</dd></div>
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">مبيعات البيض</dt><dd class="mt-1 text-xl font-bold text-ios-blue">{{ number_format($eggRevenue, 2) }}</dd><dd class="text-xs text-slate-400">أخرى: {{ number_format($otherRevenue, 2) }}</dd></div>
            <div class="px-2"><dt class="text-[0.68rem] text-slate-400">المبالغ الآجلة</dt><dd class="mt-1 text-xl font-bold text-ios-orange">{{ number_format($pendingRevenue, 2) }}</dd><dd class="text-xs text-slate-400">مطلوب تحصيلها</dd></div>
        </dl>
    </section>

    <section class="flex flex-col gap-2" aria-labelledby="add-sale-title">
        <div class="px-1">
            <h2 class="ios-section-title p-0" id="add-sale-title">تسجيل إيراد</h2>
            <p class="mt-1 text-xs text-slate-400">اختر بيع البيض لتحديث المخزون، أو سجّل أي مبيعات أخرى.</p>
        </div>

        @include('sales.partials.egg-sale-form')
    </section>

    <details class="ios-card group scroll-mt-4" id="other-sale-form" @if (old('_form') === 'other-sale') open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-4">
            <span><span class="block font-semibold" id="other-sale-form-title">إضافة بيع آخر</span><span class="mt-1 block text-xs font-normal text-slate-400">شكاير فارغة أو سبلة أو فراخ لحم أو أي مبيعات أخرى</span></span>
            <svg aria-hidden="true" class="size-5 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg>
        </summary>
        <form class="flex flex-col gap-4 border-t border-slate-200/80 p-4" method="POST" action="{{ route('other-sales.store') }}" data-sale-calculator>
            @csrf
            <input name="_form" type="hidden" value="other-sale">
            <div class="ios-form-group">
                <div class="ios-form-row">
                    <label class="ios-label" for="sold_on">تاريخ البيع</label>
                    <input class="ios-input @error('sold_on') ios-input-error @enderror" id="sold_on" name="sold_on" type="date" dir="ltr" value="{{ old('sold_on', today()->toDateString()) }}" required>
                    @error('sold_on') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="product_type">نوع المبيع</label>
                    <select class="ios-input @error('product_type') ios-input-error @enderror" id="product_type" name="product_type" required data-sale-product-type>
                        <option value="">اختر النوع</option>
                        @foreach ($productTypes as $productType)<option value="{{ $productType->value }}" @selected(old('product_type') === $productType->value)>{{ $productType->label() }}</option>@endforeach
                    </select>
                    @error('product_type') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row {{ old('product_type') === 'other' ? '' : 'hidden' }}" data-custom-product-row>
                    <label class="ios-label" for="custom_product">اسم المبيع الآخر</label>
                    <input class="ios-input @error('custom_product') ios-input-error @enderror" id="custom_product" name="custom_product" type="text" maxlength="100" value="{{ old('custom_product') }}" placeholder="مثال: معدات مستعملة" data-custom-product>
                    @error('custom_product') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 border-b border-slate-200/80">
                    <div class="flex flex-col gap-1.5 px-4 py-3">
                        <label class="ios-label" for="quantity">الكمية</label>
                        <input class="ios-input @error('quantity') ios-input-error @enderror" id="quantity" name="quantity" type="number" min="0.001" step="0.001" inputmode="decimal" value="{{ old('quantity') }}" placeholder="0" required data-sale-quantity>
                        @error('quantity') <p class="ios-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5 px-4 py-3">
                        <label class="ios-label" for="unit">الوحدة</label>
                        <select class="ios-input @error('unit') ios-input-error @enderror" id="unit" name="unit" required>
                            <option value="">اختر الوحدة</option>
                            @foreach ($saleUnits as $saleUnit)<option value="{{ $saleUnit->value }}" @selected(old('unit') === $saleUnit->value)>{{ $saleUnit->label() }}</option>@endforeach
                        </select>
                        @error('unit') <p class="ios-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 border-b border-slate-200/80">
                    <div class="flex flex-col gap-1.5 px-4 py-3">
                        <label class="ios-label" for="unit_price">سعر الوحدة</label>
                        <input class="ios-input @error('unit_price') ios-input-error @enderror" id="unit_price" name="unit_price" type="number" min="0" step="0.01" inputmode="decimal" value="{{ old('unit_price') }}" placeholder="0.00" required data-sale-unit-price>
                        @error('unit_price') <p class="ios-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5 px-4 py-3"><span class="ios-label">الإجمالي</span><output class="py-2 text-lg font-bold text-ios-green" data-sale-total>0.00</output></div>
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="buyer">المشتري</label>
                    <input class="ios-input @error('buyer') ios-input-error @enderror" id="buyer" name="buyer" type="text" maxlength="150" value="{{ old('buyer') }}" placeholder="اسم المشتري أو الجهة" required>
                    @error('buyer') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="payment_status">حالة الدفع</label>
                    <select class="ios-input @error('payment_status') ios-input-error @enderror" id="payment_status" name="payment_status" required>
                        @foreach ($paymentStatuses as $paymentStatus)<option value="{{ $paymentStatus->value }}" @selected(old('payment_status', 'paid') === $paymentStatus->value)>{{ $paymentStatus->label() }}</option>@endforeach
                    </select>
                    @error('payment_status') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="invoice_number">رقم الفاتورة <span class="font-normal text-slate-300">اختياري</span></label>
                    <input class="ios-input @error('invoice_number') ios-input-error @enderror" id="invoice_number" name="invoice_number" type="text" maxlength="100" value="{{ old('invoice_number') }}" placeholder="—">
                    @error('invoice_number') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="notes">ملاحظات <span class="font-normal text-slate-300">اختياري</span></label>
                    <textarea class="ios-input min-h-20 resize-none @error('notes') ios-input-error @enderror" id="notes" name="notes" maxlength="1000" placeholder="تفاصيل إضافية">{{ old('notes') }}</textarea>
                    @error('notes') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <button class="ios-primary-button" type="submit">حفظ عملية البيع</button>
        </form>
    </details>

    <details class="ios-card group" @if ($filters !== []) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 font-semibold"><span>تصفية المبيعات</span><svg aria-hidden="true" class="size-5 text-slate-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg></summary>
        <form class="flex flex-col gap-3 border-t border-slate-200/80 p-4" method="GET" action="{{ route('sales.index') }}">
            <div class="grid grid-cols-2 gap-3">
                <div><label class="ios-label" for="from">من تاريخ</label><input class="mt-1.5 w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5 outline-none focus:ring-2 focus:ring-ios-blue" id="from" name="from" type="date" dir="ltr" value="{{ $filters['from'] ?? '' }}"></div>
                <div><label class="ios-label" for="to">إلى تاريخ</label><input class="mt-1.5 w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5 outline-none focus:ring-2 focus:ring-ios-blue" id="to" name="to" type="date" dir="ltr" value="{{ $filters['to'] ?? '' }}"></div>
            </div>
            <div>
                <label class="ios-label" for="filter_product_type">نوع المبيع</label>
                <select class="mt-1.5 w-full rounded-xl border-0 bg-slate-100 px-3 py-2.5 outline-none focus:ring-2 focus:ring-ios-blue" id="filter_product_type" name="product_type">
                    <option value="">كل الأنواع</option>
                    @foreach ($productTypes as $productType)<option value="{{ $productType->value }}" @selected(($filters['product_type'] ?? '') === $productType->value)>{{ $productType->label() }}</option>@endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2"><button class="rounded-xl bg-ios-blue px-4 py-2.5 text-sm font-semibold text-white" type="submit">تطبيق</button><a class="rounded-xl bg-slate-100 px-4 py-2.5 text-center text-sm font-semibold text-slate-600" href="{{ route('sales.index') }}">مسح الفلاتر</a></div>
        </form>
    </details>

    @include('sales.partials.egg-sales-list')

    <section class="flex flex-col gap-3" aria-labelledby="recent-sales-title">
        <div class="flex items-baseline justify-between px-1"><h2 class="ios-section-title p-0" id="recent-sales-title">المبيعات الأخرى الأخيرة</h2><span class="text-xs text-slate-400">{{ $otherSales->total() }} عملية</span></div>
        @forelse ($otherSales as $otherSale)
            <article class="ios-card p-4">
                <div class="flex items-start justify-between gap-3">
                    <div><h3 class="font-semibold">{{ $otherSale->productLabel() }}</h3><p class="mt-1 text-xs text-slate-400">{{ $otherSale->sold_on->translatedFormat('j F Y') }} · {{ $otherSale->buyer }}</p><p class="mt-1 text-xs text-slate-500">{{ rtrim(rtrim(number_format((float) $otherSale->quantity, 3), '0'), '.') }} {{ $otherSale->unit->label() }} × {{ number_format((float) $otherSale->unit_price, 2) }}</p></div>
                    <div class="text-left"><p class="font-bold text-ios-green">{{ number_format($otherSale->totalAmount(), 2) }}</p><p class="mt-1 text-[0.65rem] {{ $otherSale->payment_status->value === 'pending' ? 'text-ios-orange' : 'text-slate-400' }}">{{ $otherSale->payment_status->label() }}</p></div>
                </div>
                @if ($otherSale->invoice_number || $otherSale->notes)<div class="mt-3 flex flex-col gap-1 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-500">@if ($otherSale->invoice_number)<p>فاتورة: {{ $otherSale->invoice_number }}</p>@endif @if ($otherSale->notes)<p>{{ $otherSale->notes }}</p>@endif</div>@endif
                <div class="mt-3 flex gap-2 border-t border-slate-100 pt-3">
                    <a class="flex-1 rounded-xl bg-blue-50 px-3 py-2 text-center text-sm font-semibold text-ios-blue" href="{{ route('other-sales.edit', $otherSale) }}">تعديل</a>
                    <form class="flex-1" method="POST" action="{{ route('other-sales.destroy', $otherSale) }}" data-confirm-delete data-confirm-message="هل أنت متأكد من حذف عملية البيع؟">@csrf @method('DELETE')<button class="w-full rounded-xl bg-red-50 px-3 py-2 text-sm font-semibold text-ios-red" type="submit">حذف</button></form>
                </div>
            </article>
        @empty
            <div class="ios-card px-5 py-10 text-center text-sm text-slate-400">لا توجد مبيعات أخرى مطابقة حتى الآن.</div>
        @endforelse
        @if ($otherSales->hasPages())
            <nav class="ios-card flex items-center justify-between gap-3 p-2" aria-label="صفحات المبيعات">
                @if ($otherSales->onFirstPage())<span class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-300">السابق</span>@else<a class="rounded-xl px-3 py-2 text-sm font-semibold text-ios-blue" href="{{ $otherSales->previousPageUrl() }}" rel="prev">السابق</a>@endif
                <span class="text-xs text-slate-400">صفحة {{ $otherSales->currentPage() }} من {{ $otherSales->lastPage() }}</span>
                @if ($otherSales->hasMorePages())<a class="rounded-xl px-3 py-2 text-sm font-semibold text-ios-blue" href="{{ $otherSales->nextPageUrl() }}" rel="next">التالي</a>@else<span class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-300">التالي</span>@endif
            </nav>
        @endif
    </section>
</x-app-shell>
