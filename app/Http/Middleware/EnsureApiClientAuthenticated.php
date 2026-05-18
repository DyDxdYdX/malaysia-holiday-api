<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiClientAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rawApiKey = trim((string) $request->header('X-API-Key'));

        if ($rawApiKey === '') {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'API key is required.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $apiKeyHash = hash('sha256', $rawApiKey);
        $client = ApiClient::query()
            ->where('api_key_hash', $apiKeyHash)
            ->where('status', 'active')
            ->first();

        if ($client === null) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'API key is invalid or disabled.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $rateLimitKey = 'api-client:'.$client->id;
        $maxAttempts = max((int) $client->rate_limit_per_minute, 1);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            return response()->json([
                'error' => [
                    'code' => 'TOO_MANY_REQUESTS',
                    'message' => 'API rate limit exceeded.',
                ],
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::hit($rateLimitKey, 60);
        $request->attributes->set('api_client', $client);

        return $next($request);
    }
}
