<?php

namespace Database\Factories;

use App\ExpensePaymentMethod;
use App\Models\WorkerAdvance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkerAdvance>
 */
class WorkerAdvanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'advanced_on' => fake()->date(),
            'worker_name' => fake()->name(),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'payment_method' => fake()->randomElement(ExpensePaymentMethod::cases()),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
