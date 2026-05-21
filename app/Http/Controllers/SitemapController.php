<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Support\MalaysiaStates;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate the dynamic sitemap XML.
     */
    public function sitemap(): Response
    {
        $lastModified = now()->toDateString();

        /** @var list<string> $urls */
        $urls = [
            route('home'),
            route('api.docs'),
            route('api.playground'),
            route('holidays.calendar'),
        ];

        /** @var array<int, int> $years */
        $years = Holiday::query()
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->all();

        if (empty($years)) {
            $years = [now()->year, now()->year + 1];
        }

        $states = MalaysiaStates::codes();

        foreach ($years as $year) {
            $urls[] = route('holidays.calendar', ['year' => $year]);

            foreach ($states as $stateCode) {
                $urls[] = route('holidays.calendar', [
                    'year' => $year,
                    'state_code' => $stateCode,
                ]);
            }
        }

        $entries = collect($urls)->map(function (string $url) use ($lastModified): string {
            return implode('', [
                '<url>',
                '<loc>'.e($url).'</loc>',
                '<lastmod>'.$lastModified.'</lastmod>',
                '<changefreq>weekly</changefreq>',
                '</url>',
            ]);
        })->implode('');

        $xml = implode('', [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            $entries,
            '</urlset>',
        ]);

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Generate the robots.txt content referencing the dynamic sitemap.
     */
    public function robots(): Response
    {
        $content = implode(PHP_EOL, [
            'User-agent: *',
            'Allow: /',
            'Sitemap: '.route('sitemap'),
            '',
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
