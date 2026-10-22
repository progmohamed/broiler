<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ], [
            'from.date_format' => 'تاريخ البداية غير صحيح.',
            'to.date_format' => 'تاريخ النهاية غير صحيح.',
            'to.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية أو مساويًا له.',
        ]);

        $to = isset($filters['to'])
            ? Carbon::createFromFormat('Y-m-d', $filters['to'])
            : today();
        $from = isset($filters['from'])
            ? Carbon::createFromFormat('Y-m-d', $filters['from'])
            : $to->copy()->subDays(29);

        $records = DailyRecord::query()
            ->whereBetween('recorded_on', [$from->toDateString(), $to->toDateString()])
            ->oldest('recorded_on')
            ->get(['id', 'recorded_on', 'deaths', 'production', 'feed']);

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'recordedDays' => $records->count(),
            'mortalityReport' => $this->buildReport($records, 'deaths'),
            'productionReport' => $this->buildReport($records, 'production'),
            'feedReport' => $this->buildReport($records, 'feed'),
        ]);
    }

    /**
     * @param  Collection<int, DailyRecord>  $records
     * @return array{points: list<array{date: Carbon, value: int, height: int}>, total: int, average: float, maximum: int}
     */
    private function buildReport(Collection $records, string $attribute): array
    {
        $maximum = max(0, (int) $records->max($attribute));
        $scaleMaximum = max(1, $maximum);

        $points = $records
            ->map(function (DailyRecord $record) use ($attribute, $scaleMaximum): array {
                $value = (int) $record->getAttribute($attribute);

                return [
                    'date' => $record->recorded_on,
                    'value' => $value,
                    'height' => $value === 0 ? 3 : max(8, (int) round(($value / $scaleMaximum) * 100)),
                ];
            })
            ->values()
            ->all();

        return [
            'points' => $points,
            'total' => (int) $records->sum($attribute),
            'average' => $records->isEmpty() ? 0.0 : round((float) $records->avg($attribute), 1),
            'maximum' => $maximum,
        ];
    }
}
