<?php

namespace Tests\Feature;

use App\BroilerLedger;
use App\Models\BroilerCycle;
use App\Models\BroilerEntry;
use App\Models\BroilerHouse;
use App\Models\FarmSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BroilerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_home_shows_broiler_cycles_and_escapes_names(): void
    {
        BroilerCycle::factory()->create(['name' => '<script>alert(1)</script>']);
        $this->get('/')->assertOk()->assertSee('الدورات والعنابر')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_creates_house_and_cycle(): void
    {
        $this->post(route('broiler.houses.store'), ['name' => 'عنبر 1', 'capacity' => 1000])->assertRedirect(route('broiler.index'));
        $house = BroilerHouse::query()->firstOrFail();
        $this->post(route('broiler.store'), ['broiler_house_id' => $house->id, 'name' => 'دورة أولى', 'started_on' => today()->toDateString(), 'initial_birds' => 1000, 'initial_weight_g' => 40, 'chick_cost' => 5000])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('broiler_cycles', ['broiler_house_id' => $house->id, 'initial_birds' => 1000, 'chick_cost' => 5000]);
    }

    public function test_rejects_over_capacity_and_an_occupied_house(): void
    {
        $house = BroilerHouse::factory()->create(['capacity' => 100]);
        $payload = ['broiler_house_id' => $house->id, 'name' => 'جديدة', 'started_on' => today()->toDateString(), 'initial_birds' => 101, 'initial_weight_g' => 40, 'chick_cost' => 100];
        $this->post(route('broiler.store'), $payload)->assertSessionHasErrors('initial_birds');
        $this->assertDatabaseCount('broiler_cycles', 0);
        BroilerCycle::factory()->create(['broiler_house_id' => $house->id, 'initial_birds' => 100]);
        $this->post(route('broiler.store'), array_replace($payload, ['initial_birds' => 100]))->assertSessionHasErrors('broiler_house_id');
        $this->assertDatabaseCount('broiler_cycles', 1);
    }

    public function test_daily_consumption_and_mortality_are_recorded_once_without_recharging_feed(): void
    {
        $cycle = BroilerCycle::factory()->create();
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'feed_delivery', 'feed_kg' => 100.5, 'amount' => 201]);
        $payload = ['kind' => 'daily', 'recorded_on' => today()->toDateString(), 'birds' => 10, 'feed_sacks' => 2.01, 'unit_price' => 2, 'amount' => 99999];
        $this->post(route('broiler.entries.store', $cycle), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('broiler_entries', ['broiler_cycle_id' => $cycle->id, 'kind' => 'daily', 'amount' => 0, 'birds' => 10]);
        $this->post(route('broiler.entries.store', $cycle), $payload)->assertSessionHasErrors('recorded_on');
        $this->assertDatabaseCount('broiler_entries', 2);
    }

    public function test_calculates_partial_sales_fcr_and_profit_without_double_counting(): void
    {
        $cycle = BroilerCycle::factory()->create(['initial_birds' => 100, 'chick_cost' => 100]);
        foreach ([
            ['kind' => 'feed_delivery', 'weight_tons' => 0.18, 'price_per_ton' => 1000, 'shipping_cost' => 0, 'feed_type' => 'بادئ'],
            ['kind' => 'daily', 'birds' => 10, 'feed_sacks' => 3.6, 'unit_price' => 1],
            ['kind' => 'weight', 'birds' => 10, 'weight_kg' => 20],
            ['kind' => 'medicine', 'description' => 'تحصين', 'amount' => 10],
            ['kind' => 'expense', 'category' => 'other', 'description' => 'كهرباء', 'amount' => 20],
            ['kind' => 'sale', 'birds' => 40, 'weight_kg' => 80, 'unit_price' => 5, 'paid' => 300],
        ] as $entry) {
            $this->post(route('broiler.entries.store', $cycle), $entry + ['recorded_on' => today()->toDateString()])->assertSessionHasNoErrors();
        }
        $summary = app(BroilerLedger::class)->summary($cycle);
        $this->assertSame(50, $summary['remaining']);
        $this->assertSame(1.0, $summary['fcr']);
        $this->assertSame(310.0, $summary['costs']);
        $this->assertSame(90.0, $summary['profit']);
        $this->assertSame(100.0, $summary['receivable']);
        $this->assertSame(10.0, $summary['mortality']);
        $this->get(route('broiler.show', $cycle))->assertOk()->assertSee('معامل التحويل الغذائي');
    }

    public function test_rejects_sales_and_deaths_exceeding_remaining_birds(): void
    {
        $cycle = BroilerCycle::factory()->create(['initial_birds' => 10]);
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'sale', 'recorded_on' => today()->toDateString(), 'birds' => 11, 'weight_kg' => 20, 'unit_price' => 5, 'paid' => 0])->assertSessionHasErrors('birds');
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'daily', 'recorded_on' => today()->toDateString(), 'birds' => 11, 'feed_sacks' => 0.02, 'unit_price' => 1])->assertSessionHasErrors('birds');
        $this->assertDatabaseCount('broiler_entries', 0);
    }

    public function test_rejects_future_and_pre_cycle_dates(): void
    {
        $cycle = BroilerCycle::factory()->create();
        foreach ([today()->addDay()->toDateString(), $cycle->started_on->subDay()->toDateString()] as $date) {
            $this->post(route('broiler.entries.store', $cycle), ['kind' => 'expense', 'category' => 'other', 'recorded_on' => $date, 'description' => 'تكلفة', 'amount' => 10])->assertSessionHasErrors('recorded_on');
        }
        $this->assertDatabaseCount('broiler_entries', 0);
    }

    public function test_rejects_invalid_kind_negative_cost_and_zero_sample(): void
    {
        $cycle = BroilerCycle::factory()->create();
        foreach ([['kind' => 'unknown'], ['kind' => 'expense', 'category' => 'other', 'description' => 'تكلفة', 'amount' => -1], ['kind' => 'weight', 'birds' => 0, 'weight_kg' => 0]] as $entry) {
            $this->post(route('broiler.entries.store', $cycle), $entry + ['recorded_on' => today()->toDateString()])->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('broiler_entries', 0);
    }

    public function test_rejects_overpayment_and_oversized_sample(): void
    {
        $cycle = BroilerCycle::factory()->create(['initial_birds' => 10]);
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'sale', 'recorded_on' => today()->toDateString(), 'birds' => 5, 'weight_kg' => 10, 'unit_price' => 2, 'paid' => 21])->assertSessionHasErrors('paid');
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'weight', 'recorded_on' => today()->toDateString(), 'birds' => 11, 'weight_kg' => 10])->assertSessionHasErrors('birds');
        $this->assertDatabaseCount('broiler_entries', 0);
    }

    public function test_closes_sold_out_cycle_and_rejects_further_entries(): void
    {
        $cycle = BroilerCycle::factory()->create(['initial_birds' => 10]);
        $this->post(route('broiler.close', $cycle))->assertSessionHasErrors('cycle');
        $this->assertNull($cycle->fresh()->closed_on);
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'sale', 'recorded_on' => today()->toDateString(), 'birds' => 10, 'weight_kg' => 20, 'unit_price' => 2, 'paid' => 40])->assertSessionHasNoErrors();
        $this->post(route('broiler.close', $cycle))->assertSessionHasNoErrors();
        $this->assertNotNull($cycle->fresh()->closed_on);
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'expense', 'category' => 'other', 'recorded_on' => today()->toDateString(), 'amount' => 1, 'description' => 'اختبار'])->assertSessionHasErrors('cycle');
        $this->assertDatabaseCount('broiler_entries', 1);
    }

    public function test_cycles_are_isolated_and_missing_weights_do_not_produce_false_fcr(): void
    {
        $cycle = BroilerCycle::factory()->create();
        BroilerEntry::factory()->create(['amount' => 9000]);
        $summary = app(BroilerLedger::class)->summary($cycle);
        $this->assertSame(1000.0, $summary['costs']);
        $this->assertNull($summary['fcr']);
        $this->assertSame(1000, $summary['remaining']);
    }

    public function test_pin_protects_cycle_writes(): void
    {
        $setting = new FarmSetting;
        $setting->farm_name = 'تسمين';
        $setting->herd_birth_date = today();
        $setting->total_herd_count = 0;
        $setting->app_pin = bcrypt('1234');
        $setting->save();
        $this->post(route('broiler.houses.store'), ['name' => 'عنبر', 'capacity' => 100])->assertRedirect(route('app-lock.create'));
        $this->assertDatabaseCount('broiler_houses', 0);
    }

    public function test_configures_pin_and_unlocks_to_broiler_home(): void
    {
        $this->put(route('broiler.pin.update'), ['pin' => '5678', 'pin_confirmation' => '5678'])->assertSessionHasNoErrors()->assertRedirect(route('broiler.index'));
        $this->assertTrue(Hash::check('5678', FarmSetting::query()->firstOrFail()->app_pin));
        $this->post(route('app-lock.destroy'))->assertRedirect(route('app-lock.create'));
        $this->get('/')->assertRedirect(route('app-lock.create'));
        $this->post(route('app-lock.store'), ['pin' => '5678'])->assertRedirect(route('broiler.index'));
        $this->get('/')->assertOk();
    }

    public function test_daily_medicines_are_saved_and_escaped_without_adding_expense(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'daily', 'recorded_on' => today()->toDateString(), 'birds' => 0, 'feed_sacks' => 0, 'medicines' => 'دواء <script>alert(1)</script>'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('broiler_entries', ['broiler_cycle_id' => $cycle->id, 'medicines' => 'دواء <script>alert(1)</script>', 'amount' => 0]);
        $this->get(route('broiler.show', $cycle))->assertSee('الدواء:')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_daily_medicines_reject_overlong_text(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $this->post(route('broiler.entries.store', $cycle), ['kind' => 'daily', 'recorded_on' => today()->toDateString(), 'birds' => 0, 'feed_sacks' => 0, 'medicines' => str_repeat('a', 1001)])->assertSessionHasErrors('medicines');
        $this->assertDatabaseCount('broiler_entries', 0);
    }

    public function test_unknown_cycle_returns_404(): void
    {
        $this->get('/cycles/999')->assertNotFound();
    }
}
