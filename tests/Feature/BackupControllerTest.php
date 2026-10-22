<?php

namespace Tests\Feature;

use App\Models\DailyRecord;
use App\Models\FarmSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BackupControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_backup_download_contains_the_supported_farm_tables(): void
    {
        FarmSetting::query()->create([
            'farm_name' => 'مزرعة الاختبار',
            'herd_birth_date' => '2026-01-01',
            'total_herd_count' => 1000,
        ]);
        DailyRecord::factory()->create(['recorded_on' => '2026-09-01']);

        $response = $this->get(route('backups.download'));

        $response->assertOk()
            ->assertHeader('content-disposition')
            ->assertJsonPath('application', 'layer-farm-management')
            ->assertJsonPath('version', 1)
            ->assertJsonCount(1, 'tables.daily_records')
            ->assertJsonCount(1, 'tables.farm_settings')
            ->assertJsonMissingPath('tables.farm_settings.0.app_pin');
    }

    public function test_a_valid_backup_can_replace_and_restore_the_data(): void
    {
        DailyRecord::factory()->create(['recorded_on' => '2026-09-01', 'production' => 500]);
        $backupContents = $this->get(route('backups.download'))->getContent();
        DailyRecord::query()->delete();
        DailyRecord::factory()->create(['recorded_on' => '2026-09-02', 'production' => 999]);

        $this->post(route('backups.restore'), [
            'backup' => UploadedFile::fake()->createWithContent('backup.json', $backupContents),
            'confirmation' => 'استعادة',
        ])->assertRedirect(route('backups.index'))
            ->assertSessionHas('status', 'تمت استعادة النسخة الاحتياطية بنجاح.');

        $this->assertDatabaseCount('daily_records', 1);
        $this->assertDatabaseHas('daily_records', ['recorded_on' => '2026-09-01 00:00:00', 'production' => 500]);
        $this->assertDatabaseMissing('daily_records', ['recorded_on' => '2026-09-02']);
    }

    public function test_restore_requires_confirmation_and_a_valid_backup(): void
    {
        $this->from(route('backups.index'))->post(route('backups.restore'), [
            'backup' => UploadedFile::fake()->createWithContent('bad.json', '{}'),
            'confirmation' => 'نعم',
        ])->assertRedirect(route('backups.index'))
            ->assertInvalid(['confirmation']);

        $this->assertDatabaseCount('daily_records', 0);
    }
}
