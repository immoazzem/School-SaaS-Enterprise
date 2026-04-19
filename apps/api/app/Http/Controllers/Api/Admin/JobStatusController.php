<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JobStatusController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'pending' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
                'recent_failures' => DB::table('failed_jobs')
                    ->orderByDesc('failed_at')
                    ->limit(10)
                    ->get(['id', 'uuid', 'connection', 'queue', 'failed_at', 'exception'])
                    ->map(fn (object $failure): array => [
                        'id' => $failure->id,
                        'uuid' => $failure->uuid,
                        'connection' => $failure->connection,
                        'queue' => $failure->queue,
                        'failed_at' => $failure->failed_at,
                        'exception' => Str::limit($failure->exception, 500),
                    ]),
            ],
        ]);
    }

    public function retry(string $id): JsonResponse
    {
        $failure = DB::table('failed_jobs')
            ->where('uuid', $id)
            ->when(ctype_digit($id), fn ($query) => $query->orWhere('id', (int) $id))
            ->first();

        abort_unless($failure, 404);

        $retryId = $failure->uuid ?: (string) $failure->id;
        $exitCode = Artisan::call('queue:retry', ['id' => [$retryId]]);

        return response()->json([
            'data' => [
                'id' => $failure->id,
                'uuid' => $failure->uuid,
                'queued_for_retry' => $exitCode === 0,
                'output' => trim(Artisan::output()),
            ],
        ], $exitCode === 0 ? 202 : 500);
    }
}
