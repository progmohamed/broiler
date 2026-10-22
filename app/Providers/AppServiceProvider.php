<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('farm-assistant', fn (Request $request): Limit => Limit::perMinute(10)
            ->by('farm-assistant:'.$request->ip())
            ->response(fn (Request $request, array $headers): JsonResponse => response()->json([
                'message' => 'أرسلت أسئلة كثيرة خلال دقيقة. انتظر قليلًا ثم حاول مجددًا.',
            ], 429, $headers)));
    }
}
