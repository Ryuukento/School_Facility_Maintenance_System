<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TASK 16 — single-row global School Settings.
 *
 * Deliberately minimal: `school_year` + `current_semester`, nothing more.
 * Exactly one row (id = 1) ever exists. No history, no per-report tagging.
 *
 * `current()` is the one entry point the rest of the app should use — it
 * always returns that single row (creating the default if it is somehow
 * missing, so a fresh/blank database never breaks the dashboard).
 */
class SchoolSetting extends Model
{
    protected $table = 'school_settings';

    public $timestamps = true;

    protected $fillable = [
        'school_year',
        'current_semester',
        'semester_started_at',
    ];

    protected $casts = [
        'semester_started_at' => 'datetime',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::create([
            'school_year'         => '2026-2027',
            'current_semester'    => 'First Semester',
            'semester_started_at' => now(),
        ]);
    }
}
