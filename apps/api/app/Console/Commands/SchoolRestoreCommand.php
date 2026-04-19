<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SchoolRestoreCommand extends Command
{
    protected $signature = 'school:restore {archive : Path under storage/app or an absolute path} {--force : Skip confirmation prompt}';

    protected $description = 'Restore a JSON backup archive created by school:backup.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $archive = (string) $this->argument('archive');
        $contents = $this->readArchive($archive);

        if ($contents === null) {
            $this->error("Backup archive was not found: {$archive}");

            return self::FAILURE;
        }

        $payload = json_decode($contents, true);

        if (! is_array($payload) || ($payload['format'] ?? null) !== 'school-saas-enterprise-backup-v1') {
            $this->error('Backup archive format is not supported.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Restore this backup into the current database? Existing matching primary keys will be overwritten.')) {
            $this->warn('Restore cancelled.');

            return self::FAILURE;
        }

        $tables = $payload['tables'] ?? [];

        if (! is_array($tables)) {
            $this->error('Backup archive does not contain table data.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($tables): void {
            foreach ($tables as $table => $rows) {
                if (! is_string($table) || ! Schema::hasTable($table) || ! is_array($rows)) {
                    continue;
                }

                $columns = Schema::getColumnListing($table);
                $primaryKey = in_array('id', $columns, true) ? 'id' : ($columns[0] ?? null);

                foreach ($rows as $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    $filtered = array_intersect_key($row, array_flip($columns));

                    if ($primaryKey !== null && array_key_exists($primaryKey, $filtered)) {
                        DB::table($table)->updateOrInsert(
                            [$primaryKey => $filtered[$primaryKey]],
                            $filtered
                        );
                    } else {
                        DB::table($table)->insert($filtered);
                    }
                }
            }
        });

        $this->info('Backup restored.');

        return self::SUCCESS;
    }

    private function readArchive(string $archive): ?string
    {
        if (is_file($archive)) {
            return file_get_contents($archive) ?: null;
        }

        return Storage::disk('local')->exists($archive)
            ? Storage::disk('local')->get($archive)
            : null;
    }
}
