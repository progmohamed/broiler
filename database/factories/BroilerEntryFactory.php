<?php

namespace Database\Factories;

use App\Models\BroilerCycle;
use App\Models\BroilerEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BroilerEntry> */
class BroilerEntryFactory extends Factory
{
    public function definition(): array
    {
        return ['broiler_cycle_id' => BroilerCycle::factory(), 'kind' => 'expense', 'recorded_on' => today(), 'description' => 'كهرباء', 'amount' => 100];
    }
}
