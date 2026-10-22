<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateFarmSettingRequest;
use App\Models\FarmSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class FarmSettingController extends Controller
{
    public function edit(): View
    {
        return view('farm-settings.edit', [
            'setting' => FarmSetting::query()->first(),
        ]);
    }

    public function update(UpdateFarmSettingRequest $request): RedirectResponse
    {
        $setting = FarmSetting::query()->firstOrNew(['id' => 1]);
        $setting->fill($request->safe()->only(['farm_name', 'herd_birth_date', 'total_herd_count']));

        if ($request->boolean('remove_pin')) {
            $setting->app_pin = null;
            $request->session()->forget('farm_unlocked');
        } elseif ($request->filled('new_pin')) {
            $setting->app_pin = Hash::make($request->string('new_pin')->toString());
            $request->session()->put('farm_unlocked', true);
        }

        $setting->save();

        return to_route('farm-settings.edit')->with('status', 'تم حفظ إعدادات المزرعة.');
    }
}
