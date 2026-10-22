<x-app-shell title="تعديل عملية البيع" active="finance">
    <header class="ios-page-header">
        <a class="mb-1 text-sm font-semibold text-ios-blue" href="{{ route('sales.index') }}">الإيرادات ←</a>
        <h1 class="ios-large-title">تعديل عملية البيع</h1>
        <p class="text-sm text-slate-500">حدّث بيانات البيع ثم احفظ التغييرات.</p>
    </header>

    <form class="flex flex-col gap-4" method="POST" action="{{ route('other-sales.update', $otherSale) }}" data-sale-calculator>
        @csrf
        @method('PUT')
        <div class="ios-form-group">
            <div class="ios-form-row">
                <label class="ios-label" for="sold_on">تاريخ البيع</label>
                <input class="ios-input @error('sold_on') ios-input-error @enderror" id="sold_on" name="sold_on" type="date" dir="ltr" value="{{ old('sold_on', $otherSale->sold_on->toDateString()) }}" required>
                @error('sold_on') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div class="ios-form-row">
                <label class="ios-label" for="product_type">نوع المبيع</label>
                <select class="ios-input @error('product_type') ios-input-error @enderror" id="product_type" name="product_type" required data-sale-product-type>
                    @foreach ($productTypes as $productType)<option value="{{ $productType->value }}" @selected(old('product_type', $otherSale->product_type->value) === $productType->value)>{{ $productType->label() }}</option>@endforeach
                </select>
                @error('product_type') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            @php($selectedProductType = old('product_type', $otherSale->product_type->value))
            <div class="ios-form-row {{ $selectedProductType === 'other' ? '' : 'hidden' }}" data-custom-product-row>
                <label class="ios-label" for="custom_product">اسم المبيع الآخر</label>
                <input class="ios-input @error('custom_product') ios-input-error @enderror" id="custom_product" name="custom_product" type="text" maxlength="100" value="{{ old('custom_product', $otherSale->custom_product) }}" data-custom-product>
                @error('custom_product') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 border-b border-slate-200/80">
                <div class="flex flex-col gap-1.5 px-4 py-3">
                    <label class="ios-label" for="quantity">الكمية</label>
                    <input class="ios-input @error('quantity') ios-input-error @enderror" id="quantity" name="quantity" type="number" min="0.001" step="0.001" value="{{ old('quantity', $otherSale->quantity) }}" required data-sale-quantity>
                    @error('quantity') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col gap-1.5 px-4 py-3">
                    <label class="ios-label" for="unit">الوحدة</label>
                    <select class="ios-input @error('unit') ios-input-error @enderror" id="unit" name="unit" required>
                        @foreach ($saleUnits as $saleUnit)<option value="{{ $saleUnit->value }}" @selected(old('unit', $otherSale->unit->value) === $saleUnit->value)>{{ $saleUnit->label() }}</option>@endforeach
                    </select>
                    @error('unit') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 border-b border-slate-200/80">
                <div class="flex flex-col gap-1.5 px-4 py-3">
                    <label class="ios-label" for="unit_price">سعر الوحدة</label>
                    <input class="ios-input @error('unit_price') ios-input-error @enderror" id="unit_price" name="unit_price" type="number" min="0" step="0.01" value="{{ old('unit_price', $otherSale->unit_price) }}" required data-sale-unit-price>
                    @error('unit_price') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col gap-1.5 px-4 py-3"><span class="ios-label">الإجمالي</span><output class="py-2 text-lg font-bold text-ios-green" data-sale-total>0.00</output></div>
            </div>
            <div class="ios-form-row">
                <label class="ios-label" for="buyer">المشتري</label>
                <input class="ios-input @error('buyer') ios-input-error @enderror" id="buyer" name="buyer" type="text" maxlength="150" value="{{ old('buyer', $otherSale->buyer) }}" required>
                @error('buyer') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div class="ios-form-row">
                <label class="ios-label" for="payment_status">حالة الدفع</label>
                <select class="ios-input @error('payment_status') ios-input-error @enderror" id="payment_status" name="payment_status" required>
                    @foreach ($paymentStatuses as $paymentStatus)<option value="{{ $paymentStatus->value }}" @selected(old('payment_status', $otherSale->payment_status->value) === $paymentStatus->value)>{{ $paymentStatus->label() }}</option>@endforeach
                </select>
                @error('payment_status') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div class="ios-form-row">
                <label class="ios-label" for="invoice_number">رقم الفاتورة <span class="font-normal text-slate-300">اختياري</span></label>
                <input class="ios-input @error('invoice_number') ios-input-error @enderror" id="invoice_number" name="invoice_number" type="text" maxlength="100" value="{{ old('invoice_number', $otherSale->invoice_number) }}">
                @error('invoice_number') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
            <div class="ios-form-row">
                <label class="ios-label" for="notes">ملاحظات <span class="font-normal text-slate-300">اختياري</span></label>
                <textarea class="ios-input min-h-20 resize-none @error('notes') ios-input-error @enderror" id="notes" name="notes" maxlength="1000">{{ old('notes', $otherSale->notes) }}</textarea>
                @error('notes') <p class="ios-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <button class="ios-primary-button" type="submit">حفظ التغييرات</button>
        <a class="rounded-2xl bg-white px-5 py-3 text-center font-semibold text-slate-600 shadow-sm ring-1 ring-slate-900/5" href="{{ route('sales.index') }}">إلغاء</a>
    </form>
</x-app-shell>
