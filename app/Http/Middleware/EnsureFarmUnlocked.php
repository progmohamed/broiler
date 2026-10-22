<?php

namespace App\Http\Middleware;

use App\Models\FarmSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFarmUnlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $pinIsEnabled = FarmSetting::query()->whereNotNull('app_pin')->exists();

        if ($pinIsEnabled && ! (bool) $request->session()->get('farm_unlocked', false)) {
            return to_route('app-lock.create');
        }

        return $next($request);
    }
}
