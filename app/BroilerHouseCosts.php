<?php

namespace App;

use App\Models\BroilerCycle;
use App\Models\BroilerHouseExpense;
use Carbon\CarbonImmutable;

class BroilerHouseCosts
{
    public function allocation(BroilerHouseExpense $expense, BroilerCycle $cycle): float
    {
        if ($expense->broiler_house_id !== $cycle->broiler_house_id) {
            return 0.0;
        }
        $start = $expense->starts_on->max(CarbonImmutable::parse($cycle->started_on->toDateString()));
        $end = $expense->ends_on->min(CarbonImmutable::parse(($cycle->closed_on ?? today())->toDateString()))->min(CarbonImmutable::today());
        if ($end->lt($start)) {
            return 0.0;
        }
        $days = (int) $expense->starts_on->diffInDays($expense->ends_on) + 1;
        $offset = (int) $expense->starts_on->diffInDays($start);
        $until = (int) $expense->starts_on->diffInDays($end) + 1;
        $cents = (int) round((float) $expense->amount * 100);

        return (round($cents * $until / $days) - round($cents * $offset / $days)) / 100;
    }
}
