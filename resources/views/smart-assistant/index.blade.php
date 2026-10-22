<x-app-shell title="المساعد الذكي" active="more">
    <header class="ios-page-header gap-3">
        <div class="flex items-center justify-between gap-3">
            <p class="ios-eyebrow">تقارير المزرعة</p>
            <a class="text-xs font-semibold text-ios-blue" href="{{ route('reports.index') }}">التقارير المعتادة</a>
        </div>
        <h1 class="ios-large-title">المساعد الذكي</h1>
        <p class="text-sm leading-6 text-slate-500">اسأل عن أي بيانات مسجلة في المزرعة أو اطلب إرشادًا عامًا.</p>
    </header>

    @if (! $enabled)
        <div class="ios-status" role="status">المساعد غير مفعّل بعد. يلزم إعداد مفتاح Groq على الخادم.</div>
    @endif

    <section class="flex flex-col gap-3" aria-labelledby="assistant-suggestions-title">
        <h2 class="ios-section-title" id="assistant-suggestions-title">ابدأ بسؤال</h2>
        <div class="flex flex-wrap gap-2">
            @foreach (['ما نسبة الإنتاج اليوم؟', 'اعرض تطور الإنتاج خلال هذا الشهر', 'قارن إنتاج آخر 7 أيام بالفترة السابقة', 'ما إجمالي النافق خلال هذا الشهر وأسبابه؟', 'قارن استهلاك العلف بالإنتاج خلال آخر 7 أيام', 'ما أسماء موردي العلف؟', 'من أكثر مشتري للبيض؟', 'اعرض مبيعات البيض الآجل هذا الشهر', 'اعرض مصروفات أدوية هذا الشهر', 'ما سلف العامل محمد هذا الشهر؟', 'ما رصيد العلف والبيض اليوم؟', 'جهز ملخصًا شاملًا لأداء القطيع خلال آخر 30 يومًا'] as $suggestion)
                <button class="rounded-xl bg-white px-3 py-2 text-right text-xs leading-5 text-ios-blue shadow-sm ring-1 ring-slate-900/5 active:bg-blue-50 disabled:opacity-50" type="button" data-assistant-suggestion @disabled(! $enabled)>{{ $suggestion }}</button>
            @endforeach
        </div>
    </section>

    <section class="flex min-w-0 flex-col gap-4" aria-labelledby="assistant-conversation-title">
        <div class="flex items-center justify-between gap-3">
            <h2 class="ios-section-title" id="assistant-conversation-title">المحادثة</h2>
            <form action="{{ route('smart-assistant.destroy') }}" method="POST" data-assistant-reset>
                @csrf
                @method('DELETE')
                <button class="text-xs font-semibold text-slate-500 disabled:opacity-50" type="submit">محادثة جديدة</button>
            </form>
        </div>
        <div class="flex min-w-0 flex-col gap-5" role="log" aria-label="أسئلتك وتقارير المساعد" aria-live="polite" aria-relevant="additions" data-assistant-messages>
            <div class="ios-card p-4 text-sm leading-7 text-slate-600" data-assistant-welcome>
                اسألني بطريقتك عن أي بيانات مسجلة أو عن إرشادات عامة. أبحث في الإنتاج والنافق والعلف والمخزون والمبيعات والمصروفات والسلف، وأخبرك بوضوح فقط عندما تكون المعلومة غير مسجلة.
            </div>
            @if ($report)
                <p class="self-start rounded-2xl bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-900">{{ $question }}</p>
                @include('smart-assistant.report', ['report' => $report, 'reportId' => $reportId])
            @endif
        </div>
        <p class="text-sm text-ios-blue" role="status" data-assistant-loading hidden>جارٍ فهم سؤالك وتجهيز التقرير…</p>
        <p class="ios-error" role="alert" data-assistant-error @if (! $errors->has('question')) hidden @endif>{{ $errors->first('question') }}</p>
    </section>

    <form class="ios-card flex flex-col gap-3 p-4" action="{{ route('smart-assistant.store') }}" method="POST" data-assistant-form>
        @csrf
        <label class="ios-label" for="assistant-question">سؤالك عن المزرعة</label>
        <textarea class="ios-input min-h-24 w-full resize-y" id="assistant-question" name="question" maxlength="1000" rows="3" placeholder="مثال: قارن إنتاج هذا الأسبوع بالأسبوع السابق" required aria-describedby="assistant-question-help" @disabled(! $enabled)>{{ old('question') }}</textarea>
        <p class="text-xs leading-5 text-slate-400" id="assistant-question-help">حدّد الفترة إن أمكن. يمكنك متابعة السؤال مثل «والشهر السابق؟». يُرسل السؤال ونتائج القراءة المطلوبة فقط إلى Groq لصياغة الإجابة.</p>
        <button class="ios-primary-button disabled:opacity-50" type="submit" @disabled(! $enabled)>إرسال</button>
    </form>
</x-app-shell>
