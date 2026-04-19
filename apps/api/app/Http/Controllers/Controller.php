<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\AuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    protected function recordAudit(Request $request, School $school, string $event, Model $auditable, array $metadata): void
    {
        app(AuditLogger::class)->record($request, $school, $event, $auditable, $metadata);
    }

    protected function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }

    protected function applySchoolLocale(Request $request, School $school): string
    {
        $locale = $request->query('locale');

        if (! in_array($locale, ['bn', 'en'], true)) {
            $acceptLanguage = strtolower((string) $request->header('Accept-Language', ''));
            $schoolLocale = $school->locale ?: data_get($school->settings, 'locale', 'en');

            $locale = match (true) {
                str_starts_with($acceptLanguage, 'bn') => 'bn',
                in_array($schoolLocale, ['bn', 'en'], true) => $schoolLocale,
                str_starts_with($acceptLanguage, 'en') => 'en',
                default => 'en',
            };
        }

        $locale = in_array($locale, ['bn', 'en'], true) ? $locale : 'en';
        app()->setLocale($locale);

        return $locale;
    }

    /**
     * @return array{
     *     data: array<int, mixed>,
     *     meta: array<string, int|null>,
     *     links: array<string, string|null>
     * }
     */
    protected function paginated(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }
}
