<?php

namespace Tests\Feature;

use App\Models\FarmSetting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AppLockTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_app_remains_open_when_no_pin_is_configured(): void
    {
        $this->get(route('broiler.index'))->assertOk();
    }

    public function test_locked_app_redirects_to_pin_screen_and_accepts_the_correct_pin(): void
    {
        $this->createSettingsWithPin('1234');

        $this->get(route('broiler.index'))->assertRedirect(route('app-lock.create'));
        $this->get(route('app-lock.create'))
            ->assertOk()
            ->assertSee('مزارع شوقي')
            ->assertSee('تسمين')
            ->assertSee('أدخل رقم PIN لفتح التطبيق')
            ->assertSee('images/icons/icon-192x192.png');

        $this->post(route('app-lock.store'), ['pin' => '0000'])
            ->assertInvalid(['pin' => 'رقم PIN غير صحيح.']);

        $this->post(route('app-lock.store'), ['pin' => '1234'])
            ->assertRedirect(route('broiler.index'));

        $this->get(route('broiler.index'))->assertOk();
    }

    public function test_settings_can_enable_and_remove_the_pin(): void
    {
        FarmSetting::query()->create([
            'farm_name' => 'المزرعة',
            'herd_birth_date' => '2026-01-01',
            'total_herd_count' => 1000,
        ]);

        $this->put(route('farm-settings.update'), [
            'farm_name' => 'المزرعة',
            'herd_birth_date' => '2026-01-01',
            'total_herd_count' => 1000,
            'new_pin' => '5678',
            'new_pin_confirmation' => '5678',
        ])->assertRedirect(route('farm-settings.edit'));

        $this->assertTrue(Hash::check('5678', FarmSetting::query()->firstOrFail()->app_pin));

        $this->put(route('farm-settings.update'), [
            'farm_name' => 'المزرعة',
            'herd_birth_date' => '2026-01-01',
            'total_herd_count' => 1000,
            'remove_pin' => '1',
        ])->assertRedirect(route('farm-settings.edit'));

        $this->assertNull(FarmSetting::query()->firstOrFail()->app_pin);
    }

    public function test_web_responses_include_security_headers(): void
    {
        $this->get(route('inventory.index'))
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'same-origin');
    }

    public function test_pwa_manifest_uses_shawky_farms_branding_and_icons(): void
    {
        $this->get(route('laravelpwa.manifest'))
            ->assertOk()
            ->assertJsonPath('name', 'مزارع شوقي (تسمين)')
            ->assertJsonPath('short_name', 'مزارع شوقي')
            ->assertJsonPath('theme_color', '#064e3b')
            ->assertJsonPath('icons.7.src', '/images/icons/icon-512x512.png')
            ->assertJsonPath('icons.7.sizes', '512x512')
            ->assertJsonPath('icons.7.purpose', 'any maskable');
    }

    public function test_pages_publish_explicit_opaque_apple_touch_icons(): void
    {
        $this->createSettingsWithPin('1234');

        $this->get(route('app-lock.create'))
            ->assertOk()
            ->assertSee('rel="apple-touch-icon" sizes="152x152"', false)
            ->assertSee('rel="apple-touch-icon" sizes="167x167"', false)
            ->assertSee('rel="apple-touch-icon" sizes="180x180"', false)
            ->assertSee('/apple-touch-icon.png?v=2', false);

        $this->assertFileExists(public_path('apple-touch-icon.png'));
        $imageSize = getimagesize(public_path('apple-touch-icon.png'));

        $this->assertIsArray($imageSize);
        $this->assertSame(180, $imageSize[0]);
        $this->assertSame(180, $imageSize[1]);
    }

    private function createSettingsWithPin(string $pin): void
    {
        $setting = FarmSetting::query()->create([
            'farm_name' => 'المزرعة',
            'herd_birth_date' => '2026-01-01',
            'total_herd_count' => 1000,
        ]);

        $setting->app_pin = Hash::make($pin);
        $setting->save();
    }
}
