<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryRoomController extends Controller
{
    use ApiResponder;

    /**
     * GET /api/inventory-rooms
     * List active inventory rooms with item counts.
     *
     * TASK 6B PHASE 2 — inventory rooms are no longer a user-facing concept:
     * the UI now presents one centralized Inventory. This controller and its
     * routes are intentionally left in place and unchanged as an internal
     * implementation detail; no page calls them any more.
     * Supports: q, per_page, page
     * Response: { success, data: { rooms: [{id, name, code, description,
     *   is_active, sort_order, item_count, total_quantity}], pagination } }
     */
    public function index(Request $request): JsonResponse
    {
        $q       = trim((string) $request->query('q', ''));
        $perPage = max(1, min(200, (int) $request->query('per_page', 20)));
        $page    = max(1, (int) $request->query('page', 1));

        $baseQuery = fn () => DB::table('inventory_rooms as ir')
            ->where('ir.is_active', 1)
            ->when($q !== '', function ($q2) use ($q) {
                $like = '%' . strtolower($q) . '%';
                $q2->where(function ($sub) use ($like) {
                    $sub->whereRaw("LOWER(ir.name) LIKE ?",                [$like])
                        ->orWhereRaw("LOWER(COALESCE(ir.code,'')) LIKE ?", [$like]);
                });
            });

        $total = $baseQuery()->count();

        $rooms = $baseQuery()
            ->select([
                'ir.id', 'ir.name', 'ir.code', 'ir.description',
                'ir.is_active', 'ir.sort_order',
                DB::raw('COUNT(i.id) AS item_count'),
                DB::raw('COALESCE(SUM(i.quantity), 0) AS total_quantity'),
            ])
            ->leftJoin('items as i', function ($join) {
                $join->on('i.inventory_room_id', '=', 'ir.id')
                     ->where('i.item_type', '=', 'inventory_stock');
            })
            ->groupBy('ir.id', 'ir.name', 'ir.code', 'ir.description', 'ir.is_active', 'ir.sort_order')
            ->orderBy('ir.sort_order')
            ->orderBy('ir.name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $this->ok('Rooms retrieved', [
            'rooms'      => $rooms,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / max(1, $perPage)),
            ],
        ]);
    }

    /**
     * POST /api/inventory-rooms
     * Create a new inventory room.
     * Body: { name, code?, description? }
     * Response: { success, message, data: { room: {...} } }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $name = trim($validated['name']);
        $code = isset($validated['code']) && trim((string) $validated['code']) !== ''
            ? strtolower(trim((string) $validated['code']))
            : null;
        $description = isset($validated['description']) && trim((string) $validated['description']) !== ''
            ? trim((string) $validated['description'])
            : null;

        if (DB::table('inventory_rooms')->whereRaw('LOWER(name) = LOWER(?)', [$name])->exists()) {
            return $this->fail('Inventory room already exists', 409);
        }

        if ($code !== null && DB::table('inventory_rooms')->where('code', $code)->exists()) {
            return $this->fail('Inventory room code already exists', 409);
        }

        $id = DB::table('inventory_rooms')->insertGetId([
            'name'        => $name,
            'code'        => $code,
            'description' => $description,
            'is_active'   => 1,
            'sort_order'  => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $room = DB::table('inventory_rooms')
            ->select(['id', 'name', 'code', 'description', 'is_active', 'sort_order'])
            ->where('id', $id)
            ->first();

        return $this->ok('Inventory room created successfully', ['room' => $room], 201);
    }

    /**
     * PATCH /api/inventory-rooms/{id}
     * Update name, code, description, is_active, or sort_order.
     * Response: { success, message }
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!DB::table('inventory_rooms')->where('id', $id)->exists()) {
            return $this->fail('Inventory room not found', 404);
        }

        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'code'        => ['sometimes', 'nullable', 'string', 'max:50'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
            'sort_order'  => ['sometimes', 'integer', 'min:0'],
        ]);

        $updates = [];

        if (isset($validated['name'])) {
            $name = trim($validated['name']);
            if (DB::table('inventory_rooms')
                ->whereRaw('LOWER(name) = LOWER(?)', [$name])
                ->where('id', '!=', $id)
                ->exists()) {
                return $this->fail('Another inventory room with this name already exists', 409);
            }
            $updates['name'] = $name;
        }

        if (array_key_exists('code', $validated)) {
            $code = isset($validated['code']) && trim((string) $validated['code']) !== ''
                ? strtolower(trim((string) $validated['code']))
                : null;
            if ($code !== null && DB::table('inventory_rooms')->where('code', $code)->where('id', '!=', $id)->exists()) {
                return $this->fail('Another inventory room with this code already exists', 409);
            }
            $updates['code'] = $code;
        }

        if (array_key_exists('description', $validated)) {
            $updates['description'] = isset($validated['description']) && trim((string) $validated['description']) !== ''
                ? trim((string) $validated['description'])
                : null;
        }

        if (isset($validated['is_active'])) {
            $updates['is_active'] = $validated['is_active'] ? 1 : 0;
        }

        if (isset($validated['sort_order'])) {
            $updates['sort_order'] = $validated['sort_order'];
        }

        if (!empty($updates)) {
            $updates['updated_at'] = now();
            DB::table('inventory_rooms')->where('id', $id)->update($updates);
        }

        return $this->ok('Inventory room updated successfully');
    }

    /**
     * DELETE /api/inventory-rooms/{id}
     * Blocked if the room has items assigned to it.
     * Response: { success, message }
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!DB::table('inventory_rooms')->where('id', $id)->exists()) {
            return $this->fail('Inventory room not found', 404);
        }

        if (DB::table('items')->where('inventory_room_id', $id)->exists()) {
            return $this->fail('Cannot delete inventory room with existing items', 409);
        }

        DB::table('inventory_rooms')->where('id', $id)->delete();

        return $this->ok('Inventory room deleted successfully');
    }
}
