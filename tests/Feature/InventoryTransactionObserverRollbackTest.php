<?php

namespace Tests\Feature;

use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsSharedTestSchema;
use Tests\Support\ConfiguresIsolatedSqliteConnection;
use Tests\TestCase;

/**
 * No public endpoint deletes an InventoryTransaction today; these tests
 * exercise InventoryTransactionObserver::deleted() directly against the
 * model, the same "integration-style, no HTTP layer" approach already
 * used by PurchaseReceiptPostingTest::test_inventory_transaction_observer_adjustment_increases_quantity_once().
 */
class InventoryTransactionObserverRollbackTest extends TestCase
{
    use BuildsSharedTestSchema;
    use ConfiguresIsolatedSqliteConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useInMemoryDatabase('observer_rollback_testing');
        $this->createTestSchema();
    }

    public function test_deleting_deploy_transaction_after_prior_reserve_restores_quantity_and_reserved(): void
    {
        $userId = $this->seedUser();
        $itemId = $this->seedItem(['name' => 'Extension Cord', 'quantity' => 10, 'reserved_quantity' => 0]);

        $reserve = InventoryTransaction::query()->create([
            'item_id' => $itemId,
            'transaction_type' => 'reserve',
            'quantity' => 4,
            'performed_by' => $userId,
        ]);
        $this->assertSame(10, (int) DB::table('items')->where('id', $itemId)->value('quantity'));
        $this->assertSame(4, (int) DB::table('items')->where('id', $itemId)->value('reserved_quantity'));

        $deploy = InventoryTransaction::query()->create([
            'item_id' => $itemId,
            'transaction_type' => 'deploy',
            'quantity' => 4,
            'performed_by' => $userId,
        ]);
        $this->assertSame(6, (int) DB::table('items')->where('id', $itemId)->value('quantity'));
        $this->assertSame(0, (int) DB::table('items')->where('id', $itemId)->value('reserved_quantity'));

        $deploy->delete();

        $item = DB::table('items')->where('id', $itemId)->first();
        $this->assertSame(10, (int) $item->quantity);
        $this->assertSame(4, (int) $item->reserved_quantity);
        $this->assertGreaterThanOrEqual(0, (int) $item->quantity);
        $this->assertGreaterThanOrEqual(0, (int) $item->reserved_quantity);

        // The reserve transaction itself is untouched by the deploy rollback.
        $this->assertSame(1, DB::table('inventory_transactions')->where('id', $reserve->id)->count());
    }

    public function test_deleting_deploy_transaction_never_drives_quantity_negative(): void
    {
        $userId = $this->seedUser();
        $itemId = $this->seedItem(['name' => 'Fire Extinguisher', 'quantity' => 3, 'reserved_quantity' => 0]);

        $deploy = InventoryTransaction::query()->create([
            'item_id' => $itemId,
            'transaction_type' => 'deploy',
            'quantity' => 3,
            'performed_by' => $userId,
        ]);
        $this->assertSame(0, (int) DB::table('items')->where('id', $itemId)->value('quantity'));

        $deploy->delete();

        $item = DB::table('items')->where('id', $itemId)->first();
        $this->assertSame(3, (int) $item->quantity);
        $this->assertGreaterThanOrEqual(0, (int) $item->quantity);
        $this->assertGreaterThanOrEqual(0, (int) $item->reserved_quantity);
    }

    public function test_deleting_adjustment_transaction_reverses_stock_increase(): void
    {
        $userId = $this->seedUser();
        $itemId = $this->seedItem(['name' => 'Mouse', 'quantity' => 5, 'reserved_quantity' => 0]);

        $adjustment = InventoryTransaction::query()->create([
            'item_id' => $itemId,
            'transaction_type' => 'adjustment',
            'quantity' => 3,
            'reference_note' => 'Initial stock',
            'performed_by' => $userId,
        ]);
        $this->assertSame(8, (int) DB::table('items')->where('id', $itemId)->value('quantity'));

        $adjustment->delete();

        $this->assertSame(5, (int) DB::table('items')->where('id', $itemId)->value('quantity'));
    }

    public function test_deleting_release_transaction_restores_reserved_quantity(): void
    {
        $userId = $this->seedUser();
        $itemId = $this->seedItem(['name' => 'Whiteboard Marker', 'quantity' => 10, 'reserved_quantity' => 0]);

        InventoryTransaction::query()->create([
            'item_id' => $itemId,
            'transaction_type' => 'reserve',
            'quantity' => 6,
            'performed_by' => $userId,
        ]);
        $this->assertSame(6, (int) DB::table('items')->where('id', $itemId)->value('reserved_quantity'));

        $release = InventoryTransaction::query()->create([
            'item_id' => $itemId,
            'transaction_type' => 'release',
            'quantity' => 6,
            'performed_by' => $userId,
        ]);
        $this->assertSame(0, (int) DB::table('items')->where('id', $itemId)->value('reserved_quantity'));

        $release->delete();

        $item = DB::table('items')->where('id', $itemId)->first();
        $this->assertSame(6, (int) $item->reserved_quantity);
        $this->assertSame(10, (int) $item->quantity);
    }

    private function createTestSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('items');
        Schema::dropIfExists('users');

        $this->createUsersTable();
        $this->createItemsTable();
        $this->createInventoryTransactionsTable();
        $this->createActivityLogsTable();

        Schema::enableForeignKeyConstraints();
    }
}
