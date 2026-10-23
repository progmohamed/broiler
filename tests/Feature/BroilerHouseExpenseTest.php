<?php

namespace Tests\Feature;

use App\BroilerHouseCosts;
use App\BroilerLedger;
use App\Models\BroilerCycle;
use App\Models\BroilerHouse;
use App\Models\BroilerHouseExpense;
use App\Models\FarmSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BroilerHouseExpenseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_annual_rent_is_stored_for_house_and_only_occupied_days_affect_cycles(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 10));
        $house = BroilerHouse::factory()->create();
        $first = BroilerCycle::factory()->create(['broiler_house_id' => $house->id, 'started_on' => '2026-01-01', 'closed_on' => '2026-01-31', 'chick_cost' => 0]);
        $second = BroilerCycle::factory()->create(['broiler_house_id' => $house->id, 'started_on' => '2026-03-01', 'chick_cost' => 0]);
        $this->post(route('broiler.houses.expenses.store', $house), $this->payload())->assertSessionHasNoErrors()->assertRedirect(route('broiler.houses.expenses', $house));
        $this->assertDatabaseCount('broiler_entries', 0);
        $this->assertSame(3100.0, app(BroilerLedger::class)->summary($first)['house_costs']);
        $this->assertSame(-1000.0, app(BroilerLedger::class)->summary($second)['profit']);
        $this->get(route('broiler.houses.expenses', $house))->assertOk()->assertSee('32,400.00')->assertSee('إيجار السنة');
        $this->get(route('broiler.show', $second))->assertOk()->assertSee('نصيب الدورة من مصروفات العنبر');
        $expense = $house->expenses()->sole();
        $this->put(route('broiler.houses.expenses.update', [$house, $expense]), $this->payload(['amount' => 73000]))->assertSessionHasNoErrors();
        $this->assertSame(6200.0, app(BroilerLedger::class)->summary($first)['house_costs']);
        $this->assertDatabaseCount('broiler_house_expenses', 1);
    }

    #[TestWith(['2024-01-01', '2024-12-31', 36600, '2024-02-01', '2024-02-29', 2900.0])]
    #[TestWith(['2026-02-01', '2026-02-28', 2800, '2026-01-20', '2026-02-10', 1000.0])]
    #[TestWith(['2026-02-01', '2026-02-28', 2800, '2026-02-20', '2026-03-10', 900.0])]
    #[TestWith(['2026-02-01', '2026-02-28', 2800, '2026-03-01', '2026-03-10', 0.0])]
    #[TestWith(['2026-02-01', '2026-02-01', 100, '2026-02-01', '2026-02-01', 100.0])]
    public function test_allocation_respects_inclusive_period_boundaries(string $start, string $end, int $amount, string $cycleStart, string $cycleEnd, float $expected): void
    {
        $this->travelTo(now()->setDate(2026, 10, 23));
        $expense = BroilerHouseExpense::factory()->create(['starts_on' => $start, 'ends_on' => $end, 'amount' => $amount]);
        $cycle = BroilerCycle::factory()->create(['broiler_house_id' => $expense->broiler_house_id, 'started_on' => $cycleStart, 'closed_on' => $cycleEnd]);
        $this->assertSame($expected, app(BroilerHouseCosts::class)->allocation($expense, $cycle));
    }

    public function test_future_rent_and_other_houses_do_not_affect_profit(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 10));
        $cycle = BroilerCycle::factory()->create();
        BroilerHouseExpense::factory()->create(['broiler_house_id' => $cycle->broiler_house_id, 'starts_on' => '2026-04-01', 'ends_on' => '2027-03-31']);
        BroilerHouseExpense::factory()->create();
        $this->assertSame(0.0, app(BroilerLedger::class)->summary($cycle)['house_costs']);
    }

    public function test_rounding_does_not_duplicate_cents_across_consecutive_cycles(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 23));
        $expense = BroilerHouseExpense::factory()->create(['starts_on' => '2026-01-01', 'ends_on' => '2026-01-03', 'amount' => 0.02]);
        $amounts = [];
        foreach ([1, 2, 3] as $day) {
            $cycle = BroilerCycle::factory()->create(['broiler_house_id' => $expense->broiler_house_id, 'started_on' => '2026-01-0'.$day, 'closed_on' => '2026-01-0'.$day]);
            $amounts[] = app(BroilerHouseCosts::class)->allocation($expense, $cycle);
        }
        $this->assertEqualsWithDelta(0.02, array_sum($amounts), 0.00001);
    }

    #[TestWith(['amount', -1])]
    #[TestWith(['amount', 1.111])]
    #[TestWith(['description', ''])]
    #[TestWith(['ends_on', '2025-12-31'])]
    #[TestWith(['starts_on', 'invalid'])]
    public function test_invalid_house_expense_is_not_saved(string $field, mixed $value): void
    {
        $house = BroilerHouse::factory()->create();
        $this->post(route('broiler.houses.expenses.store', $house), $this->payload([$field => $value]))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('broiler_house_expenses', 0);
    }

    public function test_expense_cannot_be_edited_through_another_house_and_lock_is_enforced(): void
    {
        $house = BroilerHouse::factory()->create();
        $expense = BroilerHouseExpense::factory()->create();
        $this->put(route('broiler.houses.expenses.update', [$house, $expense]), $this->payload())->assertNotFound();
        $setting = new FarmSetting;
        $setting->herd_birth_date = today();
        $setting->total_herd_count = 0;
        $setting->app_pin = Hash::make('1234');
        $setting->save();
        $this->get(route('broiler.houses.expenses', $house))->assertRedirect(route('app-lock.create'));
        $this->post(route('broiler.houses.expenses.store', $house), $this->payload())->assertRedirect(route('app-lock.create'));
        $this->put(route('broiler.houses.expenses.update', [$expense->house, $expense]), $this->payload())->assertRedirect(route('app-lock.create'));
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['description' => 'إيجار السنة', 'amount' => 36500, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31'], $overrides);
    }
}
