<?php

namespace Database\Factories;

use App\ExpenseCategory;
use App\ExpensePaymentMethod;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'spent_on' => fake()->date(),
            'category' => fake()->randomElement(ExpenseCategory::cases()),
            'amount' => fake()->randomFloat(2, 1, 10000),
            'payment_method' => fake()->randomElement(ExpensePaymentMethod::cases()),
            'payee' => fake()->optional()->company(),
            'invoice_number' => fake()->optional()->numerify('EXP-####'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
