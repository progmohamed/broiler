<details class="mt-2" @if(old('cost_entry') == $entry->id) open @endif>
<summary class="cursor-pointer font-semibold text-ios-blue">تعديل المصروف</summary>
<form method="POST" action="{{ route('broiler.entries.cost.update', [$cycle, $entry]) }}" class="flex min-w-60 flex-col gap-3 py-3">
@csrf @method('PUT')<input type="hidden" name="cost_entry" value="{{ $entry->id }}">
@php($editing = old('cost_entry') == $entry->id)
@if($entry->kind === 'feed_delivery')
<label class="ios-label">سعر الطن<input class="ios-input" name="price_per_ton" type="number" min="0" step="0.01" value="{{ $editing ? old('price_per_ton') : ($entry->price_per_ton ?? $entry->unit_price * 1000) }}" required></label>
<label class="ios-label">النقل<input class="ios-input" name="shipping_cost" type="number" min="0" step="0.01" value="{{ $editing ? old('shipping_cost') : $entry->shipping_cost }}" required></label>
<label class="ios-label">طريقة الدفع<select class="ios-input" name="payment_status" required><option value="">اختر</option>@foreach(\App\Models\BroilerEntry::PAYMENT_STATUSES as $value => $label)<option value="{{ $value }}" @selected(($editing ? old('payment_status') : $entry->payment_status) === $value)>{{ $label }}</option>@endforeach</select></label>
@else
<label class="ios-label">المبلغ<input class="ios-input" type="number" name="amount" min="0" step="0.01" value="{{ $editing ? old('amount') : $entry->amount }}" required></label>
<label class="ios-label">البيان<input class="ios-input" name="description" maxlength="255" value="{{ $editing ? old('description') : $entry->description }}" required></label>
@if($entry->kind === 'expense')<label class="ios-label">التصنيف<select class="ios-input" name="category" required>@foreach(\App\Models\BroilerEntry::EXPENSE_CATEGORIES as $value => $label)<option value="{{ $value }}" @selected(($editing ? old('category') : $entry->category) === $value)>{{ $label }}</option>@endforeach</select></label>@endif
@endif
<button class="ios-primary-button">حفظ التعديل</button>
</form></details>
