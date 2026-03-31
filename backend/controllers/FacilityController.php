<?php
/**
 * Facility Controller
 */

class FacilityController {
    private $facilityService;

    public function __construct($pdo) {
        $this->facilityService = new FacilityService($pdo);
    }

    public function listBuildings() {
        $this->guard();
        $this->sendLegacy($this->facilityService->listBuildings());
    }

    public function getBuilding($id) {
        $this->guard();
        $this->sendLegacy($this->facilityService->getBuilding($id));
    }

    public function createBuilding($data) {
        $this->guard();
        $this->sendLegacy($this->facilityService->createBuilding($data));
    }

    public function updateBuilding($data) {
        $this->guard();
        $this->sendLegacy($this->facilityService->updateBuilding($data));
    }

    public function deleteBuilding($id) {
        $this->guard();
        $this->sendLegacy($this->facilityService->deleteBuilding($id));
    }

    public function listFloors() {
        $this->guard();
        $this->sendLegacy($this->facilityService->listFloors());
    }

    public function listFloorsByBuilding($buildingId) {
        $this->guard();
        $this->sendLegacy($this->facilityService->listFloorsByBuilding($buildingId));
    }

    public function getFloor($id) {
        $this->guard();
        $this->sendLegacy($this->facilityService->getFloor($id));
    }

    public function createFloor($data) {
        $this->guard();
        $this->sendLegacy($this->facilityService->createFloor($data));
    }

    public function updateFloor($data) {
        $this->guard();
        $this->sendLegacy($this->facilityService->updateFloor($data));
    }

    public function deleteFloor($id) {
        $this->guard();
        $this->sendLegacy($this->facilityService->deleteFloor($id));
    }

    public function listRooms() {
        $this->guard();
        $this->sendLegacy($this->facilityService->listRooms());
    }

    public function listRoomsByBuilding($buildingId) {
        $this->guard();
        $this->sendLegacy($this->facilityService->listRoomsByBuilding($buildingId));
    }

    public function listRoomsByFloor($floorId) {
        $this->guard();
        $this->sendLegacy($this->facilityService->listRoomsByFloor($floorId));
    }

    public function getRoom($id) {
        $this->guard();
        $this->sendLegacy($this->facilityService->getRoom($id));
    }

    public function createRoom($data) {
        $this->guard();
        $this->sendLegacy($this->facilityService->createRoom($data));
    }

    public function updateRoom($data) {
        $this->guard();
        $this->sendLegacy($this->facilityService->updateRoom($data));
    }

    public function deleteRoom($id) {
        $this->guard();
        $this->sendLegacy($this->facilityService->deleteRoom($id));
    }

    public function listItems() {
        $this->guard();
        $this->sendLegacy($this->facilityService->listItems());
    }

    public function listItemsByRoom($roomId) {
        $this->guard();
        $this->sendLegacy($this->facilityService->listItemsByRoom($roomId));
    }

    public function getItem($id) {
        $this->guard();
        $this->sendLegacy($this->facilityService->getItem($id));
    }

    public function createItem($data) {
        $this->guard();
        $this->sendLegacy($this->facilityService->createItem($data));
    }

    public function updateItem($data) {
        $this->guard();
        $this->sendLegacy($this->facilityService->updateItem($data));
    }

    public function deleteItem($id) {
        $this->guard();
        $this->sendLegacy($this->facilityService->deleteItem($id));
    }

    private function guard() {
        SessionMiddleware::initialize();
        AuthMiddleware::protect();
    }

    private function sendLegacy($result) {
        $status = $result['code'] ?? Response::HTTP_OK;
        unset($result['code']);
        Response::send($result, $status);
    }
}
