<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        RateLimiter::for('api-client', function (Request $request): Limit {
            $client = $request->attributes->get('api_client');
            $limit = (int) ($client?->rate_limit_per_minute ?? 60);
            $key = $client?->id !== null ? 'api-client:'.$client->id : $request->ip();

            return Limit::perMinute(max($limit, 1))
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'error' => [
                            'code' => 'TOO_MANY_REQUESTS',
                            'message' => 'API rate limit exceeded.',
                        ],
                    ], 429, $headers);
                });
        });

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
