<details class="ios-card" @if(old('kind') === 'feed_delivery') open @endif>
    <summary class="cursor-pointer p-5 font-bold text-ios-blue">+ نقلة علف للمخزن</summary>
    <form method="POST" action="{{ route('broiler.entries.store', $cycle) }}">
        @csrf
        <input type="hidden" name="kind" value="feed_delivery">
        <p class="px-4 pb-3 text-sm text-slate-500">قيمة العلف + تكلفة النقل تُضاف تلقائيًا إلى مصروفات هذه الدورة.</p>
        <label class="ios-form-row">
            <span class="ios-label">تاريخ دخول المخزن</span>
            <input class="ios-input" type="date" name="recorded_on" value="{{ old('recorded_on', today()->toDateString()) }}" required>
        </label>
        <label class="ios-form-row">
            <span class="ios-label">نوع العلف (بادئ / نامي / ناهي)</span>
            <input class="ios-input" name="feed_type" value="{{ old('feed_type') }}" maxlength="100" required>
        </label>
        <label class="ios-form-row">
            <span class="ios-label">الكمية بالطن — الطن = 20 شيكارة (50 كجم)</span>
            <input class="ios-input" type="number" name="weight_tons" value="{{ old('weight_tons') }}" min="0.001" step="0.001" required>
        </label>
        <label class="ios-form-row">
            <span class="ios-label">سعر طن العلف</span>
            <input class="ios-input" type="number" name="price_per_ton" value="{{ old('price_per_ton') }}" min="0" step="0.01" required>
        </label>
        <label class="ios-form-row">
            <span class="ios-label">تكلفة النقل</span>
            <input class="ios-input" type="number" name="shipping_cost" value="{{ old('shipping_cost', 0) }}" min="0" step="0.01" required>
        </label>
        <label class="ios-form-row">
            <span class="ios-label">المورد (اختياري)</span>
            <input class="ios-input" name="supplier" value="{{ old('supplier') }}" maxlength="150">
        </label>
        <label class="ios-form-row">
            <span class="ios-label">رقم الفاتورة (اختياري)</span>
            <input class="ios-input" name="invoice_number" value="{{ old('invoice_number') }}" maxlength="100">
        </label>
        <label class="ios-form-row">
            <span class="ios-label">ملاحظات</span>
            <textarea class="ios-input" name="notes" rows="2">{{ old('notes') }}</textarea>
        </label>
        <div class="p-4"><button class="ios-primary-button">حفظ النقلة وإضافتها للمصروفات</button></div>
    </form>
</details>
