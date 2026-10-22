@props(['title'])
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#064e3b">
<title>{{ $title }} | {{ config('app.name') }}</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ios-background text-slate-950 antialiased">
<header class="border-b border-emerald-900/10 bg-white"><nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-5" aria-label="التنقل الرئيسي">
<a href="{{ route('broiler.index') }}" class="text-xl font-bold text-emerald-900">مزارع شوقي <span class="text-sm font-normal">/ التسمين</span></a>
<a href="{{ route('broiler.index') }}" class="text-sm font-semibold text-ios-blue">الدورات والعنابر</a>
</nav></header>
<main class="mx-auto flex max-w-6xl flex-col gap-6 p-5 pb-16">
@if(session('status'))<p class="ios-status" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="rounded-2xl bg-red-50 p-4 text-red-800" role="alert"><p class="font-bold">راجع البيانات التالية</p><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
{{ $slot }}
</main></body></html>
