<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class MigrationSyncService
{
    public static bool $skipAutoSync = false;

    public static function sync(): int
    {
        if (! Schema::hasTable('migrations')) {
            return 0;
        }

        $ranMigrations = DB::table('migrations')->pluck('migration')->all();
        $batch = (int) (DB::table('migrations')->max('batch') ?? 0);
        $synced = 0;

        foreach (self::migrationFiles() as $migration) {
            if (in_array($migration, $ranMigrations, true)) {
                continue;
            }

            $tables = self::extractCreatedTables(database_path("migrations/{$migration}.php"));

            if ($tables === [] || ! self::anyTableExists($tables)) {
                continue;
            }

            $batch++;
            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => $batch,
            ]);
            $ranMigrations[] = $migration;
            $synced++;
        }

        return $synced;
    }

    /**
     * @return list<string>
     */
    public static function migrationFiles(): array
    {
        return collect(File::glob(database_path('migrations/*.php')))
            ->map(fn (string $path) => pathinfo($path, PATHINFO_FILENAME))
            ->sort()
            ->values()
            ->all();
    }

    public static function firstPendingMigration(): ?string
    {
        $ranMigrations = DB::table('migrations')->pluck('migration')->all();

        foreach (self::migrationFiles() as $migration) {
            if (! in_array($migration, $ranMigrations, true)) {
                return $migration;
            }
        }

        return null;
    }

    public static function markMigrationRan(string $migration): void
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
    public static function anyTableExists(array $tables): bool
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
    public static function extractCreatedTables(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }

        $contents = File::get($path);
        preg_match_all("/Schema::create\(\s*['\"]([^'\"]+)['\"]/", $contents, $matches);

        return $matches[1] ?? [];
    }
}
