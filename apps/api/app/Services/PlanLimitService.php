<?php

namespace App\Services;

use App\Models\School;
use Illuminate\Validation\ValidationException;

class PlanLimitService
{
    public function assertCanStoreDocument(School $school, int $newFileSizeBytes): void
    {
        $maxStorageMb = (int) data_get($school->settings, 'plan_limits.max_storage_mb', data_get($school->settings, 'max_storage_mb', 512));
        $maxStorageBytes = max($maxStorageMb, 1) * 1024 * 1024;
        $usedBytes = (int) $school->documents()->sum('file_size_bytes');

        if ($usedBytes + $newFileSizeBytes > $maxStorageBytes) {
            throw ValidationException::withMessages([
                'file' => 'Plan storage limit reached.',
                'error' => 'plan_limit_reached',
            ]);
        }
    }
}
