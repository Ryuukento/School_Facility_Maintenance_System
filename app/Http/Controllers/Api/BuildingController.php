<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuildingController extends Controller
{
    use ApiResponder;

    /**
     * GET /api/buildings
     *
     * Query params:
     *   q        – optional search (matches building name)
     *   per_page – 1–200, default 100
     *   page     – default 1
     *
     * Response shape:
     *   { success, message, data: { buildings: [{id, name, description, floor_count, room_count}], pagination } }
     */
    public function index(Request $request): JsonResponse
    {
        $q       = trim((string) $request->query('q', ''));
        $perPage = max(1, min(200, (int) $request->query('per_page', 100)));
        $page    = max(1, (int) $request->query('page', 1));

        $baseQuery = fn () => DB::table('buildings as b')
            ->when($q !== '', fn ($q2) => $q2->whereRaw('LOWER(b.name) LIKE ?', ['%' . strtolower($q) . '%']));

        $total = $baseQuery()->count();

        $buildings = $baseQuery()
            ->select([
                'b.id',
                'b.name',
                'b.description',
                DB::raw('COUNT(DISTINCT f.id) as floor_count'),
                DB::raw('COUNT(DISTINCT r.id) as room_count'),
            ])
            ->leftJoin('floors as f', 'b.id', '=', 'f.building_id')
            ->leftJoin('rooms as r',  'b.id', '=', 'r.building_id')
            ->groupBy('b.id', 'b.name', 'b.description')
            ->orderByDesc('b.created_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return $this->ok('Buildings retrieved', [
            'buildings'  => $buildings,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / max(1, $perPage)),
            ],
        ]);
    }

    /**
     * GET /api/buildings/{id}/floors
     *
     * Response shape:
     *   { success, message, data: { floors: [{id, name, description, building_id, room_count, item_count}] } }
     */
    public function floors(int $id): JsonResponse
    {
        $floors = DB::table('floors as f')
            ->select([
                'f.id',
                'f.name',
                'f.description',
                'f.building_id',
                DB::raw('COALESCE(COUNT(DISTINCT r.id), 0) as room_count'),
                DB::raw('COALESCE(SUM(COALESCE(i.quantity, 0)), 0) as item_count'),
            ])
            ->leftJoin('rooms as r', 'f.id', '=', 'r.floor_id')
            ->leftJoin('items as i', 'r.id', '=', 'i.room_id')
            ->where('f.building_id', $id)
            ->groupBy('f.id', 'f.name', 'f.description', 'f.building_id')
            ->orderBy('f.name')
            ->get();

        return $this->ok('Floors retrieved', [
            'floors' => $floors,
        ]);
    }

    /** GET /api/buildings/deployed-items */
    public function deployedItems(Request $request): JsonResponse
    {
        $buildingId = (int) $request->query('building_id', 0);
        $roomId = (int) $request->query('room_id', 0);

        $query = DB::table('dispatches as d')
            ->select([
                'i.name as item_name',
                DB::raw('COALESCE(c.name, \'Uncategorized\') as category'),
                'di.quantity',
                'i.unit_type as unit',
                'i.item_condition as condition',
                'd.updated_at as date_dispatched',
                'd.dispatch_code',
                'r.name as room_name',
                'b.name as building_name',
                'b.id as building_id',
                'r.id as room_id',
            ])
            ->join('dispatch_items as di', 'd.id', '=', 'di.dispatch_id')
            ->join('items as i', 'di.item_id', '=', 'i.id')
            ->leftJoin('rooms as r', 'd.room_id', '=', 'r.id')
            ->leftJoin('buildings as b', 'r.building_id', '=', 'b.id')
            ->leftJoin('inventory_categories as c', 'i.category_id', '=', 'c.id')
            ->where('d.status', 'released');

        if ($buildingId > 0) {
            $query->where('b.id', $buildingId);
        }

        if ($roomId > 0) {
            $query->where('r.id', $roomId);
        }

        $items = $query
            ->orderBy('b.name')
            ->orderBy('r.name')
            ->orderBy('d.updated_at')
            ->orderBy('i.name')
            ->get();

        return $this->ok('Deployed items retrieved', ['items' => $items]);
    }

    /** POST /api/buildings */
    public function store(Request $request): JsonResponse
    {
        $name        = trim((string) $request->input('name', ''));
        $description = trim((string) $request->input('description', ''));

        if ($name === '') {
            return $this->fail('Building name is required', 422);
        }

        $exists = DB::table('buildings')
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->exists();
        if ($exists) {
            return $this->fail('Building with this name already exists', 409);
        }

        $id = DB::table('buildings')->insertGetId([
            'name'        => $name,
            'description' => $description,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return $this->ok('Building created successfully', ['id' => $id, 'name' => $name], 201);
    }

    /** PATCH /api/buildings/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $name        = trim((string) $request->input('name', ''));
        $description = trim((string) $request->input('description', ''));

        if ($name === '') {
            return $this->fail('Building name is required', 422);
        }

        $building = DB::table('buildings')->where('id', $id)->first();
        if (!$building) {
            return $this->fail('Building not found', 404);
        }

        $duplicate = DB::table('buildings')
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->where('id', '!=', $id)
            ->exists();
        if ($duplicate) {
            return $this->fail('Building with this name already exists', 409);
        }

        DB::table('buildings')->where('id', $id)->update([
            'name'        => $name,
            'description' => $description,
            'updated_at'  => now(),
        ]);

        return $this->ok('Building updated successfully');
    }

    /** DELETE /api/buildings/{id} */
    public function destroy(int $id): JsonResponse
    {
        if (DB::table('floors')->where('building_id', $id)->exists()) {
            return $this->fail('Cannot delete building with existing floors', 409);
        }
        if (DB::table('rooms')->where('building_id', $id)->exists()) {
            return $this->fail('Cannot delete building with existing rooms', 409);
        }

        $deleted = DB::table('buildings')->where('id', $id)->delete();
        if (!$deleted) {
            return $this->fail('Building not found', 404);
        }

        return $this->ok('Building deleted successfully');
    }

    /** POST /api/buildings/{id}/floors */
    public function storeFloor(Request $request, int $id): JsonResponse
    {
        $name = trim((string) $request->input('name', ''));

        if ($name === '') {
            return $this->fail('Floor name is required', 422);
        }

        if (!DB::table('buildings')->where('id', $id)->exists()) {
            return $this->fail('Building not found', 404);
        }

        $duplicate = DB::table('floors')
            ->where('building_id', $id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->exists();
        if ($duplicate) {
            return $this->fail('Floor with this name already exists in this building', 409);
        }

        $floorId = DB::table('floors')->insertGetId([
            'building_id' => $id,
            'name'        => $name,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return $this->ok('Floor created successfully', ['id' => $floorId, 'name' => $name], 201);
    }

    /** DELETE /api/buildings/{id}/floors/{floorId} */
    public function destroyFloor(int $id, int $floorId): JsonResponse
    {
        if (DB::table('rooms')->where('floor_id', $floorId)->exists()) {
            return $this->fail('Cannot delete floor with existing rooms', 409);
        }

        $deleted = DB::table('floors')
            ->where('id', $floorId)
            ->where('building_id', $id)
            ->delete();
        if (!$deleted) {
            return $this->fail('Floor not found', 404);
        }

        return $this->ok('Floor deleted successfully');
    }
}
