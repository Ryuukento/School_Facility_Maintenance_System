<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only completion record for a PreventiveMaintenanceTask. Nothing in
 * this codebase ever updates or deletes a row here once written — see the
 * migration's design notes.
 */
class PreventiveMaintenanceHistory extends Model
{
    use HasFactory;

    protected $table = 'preventive_maintenance_history';

    protected $fillable = [
        'preventive_maintenance_task_id',
        'completed_date',
        'performed_by',
        'recorded_by',
        'notes',
        'findings',
        'action_taken',
        'completion_proof_path',
        'next_due_date_snapshot',
    ];

    protected function casts(): array
    {
        return [
            // See PreventiveMaintenanceTask::casts() for why these use the
            // Y-m-d format modifier rather than plain 'date' — it avoids a
            // UTC-shift-driven off-by-one-day bug when this record is
            // serialized to JSON for the frontend (app.timezone is
            // Asia/Manila, UTC+8).
            'completed_date' => 'date:Y-m-d',
            'next_due_date_snapshot' => 'date:Y-m-d',
        ];
    }

    protected $appends = [
        'performed_by_name',
        'recorded_by_name',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(PreventiveMaintenanceTask::class, 'preventive_maintenance_task_id', 'id');
    }

    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by', 'user_id');
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by', 'user_id');
    }

    public function getPerformedByNameAttribute(): ?string
    {
        return $this->performedByUser?->full_name;
    }

    public function getRecordedByNameAttribute(): ?string
    {
        return $this->recordedByUser?->full_name;
    }
}
