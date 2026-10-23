<details class="ios-card" id="chick-cost-report">
    <summary class="cursor-pointer p-5 text-lg font-bold">تقرير نصيب الكتكوت</summary>
    <p class="px-5 pb-3 text-sm text-slate-500">على أساس {{ number_format($cycle->initial_birds) }} كتكوت عند بداية الدورة.</p>
    <div class="overflow-x-auto">
        <table class="w-full text-right text-sm">
            <thead class="bg-slate-50"><tr><th class="p-3">البند</th><th class="p-3">إجمالي المصروف</th><th class="p-3">نصيب الكتكوت</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach(\App\Models\BroilerEntry::CHICK_COST_CATEGORIES as $category => $label)
                    <tr><td class="p-3">{{ $label }}</td><td class="p-3">{{ number_format($summary['expenses'][$category], 2) }}</td><td class="p-3">{{ number_format($summary['chick_costs'][$category], 3) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50 font-bold"><tr><td class="p-3">إجمالي البنود الأربعة</td><td class="p-3">{{ number_format(collect($summary['expenses'])->only(array_keys(\App\Models\BroilerEntry::CHICK_COST_CATEGORIES))->sum(), 2) }}</td><td class="p-3">{{ number_format($summary['chick_cost_total'], 3) }}</td></tr></tfoot>
        </table>
    </div>
</details>
