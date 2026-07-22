<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shared table builders and default-row seeders for the self-contained,
 * in-memory-SQLite Feature tests. Each test file still composes its own
 * createTestSchema() (dropping and creating only the tables it needs, in
 * its own order) — this trait just removes the byte-for-byte-duplicated
 * Blueprint/seed definitions that were repeated across those files.
 */
trait BuildsSharedTestSchema
{
    protected function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->increments('user_id');
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 50)->default('maintenance_admin');
            $table->unsignedInteger('department_id')->nullable();
            $table->string('status', 50)->default('active');
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }

    protected function createItemsTable(): void
    {
        Schema::create('items', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('room_id')->nullable();
            $table->unsignedInteger('inventory_room_id')->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('item_type', 50)->default('inventory_stock');
            $table->string('name');
            $table->string('unit_type')->nullable();
            $table->string('item_condition')->nullable();
            $table->string('status', 50)->default('available');
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->integer('low_stock_threshold_override')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    protected function createInventoryTransactionsTable(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('item_id');
            $table->unsignedInteger('report_id')->nullable();
            $table->unsignedInteger('room_id')->nullable();
            $table->string('transaction_type', 50);
            $table->integer('quantity');
            $table->text('reference_note')->nullable();
            $table->unsignedInteger('performed_by')->nullable();
            $table->timestamps();
        });
    }

    protected function createActivityLogsTable(): void
    {
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('user_role', 100)->nullable();
            $table->string('action', 100);
            $table->string('module', 100)->nullable();
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('meta_json')->nullable();
            $table->string('dedupe_key', 64)->nullable();
            $table->timestamps();
        });
    }

    protected function createDepartmentsTable(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->increments('department_id');
            $table->string('name');
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    protected function createMaintenanceReportsTable(): void
    {
        Schema::create('maintenance_reports', function (Blueprint $table): void {
            $table->increments('report_id');
            $table->string('title');
            $table->longText('description');
            $table->string('location')->nullable();
            $table->string('priority', 20)->default('medium');
            $table->string('status', 20)->default('submitted');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('assigned_to')->nullable();
            $table->unsignedInteger('department_id')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->unsignedInteger('need_change_item_id')->nullable();
            $table->integer('need_change_quantity')->default(1);
            $table->string('need_change_status', 50)->nullable();
            $table->unsignedInteger('need_change_approved_by')->nullable();
            $table->dateTime('need_change_approved_at')->nullable();
            $table->dateTime('need_change_deducted_at')->nullable();
            $table->string('completion_proof_image')->nullable();
            $table->timestamps();
        });
    }

    protected function seedUser(array $overrides = []): int
    {
        return DB::table('users')->insertGetId(array_merge([
            'full_name' => 'Test User',
            'email' => 'user' . uniqid('', true) . '@example.com',
            'password' => 'secret',
            'role' => 'maintenance_admin',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides), 'user_id');
    }

    protected function seedItem(array $overrides = []): int
    {
        return DB::table('items')->insertGetId(array_merge([
            'room_id' => null,
            'inventory_room_id' => null,
            'category_id' => null,
            'item_type' => 'inventory_stock',
            'name' => 'Test Item',
            'status' => 'available',
            'quantity' => 0,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
            'unit_type' => 'pc',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
