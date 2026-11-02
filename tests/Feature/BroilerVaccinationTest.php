<?php

namespace Tests\Feature;

use App\Models\BroilerCycle;
use App\Models\BroilerVaccination;
use App\Models\FarmSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BroilerVaccinationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_stores_local_appointment_and_reminder_as_utc(): void
    {
        $cycle = BroilerCycle::factory()->create(['started_on' => '2026-10-01']);
        $this->post(route('broiler.vaccinations.store', $cycle), $this->payload())->assertSessionHasNoErrors()->assertRedirect(route('broiler.show', $cycle));
        $record = $cycle->vaccinations()->sole();
        $this->assertSame('2026-10-24 06:00:00', $record->due_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-23 06:00:00', $record->remind_at->format('Y-m-d H:i:s'));
        $this->assertSame('Africa/Cairo', $record->timezone);
        $this->get(route('broiler.show', $cycle))->assertOk()->assertSee('جدول تحصينات الدورة')->assertSee('2026-10-24 09:00');
    }

    public function test_uses_cairo_winter_time_and_ignores_submitted_timezone(): void
    {
        $cycle = BroilerCycle::factory()->create(['started_on' => '2026-10-01']);
        $this->post(route('broiler.vaccinations.store', $cycle), $this->payload(['scheduled_at' => '2027-01-10T09:00', 'timezone' => 'Asia/Riyadh']))->assertSessionHasNoErrors();
        $record = $cycle->vaccinations()->sole();
        $this->assertSame('2027-01-10 07:00:00', $record->due_at->format('Y-m-d H:i:s'));
        $this->assertSame('Africa/Cairo', $record->timezone);
        $this->get(route('broiler.show', $cycle))->assertSee('2027-01-10 09:00')->assertDontSee('name="timezone"', false);
    }

    public function test_reminder_appears_at_threshold_and_excludes_completed_closed_and_future_records(): void
    {
        $this->travelTo('2026-10-23 06:00:00');
        $due = BroilerVaccination::factory()->create(['name' => 'تنبيه مستحق', 'remind_at' => now(), 'due_at' => now()->addDay()]);
        BroilerVaccination::factory()->create(['name' => 'لاحق', 'remind_at' => now()->addMinute()]);
        BroilerVaccination::factory()->create(['name' => 'منفذ', 'completed_at' => now()]);
        BroilerVaccination::factory()->create(['name' => 'مغلق', 'broiler_cycle_id' => BroilerCycle::factory()->create(['closed_on' => today()])->id]);
        $this->getJson(route('broiler.vaccinations.reminders'))->assertOk()->assertJsonCount(1, 'reminders')->assertJsonPath('reminders.0.id', $due->id)->assertJsonPath('reminders.0.overdue', false)->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_overdue_reminders_remain_until_completed(): void
    {
        $record = BroilerVaccination::factory()->create(['due_at' => now()->subDay(), 'remind_at' => now()->subDays(2)]);
        $this->getJson(route('broiler.vaccinations.reminders'))->assertJsonPath('reminders.0.overdue', true);
        $this->post(route('broiler.vaccinations.complete', [$record->cycle, $record]))->assertSessionHasNoErrors();
        $this->assertNotNull($record->fresh()->completed_at);
        $this->getJson(route('broiler.vaccinations.reminders'))->assertJsonCount(0, 'reminders');
    }

    public function test_edit_reschedules_reminder_and_delete_removes_it(): void
    {
        $cycle = BroilerCycle::factory()->create(['started_on' => '2026-10-01']);
        $record = BroilerVaccination::factory()->create(['broiler_cycle_id' => $cycle->id]);
        $this->put(route('broiler.vaccinations.update', [$cycle, $record]), $this->payload(['remind_before_hours' => 6]))->assertSessionHasNoErrors();
        $this->assertSame('2026-10-24 00:00:00', $record->fresh()->remind_at->format('Y-m-d H:i:s'));
        $this->delete(route('broiler.vaccinations.destroy', [$cycle, $record]))->assertSessionHasNoErrors();
        $this->assertModelMissing($record);
    }

    public function test_other_cycle_cannot_change_complete_or_delete_appointment(): void
    {
        $cycle = BroilerCycle::factory()->create(['started_on' => '2026-10-01']);
        $record = BroilerVaccination::factory()->create();
        $this->put(route('broiler.vaccinations.update', [$cycle, $record]), $this->payload())->assertNotFound();
        $this->post(route('broiler.vaccinations.complete', [$cycle, $record]))->assertNotFound();
        $this->delete(route('broiler.vaccinations.destroy', [$cycle, $record]))->assertNotFound();
        $this->assertNull($record->fresh()->completed_at);
        $this->assertModelExists($record);
    }

    public function test_closed_cycle_rejects_schedule_changes(): void
    {
        $cycle = BroilerCycle::factory()->create(['started_on' => '2026-10-01', 'closed_on' => today()]);
        $record = BroilerVaccination::factory()->create(['broiler_cycle_id' => $cycle->id]);
        $this->post(route('broiler.vaccinations.store', $cycle), $this->payload())->assertSessionHasErrors('cycle');
        $this->post(route('broiler.vaccinations.complete', [$cycle, $record]))->assertSessionHasErrors('cycle');
        $this->delete(route('broiler.vaccinations.destroy', [$cycle, $record]))->assertSessionHasErrors('cycle');
        $this->assertDatabaseCount('broiler_vaccinations', 1);
    }

    public function test_completed_records_are_preserved_and_completion_is_idempotent(): void
    {
        $record = BroilerVaccination::factory()->create(['completed_at' => now()->subDay()]);
        $original = $record->completed_at->toDateTimeString();
        $this->post(route('broiler.vaccinations.complete', [$record->cycle, $record]))->assertSessionHasNoErrors();
        $this->assertSame($original, $record->fresh()->completed_at->toDateTimeString());
        $this->put(route('broiler.vaccinations.update', [$record->cycle, $record]), $this->payload())->assertSessionHasErrors('vaccination');
        $this->delete(route('broiler.vaccinations.destroy', [$record->cycle, $record]))->assertSessionHasErrors('vaccination');
    }

    #[TestWith(['name', ''])]
    #[TestWith(['scheduled_at', 'not-a-date'])]
    #[TestWith(['scheduled_at', '2026-09-30T09:00'])]
    #[TestWith(['remind_before_hours', 0])]
    #[TestWith(['remind_before_hours', 169])]
    public function test_rejects_invalid_schedules(string $field, mixed $value): void
    {
        $cycle = BroilerCycle::factory()->create(['started_on' => '2026-10-01']);
        $this->post(route('broiler.vaccinations.store', $cycle), $this->payload([$field => $value]))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('broiler_vaccinations', 0);
    }

    public function test_calendar_contains_utc_dates_alarm_and_only_this_cycles_pending_records(): void
    {
        $cycle = BroilerCycle::factory()->create(['started_on' => '2026-10-01']);
        $this->post(route('broiler.vaccinations.store', $cycle), $this->payload(['name' => 'موعد خاص', 'notes' => "ملاحظة\nEND:VEVENT"]))->assertSessionHasNoErrors();
        BroilerVaccination::factory()->create(['name' => 'دورة أخرى']);
        BroilerVaccination::factory()->create(['broiler_cycle_id' => $cycle->id, 'name' => 'مكتمل', 'completed_at' => now()]);
        $response = $this->get(route('broiler.vaccinations.calendar', $cycle))->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $content = str_replace("\r\n ", '', $response->getContent());
        $this->assertStringContainsString('DTSTART:20261024T060000Z', $content);
        $this->assertStringContainsString('TRIGGER:-PT24H', $content);
        $this->assertSame(1, substr_count($content, "\r\nEND:VEVENT\r\n"));
        $this->assertStringNotContainsString('دورة أخرى', $content);
        $this->assertStringNotContainsString('مكتمل', $content);
    }

    public function test_escapes_vaccine_names_and_ignores_client_completion_fields(): void
    {
        $cycle = BroilerCycle::factory()->create(['started_on' => '2026-10-01']);
        $this->post(route('broiler.vaccinations.store', $cycle), $this->payload(['name' => '<script>alert(1)</script>', 'completed_at' => now()->toDateTimeString(), 'broiler_cycle_id' => 999]))->assertSessionHasNoErrors();
        $this->assertNull($cycle->vaccinations()->sole()->completed_at);
        $this->get(route('broiler.show', $cycle))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_pin_protects_reminders_and_calendar(): void
    {
        $setting = new FarmSetting;
        $setting->herd_birth_date = today();
        $setting->total_herd_count = 0;
        $setting->app_pin = bcrypt('1234');
        $setting->save();
        $cycle = BroilerCycle::factory()->create();
        $this->getJson(route('broiler.vaccinations.reminders'))->assertRedirect(route('app-lock.create'));
        $this->get(route('broiler.vaccinations.calendar', $cycle))->assertRedirect(route('app-lock.create'));
        $this->post(route('broiler.vaccinations.store', $cycle), $this->payload())->assertRedirect(route('app-lock.create'));
        $this->assertDatabaseCount('broiler_vaccinations', 0);
    }

    /** @param array<string, mixed> $overrides @return array<string, mixed> */
    #[TestWith([1, '2026-10-21 06:00:00'])]
    #[TestWith([7, '2026-10-27 06:00:00'])]
    #[TestWith([11, '2026-10-31 07:00:00'])]
    public function test_schedules_by_cycle_day_with_cairo_daylight_saving(int $day, string $expected): void
    {
        $cycle = BroilerCycle::factory()->create(['started_on' => '2026-10-21']);
        $this->post(route('broiler.vaccinations.store', $cycle), ['name' => 'تحصين', 'cycle_day' => $day, 'scheduled_time' => '09:00', 'remind_before_hours' => 24])->assertSessionHasNoErrors();
        $record = $cycle->vaccinations()->sole();
        $this->assertSame($expected, $record->due_at->format('Y-m-d H:i:s'));
        $this->assertSame($day, $record->cycleDay($cycle));
        $this->get(route('broiler.show', $cycle))->assertSee('اليوم '.$day.' من الدورة')->assertSee('name="cycle_day"', false)->assertDontSee('type="datetime-local"', false);
        $this->put(route('broiler.vaccinations.update', [$cycle, $record]), ['name' => 'تحصين', 'cycle_day' => 8, 'scheduled_time' => '10:00', 'remind_before_hours' => 6])->assertSessionHasNoErrors();
        $this->assertSame('2026-10-28 07:00:00', $record->fresh()->due_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-28 01:00:00', $record->fresh()->remind_at->format('Y-m-d H:i:s'));
    }

    #[TestWith([0, '09:00', 'cycle_day'])]
    #[TestWith([-1, '09:00', 'cycle_day'])]
    #[TestWith([1.5, '09:00', 'cycle_day'])]
    #[TestWith([null, '09:00', 'cycle_day'])]
    #[TestWith([7, '25:00', 'scheduled_time'])]
    public function test_invalid_cycle_day_or_time_is_rejected(mixed $day, string $time, string $field): void
    {
        $cycle = BroilerCycle::factory()->create();
        $this->post(route('broiler.vaccinations.store', $cycle), ['name' => 'تحصين', 'cycle_day' => $day, 'scheduled_time' => $time, 'remind_before_hours' => 24])->assertSessionHasErrors($field);
        $this->assertDatabaseCount('broiler_vaccinations', 0);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['name' => 'تحصين الدورة', 'scheduled_at' => '2026-10-24T09:00', 'remind_before_hours' => 24], $overrides);
    }
}
