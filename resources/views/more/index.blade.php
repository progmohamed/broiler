<x-app-shell title="المزيد" active="more">
    <header class="ios-page-header">
        <p class="ios-eyebrow">الإدارة والتنظيم</p>
        <h1 class="ios-large-title">المزيد</h1>
        <p class="text-sm text-slate-500">الإعدادات والأقسام الإدارية الأقل استخدامًا.</p>
    </header>

    <section class="flex flex-col gap-2" aria-labelledby="more-settings-title">
        <h2 class="ios-section-title" id="more-settings-title">الإعدادات</h2>
        <div class="ios-card divide-y divide-slate-200/80">
            <a class="flex items-center gap-4 p-4 transition active:bg-slate-100" href="{{ route('farm-settings.edit') }}">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <svg aria-hidden="true" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9.6 3.9 10 2.75h4l.4 1.15a2 2 0 0 0 2.55 1.2l1.1-.4 2 3.46-.9.75a2 2 0 0 0 0 3.08l.9.75-2 3.46-1.1-.4a2 2 0 0 0-2.55 1.2L14 18.25h-4l-.4-1.15a2 2 0 0 0-2.55-1.2l-1.1.4-2-3.46.9-.75a2 2 0 0 0 0-3.08l-.9-.75 2-3.46 1.1.4A2 2 0 0 0 9.6 3.9Z" /><circle cx="12" cy="10.5" r="2.5" /></svg>
                </span>
                <span class="flex-1 font-semibold">إعدادات المزرعة</span>
                <svg aria-hidden="true" class="size-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14 18-6-6 6-6" /></svg>
            </a>
            <a class="flex items-center gap-4 p-4 transition active:bg-slate-100" href="{{ route('backups.index') }}"><span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-ios-blue">↥</span><span class="flex-1 font-semibold">النسخ الاحتياطي والاستعادة</span><span class="text-slate-300">‹</span></a>
            <a class="flex items-center gap-4 p-4 transition active:bg-slate-100" href="{{ route('worker-advances.index') }}"><span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-ios-orange">$</span><span class="flex-1 font-semibold">سلف العمال</span><span class="text-slate-300">‹</span></a>
            @if (session('farm_unlocked'))<form method="POST" action="{{ route('app-lock.destroy') }}">@csrf<button class="flex w-full items-center gap-4 p-4 text-right text-ios-red transition active:bg-slate-100"><span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-red-50">⌁</span><span class="flex-1 font-semibold">قفل التطبيق الآن</span></button></form>@endif
        </div>
    </section>

    <section class="flex flex-col gap-2" aria-labelledby="more-records-title">
        <h2 class="ios-section-title" id="more-records-title">السجلات</h2>
        <div class="ios-card divide-y divide-slate-200/80">
            <a class="flex items-center gap-4 p-4 transition active:bg-slate-100" href="{{ route('smart-assistant.index') }}"><span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-ios-blue">✦</span><span class="flex-1 font-semibold">المساعد الذكي</span><span class="text-slate-300">‹</span></a>
            <a class="flex items-center gap-4 p-4 transition active:bg-slate-100" href="{{ route('reports.index') }}"><span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-ios-blue">⌁</span><span class="flex-1 font-semibold">التقارير</span><span class="text-slate-300">‹</span></a>
            <a class="flex items-center gap-4 p-4 transition active:bg-slate-100" href="{{ route('break-even.index') }}"><span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">◎</span><span class="flex-1 font-semibold">توقع سداد المديونية <small class="font-normal text-slate-400">تجريبي</small></span><span class="text-slate-300">‹</span></a>
            <a class="flex items-center gap-4 p-4 transition active:bg-slate-100" href="{{ route('feed-deliveries.index') }}"><span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-ios-orange">↧</span><span class="flex-1 font-semibold">كل توريدات العلف</span><span class="text-slate-300">‹</span></a>
            <a class="flex items-center gap-4 p-4 transition active:bg-slate-100" href="{{ route('egg-sales.index') }}"><span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-green-50 text-ios-green">◯</span><span class="flex-1 font-semibold">كل مبيعات البيض</span><span class="text-slate-300">‹</span></a>
        </div>
    </section>

</x-app-shell>
