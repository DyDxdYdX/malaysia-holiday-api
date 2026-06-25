<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UrlSanitizer
{
    public function sanitizedPath(Request $request): string
    {
        return '/'.ltrim($request->path(), '/');
    }

    public function sanitizedUrl(Request $request): string
    {
        $path = $this->sanitizedPath($request);
        $allowedParameters = collect(config('analytics.allowed_query_parameters', []))
            ->map(fn (string $parameter): string => Str::lower($parameter))
            ->all();

        $query = [];
        foreach ($request->query() as $key => $value) {
            $normalizedKey = Str::lower((string) $key);

            if (! in_array($normalizedKey, $allowedParameters, true)) {
                continue;
            }

            $stringValue = $this->stringQueryValue($value);
            if ($stringValue === null) {
                continue;
            }

            $query[$normalizedKey] = $stringValue;
        }

        if ($query === []) {
            return $path;
        }

        return $path.'?'.Arr::query($query);
    }

    private function stringQueryValue(mixed $value): ?string
    {
        if (is_array($value) || $value === null) {
            return null;
        }

        return (string) $value;
    }
}
