<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

#[Signature('analytics:anonymize-request-ips
    {--before= : Required cutoff date in YYYY-MM-DD format; only request logs before this date are affected}
    {--chunk=1000 : Number of records to update per batch}
    {--dry-run : Report affected records without updating them}
    {--force : Required to update records}')]
#[Description('Null historical raw IP addresses in request_logs only.')]
class AnonymizeHistoricalRequestIps extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $before = $this->option('before');
        if (! is_string($before) || trim($before) === '') {
            $this->error('The --before=YYYY-MM-DD option is required.');

            return self::FAILURE;
        }

        try {
            $cutoff = Carbon::createFromFormat('Y-m-d', $before)->startOfDay();
        } catch (\Throwable) {
            $this->error('The --before option must use YYYY-MM-DD format.');

            return self::FAILURE;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $query = DB::table('request_logs')
            ->whereNotNull('ip_address')
            ->where('created_at', '<', $cutoff);

        $affected = (clone $query)->count();

        if ($dryRun) {
            $this->info("Request log IP addresses that would be nulled: {$affected}");

            return self::SUCCESS;
        }

        if (! $force) {
            $this->error('Refusing to modify records without --force. Use --dry-run to preview affected rows.');

            return self::FAILURE;
        }

        $updated = 0;
        do {
            $ids = (clone $query)
                ->orderBy('id')
                ->limit($chunkSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $updated += DB::table('request_logs')
                ->whereIn('id', $ids)
                ->update(['ip_address' => null]);
        } while ($ids->count() === $chunkSize);

        $this->info("Request log IP addresses nulled: {$updated}");

        return self::SUCCESS;
    }
}
