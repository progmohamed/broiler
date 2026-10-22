@php
    $useOld = old('vaccination_form') === (string) ($vaccination?->id ?? 'new');
    $timezone = \App\Models\BroilerVaccination::TIMEZONE;
@endphp
<form method="POST" action="{{ $vaccination ? route('broiler.vaccinations.update', [$cycle, $vaccination]) : route('broiler.vaccinations.store', $cycle) }}" class="ios-form-group">
    @csrf
    @if($vaccination) @method('PUT') @endif
    <input type="hidden" name="vaccination_form" value="{{ $vaccination?->id ?? 'new' }}">
    <label class="ios-form-row"><span class="ios-label">اسم التحصين</span><input class="ios-input" name="name" value="{{ $useOld ? old('name') : $vaccination?->name }}" maxlength="150" required></label>
    <label class="ios-form-row"><span class="ios-label">التاريخ والوقت (بتوقيت القاهرة)</span><input class="ios-input" type="datetime-local" name="scheduled_at" value="{{ $useOld ? old('scheduled_at') : $vaccination?->due_at->setTimezone($timezone)->format('Y-m-d\TH:i') }}" required></label>

    <label class="ios-form-row"><span class="ios-label">التنبيه قبل الموعد بعدد ساعات (1 إلى 168)</span><input class="ios-input" type="number" name="remind_before_hours" min="1" max="168" value="{{ $useOld ? old('remind_before_hours') : ($vaccination?->remind_before_hours ?? 24) }}" required></label>
    <label class="ios-form-row"><span class="ios-label">ملاحظات / طريقة الإعطاء</span><textarea class="ios-input" name="notes" rows="2" maxlength="5000">{{ $useOld ? old('notes') : $vaccination?->notes }}</textarea></label>
    <div class="p-4"><button class="ios-primary-button">{{ $vaccination ? 'حفظ التعديل' : 'إضافة التحصين والتنبيه' }}</button></div>
</form>
