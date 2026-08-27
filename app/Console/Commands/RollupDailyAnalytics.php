<?php

namespace App\Console\Commands;

use App\Services\Analytics\DailyAnalyticsRollup;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('analytics:rollup
    {--from= : Start date (YYYY-MM-DD); defaults to the earliest request log day}
    {--to= : End date (YYYY-MM-DD); defaults to yesterday}
    {--limit=30 : Maximum days to process per run; 0 means no limit}
    {--chunk=500 : Visitor rows to insert per batch}
    {--force : Recompute days even when rollup rows already exist}
    {--dry-run : Report days that would be rolled up without writing}')]
#[Description('Roll up request logs into daily analytics summary tables, one day at a time.')]
class RollupDailyAnalytics extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DailyAnalyticsRollup $rollup): int
    {
        $limit = max(0, (int) $this->option('limit'));
        $chunkSize = max(1, (int) $this->option('chunk'));
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        try {
            $from = $this->option('from');
            $to = $this->option('to');
            $from = is_string($from) ? $from : null;
            $to = is_string($to) ? $to : null;

            $dates = $rollup->datesToProcess($from, $to, $limit, $force);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($dates->isEmpty()) {
            $this->info('No analytics days to roll up.');

            return self::SUCCESS;
        }

        $this->info($dryRun
            ? "Days that would be rolled up: {$dates->count()}"
            : "Rolling up {$dates->count()} day(s).");

        foreach ($dates as $date) {
            $this->line(" {$date->toDateString()}");

            if (! $dryRun) {
                $rollup->rollupDay($date, $chunkSize);
            }
        }

        if (! $dryRun) {
            $this->info("Daily analytics days rolled up: {$dates->count()}");
        }

        return self::SUCCESS;
    }
}
