<x-app-shell title="إعدادات المزرعة" active="more">
    <header class="ios-page-header">
        <p class="ios-eyebrow">بيانات المزرعة</p>
        <h1 class="ios-large-title">الإعدادات</h1>
        <p class="text-sm text-slate-500">البيانات الأساسية المستخدمة في حسابات القطيع.</p>
    </header>

    @if (session('status'))
        <div class="ios-status" role="status">{{ session('status') }}</div>
    @endif

    <section class="flex flex-col gap-2" aria-labelledby="farm-settings-title">
        <h2 class="ios-section-title" id="farm-settings-title">معلومات القطيع</h2>

        <form class="flex flex-col gap-4" method="POST" action="{{ route('farm-settings.update') }}">
            @csrf
            @method('PUT')

            <div class="ios-form-group">
                <div class="ios-form-row">
                    <label class="ios-label" for="farm_name">اسم المزرعة <span class="font-normal text-slate-300">اختياري</span></label>
                    <input class="ios-input @error('farm_name') ios-input-error @enderror" id="farm_name" name="farm_name" type="text" maxlength="100" value="{{ old('farm_name', $setting?->farm_name) }}" placeholder="اسم المزرعة" autocomplete="organization">
                    @error('farm_name') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="herd_birth_date">تاريخ ميلاد القطيع</label>
                    <input class="ios-input @error('herd_birth_date') ios-input-error @enderror" id="herd_birth_date" name="herd_birth_date" type="date" dir="ltr" max="{{ today()->toDateString() }}" value="{{ old('herd_birth_date', $setting?->herd_birth_date?->toDateString()) }}" required>
                    @error('herd_birth_date') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="total_herd_count">إجمالي عدد القطيع</label>
                    <input class="ios-input @error('total_herd_count') ios-input-error @enderror" id="total_herd_count" name="total_herd_count" type="number" min="1" inputmode="numeric" value="{{ old('total_herd_count', $setting?->total_herd_count) }}" placeholder="0" required>
                    <p class="text-xs text-slate-400">عدد القطيع عند بداية دورة الإنتاج.</p>
                    @error('total_herd_count') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <button class="ios-primary-button" type="submit">حفظ إعدادات المزرعة</button>
        </form>
    </section>

    <section class="flex flex-col gap-2" aria-labelledby="security-settings-title">
        <h2 class="ios-section-title" id="security-settings-title">قفل التطبيق</h2>
        <form class="flex flex-col gap-4" method="POST" action="{{ route('farm-settings.update') }}">
            @csrf @method('PUT')
            <input name="farm_name" type="hidden" value="{{ $setting?->farm_name }}"><input name="herd_birth_date" type="hidden" value="{{ $setting?->herd_birth_date?->toDateString() }}"><input name="total_herd_count" type="hidden" value="{{ $setting?->total_herd_count }}">
            <div class="ios-form-group">
                <div class="ios-form-row"><label class="ios-label" for="new_pin">رقم PIN جديد <span class="font-normal text-slate-300">4–8 أرقام</span></label><input class="ios-input @error('new_pin') ios-input-error @enderror" id="new_pin" name="new_pin" type="password" inputmode="numeric" minlength="4" maxlength="8" autocomplete="new-password">@error('new_pin')<p class="ios-error">{{ $message }}</p>@enderror</div>
                <div class="ios-form-row"><label class="ios-label" for="new_pin_confirmation">تأكيد رقم PIN</label><input class="ios-input" id="new_pin_confirmation" name="new_pin_confirmation" type="password" inputmode="numeric" minlength="4" maxlength="8" autocomplete="new-password"></div>
                @if ($setting?->app_pin)<label class="ios-form-row flex-row items-center gap-3"><input class="size-5 accent-ios-red" name="remove_pin" type="checkbox" value="1"><span class="text-sm font-semibold text-ios-red">إلغاء قفل التطبيق الحالي</span></label>@endif
            </div>
            <button class="ios-primary-button">{{ $setting?->app_pin ? 'تحديث إعداد القفل' : 'تفعيل قفل التطبيق' }}</button>
        </form>
        <p class="px-1 text-xs leading-5 text-slate-400">احتفظ برقم PIN في مكان آمن؛ لا يمكن عرض الرقم بعد حفظه.</p>
    </section>

    <section class="ios-card p-4" aria-labelledby="about-app-title">
        <div class="flex items-center gap-3">
            <div class="flex size-11 items-center justify-center rounded-xl bg-ios-blue text-white">
                <svg aria-hidden="true" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.5v.01M12 11.5v4M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z" />
                </svg>
            </div>
            <div>
                <h2 class="font-semibold" id="about-app-title">حول البيانات</h2>
                <p class="mt-0.5 text-xs leading-5 text-slate-400">تُستخدم هذه القيم لحساب عمر القطيع ونسبة إنتاجه في السجل اليومي.</p>
            </div>
        </div>
    </section>
</x-app-shell>
