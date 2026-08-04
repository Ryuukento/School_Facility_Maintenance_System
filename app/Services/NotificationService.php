<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationService
{
    /**
     * TASK 17 — $entityType/$entityId are optional and additive so every
     * existing call site keeps compiling unchanged. When the caller already
     * has the record in scope (it always does — see DispatchService,
     * RepairService, DamageReportService), pass its type/id through so the
     * notification knows what it is about and the frontend can deep-link to
     * it without parsing $title/$message text. $entityType is a short,
     * lowercase, singular noun ('report', 'dispatch', 'repair_request',
     * 'damage_report', 'user') matching the routing map in notification.js.
     */
    public function notify(
        int $userId,
        string $title,
        string $message,
        ?string $entityType = null,
        ?int $entityId = null
    ): void {
        if ($userId <= 0) {
            return;
        }

        try {
            DB::table('notifications')->insert([
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to create notification', [
                'user_id' => $userId,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
