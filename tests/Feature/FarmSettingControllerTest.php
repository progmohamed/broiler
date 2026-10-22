<?php

namespace Tests\Feature;

use App\Models\FarmSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FarmSettingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_settings_page_is_displayed(): void
    {
        $this->get(route('farm-settings.edit'))
            ->assertOk()
            ->assertSee('إعدادات المزرعة')
            ->assertSee('تاريخ ميلاد القطيع')
            ->assertSee('إجمالي عدد القطيع');
    }

    public function test_farm_settings_are_saved(): void
    {
        $this->put(route('farm-settings.update'), [
            'farm_name' => 'Green Valley Layers',
            'herd_birth_date' => '2026-01-15',
            'total_herd_count' => 1200,
        ])->assertRedirect(route('farm-settings.edit'))
            ->assertSessionHas('status', 'تم حفظ إعدادات المزرعة.');

        $this->assertDatabaseHas('farm_settings', [
            'farm_name' => 'Green Valley Layers',
            'total_herd_count' => 1200,
        ]);
    }

    public function test_existing_farm_settings_are_updated(): void
    {
        FarmSetting::query()->create([
            'farm_name' => 'Old Farm',
            'herd_birth_date' => '2026-01-01',
            'total_herd_count' => 1000,
        ]);

        $this->put(route('farm-settings.update'), [
            'farm_name' => 'Green Valley Layers',
            'herd_birth_date' => '2026-01-15',
            'total_herd_count' => 1200,
        ])->assertRedirect(route('farm-settings.edit'));

        $this->assertDatabaseCount('farm_settings', 1);
        $this->assertDatabaseHas('farm_settings', [
            'farm_name' => 'Green Valley Layers',
            'total_herd_count' => 1200,
        ]);
    }

    public function test_farm_settings_require_birth_date_and_herd_count(): void
    {
        $this->from(route('farm-settings.edit'))
            ->put(route('farm-settings.update'), [])
            ->assertRedirect(route('farm-settings.edit'))
            ->assertInvalid([
                'herd_birth_date' => 'اختر تاريخ ميلاد القطيع.',
                'total_herd_count' => 'أدخل إجمالي عدد القطيع.',
            ]);

        $this->assertDatabaseCount('farm_settings', 0);
    }
}
