<x-app-shell title="تعديل بيع البيض" active="finance">
    <header class="ios-page-header">
        <a class="mb-1 text-sm font-semibold text-ios-blue" href="{{ route('egg-sales.index') }}">كل مبيعات البيض ←</a>
        <h1 class="ios-large-title">تعديل بيع البيض</h1>
        <p class="text-sm text-slate-500">التعديل سينعكس فورًا في الإيرادات ومخزون البيض.</p>
    </header>

    <form class="flex flex-col gap-4" method="POST" action="{{ route('egg-sales.update', $sale) }}">
        @csrf
        @method('PUT')
        <div class="ios-form-group">
            <div class="ios-form-row"><label class="ios-label" for="sold_on">تاريخ البيع</label><input class="ios-input @error('sold_on') ios-input-error @enderror" id="sold_on" name="sold_on" type="date" dir="ltr" value="{{ old('sold_on', $sale->sold_on->toDateString()) }}" required>@error('sold_on')<p class="ios-error">{{ $message }}</p>@enderror</div>
            <div class="grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 border-b border-slate-200/80">
                <div class="flex flex-col gap-1.5 px-4 py-3"><label class="ios-label" for="cartons">عدد الكراتين</label><input class="ios-input @error('cartons') ios-input-error @enderror" id="cartons" name="cartons" type="number" min="1" step="1" value="{{ old('cartons', $sale->cartons) }}" required>@error('cartons')<p class="ios-error">{{ $message }}</p>@enderror</div>
                <div class="flex flex-col gap-1.5 px-4 py-3"><label class="ios-label" for="price_per_carton">سعر الكرتونة</label><input class="ios-input @error('price_per_carton') ios-input-error @enderror" id="price_per_carton" name="price_per_carton" type="number" min="0" step="0.01" value="{{ old('price_per_carton', $sale->price_per_carton) }}" required>@error('price_per_carton')<p class="ios-error">{{ $message }}</p>@enderror</div>
            </div>
            <div class="ios-form-row"><label class="ios-label" for="buyer">المشتري</label><input class="ios-input @error('buyer') ios-input-error @enderror" id="buyer" name="buyer" type="text" maxlength="150" value="{{ old('buyer', $sale->buyer) }}" required>@error('buyer')<p class="ios-error">{{ $message }}</p>@enderror</div>
            <div class="ios-form-row"><label class="ios-label" for="payment_status">حالة الدفع</label><select class="ios-input @error('payment_status') ios-input-error @enderror" id="payment_status" name="payment_status" required><option value="paid" @selected(old('payment_status', $sale->payment_status) === 'paid')>تم الدفع</option><option value="pending" @selected(old('payment_status', $sale->payment_status) === 'pending')>آجل / لم يُدفع</option></select>@error('payment_status')<p class="ios-error">{{ $message }}</p>@enderror</div>
            <div class="ios-form-row"><label class="ios-label" for="invoice_number">رقم الفاتورة <span class="font-normal text-slate-300">اختياري</span></label><input class="ios-input @error('invoice_number') ios-input-error @enderror" id="invoice_number" name="invoice_number" type="text" maxlength="100" value="{{ old('invoice_number', $sale->invoice_number) }}">@error('invoice_number')<p class="ios-error">{{ $message }}</p>@enderror</div>
            <div class="ios-form-row"><label class="ios-label" for="notes">ملاحظات <span class="font-normal text-slate-300">اختياري</span></label><textarea class="ios-input min-h-20 resize-none @error('notes') ios-input-error @enderror" id="notes" name="notes" maxlength="1000">{{ old('notes', $sale->notes) }}</textarea>@error('notes')<p class="ios-error">{{ $message }}</p>@enderror</div>
        </div>
        <button class="ios-primary-button" type="submit">حفظ التغييرات</button>
        <a class="rounded-2xl bg-white px-5 py-3 text-center font-semibold text-slate-600 shadow-sm ring-1 ring-slate-900/5" href="{{ route('egg-sales.index') }}">إلغاء</a>
    </form>
</x-app-shell>
