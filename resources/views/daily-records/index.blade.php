<x-app-shell title="لوحة اليوم" active="records">
    <header class="ios-page-header gap-3">
        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <img class="size-14 shrink-0 rounded-[1.1rem] shadow-sm ring-1 ring-emerald-950/10" src="{{ asset('images/icons/icon-192x192.png') }}" alt="شعار مزارع شوقي">
                <div class="min-w-0">
                    <p class="ios-eyebrow">مزارع شوقي <span class="me-1 rounded-full bg-orange-50 px-2 py-0.5 text-[0.65rem] text-ios-orange">بيّاض</span></p>
                    <h1 class="ios-large-title">{{ $isToday ? 'لوحة اليوم' : 'لوحة يوم محدد' }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $setting?->farm_name ? $setting->farm_name.' · ' : '' }}{{ $selectedDate->translatedFormat('l، j F Y') }}</p>
                </div>
            </div>
            <span class="shrink-0 rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm ring-1 ring-slate-900/5">
                @if ($ageInWeeks === null)
                    العمر غير محدد
                @else
                    {{ number_format($ageInWeeks) }} أسبوع و{{ number_format($ageRemainingDays) }} يوم
                @endif
            </span>
        </div>

    </header>

    @if (session('status'))
        <div class="ios-status" role="status">{{ session('status') }}</div>
    @endif

    @if ($alerts !== [])
        <section class="flex flex-col gap-2" aria-labelledby="alerts-title">
            <div class="flex items-center justify-between px-1">
                <h2 class="ios-section-title p-0" id="alerts-title">يحتاج انتباهك</h2>
                <span class="rounded-full bg-ios-red px-2 py-0.5 text-[0.65rem] font-bold text-white">{{ count($alerts) }}</span>
            </div>
            @foreach ($alerts as $alert)
                @php
                    $alertClasses = match ($alert['tone']) {
                        'red' => 'bg-red-50 text-red-800 ring-red-200/80',
                        'orange' => 'bg-orange-50 text-orange-800 ring-orange-200/80',
                        'blue' => 'bg-blue-50 text-blue-800 ring-blue-200/80',
                        default => 'bg-white text-slate-700 ring-slate-900/5',
                    };
                @endphp
                <div class="flex items-center justify-between gap-3 rounded-2xl px-4 py-3 text-sm ring-1 {{ $alertClasses }}">
                    <p class="leading-6">{{ $alert['message'] }}</p>
                    <a class="shrink-0 font-semibold text-ios-blue" href="{{ $alert['route'] }}">{{ $alert['action'] }}</a>
                </div>
            @endforeach
        </section>
    @endif

    <section class="flex flex-col gap-2" aria-labelledby="inventory-summary-title">
        <h2 class="ios-section-title" id="inventory-summary-title">المخزون الحالي</h2>
        <dl class="grid grid-cols-2 gap-2">
            <div class="ios-card flex min-h-32 flex-col p-4">
                <dt class="text-xs font-semibold text-slate-500">العلف المتبقي بالمخزن</dt>
                <dd class="mt-2 text-3xl font-bold tracking-tight {{ $remainingFeedKilograms < 0 ? 'text-ios-red' : 'text-ios-green' }}">{{ number_format($remainingFeedSacks, 1) }}</dd>
                <dd class="mt-auto pt-2 text-xs text-slate-400">شيكارة · {{ number_format($remainingFeedKilograms) }} كجم</dd>
            </div>
            <div class="ios-card flex min-h-32 flex-col p-4">
                <dt class="text-xs font-semibold text-slate-500">كراتين البيض بالمخزن</dt>
                <dd class="mt-2 text-3xl font-bold tracking-tight {{ $remainingEggs < 0 ? 'text-ios-red' : 'text-ios-orange' }}">{{ number_format($remainingEggCartons, 1) }}</dd>
                <dd class="mt-auto pt-2 text-xs text-slate-400">{{ number_format($remainingEggs) }} بيضة متاحة</dd>
            </div>
        </dl>
        <div class="ios-card grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 px-2 py-3 text-center">
            <div class="px-2"><p class="text-[0.65rem] text-slate-400">العمر بالأيام</p><p class="mt-1 font-bold">{{ $ageInDays === null ? '—' : number_format($ageInDays) }}</p></div>
            <div class="px-2"><p class="text-[0.65rem] text-slate-400">العمر بالأسابيع</p><p class="mt-1 font-bold">{{ $ageInWeeks === null ? '—' : number_format($ageInWeeks) }}</p></div>
            <div class="px-2"><p class="text-[0.65rem] text-slate-400">يكفي العلف</p><p class="mt-1 font-bold">{{ $estimatedFeedDays === null ? '—' : number_format($estimatedFeedDays).' يوم' }}</p></div>
        </div>
        <p class="px-1 text-[0.65rem] text-slate-400">الشيكارة = 50 كجم · الكرتونة = 30 بيضة</p>
    </section>

    <section class="flex flex-col gap-2" id="daily-entry" aria-labelledby="daily-form-title">
        <div class="flex items-end justify-between px-1">
            <div>
                <h2 class="ios-section-title p-0" id="daily-form-title">{{ $record ? 'تحديث السجل' : 'سجل جديد' }}</h2>
                <p class="mt-1 text-xs text-slate-400">البيانات الأساسية أولًا، والتفاصيل اختيارية.</p>
            </div>
            @if ($record)<span class="rounded-full bg-green-50 px-2.5 py-1 text-[0.65rem] font-semibold text-green-700">تم التسجيل</span>@endif
        </div>

        <form class="flex flex-col gap-4" method="POST" action="{{ route('daily-records.store') }}">
            @csrf
            <div class="ios-form-group">
                <div class="ios-form-row">
                    <label class="ios-label" for="recorded_on">تاريخ السجل</label>
                    <input class="ios-input @error('recorded_on') ios-input-error @enderror" id="recorded_on" name="recorded_on" type="date" dir="ltr" value="{{ old('recorded_on', $selectedDate->toDateString()) }}" onchange="window.location.href = '{{ route('daily-records.index') }}?recorded_on=' + this.value + '#daily-entry'" required>
                    @error('recorded_on') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="feed_sacks">عدد شكاير العلف</label>
                    <input class="ios-input @error('feed_sacks') ios-input-error @enderror" id="feed_sacks" name="feed_sacks" type="number" min="0" step="0.5" inputmode="decimal" value="{{ old('feed_sacks', $record?->feedInSacks()) }}" placeholder="0" required autofocus>
                    <p class="text-xs text-slate-400">الشيكارة الواحدة تساوي 50 كجم.</p>
                    @error('feed_sacks') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="deaths">النافق</label>
                    <input class="ios-input @error('deaths') ios-input-error @enderror" id="deaths" name="deaths" type="number" min="0" inputmode="numeric" value="{{ old('deaths', $record?->deaths) }}" placeholder="0" required>
                    @error('deaths') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="production">إنتاج البيض</label>
                    <input class="ios-input @error('production') ios-input-error @enderror" id="production" name="production" type="number" min="0" inputmode="numeric" value="{{ old('production', $record?->production) }}" placeholder="0" required>
                    @error('production') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="medicines">الأدوية <span class="font-normal text-slate-300">اختياري</span></label>
                    <textarea class="ios-input min-h-20 resize-none @error('medicines') ios-input-error @enderror" id="medicines" name="medicines" maxlength="1000" placeholder="اسم الدواء والجرعة">{{ old('medicines', $record?->medicines) }}</textarea>
                    @error('medicines') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
                <div class="ios-form-row">
                    <label class="ios-label" for="notes">ملاحظات <span class="font-normal text-slate-300">اختياري</span></label>
                    <textarea class="ios-input min-h-20 resize-none @error('notes') ios-input-error @enderror" id="notes" name="notes" maxlength="1000" placeholder="أي تفاصيل إضافية">{{ old('notes', $record?->notes) }}</textarea>
                    @error('notes') <p class="ios-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <button class="ios-primary-button" type="submit">{{ $record ? 'تحديث السجل اليومي' : 'حفظ السجل اليومي' }}</button>
        </form>
    </section>

    <section class="flex flex-col gap-2" aria-labelledby="weekly-performance-title">
        <div class="flex items-baseline justify-between px-1">
            <div><h2 class="ios-section-title p-0" id="weekly-performance-title">أداء آخر 7 أيام</h2><p class="mt-1 text-xs text-slate-400">{{ $weeklyRecordedDays }} أيام مسجلة من 7</p></div>
            <span class="text-xs font-semibold text-ios-blue">متوسط {{ $weeklyAverageProduction === null ? '—' : number_format($weeklyAverageProduction) }} بيضة</span>
        </div>
        <div class="ios-card p-4">
            <div class="flex h-36 items-end justify-between gap-2 border-b border-slate-200 pb-2" aria-label="رسم إنتاج البيض خلال آخر سبعة أيام">
                @foreach ($productionTrend as $trendDay)
                    <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1" title="{{ $trendDay['date']->translatedFormat('j F') }}: {{ number_format($trendDay['production']) }} بيضة">
                        <span class="text-[0.55rem] font-semibold text-slate-400">{{ $trendDay['hasRecord'] ? number_format($trendDay['production']) : '—' }}</span>
                        <span class="w-full max-w-7 rounded-t-lg {{ $trendDay['hasRecord'] ? 'bg-ios-orange' : 'bg-slate-200' }}" style="height: {{ $trendDay['height'] }}%"></span>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex justify-between gap-2 text-center">
                @foreach ($productionTrend as $trendDay)<span class="min-w-0 flex-1 text-[0.6rem] text-slate-400">{{ $trendDay['date']->translatedFormat('D') }}</span>@endforeach
            </div>
            <dl class="mt-4 grid grid-cols-2 divide-x divide-x-reverse divide-slate-200 border-t border-slate-100 pt-4 text-center">
                <div class="px-1"><dt class="text-[0.65rem] text-slate-400">متوسط العلف</dt><dd class="mt-1 font-bold text-ios-blue">{{ $weeklyAverageFeedKilograms === null ? '—' : number_format($weeklyAverageFeedKilograms / 50, 1) }} شيكارة</dd></div>
                <div class="px-1"><dt class="text-[0.65rem] text-slate-400">إجمالي النافق</dt><dd class="mt-1 font-bold {{ $weeklyTotalDeaths > 0 ? 'text-ios-red' : 'text-ios-green' }}">{{ number_format($weeklyTotalDeaths) }}</dd></div>
            </dl>
        </div>
    </section>

    <section class="flex flex-col gap-3" id="recent-records" aria-labelledby="recent-records-title">
        <div class="flex items-baseline justify-between px-1">
            <div><h2 class="ios-section-title p-0" id="recent-records-title">{{ $showAllRecords ? 'كل السجلات' : 'آخر 3 سجلات' }}</h2><p class="mt-1 text-xs text-slate-400">{{ $records->total() }} سجل إجمالًا</p></div>
            <a class="text-xs font-semibold text-ios-blue" href="{{ $showAllRecords ? route('daily-records.index', ['recorded_on' => $selectedDate->toDateString()]).'#recent-records' : route('daily-records.index', ['recorded_on' => $selectedDate->toDateString(), 'show' => 'all']).'#recent-records' }}">{{ $showAllRecords ? 'عرض آخر 3' : 'عرض كل السجلات' }}</a>
        </div>

        @forelse ($records as $recentRecord)
            <article class="ios-card p-4">
                <div class="flex items-center justify-between gap-3"><p class="text-sm font-semibold text-slate-600">{{ $recentRecord['record']->recorded_on->translatedFormat('l، j F Y') }}</p><a class="text-xs font-semibold text-ios-blue" href="{{ route('daily-records.index', ['recorded_on' => $recentRecord['record']->recorded_on->toDateString()]).'#daily-entry' }}">تعديل</a></div>
                <dl class="mt-3 grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 text-center">
                    <div class="px-1"><dt class="text-xs text-slate-400">العلف</dt><dd class="mt-1 text-lg font-bold text-ios-blue">{{ number_format($recentRecord['record']->feedInSacks(), 1) }}</dd><dd class="text-[0.6rem] text-slate-400">شيكارة · {{ number_format($recentRecord['record']->feed) }} كجم</dd></div>
                    <div class="px-1"><dt class="text-xs text-slate-400">النافق</dt><dd class="mt-1 text-lg font-bold text-ios-red">{{ number_format($recentRecord['record']->deaths) }}</dd></div>
                    <div class="px-1">
                        @php($productionChange = $recentRecord['productionChange'])
                        <dt class="flex items-baseline justify-center gap-1 whitespace-nowrap text-xs text-slate-400">
                            <span>الإنتاج</span>
                            @if ($productionChange !== null)
                                <span class="inline-flex items-center gap-0.5 text-[0.6rem] font-semibold {{ $productionChange['difference'] > 0 ? 'text-green-700' : ($productionChange['difference'] < 0 ? 'text-red-700' : 'text-slate-500') }}" title="فرق نسبة الإنتاج إلى عدد القطيع عن اليوم السابق (نقطة مئوية)">
                                    <span aria-hidden="true">{{ $productionChange['difference'] > 0 ? '↑' : ($productionChange['difference'] < 0 ? '↓' : '—') }}</span>
                                    <span class="sr-only">{{ $productionChange['difference'] > 0 ? 'زيادة' : ($productionChange['difference'] < 0 ? 'انخفاض' : 'دون تغيير') }}</span>
                                    <span dir="ltr">{{ number_format(abs($productionChange['percentage']), 1) }}%</span>
                                    <span class="sr-only">فرق نسبة الإنتاج إلى عدد القطيع عن اليوم السابق (نقطة مئوية)</span>
                                </span>
                            @endif
                        </dt>
                        <dd class="mt-1 text-lg font-bold text-ios-orange">{{ number_format($recentRecord['record']->production) }}</dd>
                        <dd class="text-[0.6rem] font-medium text-slate-400">{{ number_format($recentRecord['record']->productionInCartons(), 1) }} كرتونة</dd>
                    </div>
                </dl>
                <dl class="mt-3 grid grid-cols-3 gap-2 border-t border-slate-100 pt-3 text-center">
                    <div><dt class="text-[0.6rem] text-slate-400">العمر بالأيام</dt><dd class="mt-1 text-sm font-semibold">{{ $recentRecord['ageInDays'] === null ? '—' : number_format($recentRecord['ageInDays']) }}</dd></div>
                    <div><dt class="text-[0.6rem] text-slate-400">العمر بالأسابيع</dt><dd class="mt-1 text-sm font-semibold">{{ $recentRecord['ageInWeeks'] === null ? '—' : number_format($recentRecord['ageInWeeks']) }}</dd></div>
                    <div><dt class="text-[0.6rem] text-slate-400">نسبة الإنتاج</dt><dd class="mt-1 text-sm font-semibold text-ios-green">{{ $recentRecord['productionPercentage'] === null ? '—' : number_format($recentRecord['productionPercentage'], 1).'%' }}</dd></div>
                </dl>
                @if ($recentRecord['record']->medicines || $recentRecord['record']->notes)
                    <div class="mt-3 flex flex-col gap-1 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-500">
                        @if ($recentRecord['record']->medicines)<p><span class="font-semibold text-slate-700">الأدوية:</span> {{ $recentRecord['record']->medicines }}</p>@endif
                        @if ($recentRecord['record']->notes)<p><span class="font-semibold text-slate-700">ملاحظات:</span> {{ $recentRecord['record']->notes }}</p>@endif
                    </div>
                @endif
            </article>
        @empty
            <div class="ios-card px-5 py-10 text-center text-sm text-slate-400">لا توجد سجلات حتى الآن. أضف أول سجل للقطيع أعلاه.</div>
        @endforelse

        @if ($records->hasPages())
            <nav class="ios-card flex items-center justify-between gap-3 p-2" aria-label="صفحات السجلات اليومية">
                @if ($records->onFirstPage())<span class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-300">السابق</span>@else<a class="rounded-xl px-3 py-2 text-sm font-semibold text-ios-blue active:bg-blue-50" href="{{ $records->previousPageUrl() }}#recent-records" rel="prev">السابق</a>@endif
                <span class="text-xs text-slate-400">صفحة {{ $records->currentPage() }} من {{ $records->lastPage() }}</span>
                @if ($records->hasMorePages())<a class="rounded-xl px-3 py-2 text-sm font-semibold text-ios-blue active:bg-blue-50" href="{{ $records->nextPageUrl() }}#recent-records" rel="next">التالي</a>@else<span class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-300">التالي</span>@endif
            </nav>
        @endif
    </section>
</x-app-shell>
