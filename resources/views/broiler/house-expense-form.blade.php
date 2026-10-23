@php($editing = old('house_expense_form') === (string) ($expense?->id ?? 'new'))
<form method="POST" action="{{ $expense ? route('broiler.houses.expenses.update', [$house, $expense]) : route('broiler.houses.expenses.store', $house) }}" class="ios-form-group">
@csrf @if($expense) @method('PUT') @endif
<input type="hidden" name="house_expense_form" value="{{ $expense?->id ?? 'new' }}">
<label class="ios-form-row"><span class="ios-label">المصروف</span><input class="ios-input" name="description" maxlength="255" placeholder="إيجار العنبر السنوي" value="{{ $editing ? old('description') : $expense?->description }}" required></label>
<label class="ios-form-row"><span class="ios-label">إجمالي المبلغ عن المدة كاملة</span><input class="ios-input" type="number" name="amount" min="0.01" step="0.01" value="{{ $editing ? old('amount') : $expense?->amount }}" required></label>
<label class="ios-form-row"><span class="ios-label">بداية مدة الاستفادة</span><input class="ios-input" type="date" name="starts_on" value="{{ $editing ? old('starts_on') : ($expense?->starts_on->toDateString() ?? today()->toDateString()) }}" required></label>
<label class="ios-form-row"><span class="ios-label">نهاية مدة الاستفادة</span><input class="ios-input" type="date" name="ends_on" value="{{ $editing ? old('ends_on') : ($expense?->ends_on->toDateString() ?? today()->addYear()->subDay()->toDateString()) }}" required></label>
<div class="p-4"><button class="ios-primary-button">{{ $expense ? 'حفظ التعديل' : 'إضافة المصروف' }}</button></div>
</form>
