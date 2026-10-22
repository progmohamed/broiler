<?php

namespace App\Http\Controllers;

use App\Models\FarmSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppUnlockController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (FarmSetting::query()->whereNotNull('app_pin')->doesntExist()) {
            return to_route('broiler.index');
        }

        return view('auth.unlock');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pin' => ['required', 'digits_between:4,8'],
        ], [
            'pin.required' => 'أدخل رقم PIN.',
            'pin.digits_between' => 'رقم PIN غير صحيح.',
        ]);
        $setting = FarmSetting::query()->whereNotNull('app_pin')->first();

        if ($setting === null || ! Hash::check($validated['pin'], $setting->app_pin)) {
            throw ValidationException::withMessages(['pin' => 'رقم PIN غير صحيح.']);
        }

        $request->session()->regenerate();
        $request->session()->put('farm_unlocked', true);

        return redirect()->intended(route('broiler.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('farm_unlocked');
        $request->session()->regenerateToken();

        return to_route('app-lock.create');
    }
}
