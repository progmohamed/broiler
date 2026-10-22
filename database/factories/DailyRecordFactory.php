<?php

namespace Database\Factories;

use App\Models\DailyRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyRecord>
 */
class DailyRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recorded_on' => fake()->date(),
            'production' => fake()->numberBetween(0, 10000),
            'feed' => fake()->numberBetween(0, 10000),
            'deaths' => fake()->numberBetween(0, 100),
            'medicines' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
