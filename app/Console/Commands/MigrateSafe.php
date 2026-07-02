<?php

namespace App\Console\Commands;

use App\Services\MigrationSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateSafe extends Command
{
    protected $signature = 'migrate:safe {--force : Force the operation to run in production}';

    protected $description = 'Sync migration records for existing tables, then run pending migrations';

    public function handle(): int
    {
        $this->waitForDatabase();

        if (! Schema::hasTable('migrations')) {
            Artisan::call('migrate:install');
            $this->info('Created migrations table.');
        }

        $synced = MigrationSyncService::sync();

        if ($synced > 0) {
            $this->info("Synced {$synced} migration record(s) for existing tables.");
        } else {
            $this->info('No migration records needed syncing.');
        }

        return $this->runMigrationsWithRetry();
    }

    private function waitForDatabase(): void
    {
        $timeout = (int) env('AUTORUN_LARAVEL_MIGRATION_TIMEOUT', 60);
        $startedAt = time();

        while (time() - $startedAt < $timeout) {
            try {
                DB::connection()->getPdo();

                return;
            } catch (\Throwable) {
                sleep(2);
            }
        }

        $this->error('Database connection timed out.');

        throw new \RuntimeException('Database connection timed out.');
    }

    private function runMigrationsWithRetry(): int
    {
        $maxAttempts = count(MigrationSyncService::migrationFiles()) + 1;

        MigrationSyncService::$skipAutoSync = true;

        try {
            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                $exitCode = Artisan::call('migrate', [
                    '--force' => $this->option('force'),
                ]);

                $output = Artisan::output();

                if ($output !== '') {
                    $this->output->write($output);
                }

                if ($exitCode === 0) {
                    $this->info('Migrations completed successfully.');

                    return self::SUCCESS;
                }

                if (! str_contains($output, 'already exists')) {
                    return self::FAILURE;
                }

                $migration = MigrationSyncService::firstPendingMigration();

                if ($migration === null) {
                    return self::FAILURE;
                }

                MigrationSyncService::markMigrationRan($migration);
                $this->warn("Recovered from existing-table conflict: {$migration}");
            }

            $this->error('Migration recovery attempts exhausted.');

            return self::FAILURE;
        } finally {
            MigrationSyncService::$skipAutoSync = false;
        }
    }
}
