<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\RoleNormalizerService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuildingController extends Controller
{
    use ApiResponder;

    public function __construct(
        // TASK 18 — "Building Updated" notification.
        private readonly NotificationService $notificationService
    ) {
    }

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
     *
     * room_count only counts active rooms (r.is_active = 1 is applied in the
     * JOIN's ON clause, not a WHERE, so buildings with zero active rooms
     * still appear with room_count = 0 instead of being dropped). Rooms are
     * soft-deactivated rather than hard-deleted (see RoomController), so
     * without this the count would include rooms no longer meant to surface
     * in normal building/room-picker UIs.
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
            ->leftJoin('rooms as r', function ($join) {
                $join->on('b.id', '=', 'r.building_id')
                     ->where('r.is_active', 1);
            })
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
     *
     * room_count/item_count only reflect active rooms — see the is_active
     * note on index() above. The rooms join filters on r.is_active in its ON
     * clause (not a WHERE) so floors with zero active rooms still appear
     * with room_count = 0 rather than being dropped by the LEFT JOIN.
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
            ->leftJoin('rooms as r', function ($join) {
                $join->on('f.id', '=', 'r.floor_id')
                     ->where('r.is_active', 1);
            })
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

        // TASK 18 — "Building Updated" notification.
        // TASK 52 — only notify on a genuine change of name/description.
        // Re-saving the same values (e.g. an unchanged form submit) should
        // not spam every Super Admin/Head Maintenance user, mirroring the
        // "only notify on a genuine change" pattern already established in
        // DispatchService::assignReleasePersonnel().
        if ($building->name !== $name || (string) $building->description !== $description) {
            $this->notifyBuildingUpdated($request, $id, $name);
        }

        return $this->ok('Building updated successfully');
    }

    /**
     * TASK 18 — notifies active Super Admins and Head Maintenance (maintenance_admin)
     * users that a building's details were updated. Excludes the acting user
     * to avoid self-notification. entity_type is 'building' — there is no
     * building detail page, so the frontend ENTITY_ROUTES entry for
     * 'building' is clientOnly and navigates to buildings-overview.php with
     * a highlight, mirroring the existing 'user' entity route.
     */
    private function notifyBuildingUpdated(Request $request, int $buildingId, string $buildingName): void
    {
        $performedBy = (int) $request->session()->get('user_id');

        $title = 'Building Updated: ' . $buildingName;
        $message = 'Building "' . $buildingName . '" details were updated.';

        $recipientRoles = RoleNormalizerService::rawValuesFor(['super_admin', 'maintenance_admin']);
        $recipientIds = User::query()
            ->where('status', 'active')
            ->whereIn('role', $recipientRoles)
            ->when($performedBy, fn ($query) => $query->where('user_id', '!=', $performedBy))
            ->pluck('user_id');

        foreach ($recipientIds as $recipientId) {
            $this->notificationService->notify((int) $recipientId, $title, $message, 'building', $buildingId);
        }
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
