<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DamageReport extends Model
{
    use HasFactory;

    protected $table = 'damage_reports';

    protected $fillable = [
        'damage_report_code',
        'item_id',
        'room_id',
        'department_id',
        'source_dispatch_id',
        'damage_description',
        'severity_level',
        'reported_by',
        'status',
        'image_path',
        'repair_notes',
        'replacement_item_id',
        'replacement_quantity',
        'replacement_transaction_id',
        'replaced_by',
        'replaced_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'replaced_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
    }

    public function replacementItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'replacement_item_id', 'id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(DamageReportHistory::class, 'damage_report_id', 'id');
    }

    public function repairRequest(): HasOne
    {
        return $this->hasOne(RepairRequest::class, 'damage_report_id', 'id');
    }
}
