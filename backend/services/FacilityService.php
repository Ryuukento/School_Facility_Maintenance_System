<?php
/**
 * Facility Service
 * Centralized business logic for buildings, floors, rooms, and items.
 */

class FacilityService {
    private $pdo;
    private $buildingModel;
    private $floorModel;
    private $roomModel;
    private $itemModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->buildingModel = new Building($pdo);
        $this->floorModel = new Floor($pdo);
        $this->roomModel = new Room($pdo);
        $this->itemModel = new Item($pdo);
    }

    public function listBuildings() {
        try {
            $rows = $this->buildingModel->getAllWithCounts();
            return ['success' => true, 'message' => 'Buildings retrieved successfully', 'buildings' => $rows];
        } catch (Exception $e) {
            Logger::error('Failed to list buildings', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to retrieve buildings', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function getBuilding($id) {
        if (!$id) {
            return ['success' => false, 'message' => 'Building ID is required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            $row = $this->buildingModel->findByIdWithCounts($id);
            if (!$row) {
                return ['success' => false, 'message' => 'Building not found', 'code' => Response::HTTP_NOT_FOUND];
            }
            return ['success' => true, 'message' => 'Building retrieved successfully', 'building' => $row];
        } catch (Exception $e) {
            Logger::error('Failed to get building', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to retrieve building', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function createBuilding($data) {
        $name = trim((string)($data['name'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));

        if ($name === '') {
            return ['success' => false, 'message' => 'Building name is required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            if ($this->buildingModel->existsByName($name)) {
                return ['success' => false, 'message' => 'Building with this name already exists', 'code' => Response::HTTP_CONFLICT];
            }

            $id = $this->buildingModel->create($name, $description);
            return ['success' => true, 'message' => 'Building created successfully', 'id' => $id, 'name' => $name];
        } catch (Exception $e) {
            Logger::error('Failed to create building', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function updateBuilding($data) {
        $id = (int)($data['id'] ?? 0);
        $name = trim((string)($data['name'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));

        if ($id <= 0 || $name === '') {
            return ['success' => false, 'message' => 'Building ID and name are required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            if ($this->buildingModel->existsByName($name, $id)) {
                return ['success' => false, 'message' => 'Building with this name already exists', 'code' => Response::HTTP_CONFLICT];
            }

            $this->buildingModel->update($id, $name, $description);
            return ['success' => true, 'message' => 'Building updated successfully'];
        } catch (Exception $e) {
            Logger::error('Failed to update building', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to update building', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function deleteBuilding($id) {
        if (!$id) {
            return ['success' => false, 'message' => 'Building ID is required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            if ($this->buildingModel->hasFloors($id)) {
                return ['success' => false, 'message' => 'Cannot delete building with existing floors', 'code' => Response::HTTP_CONFLICT];
            }
            if ($this->buildingModel->hasRooms($id)) {
                return ['success' => false, 'message' => 'Cannot delete building with existing rooms', 'code' => Response::HTTP_CONFLICT];
            }

            $this->buildingModel->delete($id);
            return ['success' => true, 'message' => 'Building deleted successfully'];
        } catch (Exception $e) {
            Logger::error('Failed to delete building', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to delete building', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function listFloors() {
        try {
            $rows = $this->floorModel->getAll();
            return ['success' => true, 'message' => 'Floors retrieved successfully', 'floors' => $rows];
        } catch (Exception $e) {
            Logger::error('Failed to list floors', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to retrieve floors', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function listFloorsByBuilding($buildingId) {
        if (!$buildingId) {
            return ['success' => false, 'message' => 'Building ID is required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            $rows = $this->floorModel->getByBuilding($buildingId);
            return ['success' => true, 'message' => 'Floors retrieved successfully', 'floors' => $rows];
        } catch (Exception $e) {
            Logger::error('Failed to list floors by building', ['building_id' => $buildingId, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to retrieve floors', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function getFloor($id) {
        if (!$id) {
            return ['success' => false, 'message' => 'Floor ID is required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            $row = $this->floorModel->findById($id);
            if (!$row) {
                return ['success' => false, 'message' => 'Floor not found', 'code' => Response::HTTP_NOT_FOUND];
            }
            return ['success' => true, 'message' => 'Floor retrieved successfully', 'floor' => $row];
        } catch (Exception $e) {
            Logger::error('Failed to get floor', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to retrieve floor', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function createFloor($data) {
        $buildingId = (int)($data['building_id'] ?? 0);
        $name = trim((string)($data['name'] ?? ''));

        if ($buildingId <= 0 || $name === '') {
            return ['success' => false, 'message' => 'Building ID and floor name are required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            if (!$this->buildingModel->findByIdWithCounts($buildingId)) {
                return ['success' => false, 'message' => 'Building not found', 'code' => Response::HTTP_NOT_FOUND];
            }

            if ($this->floorModel->existsByBuildingAndName($buildingId, $name)) {
                return ['success' => false, 'message' => 'Floor with this name already exists in this building', 'code' => Response::HTTP_CONFLICT];
            }

            $id = $this->floorModel->create($buildingId, $name);
            return ['success' => true, 'message' => 'Floor created successfully', 'id' => $id, 'name' => $name];
        } catch (Exception $e) {
            Logger::error('Failed to create floor', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function updateFloor($data) {
        $id = (int)($data['id'] ?? 0);
        $buildingId = (int)($data['building_id'] ?? 0);
        $name = trim((string)($data['name'] ?? ''));

        if ($id <= 0 || $buildingId <= 0 || $name === '') {
            return ['success' => false, 'message' => 'Floor ID, building ID and name are required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            if (!$this->buildingModel->findByIdWithCounts($buildingId)) {
                return ['success' => false, 'message' => 'Building not found', 'code' => Response::HTTP_NOT_FOUND];
            }

            if ($this->floorModel->existsByBuildingAndName($buildingId, $name, $id)) {
                return ['success' => false, 'message' => 'Floor with this name already exists in this building', 'code' => Response::HTTP_CONFLICT];
            }

            $this->floorModel->update($id, $buildingId, $name);
            return ['success' => true, 'message' => 'Floor updated successfully'];
        } catch (Exception $e) {
            Logger::error('Failed to update floor', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to update floor', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function deleteFloor($id) {
        if (!$id) {
            return ['success' => false, 'message' => 'Floor ID is required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            if ($this->floorModel->hasRooms($id)) {
                return ['success' => false, 'message' => 'Cannot delete floor with existing rooms', 'code' => Response::HTTP_CONFLICT];
            }
            $this->floorModel->delete($id);
            return ['success' => true, 'message' => 'Floor deleted successfully'];
        } catch (Exception $e) {
            Logger::error('Failed to delete floor', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to delete floor', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function listRooms() {
        try {
            $rows = $this->roomModel->getAll();
            return ['success' => true, 'message' => 'Rooms retrieved successfully', 'rooms' => $rows];
        } catch (Exception $e) {
            Logger::error('Failed to list rooms', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to retrieve rooms', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function listRoomsByBuilding($buildingId) {
        if (!$buildingId) {
            return ['success' => false, 'message' => 'Building ID is required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            $rows = $this->roomModel->getByBuilding($buildingId);
            return ['success' => true, 'message' => 'Rooms retrieved successfully', 'rooms' => $rows];
        } catch (Exception $e) {
            Logger::error('Failed to list rooms by building', ['building_id' => $buildingId, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to retrieve rooms', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function listRoomsByFloor($floorId) {
        if (!$floorId) {
            return ['success' => false, 'message' => 'Floor ID is required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            $rows = $this->roomModel->getByFloor($floorId);
            return ['success' => true, 'message' => 'Rooms retrieved successfully', 'rooms' => $rows];
        } catch (Exception $e) {
            Logger::error('Failed to list rooms by floor', ['floor_id' => $floorId, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to retrieve rooms', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function getRoom($id) {
        if (!$id) {
            return ['success' => false, 'message' => 'Room ID is required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            $row = $this->roomModel->findById($id);
            if (!$row) {
                return ['success' => false, 'message' => 'Room not found', 'code' => Response::HTTP_NOT_FOUND];
            }
            return ['success' => true, 'message' => 'Room retrieved successfully', 'room' => $row];
        } catch (Exception $e) {
            Logger::error('Failed to get room', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to retrieve room', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function createRoom($data) {
        $buildingId = (int)($data['building_id'] ?? 0);
        $floorId = (int)($data['floor_id'] ?? 0);
        $name = trim((string)($data['name'] ?? ''));
        $capacity = $data['capacity'] ?? null;

        if ($buildingId <= 0 || $floorId <= 0 || $name === '') {
            return ['success' => false, 'message' => 'Building, floor and room name are required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            if (!$this->buildingModel->findByIdWithCounts($buildingId)) {
                return ['success' => false, 'message' => 'Building not found', 'code' => Response::HTTP_NOT_FOUND];
            }

            $floor = $this->floorModel->findById($floorId);
            if (!$floor || (int)$floor['building_id'] !== $buildingId) {
                return ['success' => false, 'message' => 'Floor not found or does not belong to building', 'code' => Response::HTTP_BAD_REQUEST];
            }

            if ($this->roomModel->existsByFloorAndName($floorId, $name)) {
                return ['success' => false, 'message' => 'Room with this name already exists on this floor', 'code' => Response::HTTP_CONFLICT];
            }

            $id = $this->roomModel->create($buildingId, $floorId, $name, $capacity);
            return ['success' => true, 'message' => 'Room created successfully', 'id' => $id, 'name' => $name];
        } catch (Exception $e) {
            Logger::error('Failed to create room', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function updateRoom($data) {
        $id = (int)($data['id'] ?? 0);
        $buildingId = (int)($data['building_id'] ?? 0);
        $floorId = (int)($data['floor_id'] ?? 0);
        $name = trim((string)($data['name'] ?? ''));
        $capacity = $data['capacity'] ?? null;

        if ($id <= 0 || $buildingId <= 0 || $floorId <= 0 || $name === '') {
            return ['success' => false, 'message' => 'Room ID, building ID, floor ID and name are required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            $floor = $this->floorModel->findById($floorId);
            if (!$floor || (int)$floor['building_id'] !== $buildingId) {
                return ['success' => false, 'message' => 'Floor not found or does not belong to building', 'code' => Response::HTTP_BAD_REQUEST];
            }

            if ($this->roomModel->existsByFloorAndName($floorId, $name, $id)) {
                return ['success' => false, 'message' => 'Room with this name already exists on this floor', 'code' => Response::HTTP_CONFLICT];
            }

            $this->roomModel->update($id, $buildingId, $floorId, $name, $capacity);
            return ['success' => true, 'message' => 'Room updated successfully'];
        } catch (Exception $e) {
            Logger::error('Failed to update room', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to update room', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function deleteRoom($id) {
        if (!$id) {
            return ['success' => false, 'message' => 'Room ID is required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            $this->roomModel->delete($id);
            return ['success' => true, 'message' => 'Room deleted successfully'];
        } catch (Exception $e) {
            Logger::error('Failed to delete room', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to delete room', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function listItems() {
        try {
            $rows = $this->itemModel->getAll();
            return ['success' => true, 'message' => 'Items retrieved successfully', 'items' => $rows];
        } catch (Exception $e) {
            Logger::error('Failed to list items', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to retrieve items', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function listItemsByRoom($roomId) {
        if (!$roomId) {
            return ['success' => false, 'message' => 'Room ID is required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            $rows = $this->itemModel->getByRoom($roomId);
            return ['success' => true, 'message' => 'Items retrieved successfully', 'items' => $rows];
        } catch (Exception $e) {
            Logger::error('Failed to list items by room', ['room_id' => $roomId, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to retrieve items', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function getItem($id) {
        if (!$id) {
            return ['success' => false, 'message' => 'Item ID is required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            $row = $this->itemModel->findById($id);
            if (!$row) {
                return ['success' => false, 'message' => 'Item not found', 'code' => Response::HTTP_NOT_FOUND];
            }
            return ['success' => true, 'message' => 'Item retrieved successfully', 'item' => $row];
        } catch (Exception $e) {
            Logger::error('Failed to get item', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to retrieve item', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function createItem($data) {
        $roomId = (int)($data['room_id'] ?? 0);
        $name = trim((string)($data['name'] ?? ''));
        $status = trim((string)($data['status'] ?? 'available'));
        $quantity = (int)($data['quantity'] ?? 1);
        $description = trim((string)($data['description'] ?? ''));

        if ($roomId <= 0 || $name === '') {
            return ['success' => false, 'message' => 'Room ID and item name are required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            if (!$this->roomModel->findById($roomId)) {
                return ['success' => false, 'message' => 'Room not found', 'code' => Response::HTTP_NOT_FOUND];
            }

            $id = $this->itemModel->create($roomId, $name, $status, $quantity, $description);
            return ['success' => true, 'message' => 'Item created successfully', 'id' => $id, 'name' => $name];
        } catch (Exception $e) {
            Logger::error('Failed to create item', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function updateItem($data) {
        $id = (int)($data['id'] ?? 0);
        $roomId = (int)($data['room_id'] ?? 0);
        $name = trim((string)($data['name'] ?? ''));
        $status = $data['status'] ?? null;
        $quantity = $data['quantity'] ?? null;
        $description = $data['description'] ?? null;

        if ($id <= 0 || $roomId <= 0 || $name === '') {
            return ['success' => false, 'message' => 'Item ID, room ID and name are required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            $this->itemModel->update($id, $roomId, $name, $status, $quantity, $description);
            return ['success' => true, 'message' => 'Item updated successfully'];
        } catch (Exception $e) {
            Logger::error('Failed to update item', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to update item', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }

    public function deleteItem($id) {
        if (!$id) {
            return ['success' => false, 'message' => 'Item ID is required', 'code' => Response::HTTP_BAD_REQUEST];
        }

        try {
            $this->itemModel->delete($id);
            return ['success' => true, 'message' => 'Item deleted successfully'];
        } catch (Exception $e) {
            Logger::error('Failed to delete item', ['id' => $id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Failed to delete item', 'code' => Response::HTTP_INTERNAL_ERROR];
        }
    }
}
