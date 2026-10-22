<?php

namespace Database\Factories;

use App\Models\BroilerCycle;
use App\Models\BroilerVaccination;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BroilerVaccination> */
class BroilerVaccinationFactory extends Factory
{
    public function definition(): array
    {
        return ['broiler_cycle_id' => BroilerCycle::factory(), 'name' => 'تحصين تجريبي', 'due_at' => now('UTC')->addDay(), 'remind_at' => now('UTC'), 'remind_before_hours' => 24, 'timezone' => 'UTC'];
    }
}
