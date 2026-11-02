<?php

namespace Tests\Feature;

use App\BroilerLedger;
use App\Models\BroilerCycle;
use App\Models\BroilerEntry;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BroilerDailyEditingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_daily_edit_updates_same_record_and_summary_without_changing_cost(): void
    {
        $cycle = BroilerCycle::factory()->create();
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'feed_delivery', 'recorded_on' => today()->subDays(2), 'feed_kg' => 500, 'amount' => 200]);
        $entry = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'daily', 'daily_on' => today(), 'birds' => 5, 'feed_kg' => 100, 'amount' => 0]);

        $this->put(route('broiler.entries.daily.update', [$cycle, $entry]), ['recorded_on' => today()->subDay()->toDateString(), 'birds' => 10, 'feed_sacks' => 3, 'medicines' => 'دواء', 'notes' => 'تصحيح', 'amount' => 999, 'kind' => 'sale'])->assertSessionHasNoErrors()->assertSessionHas('cycle_tab', 'daily');

        $this->assertDatabaseCount('broiler_entries', 2);
        $this->assertDatabaseHas('broiler_entries', ['id' => $entry->id, 'kind' => 'daily', 'birds' => 10, 'feed_kg' => 150, 'amount' => 0, 'medicines' => 'دواء', 'notes' => 'تصحيح']);
        $this->assertSame(today()->subDay()->toDateString(), $entry->fresh()->daily_on->toDateString());
        $summary = app(BroilerLedger::class)->summary($cycle);
        $this->assertSame(990, $summary['remaining']);
        $this->assertSame(350.0, $summary['feed_stock']);
        $this->assertSame(1200.0, $summary['costs']);
        $this->get(route('broiler.show', $cycle))->assertOk()->assertSee('تعديل اليومية');
    }

    #[TestWith(['birds', 1001, 'birds'])]
    #[TestWith(['birds', -1, 'birds'])]
    #[TestWith(['feed_sacks', 3, 'feed_sacks'])]
    #[TestWith(['recorded_on', '2000-01-01', 'recorded_on'])]
    public function test_invalid_edit_preserves_original_record(string $field, mixed $value, string $error): void
    {
        $entry = BroilerEntry::factory()->create(['kind' => 'daily', 'birds' => 0, 'feed_kg' => 0, 'amount' => 0]);
        $before = $entry->fresh()->getAttributes();
        $payload = ['recorded_on' => today()->toDateString(), 'birds' => 0, 'feed_sacks' => 0];
        $payload[$field] = $value;
        $this->put(route('broiler.entries.daily.update', [$entry->cycle, $entry]), $payload)->assertSessionHasErrors($error);
        $this->assertSame($before, $entry->fresh()->getAttributes());
    }

    public function test_duplicate_date_and_closed_cycle_are_rejected(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $entry = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'daily', 'daily_on' => today()->subDay(), 'recorded_on' => today()->subDay(), 'amount' => 0]);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'daily', 'daily_on' => today(), 'amount' => 0]);
        $payload = ['recorded_on' => today()->toDateString(), 'birds' => 0, 'feed_sacks' => 0];
        $this->put(route('broiler.entries.daily.update', [$cycle, $entry]), $payload)->assertSessionHasErrors('recorded_on');
        $cycle->update(['closed_on' => today()]);
        $payload['recorded_on'] = today()->subDay()->toDateString();
        $this->put(route('broiler.entries.daily.update', [$cycle, $entry]), $payload)->assertSessionHasErrors('cycle');
        $this->assertSame(today()->subDay()->toDateString(), $entry->fresh()->daily_on->toDateString());
    }

    public function test_other_cycle_and_non_daily_records_are_rejected(): void
    {
        $cycle = BroilerCycle::factory()->create();
        $other = BroilerEntry::factory()->create(['kind' => 'daily']);
        $expense = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id]);
        $payload = ['recorded_on' => today()->toDateString(), 'birds' => 0, 'feed_sacks' => 0];
        $this->put(route('broiler.entries.daily.update', [$cycle, $other]), $payload)->assertNotFound();
        $this->put(route('broiler.entries.daily.update', [$cycle, $expense]), $payload)->assertNotFound();
        $this->assertModelExists($other);
        $this->assertModelExists($expense);
    }

    public function test_edit_cannot_consume_feed_before_delivery_or_invalidate_weight_samples(): void
    {
        $cycle = BroilerCycle::factory()->create();
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'feed_delivery', 'recorded_on' => today(), 'feed_kg' => 100]);
        $entry = BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'daily', 'daily_on' => today(), 'feed_kg' => 50, 'birds' => 0, 'amount' => 0]);
        BroilerEntry::factory()->create(['broiler_cycle_id' => $cycle->id, 'kind' => 'weight', 'birds' => 990, 'weight_kg' => 990, 'amount' => 0]);
        $before = $entry->fresh()->getAttributes();
        $this->put(route('broiler.entries.daily.update', [$cycle, $entry]), ['recorded_on' => today()->subDay()->toDateString(), 'birds' => 0, 'feed_sacks' => 1])->assertSessionHasErrors('feed_sacks');
        $this->put(route('broiler.entries.daily.update', [$cycle, $entry]), ['recorded_on' => today()->toDateString(), 'birds' => 20, 'feed_sacks' => 1])->assertSessionHasErrors('birds');
        $this->assertSame($before, $entry->fresh()->getAttributes());
    }
}
