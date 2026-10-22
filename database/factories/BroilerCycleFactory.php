<?php

namespace Database\Factories;

use App\Models\BroilerCycle;
use App\Models\BroilerHouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BroilerCycle> */
class BroilerCycleFactory extends Factory
{
    public function definition(): array
    {
        return ['broiler_house_id' => BroilerHouse::factory(), 'name' => 'دورة اختبار', 'started_on' => today()->subDays(40), 'initial_birds' => 1000, 'initial_weight_g' => 40, 'chick_cost' => 1000];
    }
}
