@props(['title', 'active'])

<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#064e3b">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <title>{{ $title }} | {{ config('app.name') }}</title>

        @laravelPWA

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-ios-background text-slate-950 antialiased">
        <main class="mx-auto flex min-h-screen max-w-lg flex-col gap-6 px-4 pb-32 pt-[max(1.5rem,env(safe-area-inset-top))] sm:px-6">
            {{ $slot }}
        </main>

        <nav class="ios-tab-bar" aria-label="التنقل الرئيسي">
            <div class="mx-auto grid max-w-lg grid-cols-5 px-1">
                <a class="ios-tab {{ $active === 'records' ? 'text-ios-blue' : 'text-slate-500' }}" href="{{ route('daily-records.index') }}" @if ($active === 'records') aria-current="page" @endif>
                    <svg aria-hidden="true" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75h10.5a1.5 1.5 0 0 1 1.5 1.5v13.5a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Z" />
                        <path stroke-linecap="round" d="M8.5 8h7M8.5 12h7M8.5 16h4" />
                    </svg>
                    <span>اليومية</span>
                </a>
                <a class="ios-tab {{ $active === 'inventory' ? 'text-ios-blue' : 'text-slate-500' }}" href="{{ route('inventory.index') }}" @if ($active === 'inventory') aria-current="page" @endif>
                    <svg aria-hidden="true" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 8 7.5-4 7.5 4v9L12 21l-7.5-4V8Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 8 7.5 4 7.5-4M12 12v9" />
                    </svg>
                    <span>المخزون</span>
                </a>
                <button class="ios-tab text-ios-blue" type="button" data-quick-add-open aria-label="إضافة سريعة" aria-haspopup="dialog">
                    <span class="flex size-11 items-center justify-center rounded-full bg-ios-blue text-white shadow-lg shadow-blue-500/25">
                        <svg aria-hidden="true" class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                        </svg>
                    </span>
                    <span>إضافة</span>
                </button>
                <a class="ios-tab {{ $active === 'finance' ? 'text-ios-blue' : 'text-slate-500' }}" href="{{ route('finance.index') }}" @if ($active === 'finance') aria-current="page" @endif>
                    <svg aria-hidden="true" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z" />
                        <path stroke-linecap="round" d="M7.5 15.5V12m4.5 3.5v-7m4.5 7v-5" />
                    </svg>
                    <span>المالية</span>
                </a>
                <a class="ios-tab {{ $active === 'more' ? 'text-ios-blue' : 'text-slate-500' }}" href="{{ route('more.index') }}" @if ($active === 'more') aria-current="page" @endif>
                    <svg aria-hidden="true" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <circle cx="5" cy="12" r="1.25" fill="currentColor" stroke="none" />
                        <circle cx="12" cy="12" r="1.25" fill="currentColor" stroke="none" />
                        <circle cx="19" cy="12" r="1.25" fill="currentColor" stroke="none" />
                    </svg>
                    <span>المزيد</span>
                </a>
            </div>
        </nav>

        <dialog class="ios-quick-add-dialog" data-quick-add-dialog aria-labelledby="quick-add-title">
            <div class="flex flex-col gap-2">
                <section class="ios-action-sheet">
                    <div class="border-b border-slate-200/80 px-4 py-3 text-center">
                        <h2 class="font-semibold" id="quick-add-title">إضافة سريعة</h2>
                        <p class="mt-0.5 text-xs text-slate-400">اختر العملية التي تريد تسجيلها</p>
                    </div>
                    <a class="ios-action-row" href="{{ route('daily-records.index') }}#daily-entry">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-blue-50 text-ios-blue">
                            <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h3" /></svg>
                        </span>
                        <span>سجل يومي</span>
                    </a>
                    <a class="ios-action-row" href="{{ route('finance.index') }}#feed-purchase-form">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-orange-50 text-ios-orange">
                            <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5h9l2.25 5.25-1.5 9.75H6.75l-1.5-9.75L7.5 4.5Z" /></svg>
                        </span>
                        <span>شراء علف</span>
                    </a>
                    <a class="ios-action-row" href="{{ route('sales.index') }}#egg-sale-form">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-green-50 text-ios-green">
                            <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.8 9.25c.75 1.65 1.2 3.35 1.2 4.75a5 5 0 0 1-10 0c0-1.4.45-3.1 1.2-4.75C9.15 7.15 10.55 5 12 5s2.85 2.15 3.8 4.25Z" /></svg>
                        </span>
                        <span>بيع بيض</span>
                    </a>
                    <a class="ios-action-row" href="{{ route('sales.index') }}#other-sale-form">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                            <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5h15M6 4.5h12a1.5 1.5 0 0 1 1.5 1.5v12A1.5 1.5 0 0 1 18 19.5H6A1.5 1.5 0 0 1 4.5 18V6A1.5 1.5 0 0 1 6 4.5ZM8 12h8" /></svg>
                        </span>
                        <span>بيع آخر</span>
                    </a>
                    <a class="ios-action-row" href="{{ route('finance.index') }}#expense-form">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-red-50 text-ios-red">
                            <svg aria-hidden="true" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11ZM8 12h8" /></svg>
                        </span>
                        <span>إضافة مصروف</span>
                    </a>
                </section>
                <form method="dialog">
                    <button class="min-h-13 w-full rounded-[1.35rem] bg-white/95 px-4 py-3 text-center text-base font-semibold text-ios-blue shadow-xl backdrop-blur-xl active:bg-slate-100" type="submit">إلغاء</button>
                </form>
            </div>
        </dialog>
    </body>
</html>
