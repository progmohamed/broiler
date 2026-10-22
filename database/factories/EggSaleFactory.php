<?php

namespace Database\Factories;

use App\Models\EggSale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EggSale>
 */
class EggSaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sold_on' => fake()->date(),
            'cartons' => fake()->numberBetween(1, 500),
            'price_per_carton' => fake()->randomFloat(2, 50, 500),
            'buyer' => fake()->company(),
            'payment_status' => fake()->randomElement(['paid', 'pending']),
            'invoice_number' => fake()->optional()->numerify('SALE-####'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
