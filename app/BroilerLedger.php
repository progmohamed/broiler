<?php

namespace App;

use App\Models\BroilerCycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BroilerLedger
{
    /** @return array{remaining: int, deaths: int, sold: int, feed: float, revenue: float, costs: float, profit: float, receivable: float, mortality: float, fcr: ?float, average_weight: ?float, weight_date: ?string} */
    public function summary(BroilerCycle $cycle): array
    {
        $entries = $cycle->entries()->orderBy('recorded_on')->orderBy('id')->get();
        $daily = $entries->where('kind', 'daily');
        $sales = $entries->where('kind', 'sale');
        $deaths = (int) $daily->sum('birds');
        $sold = (int) $sales->sum('birds');
        $remaining = $cycle->initial_birds - $deaths - $sold;
        $weight = $entries->where('kind', 'weight')->last();
        $average = $weight ? (float) $weight->weight_kg / $weight->birds : null;
        $feed = (float) $daily->sum('feed_kg');
        $biomass = (float) $sales->sum('weight_kg') + $remaining * ($average ?? 0);
        $revenue = (float) $sales->sum('amount');
        $costs = round((float) $cycle->chick_cost + $entries->where('kind', '!=', 'sale')->sum('amount'), 2);

        return [
            'remaining' => $remaining, 'deaths' => $deaths, 'sold' => $sold, 'feed' => $feed,
            'revenue' => $revenue, 'costs' => $costs, 'profit' => round($revenue - $costs, 2),
            'receivable' => round($revenue - $sales->sum('paid'), 2),
            'mortality' => $deaths / $cycle->initial_birds * 100,
            'fcr' => $biomass > 0 && ($remaining === 0 || $average !== null) ? $feed / $biomass : null,
            'average_weight' => $average, 'weight_date' => $weight?->recorded_on->toDateString(),
        ];
    }

    /** @param array<string, mixed> $data */
    public function record(BroilerCycle $cycle, array $data): void
    {
        DB::transaction(function () use ($cycle, $data): void {
            $cycle = BroilerCycle::query()->lockForUpdate()->findOrFail($cycle->id);
            $this->ensureOpen($cycle);
            if ($data['recorded_on'] < $cycle->started_on->toDateString()) {
                throw ValidationException::withMessages(['recorded_on' => 'لا يمكن التسجيل قبل بداية الدورة.']);
            }
            $summary = $this->summary($cycle);
            if (in_array($data['kind'], ['daily', 'sale'], true) && $data['birds'] > $summary['remaining']) {
                throw ValidationException::withMessages(['birds' => 'العدد أكبر من الطيور المتبقية في الدورة.']);
            }
            if ($data['kind'] === 'weight') {
                $removed = $cycle->entries()->whereDate('recorded_on', '<=', $data['recorded_on'])->whereIn('kind', ['daily', 'sale'])->sum('birds');
                if ($data['birds'] > $cycle->initial_birds - $removed) {
                    throw ValidationException::withMessages(['birds' => 'عدد العينة أكبر من الطيور الموجودة في هذا التاريخ.']);
                }
            }
            if ($data['kind'] === 'daily') {
                if ($cycle->entries()->whereDate('daily_on', $data['recorded_on'])->exists()) {
                    throw ValidationException::withMessages(['recorded_on' => 'تم تسجيل يومية لهذا التاريخ بالفعل.']);
                }
                $data['daily_on'] = $data['recorded_on'];
                $data['amount'] = round($data['feed_kg'] * $data['unit_price'], 2);
            }
            if ($data['kind'] === 'sale') {
                $data['amount'] = round($data['weight_kg'] * $data['unit_price'], 2);
                if ($data['paid'] > $data['amount']) {
                    throw ValidationException::withMessages(['paid' => 'المحصل لا يتجاوز قيمة البيع.']);
                }
            }
            $cycle->entries()->create($data);
        });
    }

    public function close(BroilerCycle $cycle): void
    {
        DB::transaction(function () use ($cycle): void {
            $cycle = BroilerCycle::query()->lockForUpdate()->findOrFail($cycle->id);
            $this->ensureOpen($cycle);
            if ($this->summary($cycle)['remaining'] !== 0) {
                throw ValidationException::withMessages(['cycle' => 'يجب تسجيل بيع أو نفوق جميع الطيور قبل إغلاق الدورة.']);
            }
            $cycle->update(['closed_on' => today()->toDateString()]);
        });
    }

    private function ensureOpen(BroilerCycle $cycle): void
    {
        if ($cycle->closed_on !== null) {
            throw ValidationException::withMessages(['cycle' => 'الدورة مغلقة ولا يمكن إضافة سجلات إليها.']);
        }
    }
}
