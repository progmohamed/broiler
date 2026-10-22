<x-app-shell title="تعديل المصروف" active="finance">
    <header class="ios-page-header">
        <a class="mb-1 text-sm font-semibold text-ios-blue" href="{{ route('finance.index') }}">المالية ←</a>
        <h1 class="ios-large-title">تعديل المصروف</h1>
        <p class="text-sm text-slate-500">حدّث بيانات العملية ثم احفظ التغييرات.</p>
    </header>

    <section class="flex flex-col gap-2" aria-labelledby="edit-expense-title">
        <h2 class="ios-section-title" id="edit-expense-title">بيانات المصروف</h2>
        <form class="flex flex-col gap-4" method="POST" action="{{ route('expenses.update', $expense) }}">
            @csrf
            @method('PUT')

            <div class="ios-form-group">
                <div class="ios-form-row">
                    <label class="ios-label" for="spent_on">تاريخ المصروف</label>
                    <input class="ios-input @error('spent_on') ios-input-error @enderror" id="spent_on" name="spent_on" type="date" dir="ltr" value="{{ old('spent_on', $expense->spent_on->toDateString()) }}" required>
                    @error('spent_on') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="category">التصنيف</label>
                    <select class="ios-input @error('category') ios-input-error @enderror" id="category" name="category" required>
                        @foreach ($categoriesByGroup as $group => $categories)
                            <optgroup label="{{ $group }}">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->value }}" @selected(old('category', $expense->category->value) === $category->value)>{{ $category->label() }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('category') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 border-b border-slate-200/80">
                    <div class="flex flex-col gap-1.5 px-4 py-3">
                        <label class="ios-label" for="amount">المبلغ</label>
                        <input class="ios-input @error('amount') ios-input-error @enderror" id="amount" name="amount" type="number" min="0.01" step="0.01" inputmode="decimal" value="{{ old('amount', $expense->amount) }}" required>
                        @error('amount') <p class="ios-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-col gap-1.5 px-4 py-3">
                        <label class="ios-label" for="payment_method">طريقة الدفع</label>
                        <select class="ios-input @error('payment_method') ios-input-error @enderror" id="payment_method" name="payment_method" required>
                            @foreach ($paymentMethods as $paymentMethod)
                                <option value="{{ $paymentMethod->value }}" @selected(old('payment_method', $expense->payment_method->value) === $paymentMethod->value)>{{ $paymentMethod->label() }}</option>
                            @endforeach
                        </select>
                        @error('payment_method') <p class="ios-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="payee">الجهة أو العامل <span class="font-normal text-slate-300">اختياري</span></label>
                    <input class="ios-input @error('payee') ios-input-error @enderror" id="payee" name="payee" type="text" maxlength="150" value="{{ old('payee', $expense->payee) }}">
                    @error('payee') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="invoice_number">رقم الفاتورة <span class="font-normal text-slate-300">اختياري</span></label>
                    <input class="ios-input @error('invoice_number') ios-input-error @enderror" id="invoice_number" name="invoice_number" type="text" maxlength="100" value="{{ old('invoice_number', $expense->invoice_number) }}">
                    @error('invoice_number') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="notes">ملاحظات <span class="font-normal text-slate-300">اختياري</span></label>
                    <textarea class="ios-input min-h-20 resize-none @error('notes') ios-input-error @enderror" id="notes" name="notes" maxlength="1000">{{ old('notes', $expense->notes) }}</textarea>
                    @error('notes') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <button class="ios-primary-button" type="submit">حفظ التغييرات</button>
            <a class="rounded-2xl bg-white px-5 py-3 text-center font-semibold text-slate-600 shadow-sm ring-1 ring-slate-900/5" href="{{ route('finance.index') }}">إلغاء</a>
        </form>
    </section>
</x-app-shell>
