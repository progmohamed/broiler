<?php

namespace App;

use App\Models\BroilerCycle;
use App\Models\BroilerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BroilerLedger
{
    /** @return array{remaining: int, deaths: int, sold: int, feed: float, revenue: float, costs: float, profit: float, receivable: float, mortality: float, fcr: ?float, average_weight: ?float, weight_date: ?string, feed_received: float, feed_stock: float, expenses: array<string, float>} */
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
        $received = (float) $entries->where('kind', 'feed_delivery')->sum('feed_kg');
        $expenses = array_fill_keys(['feed', ...array_keys(BroilerEntry::EXPENSE_CATEGORIES)], 0.0);
        foreach ($entries as $entry) {
            $category = match ($entry->kind) {
                'feed_delivery', 'daily' => 'feed',
                'medicine' => 'medicines',
                'expense' => $entry->category ?? 'other',
                default => null,
            };
            if ($category !== null) {
                $expenses[$category] = round($expenses[$category] + (float) $entry->amount, 2);
            }
        }
        $biomass = (float) $sales->sum('weight_kg') + $remaining * ($average ?? 0);
        $revenue = (float) $sales->sum('amount');
        $costs = round((float) $cycle->chick_cost + $entries->where('kind', '!=', 'sale')->sum('amount'), 2);

        return [
            'feed_received' => $received, 'feed_stock' => round($received - $feed, 3), 'expenses' => $expenses,
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
                $data['feed_kg'] = round((float) $data['feed_sacks'] * BroilerEntry::SACK_WEIGHT_KG, 3);
                unset($data['feed_sacks']);
                $data['daily_on'] = $data['recorded_on'];
                $this->ensureFeedAvailable($cycle, $data['recorded_on'], (float) $data['feed_kg']);
                $data['amount'] = 0;
                $data['unit_price'] = 0;
            }
            if ($data['kind'] === 'feed_delivery') {
                $data['feed_kg'] = round((float) $data['weight_tons'] * 1000, 3);
                $data['unit_price'] = round((float) $data['price_per_ton'] / 1000, 2);
                $data['amount'] = round($data['weight_tons'] * $data['price_per_ton'] + $data['shipping_cost'], 2);
                unset($data['weight_tons']);
                $data['category'] = 'feed';
            }
            if ($data['kind'] === 'medicine') {
                $data['category'] = 'medicines';
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

    private function ensureFeedAvailable(BroilerCycle $cycle, string $date, float $quantity): void
    {
        $movements = [];
        foreach ($cycle->entries()->whereIn('kind', ['daily', 'feed_delivery'])->get() as $entry) {
            $day = $entry->recorded_on->toDateString();
            $grams = (int) round((float) $entry->feed_kg * 1000);
            $movements[$day] = ($movements[$day] ?? 0) + ($entry->kind === 'feed_delivery' ? $grams : -$grams);
        }
        $movements[$date] = ($movements[$date] ?? 0) - (int) round($quantity * 1000);
        ksort($movements);
        $balance = 0;
        foreach ($movements as $movement) {
            $balance += $movement;
            if ($balance < 0) {
                throw ValidationException::withMessages(['feed_sacks' => 'العلف المستهلك يتجاوز رصيد مخزن الدورة في هذا التاريخ. سجّل نقلة العلف أولًا.']);
            }
        }
    }

    private function ensureOpen(BroilerCycle $cycle): void
    {
        if ($cycle->closed_on !== null) {
            throw ValidationException::withMessages(['cycle' => 'الدورة مغلقة ولا يمكن إضافة سجلات إليها.']);
        }
    }
}
