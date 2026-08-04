<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolSetting;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * TASK 16 — School Settings (school_year / current_semester).
 *
 * Single-row global settings that the dashboard uses to scope KPI
 * statistics (Total Reports, Pending, In Progress, Completed) to the
 * "current semester". This controller only reads/writes that one row —
 * it never touches maintenance_reports, audit logs, or report filters.
 */
class SchoolSettingsController extends Controller
{
    use ApiResponder;

    // Any authenticated user may view the current semester label
    // (e.g. to display it next to the dashboard KPI cards).
    public function show(): JsonResponse
    {
        $settings = SchoolSetting::current();

        return $this->ok('School settings retrieved', [
            'school_year'          => $settings->school_year,
            'current_semester'     => $settings->current_semester,
            'semester_started_at'  => optional($settings->semester_started_at)->toIso8601String(),
            'updated_at'           => optional($settings->updated_at)->toIso8601String(),
        ]);
    }

    // super_admin only (route-gated via EnsureRole::class . ':super_admin').
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'school_year'      => ['required', 'string', 'max:20'],
            'current_semester' => ['required', 'string', 'in:First Semester,Second Semester'],
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed', 422, $validator->errors());
        }

        $validated = $validator->validated();

        $settings = SchoolSetting::current();

        // `semester_started_at` is an explicit business field, not metadata:
        // it must ONLY change when the Administrator is actually starting a
        // new semester (school_year and/or current_semester actually
        // differ from what's stored) — never on an unrelated/no-op save.
        $isNewSemester = $validated['school_year'] !== $settings->school_year
            || $validated['current_semester'] !== $settings->current_semester;

        $settings->school_year      = $validated['school_year'];
        $settings->current_semester = $validated['current_semester'];
        if ($isNewSemester) {
            $settings->semester_started_at = now();
        }
        $settings->save();

        return $this->ok('School settings updated', [
            'school_year'          => $settings->school_year,
            'current_semester'     => $settings->current_semester,
            'semester_started_at'  => optional($settings->semester_started_at)->toIso8601String(),
            'updated_at'           => optional($settings->updated_at)->toIso8601String(),
        ]);
    }
}
