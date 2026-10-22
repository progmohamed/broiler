<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#064e3b">
        <title>فتح التطبيق | {{ config('app.name') }}</title>

        @laravelPWA

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-ios-background text-slate-950 antialiased">
        <main class="mx-auto flex min-h-screen max-w-sm flex-col justify-center gap-6 px-6 py-12">
            <header class="text-center"><img class="mx-auto size-24 rounded-[1.8rem] shadow-xl shadow-emerald-950/15 ring-1 ring-emerald-950/10" src="{{ asset('images/icons/icon-192x192.png') }}" alt="شعار مزارع شوقي"><h1 class="mt-5 text-3xl font-bold tracking-tight">مزارع شوقي</h1><p class="mt-1 font-semibold text-ios-orange">تسمين</p><p class="mt-2 text-sm text-slate-500">أدخل رقم PIN لفتح التطبيق</p></header>
            <form class="flex flex-col gap-4" method="POST" action="{{ route('app-lock.store') }}">@csrf<div class="ios-form-group"><div class="ios-form-row"><label class="ios-label" for="pin">رقم PIN</label><input class="ios-input text-center text-2xl tracking-[0.5em] @error('pin') ios-input-error @enderror" id="pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]*" minlength="4" maxlength="8" autocomplete="current-password" autofocus required>@error('pin')<p class="ios-error text-center">{{ $message }}</p>@enderror</div></div><button class="ios-primary-button">فتح التطبيق</button></form>
        </main>
    </body>
</html>
