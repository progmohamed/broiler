<?php

namespace Database\Factories;

use App\Models\OtherSale;
use App\SalePaymentStatus;
use App\SaleProductType;
use App\SaleUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OtherSale>
 */
class OtherSaleFactory extends Factory
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
            'product_type' => fake()->randomElement(SaleProductType::cases()),
            'custom_product' => null,
            'quantity' => fake()->randomFloat(3, 1, 100),
            'unit' => fake()->randomElement(SaleUnit::cases()),
            'unit_price' => fake()->randomFloat(2, 1, 1000),
            'buyer' => fake()->name(),
            'payment_status' => fake()->randomElement(SalePaymentStatus::cases()),
            'invoice_number' => fake()->optional()->numerify('SALE-####'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
