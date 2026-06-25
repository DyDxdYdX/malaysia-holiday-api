<?php

namespace App\Services\Analytics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VisitorIdentifier
{
    public function hash(?string $ipAddress, ?Carbon $now = null): ?string
    {
        $normalizedIp = $this->normalizeIp($ipAddress);
        if ($normalizedIp === null) {
            return null;
        }

        $secret = trim((string) config('analytics.hash_secret', ''));
        if ($secret === '') {
            $this->warnMissingSecret();

            return null;
        }

        $rotationKey = match (config('analytics.hash_rotation', 'daily')) {
            'daily' => ($now ?? now())->clone()->utc()->toDateString(),
            default => ($now ?? now())->clone()->utc()->toDateString(),
        };

        return hash_hmac('sha256', $rotationKey.'|'.$normalizedIp, $secret);
    }

    public function networkPrefix(?string $ipAddress): ?string
    {
        if (! config('analytics.store_network_prefix', false)) {
            return null;
        }

        $normalizedIp = $this->normalizeIp($ipAddress);
        if ($normalizedIp === null) {
            return null;
        }

        if (filter_var($normalizedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $parts = explode('.', $normalizedIp);

            return "{$parts[0]}.{$parts[1]}.{$parts[2]}.0/24";
        }

        $packed = inet_pton($normalizedIp);
        if ($packed === false) {
            return null;
        }

        $prefixHex = substr(bin2hex($packed), 0, 16).str_repeat('0', 16);
        $prefix = inet_ntop(hex2bin($prefixHex));

        return $prefix === false ? null : "{$prefix}/64";
    }

    private function normalizeIp(?string $ipAddress): ?string
    {
        $ipAddress = trim((string) $ipAddress);
        if ($ipAddress === '') {
            return null;
        }

        $packed = @inet_pton($ipAddress);
        if ($packed === false) {
            return null;
        }

        $normalizedIp = inet_ntop($packed);

        return $normalizedIp === false ? null : $normalizedIp;
    }

    private function warnMissingSecret(): void
    {
        try {
            if (! Cache::add('analytics:missing-hash-secret-warning', true, now()->addHour())) {
                return;
            }
        } catch (\Throwable) {
            //
        }

        Log::warning('Analytics request logging skipped because ANALYTICS_HASH_SECRET is not configured.');
    }
}
