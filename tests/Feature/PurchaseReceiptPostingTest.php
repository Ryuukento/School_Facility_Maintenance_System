<?php

namespace Tests\Feature;

use App\Models\InventoryTransaction;
use App\Services\ActivityLogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsSharedTestSchema;
use Tests\Support\ConfiguresIsolatedSqliteConnection;
use Tests\Support\InteractsWithLegacySession;
use Tests\TestCase;

class PurchaseReceiptPostingTest extends TestCase
{
    use BuildsSharedTestSchema;
    use ConfiguresIsolatedSqliteConnection;
    use InteractsWithLegacySession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        $this->useInMemoryDatabase('purchase_receipt_testing');
        $this->createTestSchema();
    }

    public function test_posting_draft_receipt_updates_existing_item_exactly_once(): void
    {
        $userId = $this->seedUser();
        $roomId = $this->seedInventoryRoom();
        $itemId = $this->seedItem([
            'inventory_room_id' => $roomId,
            'name' => 'Extension Cord',
            'quantity' => 10,
            'reserved_quantity' => 0,
            'reorder_level' => 2,
        ]);

        $receiptId = $this->seedReceipt(['received_by' => $userId]);
        $this->seedReceiptItem($receiptId, [
            'item_id' => $itemId,
            'item_name' => 'Extension Cord',
            'inventory_room_id' => $roomId,
            'quantity_received' => 4,
        ]);

        $response = $this
            ->actingAsSessionUser($userId, 'maintenance_admin')
            ->postJson("/api/purchase-receipts/{$receiptId}/post");

        $response->assertOk();
        $this->assertSame('posted', DB::table('purchase_receipts')->where('id', $receiptId)->value('status'));
        $this->assertSame(14, (int) DB::table('items')->where('id', $itemId)->value('quantity'));
        $this->assertSame(1, DB::table('inventory_transactions')->where('item_id', $itemId)->count());

        $tx = DB::table('inventory_transactions')->where('item_id', $itemId)->first();
        $this->assertSame('adjustment', $tx->transaction_type);
        $this->assertSame(4, (int) $tx->quantity);
    }

    public function test_posting_multi_item_receipt_creates_ledger_entry_per_line(): void
    {
        $userId = $this->seedUser();
        $roomId = $this->seedInventoryRoom();
        $firstItemId = $this->seedItem([
            'inventory_room_id' => $roomId,
            'name' => 'Projector',
            'quantity' => 2,
            'reorder_level' => 0,
        ]);
        $secondItemId = $this->seedItem([
            'inventory_room_id' => $roomId,
            'name' => 'HDMI Cable',
            'quantity' => 7,
            'reorder_level' => 1,
        ]);

        $receiptId = $this->seedReceipt(['received_by' => $userId]);
        $this->seedReceiptItem($receiptId, [
            'item_id' => $firstItemId,
            'item_name' => 'Projector',
            'inventory_room_id' => $roomId,
            'quantity_received' => 1,
        ]);
        $this->seedReceiptItem($receiptId, [
            'item_id' => $secondItemId,
            'item_name' => 'HDMI Cable',
            'inventory_room_id' => $roomId,
            'quantity_received' => 5,
        ]);

        $response = $this
            ->actingAsSessionUser($userId, 'maintenance_staff')
            ->postJson("/api/purchase-receipts/{$receiptId}/post");

        $response->assertOk();
        $this->assertSame(3, (int) DB::table('items')->where('id', $firstItemId)->value('quantity'));
        $this->assertSame(12, (int) DB::table('items')->where('id', $secondItemId)->value('quantity'));
        $this->assertSame(2, DB::table('inventory_transactions')->count());
        $this->assertSame('posted', DB::table('purchase_receipts')->where('id', $receiptId)->value('status'));
    }

    public function test_posting_can_create_new_inventory_item_without_doubling_quantity(): void
    {
        $userId = $this->seedUser();
        $roomId = $this->seedInventoryRoom();
        $receiptId = $this->seedReceipt(['received_by' => $userId]);

        $lineItemId = $this->seedReceiptItem($receiptId, [
            'item_id' => null,
            'item_name' => 'Power Strip',
            'inventory_room_id' => $roomId,
            'quantity_received' => 6,
            'unit' => 'pc',
        ]);

        $response = $this
            ->actingAsSessionUser($userId, 'super_admin')
            ->postJson("/api/purchase-receipts/{$receiptId}/post");

        $response->assertOk();

        $item = DB::table('items')
            ->where('inventory_room_id', $roomId)
            ->where('name', 'Power Strip')
            ->first();

        $this->assertNotNull($item);
        $this->assertSame(6, (int) $item->quantity);
        $this->assertSame((int) $item->id, (int) DB::table('purchase_receipt_items')->where('id', $lineItemId)->value('item_id'));
        $this->assertSame(1, DB::table('inventory_transactions')->where('item_id', $item->id)->count());
    }

    public function test_reposting_posted_receipt_is_rejected_without_duplicate_stock(): void
    {
        $userId = $this->seedUser();
        $roomId = $this->seedInventoryRoom();
        $itemId = $this->seedItem([
            'inventory_room_id' => $roomId,
            'name' => 'Network Switch',
            'quantity' => 9,
        ]);

        $receiptId = $this->seedReceipt([
            'received_by' => $userId,
            'status' => 'posted',
        ]);
        $this->seedReceiptItem($receiptId, [
            'item_id' => $itemId,
            'item_name' => 'Network Switch',
            'inventory_room_id' => $roomId,
            'quantity_received' => 3,
        ]);

        $response = $this
            ->actingAsSessionUser($userId, 'maintenance_admin')
            ->postJson("/api/purchase-receipts/{$receiptId}/post");

        $response->assertStatus(422);
        $this->assertSame(9, (int) DB::table('items')->where('id', $itemId)->value('quantity'));
        $this->assertSame(0, DB::table('inventory_transactions')->where('item_id', $itemId)->count());
    }

    public function test_posting_rolls_back_all_stock_changes_when_exception_occurs(): void
    {
        $userId = $this->seedUser();
        $roomId = $this->seedInventoryRoom();
        $itemId = $this->seedItem([
            'inventory_room_id' => $roomId,
            'name' => 'Portable Fan',
            'quantity' => 5,
        ]);

        $receiptId = $this->seedReceipt(['received_by' => $userId]);
        $this->seedReceiptItem($receiptId, [
            'item_id' => $itemId,
            'item_name' => 'Portable Fan',
            'inventory_room_id' => $roomId,
            'quantity_received' => 2,
        ]);

        app()->instance(ActivityLogService::class, new class extends ActivityLogService {
            public function log(array $payload, ?Request $request = null): ?\App\Models\ActivityLog
            {
                throw new \RuntimeException('Forced observer failure');
            }
        });

        $response = $this
            ->actingAsSessionUser($userId, 'maintenance_admin')
            ->postJson("/api/purchase-receipts/{$receiptId}/post");

        $response->assertStatus(500);
        $this->assertSame('draft', DB::table('purchase_receipts')->where('id', $receiptId)->value('status'));
        $this->assertSame(5, (int) DB::table('items')->where('id', $itemId)->value('quantity'));
        $this->assertSame(0, DB::table('inventory_transactions')->count());
    }

    public function test_posting_receipt_with_no_items_is_rejected(): void
    {
        $userId = $this->seedUser();
        $receiptId = $this->seedReceipt(['received_by' => $userId]);

        $response = $this
            ->actingAsSessionUser($userId, 'maintenance_admin')
            ->postJson("/api/purchase-receipts/{$receiptId}/post");

        $response->assertStatus(422);
        $this->assertSame('draft', DB::table('purchase_receipts')->where('id', $receiptId)->value('status'));
        $this->assertSame(0, DB::table('inventory_transactions')->count());
    }

    public function test_inventory_transaction_observer_adjustment_increases_quantity_once(): void
    {
        $userId = $this->seedUser();
        $roomId = $this->seedInventoryRoom();
        $itemId = $this->seedItem([
            'inventory_room_id' => $roomId,
            'name' => 'Mouse',
            'quantity' => 5,
        ]);

        InventoryTransaction::query()->create([
            'item_id' => $itemId,
            'report_id' => null,
            'room_id' => null,
            'transaction_type' => 'adjustment',
            'quantity' => 3,
            'reference_note' => 'Observer test',
            'performed_by' => $userId,
        ]);

        $this->assertSame(8, (int) DB::table('items')->where('id', $itemId)->value('quantity'));
        $this->assertSame(1, DB::table('inventory_transactions')->where('item_id', $itemId)->count());
    }

    private function createTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('purchase_receipt_items');
        Schema::dropIfExists('purchase_receipts');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('items');
        Schema::dropIfExists('inventory_categories');
        Schema::dropIfExists('inventory_rooms');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('users');

        $this->createUsersTable();
        $this->createDepartmentsTable();

        Schema::create('inventory_rooms', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_categories', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        $this->createItemsTable();
        $this->createActivityLogsTable();

        Schema::create('purchase_receipts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('or_number')->unique();
            $table->date('receipt_date');
            $table->string('supplier_name');
            $table->unsignedInteger('department_id')->nullable();
            $table->unsignedInteger('received_by');
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
        });

        Schema::create('purchase_receipt_items', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('purchase_receipt_id');
            $table->unsignedInteger('item_id')->nullable();
            $table->string('item_name');
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedInteger('inventory_room_id');
            $table->integer('quantity_received');
            $table->string('unit', 20)->default('pc');
            $table->timestamps();
        });

        $this->createInventoryTransactionsTable();
        Schema::enableForeignKeyConstraints();
    }

    private function seedInventoryRoom(array $overrides = []): int
    {
        return DB::table('inventory_rooms')->insertGetId(array_merge([
            'name' => 'Electrical Inventory Room ' . uniqid(),
            'code' => 'E-' . uniqid(),
            'is_active' => 1,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function seedReceipt(array $overrides = []): int
    {
        return DB::table('purchase_receipts')->insertGetId(array_merge([
            'or_number' => 'OR-' . uniqid(),
            'receipt_date' => '2026-07-14',
            'supplier_name' => 'Test Supplier',
            'department_id' => null,
            'received_by' => $this->seedUser(),
            'remarks' => null,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function seedReceiptItem(int $receiptId, array $overrides = []): int
    {
        return DB::table('purchase_receipt_items')->insertGetId(array_merge([
            'purchase_receipt_id' => $receiptId,
            'item_id' => null,
            'item_name' => 'Receipt Item',
            'category_id' => null,
            'inventory_room_id' => $this->seedInventoryRoom(),
            'quantity_received' => 1,
            'unit' => 'pc',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
