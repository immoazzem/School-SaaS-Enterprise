<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolSettingsRequest;
use App\Models\School;
use App\ValueObjects\SchoolSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolSettingsController extends Controller
{
    public function show(Request $request, School $school): JsonResponse
    {
        abort_unless($request->user()->hasSchoolPermission($school, 'schools.manage'), 403);

        return response()->json([
            'data' => SchoolSettings::fromSchool($school)->toArray(),
        ]);
    }

    public function update(SchoolSettingsRequest $request, School $school): JsonResponse
    {
        $current = SchoolSettings::fromSchool($school)->toArray();
        $settings = SchoolSettings::fromArray(array_merge($current, $request->validated()))->toArray();

        $oldValues = [
            'timezone' => $school->timezone,
            'locale' => $school->locale,
            'settings' => $school->settings,
        ];

        $school->update([
            'timezone' => $settings['timezone'],
            'locale' => $settings['locale'],
            'settings' => $settings,
        ]);

        $this->recordAudit($request, $school, 'school.settings.updated', $school, [
            'old' => $oldValues,
            'new' => [
                'timezone' => $school->fresh()->timezone,
                'locale' => $school->fresh()->locale,
                'settings' => $school->fresh()->settings,
            ],
        ]);

        return response()->json([
            'data' => SchoolSettings::fromSchool($school->fresh())->toArray(),
        ]);
    }
}
