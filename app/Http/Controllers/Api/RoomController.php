<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    use ApiResponder;

    /**
     * GET /api/rooms
     *
     * Query params:
     *   q           – optional search (matches room name or building name)
     *   per_page    – 1–200, default 50
     *   page        – default 1
     *   building_id – optional filter by building
     *
     * Response shape:
     *   { success, message, data: { rooms: [{id, name, capacity, building_id, building_name, floor_id, floor_name}], pagination: {...} } }
     */
    public function index(Request $request): JsonResponse
    {
        $q          = trim((string) $request->query('q', ''));
        $perPage    = max(1, min(200, (int) $request->query('per_page', 50)));
        $page       = max(1, (int) $request->query('page', 1));
        $buildingId = (int) $request->query('building_id', 0);
        $floorId    = (int) $request->query('floor_id', 0);

        $query = DB::table('rooms')
            ->select([
                'rooms.id',
                'rooms.name',
                'rooms.capacity',
                'rooms.building_id',
                'rooms.floor_id',
                'buildings.name as building_name',
                'floors.name as floor_name',
            ])
            ->leftJoin('buildings', 'rooms.building_id', '=', 'buildings.id')
            ->leftJoin('floors',    'rooms.floor_id',    '=', 'floors.id')
            ->orderBy('buildings.name')
            ->orderBy('floors.name')
            ->orderBy('rooms.name');

        if ($q !== '') {
            $like = '%' . strtolower($q) . '%';
            $query->where(function ($sub) use ($like) {
                $sub->whereRaw('LOWER(rooms.name) LIKE ?',       [$like])
                    ->orWhereRaw('LOWER(buildings.name) LIKE ?', [$like]);
            });
        }

        if ($buildingId > 0) {
            $query->where('rooms.building_id', $buildingId);
        }

        if ($floorId > 0) {
            $query->where('rooms.floor_id', $floorId);
        }

        $total = $query->count();
        $rooms = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

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

    /** POST /api/rooms */
    public function store(Request $request): JsonResponse
    {
        $buildingId = (int) $request->input('building_id', 0);
        $floorId    = (int) $request->input('floor_id', 0);
        $name       = trim((string) $request->input('name', ''));
        $capacity   = $request->input('capacity');

        if ($buildingId <= 0 || $floorId <= 0 || $name === '') {
            return $this->fail('Building, floor, and room name are required', 422);
        }

        if ($capacity !== null && $capacity !== '') {
            $capacity = (int) $capacity;
            if ($capacity < 1 || $capacity > 60) {
                return $this->fail('Room capacity must be between 1 and 60', 422);
            }
        } else {
            $capacity = null;
        }

        $floor = DB::table('floors')
            ->where('id', $floorId)
            ->where('building_id', $buildingId)
            ->first();
        if (!$floor) {
            return $this->fail('Floor not found or does not belong to the building', 422);
        }

        $duplicate = DB::table('rooms')
            ->where('floor_id', $floorId)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->exists();
        if ($duplicate) {
            return $this->fail('Room with this name already exists on this floor', 409);
        }

        $id = DB::table('rooms')->insertGetId([
            'building_id' => $buildingId,
            'floor_id'    => $floorId,
            'name'        => $name,
            'capacity'    => $capacity,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return $this->ok('Room created successfully', ['id' => $id, 'name' => $name], 201);
    }

    /** DELETE /api/rooms/{id} */
    public function destroy(int $id): JsonResponse
    {
        $deleted = DB::table('rooms')->where('id', $id)->delete();
        if (!$deleted) {
            return $this->fail('Room not found', 404);
        }

        return $this->ok('Room deleted successfully');
    }
}
