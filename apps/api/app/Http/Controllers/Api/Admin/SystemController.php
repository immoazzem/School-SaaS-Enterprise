<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SystemController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json([
            'data' => [
                'status' => 'ok',
                'database' => $this->databaseStatus(),
                'checked_at' => now()->toISOString(),
            ],
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => [
                'schools' => School::query()->count(),
                'active_schools' => School::query()->where('status', 'active')->count(),
                'users' => User::query()->count(),
                'audit_logs' => AuditLog::query()->count(),
            ],
        ]);
    }

    private function databaseStatus(): string
    {
        try {
            DB::select('select 1');

            return 'ok';
        } catch (\Throwable) {
            return 'unavailable';
        }
    }
}
