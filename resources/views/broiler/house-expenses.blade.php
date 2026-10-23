<x-broiler-shell :title="'مصروفات عنبر '.$house->name">
<div class="ios-page-header"><p class="ios-eyebrow">{{ $house->name }}</p><h1 class="ios-large-title">الإيجار والمصروفات التأسيسية</h1><p class="text-sm text-slate-500">يتوزع المبلغ حسب أيام كل دورة داخل المدة، حتى اليوم أو إغلاق الدورة. الباقي يشمل أيام الخلو والمدد القادمة.</p></div>
<section class="grid gap-3 sm:grid-cols-3">
@php($allocatedTotal = collect($allocations)->sum(fn ($rows) => array_sum($rows)))
@foreach(['إجمالي مصروفات العنبر' => $expenses->sum('amount'), 'المحمّل على الدورات حتى الآن' => $allocatedTotal, 'غير محمّل على الدورات' => $expenses->sum('amount') - $allocatedTotal] as $label => $amount)
<div class="ios-card p-4"><p class="ios-label">{{ $label }}</p><p class="text-xl font-bold">{{ number_format($amount, 2) }}</p></div>
@endforeach
</section>
<details class="ios-card" @if(old('house_expense_form') === 'new') open @endif><summary class="cursor-pointer p-5 font-bold text-ios-blue">+ مصروف للعنبر</summary>@include('broiler.house-expense-form', ['expense' => null])</details>
@forelse($expenses as $expense)
<section class="ios-card p-5">
<h2 class="text-lg font-bold">{{ $expense->description }} · {{ number_format($expense->amount, 2) }}</h2>
<p class="my-3 text-sm text-slate-500">{{ $expense->starts_on->format('Y-m-d') }} — {{ $expense->ends_on->format('Y-m-d') }}</p>
<details @if(old('house_expense_form') === (string) $expense->id) open @endif><summary class="cursor-pointer font-semibold text-ios-blue">تعديل المصروف</summary>@include('broiler.house-expense-form')</details>
<details class="mt-4"><summary class="cursor-pointer font-semibold">توزيع المصروف على الدورات</summary>
<div class="overflow-x-auto"><table class="w-full text-right text-sm"><thead><tr><th class="p-3">الدورة</th><th class="p-3">نصيب الدورة</th></tr></thead><tbody>
@foreach($cycles as $cycle)
@if(($allocations[$expense->id][$cycle->id] ?? 0) > 0)<tr><td class="p-3"><a class="text-ios-blue" href="{{ route('broiler.show', $cycle) }}">{{ $cycle->name }}</a></td><td class="p-3">{{ number_format($allocations[$expense->id][$cycle->id], 2) }}</td></tr>@endif
@endforeach
<tr><td class="p-3">غير محمّل</td><td class="p-3">{{ number_format($expense->amount - array_sum($allocations[$expense->id] ?? []), 2) }}</td></tr>
</tbody></table></div></details>
</section>
@empty<p class="ios-card p-5 text-slate-500">لا توجد مصروفات مسجلة للعنبر.</p>@endforelse
</x-broiler-shell>
