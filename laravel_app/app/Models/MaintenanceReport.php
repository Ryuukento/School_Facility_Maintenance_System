<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceReport extends Model
{
    use HasFactory;

    protected $table = 'maintenance_reports';

    protected $primaryKey = 'report_id';

    protected $fillable = [
        'title',
        'description',
        'location',
        'priority',
        'status',
        'created_by',
        'assigned_to',
        'department_id',
        'due_date',
        'completed_date',
        'need_change_item_id',
        'need_change_quantity',
        'need_change_status',
        'need_change_approved_by',
        'need_change_approved_at',
        'need_change_deducted_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_date' => 'date',
            'need_change_approved_at' => 'datetime',
            'need_change_deducted_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to', 'user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function needChangeItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'need_change_item_id', 'id');
    }
}
