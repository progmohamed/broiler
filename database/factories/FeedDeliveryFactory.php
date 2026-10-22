<?php

namespace Database\Factories;

use App\ExpensePaymentMethod;
use App\Models\FeedDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedDelivery>
 */
class FeedDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivered_on' => fake()->date(),
            'feed_type' => fake()->randomElement(['علف بياض', 'علف نامي']),
            'weight_tons' => fake()->randomFloat(3, 0.5, 30),
            'price_per_ton' => fake()->randomFloat(2, 500, 5000),
            'shipping_cost' => fake()->randomFloat(2, 0, 1000),
            'tip' => fake()->randomFloat(2, 0, 200),
            'supplier' => fake()->company(),
            'payment_method' => fake()->randomElement(ExpensePaymentMethod::cases()),
            'invoice_number' => fake()->optional()->numerify('INV-####'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
