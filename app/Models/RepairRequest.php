<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairRequest extends Model
{
    use HasFactory;

    protected $table = 'repair_requests';

    protected $fillable = [
        'repair_code',
        'damage_report_id',
        'technician_user_id',
        'repair_type',
        'repair_description',
        'repair_cost',
        'repair_status',
        'repair_date',
        'estimated_completion_date',
        'completion_date',
        'notes',
        'failure_reason',
        'replacement_item_id',
        'replacement_quantity',
        'replacement_dispatch_id',
        'replacement_transaction_id',
        'created_by',
        'updated_by',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'repair_cost' => 'decimal:2',
            'repair_date' => 'date',
            'estimated_completion_date' => 'date',
            'completion_date' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    public function damageReport(): BelongsTo
    {
        return $this->belongsTo(DamageReport::class, 'damage_report_id', 'id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_user_id', 'user_id');
    }

    public function replacementItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'replacement_item_id', 'id');
    }

    public function replacementDispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class, 'replacement_dispatch_id', 'id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RepairHistory::class, 'repair_request_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }
}
