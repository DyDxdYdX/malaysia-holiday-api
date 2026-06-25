<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

#[Signature('analytics:prune {--chunk=1000 : Number of records to delete per batch} {--dry-run : Report records that would be deleted without deleting them}')]
#[Description('Prune expired analytics request logs and optionally old audit logs.')]
class PruneAnalytics extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $expiredRequestLogs = DB::table('request_logs')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());

        $requestLogCount = (clone $expiredRequestLogs)->count();
        $deletedRequestLogs = $dryRun ? 0 : $this->deleteInChunks('request_logs', $expiredRequestLogs, $chunkSize);

        $this->info($dryRun
            ? "Request logs that would be deleted: {$requestLogCount}"
            : "Request logs deleted: {$deletedRequestLogs}");

        if (! config('analytics.audit_log_pruning_enabled', false)) {
            $this->info('Audit log pruning is disabled.');

            return self::SUCCESS;
        }

        $auditCutoff = now()->subDays(max(1, (int) config('analytics.audit_log_retention_days', 365)));
        $expiredAuditLogs = DB::table('audit_logs')
            ->where('created_at', '<', $auditCutoff);

        $auditLogCount = (clone $expiredAuditLogs)->count();
        $deletedAuditLogs = $dryRun ? 0 : $this->deleteInChunks('audit_logs', $expiredAuditLogs, $chunkSize);

        $this->info($dryRun
            ? "Audit logs that would be deleted: {$auditLogCount}"
            : "Audit logs deleted: {$deletedAuditLogs}");

        return self::SUCCESS;
    }

    private function deleteInChunks(string $table, Builder $query, int $chunkSize): int
    {
        $deleted = 0;

        do {
            $ids = (clone $query)
                ->orderBy('id')
                ->limit($chunkSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += DB::table($table)
                ->whereIn('id', $ids)
                ->delete();
        } while ($ids->count() === $chunkSize);

        return $deleted;
    }
}
