<x-app-shell title="تعديل توريد العلف" active="inventory">
    <header class="ios-page-header">
        <a class="mb-1 text-sm font-semibold text-ios-blue" href="{{ route('feed-deliveries.index') }}">كل توريدات العلف ←</a>
        <h1 class="ios-large-title">تعديل التوريد</h1>
        <p class="text-sm text-slate-500">أي تعديل هنا سيحدّث مصروف العلف المرتبط تلقائيًا.</p>
    </header>

    @if (session('status'))<div class="ios-status" role="status">{{ session('status') }}</div>@endif

    <form class="flex flex-col gap-4" method="POST" action="{{ route('feed-deliveries.update', $delivery) }}">
        @csrf
        @method('PUT')
        <div class="ios-form-group">
            <div class="ios-form-row"><label class="ios-label" for="delivered_on">تاريخ التوريد</label><input class="ios-input @error('delivered_on') ios-input-error @enderror" id="delivered_on" name="delivered_on" type="date" dir="ltr" value="{{ old('delivered_on', $delivery->delivered_on->toDateString()) }}" required>@error('delivered_on')<p class="ios-error">{{ $message }}</p>@enderror</div>
            <div class="ios-form-row"><label class="ios-label" for="feed_type">نوع العلف</label><input class="ios-input @error('feed_type') ios-input-error @enderror" id="feed_type" name="feed_type" type="text" maxlength="100" value="{{ old('feed_type', $delivery->feed_type) }}" required>@error('feed_type')<p class="ios-error">{{ $message }}</p>@enderror</div>
            <div class="grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 border-b border-slate-200/80">
                <div class="flex flex-col gap-1.5 px-4 py-3"><label class="ios-label" for="weight_tons">الوزن بالطن</label><input class="ios-input @error('weight_tons') ios-input-error @enderror" id="weight_tons" name="weight_tons" type="number" min="0.001" step="0.001" value="{{ old('weight_tons', $delivery->weight_tons) }}" required>@error('weight_tons')<p class="ios-error">{{ $message }}</p>@enderror</div>
                <div class="flex flex-col gap-1.5 px-4 py-3"><label class="ios-label" for="price_per_ton">سعر الطن</label><input class="ios-input @error('price_per_ton') ios-input-error @enderror" id="price_per_ton" name="price_per_ton" type="number" min="0" step="0.01" value="{{ old('price_per_ton', $delivery->price_per_ton) }}" required>@error('price_per_ton')<p class="ios-error">{{ $message }}</p>@enderror</div>
            </div>
            <div class="grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 border-b border-slate-200/80">
                <div class="flex flex-col gap-1.5 px-4 py-3"><label class="ios-label" for="shipping_cost">تكلفة الشحن</label><input class="ios-input @error('shipping_cost') ios-input-error @enderror" id="shipping_cost" name="shipping_cost" type="number" min="0" step="0.01" value="{{ old('shipping_cost', $delivery->shipping_cost) }}" required>@error('shipping_cost')<p class="ios-error">{{ $message }}</p>@enderror</div>
                <div class="flex flex-col gap-1.5 px-4 py-3"><label class="ios-label" for="tip">الإكرامية</label><input class="ios-input @error('tip') ios-input-error @enderror" id="tip" name="tip" type="number" min="0" step="0.01" value="{{ old('tip', $delivery->tip) }}" required>@error('tip')<p class="ios-error">{{ $message }}</p>@enderror</div>
            </div>
            <div class="ios-form-row"><label class="ios-label" for="supplier">المورد</label><input class="ios-input @error('supplier') ios-input-error @enderror" id="supplier" name="supplier" type="text" maxlength="150" value="{{ old('supplier', $delivery->supplier) }}" required>@error('supplier')<p class="ios-error">{{ $message }}</p>@enderror</div>
            <div class="ios-form-row"><label class="ios-label" for="payment_method">طريقة الدفع</label><select class="ios-input @error('payment_method') ios-input-error @enderror" id="payment_method" name="payment_method" required>@foreach ($paymentMethods as $paymentMethod)<option value="{{ $paymentMethod->value }}" @selected(old('payment_method', $delivery->payment_method->value) === $paymentMethod->value)>{{ $paymentMethod->label() }}</option>@endforeach</select>@error('payment_method')<p class="ios-error">{{ $message }}</p>@enderror</div>
            <div class="ios-form-row"><label class="ios-label" for="invoice_number">رقم الفاتورة <span class="font-normal text-slate-300">اختياري</span></label><input class="ios-input @error('invoice_number') ios-input-error @enderror" id="invoice_number" name="invoice_number" type="text" maxlength="100" value="{{ old('invoice_number', $delivery->invoice_number) }}">@error('invoice_number')<p class="ios-error">{{ $message }}</p>@enderror</div>
            <div class="ios-form-row"><label class="ios-label" for="notes">ملاحظات <span class="font-normal text-slate-300">اختياري</span></label><textarea class="ios-input min-h-20 resize-none @error('notes') ios-input-error @enderror" id="notes" name="notes" maxlength="1000">{{ old('notes', $delivery->notes) }}</textarea>@error('notes')<p class="ios-error">{{ $message }}</p>@enderror</div>
        </div>
        <button class="ios-primary-button" type="submit">حفظ التغييرات</button>
        <a class="rounded-2xl bg-white px-5 py-3 text-center font-semibold text-slate-600 shadow-sm ring-1 ring-slate-900/5" href="{{ route('feed-deliveries.index') }}">إلغاء</a>
    </form>
</x-app-shell>
