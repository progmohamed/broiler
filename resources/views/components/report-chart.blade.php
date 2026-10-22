@props([
    'id',
    'title',
    'description',
    'points',
    'unit',
    'metrics',
    'tone' => 'blue',
    'precision' => 0,
])

@php
    $barClass = match ($tone) {
        'red' => 'bg-ios-red',
        'green' => 'bg-ios-green',
        'orange' => 'bg-ios-orange',
        default => 'bg-ios-blue',
    };
@endphp

<section id="{{ $id }}" {{ $attributes->merge(['class' => 'flex flex-col gap-3', 'aria-labelledby' => $id.'-title']) }}>
    <div class="flex items-end justify-between gap-3 px-1">
        <div>
            <h2 class="ios-section-title p-0" id="{{ $id }}-title">{{ $title }}</h2>
            <p class="mt-1 text-xs text-slate-400">{{ $description }}</p>
        </div>
        <span class="shrink-0 rounded-full bg-slate-200/70 px-2.5 py-1 text-[0.65rem] font-semibold text-slate-500">القيمة / {{ $unit }}</span>
    </div>

    @if ($metrics !== [])
        <dl class="ios-card grid grid-cols-3 divide-x divide-x-reverse divide-slate-200 py-4 text-center">
            @foreach ($metrics as $metric)
                <div class="px-2">
                    <dt class="text-[0.68rem] text-slate-400">{{ $metric['label'] }}</dt>
                    <dd class="mt-1 text-lg font-bold">{{ $metric['value'] }}</dd>
                    <dd class="text-xs text-slate-400">{{ $metric['suffix'] }}</dd>
                </div>
            @endforeach
        </dl>
    @endif

    <div class="ios-card overflow-hidden p-3">
        @if ($points === [])
            <div class="flex min-h-44 items-center justify-center text-center text-sm text-slate-400">لا توجد تسجيلات خلال هذه الفترة.</div>
        @else
            <div class="overflow-x-auto pb-2" dir="ltr">
                <div class="relative flex h-72 min-w-full snap-x snap-mandatory items-end gap-3 border-b border-slate-300 px-2 pt-10" style="width: max(100%, {{ count($points) * 56 }}px)">
                    <div class="pointer-events-none absolute inset-x-2 top-12 border-t border-dashed border-slate-200"></div>
                    <div class="pointer-events-none absolute inset-x-2 top-28 border-t border-dashed border-slate-200"></div>
                    <div class="pointer-events-none absolute inset-x-2 top-44 border-t border-dashed border-slate-200"></div>

                    @foreach ($points as $point)
                        <div class="relative z-10 flex h-full w-11 shrink-0 snap-start flex-col items-center justify-end gap-2">
                            <span class="text-xs font-bold text-slate-700">{{ number_format($point['value'], $precision) }}</span>
                            <div class="flex h-52 w-full items-end">
                                <div class="w-full rounded-t-xl {{ $barClass }} shadow-sm opacity-90" style="height: {{ $point['height'] }}%" title="{{ $point['date']->translatedFormat('j F Y') }}: {{ number_format($point['value'], $precision) }} {{ $unit }}" data-chart-value="{{ $point['value'] }}" role="img" aria-label="{{ $point['date']->translatedFormat('j F Y') }}: {{ number_format($point['value'], $precision) }} {{ $unit }}"></div>
                            </div>
                            <time class="text-[0.68rem] font-medium text-slate-500" datetime="{{ $point['date']->toDateString() }}">{{ $point['date']->format('d/m') }}</time>
                        </div>
                    @endforeach
                </div>
            </div>
            <p class="pt-2 text-center text-[0.65rem] text-slate-400">مرّر الرسم أفقيًا لعرض بقية الأيام.</p>
        @endif
    </div>
</section>
