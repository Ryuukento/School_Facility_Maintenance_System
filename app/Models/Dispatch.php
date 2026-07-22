<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispatch extends Model
{
    use HasFactory;

    protected $table = 'dispatches';

    protected $fillable = [
        'dispatch_code',
        'department_id',
        'approved_by',
        'released_by',
        'receiver_user_id',
        'room_id',
        'repair_request_id',
        'damage_report_id',
        'purchase_receipt_id',
        'status',
        'notes',
    ];

    /**
     * Appended accessors so department_name / room_name / approved_by_name /
     * released_by_name / receiver_name are always present in the JSON payload
     * without extra JS mapping.
     */
    protected $appends = ['department_name', 'room_name', 'approved_by_name', 'released_by_name', 'receiver_name'];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function items(): HasMany
    {
        return $this->hasMany(DispatchItem::class, 'dispatch_id', 'id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public function releasedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by', 'user_id');
    }

    public function receiverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_user_id', 'user_id');
    }

    public function repairRequest(): BelongsTo
    {
        return $this->belongsTo(RepairRequest::class, 'repair_request_id', 'id');
    }

    public function damageReport(): BelongsTo
    {
        return $this->belongsTo(DamageReport::class, 'damage_report_id', 'id');
    }

    public function purchaseReceipt(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PurchaseReceipt::class, 'purchase_receipt_id', 'id');
    }

    // -----------------------------------------------------------------------
    // Accessors — keep field names identical to the legacy PHP API so the
    // JS in dispatches.php does not need any column-name changes.
    // -----------------------------------------------------------------------

    public function getDepartmentNameAttribute(): ?string
    {
        return $this->department?->name;
    }

    public function getRoomNameAttribute(): ?string
    {
        return $this->room?->name;
    }

    public function getApprovedByNameAttribute(): ?string
    {
        return $this->approvedByUser?->full_name;
    }

    public function getReleasedByNameAttribute(): ?string
    {
        return $this->releasedByUser?->full_name;
    }

    public function getReceiverNameAttribute(): ?string
    {
        return $this->receiverUser?->full_name;
    }
}
