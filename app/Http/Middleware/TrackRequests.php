<?php

namespace App\Http\Middleware;

use App\Models\RequestLog;
use Closure;
use Illuminate\Http\Request;
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
            $path = $request->path();

            if ($this->shouldExclude($path, $request)) {
                return;
            }

            $startTime = $request->attributes->get('start_time');
            $durationMs = $startTime ? (int) round((microtime(true) - $startTime) * 1000) : null;

            $routeType = 'web';
            if ($request->is('api/*')) {
                $routeType = 'api';
            } elseif ($request->is('admin/*')) {
                $routeType = 'admin';
            }

            RequestLog::create([
                'ip_address' => $request->ip() ?? '127.0.0.1',
                'method' => $request->method(),
                'path' => '/'.ltrim($path, '/'),
                'full_url' => $request->fullUrl(),
                'status_code' => $response->getStatusCode(),
                'user_agent' => $request->userAgent(),
                'duration_ms' => $durationMs,
                'user_id' => $request->user()?->id,
                'route_type' => $routeType,
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
        if ($path === 'up' || $request->is('livewire/*') || $request->is('_boost/*') || $request->is('flux/*')) {
            return true;
        }

        if (preg_match('/\.(css|js|ico|png|jpg|jpeg|gif|svg|woff2?|map|json|txt)$/i', $path)) {
            return true;
        }

        return false;
    }
}
