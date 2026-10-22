<details class="ios-card group scroll-mt-4" id="egg-sale-form" @if (old('_form') === 'egg') open @endif>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-4">
        <span>
            <span class="block font-semibold">بيع بيض</span>
            <span class="mt-1 block text-xs font-normal text-slate-400">يخصم عدد الكراتين تلقائيًا من مخزون البيض</span>
        </span>
        <svg aria-hidden="true" class="size-5 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" /></svg>
    </summary>

    <form class="flex flex-col gap-4 border-t border-slate-200/80 p-4" method="POST" action="{{ route('egg-sales.store') }}">
        @csrf
        <input name="_form" type="hidden" value="egg">

        <div class="ios-form-group">
            <div class="ios-form-row">
                <label class="ios-label" for="egg_sold_on">تاريخ البيع</label>
                <input class="ios-input @error('sold_on') ios-input-error @enderror" id="egg_sold_on" name="sold_on" type="date" dir="ltr" value="{{ old('sold_on', today()->toDateString()) }}" required>
                @error('sold_on') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 border-b border-slate-200/80">
                <div class="flex flex-col gap-1.5 px-4 py-3">
                    <label class="ios-label" for="egg_cartons">عدد الكراتين</label>
                    <input class="ios-input @error('cartons') ios-input-error @enderror" id="egg_cartons" name="cartons" type="number" min="1" inputmode="numeric" value="{{ old('cartons') }}" placeholder="0" required>
                    @error('cartons') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col gap-1.5 px-4 py-3">
                    <label class="ios-label" for="egg_price_per_carton">سعر الكرتونة</label>
                    <input class="ios-input @error('price_per_carton') ios-input-error @enderror" id="egg_price_per_carton" name="price_per_carton" type="number" min="0" step="0.01" inputmode="decimal" value="{{ old('price_per_carton') }}" placeholder="0.00" required>
                    @error('price_per_carton') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="ios-form-row">
                <label class="ios-label" for="egg_buyer">المشتري</label>
                <input class="ios-input @error('buyer') ios-input-error @enderror" id="egg_buyer" name="buyer" type="text" maxlength="150" value="{{ old('buyer') }}" placeholder="اسم المشتري" required>
                @error('buyer') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div class="ios-form-row">
                <label class="ios-label" for="egg_payment_status">حالة الدفع</label>
                <select class="ios-input @error('payment_status') ios-input-error @enderror" id="egg_payment_status" name="payment_status" required>
                    @foreach ($paymentStatuses as $paymentStatus)
                        <option value="{{ $paymentStatus->value }}" @selected(old('payment_status', 'paid') === $paymentStatus->value)>{{ $paymentStatus->label() }}</option>
                    @endforeach
                </select>
                @error('payment_status') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div class="ios-form-row">
                <label class="ios-label" for="egg_invoice_number">رقم الفاتورة أو الإيصال <span class="font-normal text-slate-300">اختياري</span></label>
                <input class="ios-input @error('invoice_number') ios-input-error @enderror" id="egg_invoice_number" name="invoice_number" type="text" maxlength="100" value="{{ old('invoice_number') }}" placeholder="—">
                @error('invoice_number') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div class="ios-form-row">
                <label class="ios-label" for="egg_notes">ملاحظات <span class="font-normal text-slate-300">اختياري</span></label>
                <textarea class="ios-input min-h-20 resize-none @error('notes') ios-input-error @enderror" id="egg_notes" name="notes" maxlength="1000" placeholder="أي تفاصيل إضافية">{{ old('notes') }}</textarea>
                @error('notes') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <button class="ios-primary-button" type="submit">حفظ بيع البيض</button>
    </form>
</details>
