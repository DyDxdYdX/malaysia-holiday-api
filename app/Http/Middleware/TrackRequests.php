<?php

namespace App\Http\Middleware;

use App\Models\RequestLog;
use App\Services\Analytics\UrlSanitizer;
use App\Services\Analytics\VisitorIdentifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class TrackRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('start_time', microtime(true));

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            if (! config('analytics.enabled', true)) {
                return;
            }

            $path = $request->path();

            if ($this->shouldExclude($path, $request)) {
                return;
            }

            $visitorIdentifier = app(VisitorIdentifier::class);
            $visitorHash = $visitorIdentifier->hash($request->ip());

            if ($visitorHash === null) {
                return;
            }

            $urlSanitizer = app(UrlSanitizer::class);
            $startTime = $request->attributes->get('start_time');
            $durationMs = $startTime ? (int) round((microtime(true) - $startTime) * 1000) : null;

            $routeType = 'web';
            if ($request->is('api/*')) {
                $routeType = 'api';
            } elseif ($request->is('admin/*')) {
                $routeType = 'admin';
            }

            RequestLog::create([
                'ip_address' => config('analytics.store_raw_ip', false) ? $request->ip() : null,
                'visitor_hash' => $visitorHash,
                'network_prefix' => $visitorIdentifier->networkPrefix($request->ip()),
                'method' => $request->method(),
                'path' => $urlSanitizer->sanitizedPath($request),
                'full_url' => $urlSanitizer->sanitizedUrl($request),
                'status_code' => $response->getStatusCode(),
                'user_agent' => $request->userAgent(),
                'duration_ms' => $durationMs,
                'user_id' => $request->user()?->id,
                'route_type' => $routeType,
                'expires_at' => Carbon::now()->addDays(max(1, (int) config('analytics.retention_days', 90))),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Determine if the request path should be excluded from logging.
     */
    protected function shouldExclude(string $path, Request $request): bool
    {
        if ($path === 'up' || $request->is('_boost/*') || $request->is('flux/*')) {
            return true;
        }

        if ($this->isAnalyticsDashboardLivewireRequest($request)) {
            return true;
        }

        if (preg_match('/\.(css|js|ico|png|jpg|jpeg|gif|svg|woff2?|map|json|txt)$/i', $path)) {
            return true;
        }

        return false;
    }

    private function isAnalyticsDashboardLivewireRequest(Request $request): bool
    {
        if (! $request->is('livewire/*')) {
            return false;
        }

        if ($this->containsAnalyticsDashboardReference($request->input())) {
            return true;
        }

        $content = $request->getContent();

        return str_contains($content, 'App\\\\Livewire\\\\Admin\\\\AnalyticsDashboard')
            || str_contains($content, 'admin.analytics-dashboard');
    }

    /**
     * @param  array<mixed>  $payload
     */
    private function containsAnalyticsDashboardReference(array $payload): bool
    {
        foreach ($payload as $value) {
            if (is_array($value) && $this->containsAnalyticsDashboardReference($value)) {
                return true;
            }

            if (is_string($value)
                && (str_contains($value, 'App\\\\Livewire\\\\Admin\\\\AnalyticsDashboard')
                    || str_contains($value, 'admin.analytics-dashboard'))) {
                return true;
            }
        }

        return false;
    }
}
