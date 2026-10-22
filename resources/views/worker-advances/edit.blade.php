<x-app-shell title="تعديل السلفة" active="more">
    <header class="ios-page-header"><a class="mb-1 text-sm font-semibold text-ios-blue" href="{{ route('worker-advances.index') }}">سلف العمال ←</a><h1 class="ios-large-title">تعديل السلفة</h1><p class="text-sm text-slate-500">سيُحدّث المصروف المرتبط تلقائيًا.</p></header>
    <form class="flex flex-col gap-4" method="POST" action="{{ route('worker-advances.update', $advance) }}">
        @csrf @method('PUT')
        <div class="ios-form-group">
            <div class="ios-form-row"><label class="ios-label" for="advanced_on">التاريخ</label><input class="ios-input @error('advanced_on') ios-input-error @enderror" id="advanced_on" name="advanced_on" type="date" value="{{ old('advanced_on', $advance->advanced_on->toDateString()) }}" required>@error('advanced_on')<p class="ios-error">{{ $message }}</p>@enderror</div>
            <div class="ios-form-row"><label class="ios-label" for="worker_name">اسم العامل</label><input class="ios-input @error('worker_name') ios-input-error @enderror" id="worker_name" name="worker_name" type="text" maxlength="150" value="{{ old('worker_name', $advance->worker_name) }}" required>@error('worker_name')<p class="ios-error">{{ $message }}</p>@enderror</div>
            <div class="ios-form-row"><label class="ios-label" for="amount">المبلغ</label><input class="ios-input @error('amount') ios-input-error @enderror" id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount', $advance->amount) }}" required>@error('amount')<p class="ios-error">{{ $message }}</p>@enderror</div>
            <div class="ios-form-row"><label class="ios-label" for="payment_method">طريقة الدفع</label><select class="ios-input @error('payment_method') ios-input-error @enderror" id="payment_method" name="payment_method">@foreach ($paymentMethods as $paymentMethod)<option value="{{ $paymentMethod->value }}" @selected(old('payment_method', $advance->payment_method->value) === $paymentMethod->value)>{{ $paymentMethod->label() }}</option>@endforeach</select>@error('payment_method')<p class="ios-error">{{ $message }}</p>@enderror</div>
            <div class="ios-form-row"><label class="ios-label" for="notes">ملاحظات</label><textarea class="ios-input min-h-20 resize-none @error('notes') ios-input-error @enderror" id="notes" name="notes" maxlength="1000">{{ old('notes', $advance->notes) }}</textarea>@error('notes')<p class="ios-error">{{ $message }}</p>@enderror</div>
        </div>
        <button class="ios-primary-button">حفظ التغييرات</button>
        <a class="rounded-2xl bg-white px-5 py-3 text-center font-semibold text-slate-600 shadow-sm" href="{{ route('worker-advances.index') }}">إلغاء</a>
    </form>
</x-app-shell>
