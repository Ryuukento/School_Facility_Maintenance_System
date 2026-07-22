<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransaction;
use App\Models\Item;
use App\Services\ActivityLogService;
use App\Services\InventoryAdjustmentService;
use App\Services\InventoryStatusService;
use App\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ItemController extends Controller
{
    use ApiResponder;

    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly InventoryAdjustmentService $inventoryAdjustmentService
    ) {
    }

    public function index(Request $request)
    {
        $query = Item::query();

        // item_type filter (room_asset | inventory_stock)
        if ($request->filled('item_type')) {
            $query->where('item_type', $request->string('item_type')->toString());
        }

        // status filter
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        // status_filter alias (low_stock, out_of_stock, ok)
        if ($request->filled('status_filter')) {
            $sf = $request->string('status_filter')->toString();
            match ($sf) {
                'low_stock'   => $query->where('status', 'low_stock'),
                'out_of_stock'=> $query->where('quantity', 0),
                'ok'          => $query->where('status', 'active')->where('quantity', '>', 0),
                default       => null,
            };
        }

        // room_id filter
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->integer('room_id'));
        }

        // inventory_room_id filter
        if ($request->filled('inventory_room_id')) {
            $query->where('inventory_room_id', $request->integer('inventory_room_id'));
        }

        // category_id filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        // keyword search — accepts both ?q= and ?search=
        $keyword = $request->string('q', $request->string('search', '')->toString())->toString();
        if ($keyword !== '') {
            $query->where(function ($b) use ($keyword) {
                $b->where('name',  'like', "%{$keyword}%")
                  ->orWhere('brand', 'like', "%{$keyword}%")
                  ->orWhere('model', 'like', "%{$keyword}%");
            });
        }

        $perPage = max(1, min(200, $request->integer('per_page', 20)));
        $items   = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->ok('Items retrieved', $items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:inventory_categories,id'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit_type' => ['nullable', 'string', 'max:50'],
            'item_condition' => ['nullable', 'string', 'max:50'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'inventory_room_id' => ['nullable', 'integer', 'exists:inventory_rooms,id'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold_override' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        // 'quantity' expresses the requested *initial* stock, not a direct Item
        // field write. It is applied below via an InventoryTransaction so the
        // Observer performs and audits the actual mutation (see ARCHITECTURE.md
        // Section 5 — single writer principle).
        $initialQuantity = (int) $validated['quantity'];
        unset($validated['quantity']);

        // Prevent duplicate item entries in same room with same name/brand/model
        $duplicateQuery = Item::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])]);

        if (!empty($validated['brand'])) {
            $duplicateQuery->whereRaw('LOWER(IFNULL(brand,\'\')) = ?', [strtolower($validated['brand'])]);
        }

        if (!empty($validated['model'])) {
            $duplicateQuery->whereRaw('LOWER(IFNULL(model,\'\')) = ?', [strtolower($validated['model'])]);
        }

        if (!empty($validated['room_id'])) {
            $duplicateQuery->where('room_id', $validated['room_id']);
        }

        if ($duplicateQuery->exists()) {
            return $this->fail('An item with the same name/brand/model already exists in this room', 409);
        }

        $item = DB::transaction(function () use ($validated, $initialQuantity, $request) {
            $item = Item::query()->create([
                ...$validated,
                'quantity' => 0,
                'status' => InventoryStatusService::deriveStatus(0, (int) ($validated['reorder_level'] ?? 0)),
            ]);

            if ($initialQuantity > 0) {
                InventoryTransaction::query()->create([
                    'item_id' => $item->id,
                    'report_id' => null,
                    'room_id' => null,
                    'transaction_type' => 'adjustment',
                    'quantity' => $initialQuantity,
                    'reference_note' => 'Initial stock on item creation',
                    'performed_by' => $request->session()->get('user_id'),
                ]);

                $item->refresh();
                $item->status = InventoryStatusService::deriveStatus((int) $item->quantity, (int) ($item->reorder_level ?? 0));
                $item->save();
            }

            return $item;
        });

        $this->activityLogService->log([
            'user_id' => $request->session()->get('user_id'),
            'user_role' => $request->session()->get('role'),
            'action' => 'CREATE_ITEM',
            'module' => 'inventory',
            'entity_type' => 'item',
            'entity_id' => $item->id,
            'details' => 'Created item ' . $item->name . '.',
        ], $request);

        return $this->ok('Item created', ['item_id' => $item->id], 201);
    }

    public function show(Request $request, Item $item)
    {
        return $this->ok('Item retrieved', ['item' => $item]);
    }

    public function update(Request $request, Item $item)
    {
        // 'quantity' and 'status' are intentionally not accepted here — they are
        // ledger-derived fields (see ARCHITECTURE.md Section 5) and must only
        // change via an InventoryTransaction/InventoryStockEntry and the
        // corresponding Observer, never a direct Item field write.
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:inventory_categories,id'],
            'brand' => ['sometimes', 'nullable', 'string', 'max:255'],
            'model' => ['sometimes', 'nullable', 'string', 'max:255'],
            'unit_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'item_condition' => ['sometimes', 'nullable', 'string', 'max:50'],
            'room_id' => ['sometimes', 'nullable', 'integer', 'exists:rooms,id'],
            'inventory_room_id' => ['sometimes', 'nullable', 'integer', 'exists:inventory_rooms,id'],
            'reorder_level' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'low_stock_threshold_override' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        $item->update($validated);

        $this->activityLogService->log([
            'user_id' => $request->session()->get('user_id'),
            'user_role' => $request->session()->get('role'),
            'action' => 'UPDATE_ITEM',
            'module' => 'inventory',
            'entity_type' => 'item',
            'entity_id' => $item->id,
            'details' => 'Updated item ' . $item->name . '.',
        ], $request);

        return $this->ok('Item updated successfully');
    }

    public function destroy(Request $request, Item $item)
    {
        // Prevent deletion if there are inventory_transactions referencing this item
        $hasTx = DB::table('inventory_transactions')->where('item_id', $item->id)->exists();
        if ($hasTx) {
            return $this->fail('Cannot delete item with existing inventory transactions', 400);
        }

        $item->delete();

        $this->activityLogService->log([
            'user_id' => $request->session()->get('user_id'),
            'user_role' => $request->session()->get('role'),
            'action' => 'DELETE_ITEM',
            'module' => 'inventory',
            'entity_type' => 'item',
            'entity_id' => $item->id,
            'details' => 'Deleted item ' . $item->name . '.',
        ], $request);

        return $this->ok('Item deleted successfully');
    }

    public function adjustStock(Request $request, Item $item)
    {
        $validated = $request->validate([
            'direction' => ['required', 'in:increase,decrease'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $updated = $this->inventoryAdjustmentService->adjust(
                $item,
                $validated['direction'],
                (int) $validated['quantity'],
                $validated['reason'],
                (int) $request->session()->get('user_id')
            );
        } catch (ValidationException $e) {
            return $this->fail(collect($e->errors())->flatten()->first() ?: 'Unable to adjust stock', 422);
        }

        return $this->ok('Stock adjusted successfully', ['item' => $updated]);
    }

    public function history(Request $request, Item $item)
    {
        $txs = DB::table('inventory_transactions')
            ->where('item_id', $item->id)
            ->select(['id as source_id', 'transaction_type as type', 'quantity', 'reference_note as note', 'performed_by as user_id', 'created_at'])
            ->get()
            ->map(fn($r) => (object)array_merge((array)$r, ['source' => 'transaction']));

        $stock = DB::table('inventory_stock_entries')
            ->where('item_id', $item->id)
            ->select(['id as source_id', DB::raw("'stock_entry' as type"), 'quantity', 'description as note', 'receiver_user_id as user_id', 'created_at'])
            ->get()
            ->map(fn($r) => (object)array_merge((array)$r, ['source' => 'stock_entry']));

        $merged = collect($txs)->merge($stock)->sortByDesc('created_at')->values();

        return $this->ok('Item history retrieved', ['history' => $merged]);
    }
}
