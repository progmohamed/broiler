<details class="ios-card group scroll-mt-4" id="feed-purchase-form" @if (old('_form') === 'feed') open @endif>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-4">
        <span>
            <span class="block font-semibold">شراء علف</span>
            <span class="mt-1 block text-xs font-normal text-slate-400">يدخل المخزون ويُسجّل تلقائيًا كمصروف</span>
        </span>
        <svg aria-hidden="true" class="size-5 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg>
    </summary>

    <form class="flex flex-col gap-4 border-t border-slate-200/80 p-4" method="POST" action="{{ route('feed-deliveries.store') }}">
        @csrf
        <input name="_form" type="hidden" value="feed">

        <div class="ios-form-group">
            <div class="ios-form-row">
                <label class="ios-label" for="feed_delivered_on">تاريخ الشراء</label>
                <input class="ios-input @error('delivered_on') ios-input-error @enderror" id="feed_delivered_on" name="delivered_on" type="date" dir="ltr" value="{{ old('delivered_on', today()->toDateString()) }}" required>
                @error('delivered_on') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div class="ios-form-row">
                <label class="ios-label" for="feed_type">نوع العلف</label>
                <input class="ios-input @error('feed_type') ios-input-error @enderror" id="feed_type" name="feed_type" type="text" maxlength="100" value="{{ old('feed_type') }}" placeholder="مثال: علف بياض 18%" required>
                @error('feed_type') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 border-b border-slate-200/80">
                <div class="flex flex-col gap-1.5 px-4 py-3">
                    <label class="ios-label" for="feed_weight_tons">الوزن بالطن</label>
                    <input class="ios-input @error('weight_tons') ios-input-error @enderror" id="feed_weight_tons" name="weight_tons" type="number" min="0.001" step="0.001" inputmode="decimal" value="{{ old('weight_tons') }}" placeholder="0.000" required>
                    @error('weight_tons') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col gap-1.5 px-4 py-3">
                    <label class="ios-label" for="feed_price_per_ton">سعر الطن</label>
                    <input class="ios-input @error('price_per_ton') ios-input-error @enderror" id="feed_price_per_ton" name="price_per_ton" type="number" min="0" step="0.01" inputmode="decimal" value="{{ old('price_per_ton') }}" placeholder="0.00" required>
                    @error('price_per_ton') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 border-b border-slate-200/80">
                <div class="flex flex-col gap-1.5 px-4 py-3">
                    <label class="ios-label" for="feed_shipping_cost">تكلفة الشحن</label>
                    <input class="ios-input @error('shipping_cost') ios-input-error @enderror" id="feed_shipping_cost" name="shipping_cost" type="number" min="0" step="0.01" inputmode="decimal" value="{{ old('shipping_cost', 0) }}" required>
                    @error('shipping_cost') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col gap-1.5 px-4 py-3">
                    <label class="ios-label" for="feed_tip">الإكرامية</label>
                    <input class="ios-input @error('tip') ios-input-error @enderror" id="feed_tip" name="tip" type="number" min="0" step="0.01" inputmode="decimal" value="{{ old('tip', 0) }}" required>
                    @error('tip') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="ios-form-row">
                <label class="ios-label" for="feed_supplier">المورد</label>
                <input class="ios-input @error('supplier') ios-input-error @enderror" id="feed_supplier" name="supplier" type="text" maxlength="150" value="{{ old('supplier') }}" placeholder="اسم المورد" required>
                @error('supplier') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div class="ios-form-row">
                <label class="ios-label" for="feed_payment_method">طريقة الدفع</label>
                <select class="ios-input @error('payment_method') ios-input-error @enderror" id="feed_payment_method" name="payment_method" required>
                    @foreach ($paymentMethods as $paymentMethod)
                        <option value="{{ $paymentMethod->value }}" @selected(old('payment_method', 'cash') === $paymentMethod->value)>{{ $paymentMethod->label() }}</option>
                    @endforeach
                </select>
                @error('payment_method') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div class="ios-form-row">
                <label class="ios-label" for="feed_invoice_number">رقم الفاتورة <span class="font-normal text-slate-300">اختياري</span></label>
                <input class="ios-input @error('invoice_number') ios-input-error @enderror" id="feed_invoice_number" name="invoice_number" type="text" maxlength="100" value="{{ old('invoice_number') }}" placeholder="—">
                @error('invoice_number') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div class="ios-form-row">
                <label class="ios-label" for="feed_notes">ملاحظات <span class="font-normal text-slate-300">اختياري</span></label>
                <textarea class="ios-input min-h-20 resize-none @error('notes') ios-input-error @enderror" id="feed_notes" name="notes" maxlength="1000" placeholder="أي تفاصيل إضافية">{{ old('notes') }}</textarea>
                @error('notes') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <button class="ios-primary-button" type="submit">حفظ شراء العلف</button>
    </form>
</details>
