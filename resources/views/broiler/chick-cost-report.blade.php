<details class="ios-card" id="chick-cost-report">
    <summary class="cursor-pointer p-5 text-lg font-bold">تقرير نصيب الكتكوت</summary>
    <p class="px-5 pb-3 text-sm text-slate-500">على أساس {{ number_format($summary['chick_cost_birds']) }} كتكوت بعد خصم النافق من عدد الاستقبال.</p>
    <p class="px-5 pb-3 text-xs text-slate-500">يشمل العلف الوارد وتكلفة نقله، ونصيب الدورة من مصروفات العنبر حتى الآن. لا تُخصم المبيعات من التكلفة، ويشمل أساس القسمة الطيور المباعة.</p>
    <p class="px-5 pb-3 text-xs text-slate-500">سجّل تكلفة اللقاحات وتنفيذ التحصين من المصروفات بتصنيف «تحصينات». مواعيد التحصينات وملاحظات الأدوية وحدها لا تُسجل تكلفة. المصروفات القديمة تبقى ضمن تصنيفها المسجل.</p>
    @if($summary['chick_cost_birds'] === 0)<p class="px-5 pb-3 text-sm text-slate-500">لا يمكن حساب نصيب الكتكوت لعدم وجود طيور بعد خصم النافق.</p>@endif
    <div class="overflow-x-auto">
        <table class="w-full text-right text-sm">
            <thead class="bg-slate-50"><tr><th class="p-3">البند</th><th class="p-3">إجمالي المصروف</th><th class="p-3">نصيب الكتكوت</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach(\App\Models\BroilerEntry::CHICK_COST_CATEGORIES as $category => $label)
                    <tr><td class="p-3">{{ $label }}</td><td class="p-3">{{ number_format($summary['chick_cost_amounts'][$category], 2) }}</td><td class="p-3">{{ $summary['chick_costs'][$category] === null ? 'غير متاح' : number_format($summary['chick_costs'][$category], 3) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50 font-bold"><tr><td class="p-3">إجمالي التكلفة المسجلة</td><td class="p-3">{{ number_format(array_sum($summary['chick_cost_amounts']), 2) }}</td><td class="p-3">{{ $summary['chick_cost_total'] === null ? 'غير متاح' : number_format($summary['chick_cost_total'], 3) }}</td></tr></tfoot>
        </table>
    </div>
</details>
