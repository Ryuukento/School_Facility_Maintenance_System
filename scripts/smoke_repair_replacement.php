<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DamageReportHistory;
use App\Models\Dispatch;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Models\RepairHistory;
use App\Models\RepairRequest;
use App\Models\User;
use App\Services\DamageReportService;
use App\Services\RepairService;
use Illuminate\Support\Facades\DB;

function repairSmokeAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

echo "Starting repair/replacement smoke test...\n";
DB::beginTransaction();

try {
    $damageService = app(DamageReportService::class);
    $repairService = app(RepairService::class);

    $user = User::query()->first();
    if (!$user) {
        $user = User::query()->create([
            'full_name' => 'Repair Smoke User',
            'email' => 'repair.smoke+' . time() . '@example.invalid',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'role' => 'maintenance_admin',
            'department_id' => 1,
            'status' => 'active',
        ]);
    } elseif ($user->role !== 'maintenance_admin') {
        $user->role = 'maintenance_admin';
        $user->status = 'active';
        $user->department_id = $user->department_id ?: 1;
        $user->save();
    }

    $damagedItem = Item::query()->create([
        'name' => 'repair-smoke-damaged-' . time(),
        'brand' => 'SmokeBrand',
        'model' => 'R1',
        'quantity' => 4,
        'reserved_quantity' => 0,
        'unit_type' => 'pcs',
        'item_type' => 'room_asset',
        'status' => 'available',
    ]);

    $replacementItem = Item::query()->create([
        'name' => 'repair-smoke-replacement-' . time(),
        'brand' => 'SmokeBrand',
        'model' => 'SP1',
        'quantity' => 10,
        'reserved_quantity' => 0,
        'unit_type' => 'pcs',
        'item_type' => 'inventory_stock',
        'status' => 'available',
    ]);

    InventoryTransaction::query()->create([
        'item_id' => $damagedItem->id,
        'report_id' => null,
        'room_id' => 1,
        'transaction_type' => 'deploy',
        'quantity' => 1,
        'reference_note' => 'Repair smoke deployment seed',
        'performed_by' => $user->user_id,
    ]);

    $authUser = [
        'user_id' => $user->user_id,
        'role' => 'maintenance_admin',
        'full_name' => $user->full_name,
        'department_id' => 1,
    ];

    $damageReport = $damageService->createReport([
        'item_id' => $damagedItem->id,
        'room_id' => 1,
        'department_id' => 1,
        'damage_description' => 'Repair smoke damage report for replacement workflow.',
        'severity_level' => 'high',
        'repair_notes' => 'Initial intake note.',
    ], $authUser, null);

    repairSmokeAssert((bool)$damageReport, 'Damage report creation failed for repair smoke test.');

    $repair = $repairService->createRequest([
        'damage_report_id' => $damageReport->id,
        'repair_type' => 'corrective',
        'repair_description' => 'Diagnose and repair motherboard fault.',
        'repair_cost' => 0,
        'repair_date' => now()->toDateString(),
        'estimated_completion_date' => now()->addDays(2)->toDateString(),
        'notes' => 'Created from smoke test.',
    ], $authUser);

    repairSmokeAssert((bool)$repair, 'Repair request creation failed.');
    repairSmokeAssert($repair->repair_status === 'pending', 'Repair should start as pending.');

    $duplicateBlocked = false;
    try {
        $repairService->createRequest([
            'damage_report_id' => $damageReport->id,
            'repair_type' => 'diagnostic',
            'repair_description' => 'Duplicate repair request attempt.',
        ], $authUser);
    } catch (Throwable $e) {
        $duplicateBlocked = true;
    }
    repairSmokeAssert($duplicateBlocked, 'Duplicate repair request was not blocked.');

    $repair = $repairService->assignTechnician($repair, [
        'technician_user_id' => $user->user_id,
        'estimated_completion_date' => now()->addDays(3)->toDateString(),
        'notes' => 'Technician assigned during smoke test.',
    ], $authUser);

    repairSmokeAssert($repair->repair_status === 'assigned', 'Repair should move to assigned after technician assignment.');
    repairSmokeAssert((int)$repair->technician_user_id === (int)$user->user_id, 'Technician was not saved.');

    $repair = $repairService->updateRequest($repair, [
        'repair_status' => 'diagnosing',
        'repair_type' => 'corrective',
        'repair_description' => 'Diagnosing board issue.',
        'repair_cost' => 150.50,
        'notes' => 'Diagnosis in progress.',
    ], $authUser);

    repairSmokeAssert($repair->repair_status === 'diagnosing', 'Repair should transition to diagnosing.');

    $repair = $repairService->updateRequest($repair, [
        'repair_status' => 'failed',
        'repair_type' => 'corrective',
        'repair_description' => 'Repair failed after diagnostic and parts check.',
        'repair_cost' => 150.50,
        'notes' => 'Replacement recommended.',
        'failure_reason' => 'Core component no longer repairable.',
    ], $authUser);

    repairSmokeAssert($repair->repair_status === 'failed', 'Repair should transition to failed.');

    $replacementQty = 2;
    $qtyBeforeReplacement = (int)$replacementItem->fresh()->quantity;
    $repair = $repairService->fulfillReplacement($repair, [
        'replacement_item_id' => $replacementItem->id,
        'replacement_quantity' => $replacementQty,
        'notes' => 'Replacement fulfilled through smoke test.',
    ], $authUser);

    $repair->refresh();
    $replacementItem->refresh();
    $damageReport->refresh();

    repairSmokeAssert($repair->repair_status === 'archived', 'Repair should archive after replacement fulfillment.');
    repairSmokeAssert(!empty($repair->replacement_dispatch_id), 'Replacement dispatch ID should be stored.');
    repairSmokeAssert(!empty($repair->replacement_transaction_id), 'Replacement transaction ID should be stored.');
    repairSmokeAssert($damageReport->status === 'replaced', 'Damage report should move to replaced after replacement.');
    repairSmokeAssert((int)$damageReport->replacement_item_id === (int)$replacementItem->id, 'Damage report replacement item should be linked.');
    repairSmokeAssert((int)$replacementItem->quantity === ($qtyBeforeReplacement - $replacementQty), 'Replacement stock quantity should be deducted through transaction observer.');

    $dispatch = Dispatch::query()->find($repair->replacement_dispatch_id);
    repairSmokeAssert((bool)$dispatch, 'Replacement dispatch was not created.');
    repairSmokeAssert($dispatch->status === 'released', 'Replacement dispatch should be released automatically.');
    repairSmokeAssert((int)$dispatch->repair_request_id === (int)$repair->id, 'Replacement dispatch should link to repair request.');

    $transaction = InventoryTransaction::query()->find($repair->replacement_transaction_id);
    repairSmokeAssert((bool)$transaction, 'Replacement inventory transaction missing.');
    repairSmokeAssert($transaction->transaction_type === 'deploy', 'Replacement transaction should use deploy type.');

    $repairHistoryCount = RepairHistory::query()->where('repair_request_id', $repair->id)->count();
    $damageHistoryCount = DamageReportHistory::query()->where('damage_report_id', $damageReport->id)->count();
    repairSmokeAssert($repairHistoryCount >= 4, 'Expected multiple repair history entries.');
    repairSmokeAssert($damageHistoryCount >= 3, 'Expected multiple damage report history entries after repair integration.');
    repairSmokeAssert((int)$replacementItem->quantity >= 0, 'Negative stock detected after replacement flow.');

    DB::rollBack();
    echo "Repair/replacement smoke test completed successfully.\n";
} catch (Throwable $e) {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    echo 'Repair/replacement smoke test failed: ' . $e->getMessage() . "\n";
    exit(1);
}

return 0;
