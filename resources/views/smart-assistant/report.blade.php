<article class="flex min-w-0 flex-col gap-4" aria-labelledby="{{ $reportId }}-title">
    <div class="ios-card flex flex-col gap-2 p-4">
        <h3 class="font-bold text-slate-800" id="{{ $reportId }}-title">{{ $report['title'] }}</h3>
        <p class="text-xs text-slate-400" dir="ltr">{{ $report['period'] }}</p>
        <p class="text-sm leading-7 text-slate-600">{{ $report['answer'] }}</p>
    </div>

    @if ($report['metrics'] !== [])
        <dl class="grid grid-cols-2 gap-2">
            @foreach ($report['metrics'] as $metric)
                <div class="ios-card flex flex-col gap-2 p-3">
                    <dt class="text-xs leading-5 text-slate-500">{{ $metric['label'] }}</dt>
                    <dd class="break-words text-xl font-bold {{ $metric['value'] !== null && $metric['value'] < 0 ? 'text-ios-red' : 'text-ios-blue' }}" dir="ltr">{{ $metric['value'] === null ? '—' : number_format($metric['value'], $metric['precision']) }}</dd>
                    <dd class="text-xs text-slate-400">{{ $metric['unit'] }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    @if ($report['notes'] !== [])
        <div class="rounded-2xl bg-slate-100 p-4">
            <h4 class="text-xs font-semibold text-slate-600">لفهم الأرقام</h4>
            <ul class="mt-2 list-inside list-disc space-y-1 text-xs leading-6 text-slate-500">
                @foreach ($report['notes'] as $note)<li>{{ $note }}</li>@endforeach
            </ul>
        </div>
    @endif

    @foreach ($report['charts'] as $chart)
        <div data-assistant-chart="{{ json_encode(['title' => $chart['title'], 'unit' => $chart['unit'], 'tone' => $chart['tone'], 'labels' => array_map(fn (array $point): string => $point['date']->toDateString(), $chart['points']), 'values' => array_column($chart['points'], 'value')]) }}">
            <div data-assistant-chart-fallback>
                <x-report-chart
                    :id="$reportId.'-chart-'.$loop->index"
                    :title="$chart['title']"
                    description="القيم المسجلة حسب التجميع المطلوب؛ لكل رسم مقياس مستقل."
                    :points="$chart['points']"
                    :unit="$chart['unit']"
                    :tone="$chart['tone']"
                    :metrics="[]"
                    :precision="match ($chart['unit']) { 'جنيه' => 2, 'طن' => 3, default => 0 }"
                />
            </div>
        </div>
    @endforeach

    @foreach ($report['tables'] as $table)
        <details class="ios-card overflow-hidden" @if ($report['charts'] === []) open @endif>
            <summary class="cursor-pointer p-4 text-sm font-semibold text-slate-700">{{ $table['title'] }}</summary>
            <div class="overflow-x-auto px-3 pb-3">
                <table class="w-full whitespace-nowrap text-right text-xs">
                    <caption class="sr-only">{{ $table['title'] }}</caption>
                    <thead><tr class="border-b border-slate-200 text-slate-500">@foreach ($table['headings'] as $heading)<th class="px-3 py-2 font-semibold" scope="col">{{ $heading }}</th>@endforeach</tr></thead>
                    <tbody>
                        @forelse ($table['rows'] as $row)
                            <tr class="border-b border-slate-100">@foreach ($row as $cell)<td class="px-3 py-3"><bdi>{{ $cell ?? '—' }}</bdi></td>@endforeach</tr>
                        @empty
                            <tr><td class="px-3 py-4 text-center text-slate-400" colspan="{{ count($table['headings']) }}">لا توجد بيانات مسجلة خلال الفترة.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>
    @endforeach
</article>
