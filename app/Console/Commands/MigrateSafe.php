<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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

        $this->syncMigrationsForExistingTables();

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

    private function syncMigrationsForExistingTables(): void
    {
        $ranMigrations = DB::table('migrations')->pluck('migration')->all();
        $batch = (int) (DB::table('migrations')->max('batch') ?? 0);
        $synced = 0;

        foreach ($this->migrationFiles() as $migration) {
            if (in_array($migration, $ranMigrations, true)) {
                continue;
            }

            $tables = $this->extractCreatedTables(database_path("migrations/{$migration}.php"));

            if ($tables === [] || ! $this->anyTableExists($tables)) {
                continue;
            }

            $batch++;
            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => $batch,
            ]);
            $ranMigrations[] = $migration;
            $synced++;

            $this->info("Synced migration record for existing tables: {$migration}");
        }

        if ($synced === 0) {
            $this->info('No migration records needed syncing.');
        }
    }

    private function runMigrationsWithRetry(): int
    {
        $maxAttempts = count($this->migrationFiles()) + 1;

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

            $migration = $this->firstPendingMigration();

            if ($migration === null) {
                return self::FAILURE;
            }

            $this->markMigrationRan($migration);
            $this->warn("Recovered from existing-table conflict: {$migration}");
        }

        $this->error('Migration recovery attempts exhausted.');

        return self::FAILURE;
    }

    private function migrationFiles(): array
    {
        return collect(File::glob(database_path('migrations/*.php')))
            ->map(fn (string $path) => pathinfo($path, PATHINFO_FILENAME))
            ->sort()
            ->values()
            ->all();
    }

    private function firstPendingMigration(): ?string
    {
        $ranMigrations = DB::table('migrations')->pluck('migration')->all();

        foreach ($this->migrationFiles() as $migration) {
            if (! in_array($migration, $ranMigrations, true)) {
                return $migration;
            }
        }

        return null;
    }

    private function markMigrationRan(string $migration): void
    {
        $batch = (int) (DB::table('migrations')->max('batch') ?? 0) + 1;

        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => $batch,
        ]);
    }

    /**
     * @param  list<string>  $tables
     */
    private function anyTableExists(array $tables): bool
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function extractCreatedTables(string $path): array
    {
        $contents = File::get($path);
        preg_match_all("/Schema::create\(\s*['\"]([^'\"]+)['\"]/", $contents, $matches);

        return $matches[1] ?? [];
    }
}
