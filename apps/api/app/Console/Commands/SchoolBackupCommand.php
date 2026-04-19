<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SchoolBackupCommand extends Command
{
    protected $signature = 'school:backup {--school= : Back up one school id instead of the whole database}';

    protected $description = 'Create a JSON backup archive for all schools or one school tenant.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $schoolId = $this->option('school') ? (int) $this->option('school') : null;

        if ($schoolId !== null && ! DB::table('schools')->where('id', $schoolId)->exists()) {
            $this->error("School {$schoolId} was not found.");

            return self::FAILURE;
        }

        $tables = collect(Schema::getTables())
            ->pluck('name')
            ->reject(fn (string $table): bool => in_array($table, $this->excludedTables(), true))
            ->values();

        $payload = [
            'format' => 'school-saas-enterprise-backup-v1',
            'created_at' => now()->toISOString(),
            'scope' => $schoolId === null ? 'all' : 'school',
            'school_id' => $schoolId,
            'tables' => [],
        ];

        foreach ($tables as $table) {
            $columns = Schema::getColumnListing($table);
            $query = DB::table($table);

            if ($schoolId !== null) {
                if ($table === 'schools') {
                    $query->where('id', $schoolId);
                } elseif (in_array('school_id', $columns, true)) {
                    $query->where('school_id', $schoolId);
                } else {
                    continue;
                }
            }

            $payload['tables'][$table] = $query
                ->orderBy($columns[0] ?? 'id')
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all();
        }

        $fileName = sprintf(
            '%s-%s.json',
            $schoolId === null ? 'school-saas-backup-all' : "school-saas-backup-school-{$schoolId}",
            now()->format('Ymd-His')
        );
        $path = "backups/{$fileName}";

        Storage::disk('local')->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("Backup written to storage/app/{$path}");

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function excludedTables(): array
    {
        return [
            'cache',
            'cache_locks',
            'failed_jobs',
            'job_batches',
            'jobs',
            'migrations',
            'password_reset_tokens',
            'sessions',
        ];
    }
}
