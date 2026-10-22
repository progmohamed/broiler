<?php

namespace Database\Factories;

use App\Models\BroilerHouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BroilerHouse> */
class BroilerHouseFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->bothify('House-####??'), 'capacity' => 10000];
    }
}
