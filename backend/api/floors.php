<?php
/**
 * Floors API (MVC Router)
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$input = readInput();

try {
    $controller = new FacilityController($pdo);

    switch ($action) {
        case 'list':
            $controller->listFloors();
            break;

        case 'getByBuilding':
            $controller->listFloorsByBuilding($_GET['building_id'] ?? null);
            break;

        case 'get':
            $controller->getFloor($_GET['id'] ?? null);
            break;

        case 'create':
            $controller->createFloor($input);
            break;

        case 'update':
            $controller->updateFloor($input);
            break;

        case 'delete':
            $controller->deleteFloor($input['id'] ?? $_GET['id'] ?? null);
            break;

        default:
            Response::send(['success' => false, 'message' => 'Invalid action'], Response::HTTP_BAD_REQUEST);
    }
} catch (Exception $e) {
    Logger::error('floors.php router error', ['action' => $action, 'error' => $e->getMessage()]);
    Response::send(['success' => false, 'message' => 'Internal server error'], Response::HTTP_INTERNAL_ERROR);
}

function readInput() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        return $json;
    }
    if (!empty($_POST)) {
        return $_POST;
    }
    return [];
}
