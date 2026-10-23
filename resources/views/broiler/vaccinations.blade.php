<details id="vaccinations" class="ios-card" @if(old('vaccination_form')) open @endif>
    <summary class="cursor-pointer p-5 text-lg font-bold">جدول تحصينات الدورة <span class="text-sm font-normal text-slate-500">({{ $vaccinations->count() }})</span></summary>
    <div class="flex flex-col gap-4 px-5 pb-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a class="font-semibold text-ios-blue" href="{{ route('broiler.vaccinations.calendar', $cycle) }}">تنزيل المواعيد لتقويم الموبايل</a>
    </div>
    @forelse($vaccinations as $vaccination)
        <article class="ios-card flex flex-col gap-3 p-5">
            <div class="flex flex-wrap justify-between gap-2"><h3 class="font-bold">{{ $vaccination->name }}</h3><span class="text-sm {{ $vaccination->completed_at ? 'text-emerald-700' : 'text-amber-700' }}">{{ $vaccination->statusLabel() }}</span></div>
            <p>{{ $vaccination->due_at->setTimezone(\App\Models\BroilerVaccination::TIMEZONE)->format('Y-m-d H:i') }} <span class="text-sm text-slate-500">(توقيت القاهرة)</span></p>
            <p class="text-sm text-slate-500">تنبيه قبل {{ $vaccination->remind_before_hours }} ساعة · {{ $vaccination->notes }}</p>
            @if($vaccination->completed_at)
                <p class="text-sm text-emerald-700">تم التنفيذ {{ $vaccination->completed_at->setTimezone(\App\Models\BroilerVaccination::TIMEZONE)->format('Y-m-d H:i') }}</p>
            @elseif(!$cycle->closed_on)
                <div class="flex flex-wrap items-center gap-4">
                    <form method="POST" action="{{ route('broiler.vaccinations.complete', [$cycle, $vaccination]) }}">@csrf<button class="rounded-xl bg-emerald-700 px-4 py-2 font-semibold text-white">تم التحصين</button></form>
                    <form method="POST" action="{{ route('broiler.vaccinations.destroy', [$cycle, $vaccination]) }}" data-confirm-delete data-confirm-message="حذف موعد التحصين وإلغاء تنبيهه؟">@csrf @method('DELETE')<button class="text-red-700">حذف الموعد</button></form>
                </div>
                <details @if(old('vaccination_form') === (string) $vaccination->id) open @endif><summary class="cursor-pointer font-semibold text-ios-blue">تعديل الموعد</summary>@include('broiler.vaccination-form', ['vaccination' => $vaccination])</details>
            @endif
        </article>
    @empty
        <p class="ios-card p-5 text-slate-500">لم تتم إضافة تحصينات لهذه الدورة.</p>
    @endforelse
    @if(!$cycle->closed_on)
        <details class="ios-card" @if(old('vaccination_form') === 'new') open @endif><summary class="cursor-pointer p-5 font-bold text-ios-blue">+ إضافة تحصين</summary>@include('broiler.vaccination-form', ['vaccination' => null])</details>
    @endif
    </div>
</details>
