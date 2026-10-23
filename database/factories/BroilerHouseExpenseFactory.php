<?php

namespace Database\Factories;

use App\Models\BroilerHouse;
use App\Models\BroilerHouseExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BroilerHouseExpense>
 */
class BroilerHouseExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'broiler_house_id' => BroilerHouse::factory(),
            'description' => 'إيجار العنبر',
            'amount' => 36500,
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
        ];
    }
}
