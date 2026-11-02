@php($editingDaily = (string) old('daily_entry') === (string) $entry->id)
<details @if($editingDaily) open @endif>
<summary class="cursor-pointer font-semibold text-ios-blue">تعديل اليومية</summary>
<form method="POST" action="{{ route('broiler.entries.daily.update', [$cycle, $entry]) }}" class="mt-3 flex min-w-64 flex-col gap-3">
@csrf @method('PUT')
<input type="hidden" name="daily_entry" value="{{ $entry->id }}">
<label class="ios-label">التاريخ<input class="ios-input" type="date" name="recorded_on" value="{{ $editingDaily ? old('recorded_on') : $entry->recorded_on->toDateString() }}" required></label>
<label class="ios-label">النفوق اليومي<input class="ios-input" type="number" name="birds" min="0" value="{{ $editingDaily ? old('birds') : $entry->birds }}" required></label>
<label class="ios-label">العلف المستهلك (شيكارة 50 كجم)<input class="ios-input" type="number" name="feed_sacks" min="0" step="0.001" value="{{ $editingDaily ? old('feed_sacks') : $entry->feed_kg / \App\Models\BroilerEntry::SACK_WEIGHT_KG }}" required></label>
<label class="ios-label">الدواء المستخدم<textarea class="ios-input" name="medicines" maxlength="1000" rows="2">{{ $editingDaily ? old('medicines') : $entry->medicines }}</textarea></label>
<label class="ios-label">ملاحظات<textarea class="ios-input" name="notes" maxlength="5000" rows="2">{{ $editingDaily ? old('notes') : $entry->notes }}</textarea></label>
<button class="ios-primary-button">حفظ التعديل</button>
</form></details>
