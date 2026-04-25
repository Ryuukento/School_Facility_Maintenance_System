<?php
/**
 * Buildings API (MVC Router)
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$action = $_GET['action'] ?? '';
$input = readInput();

try {
    $controller = new FacilityController($pdo);

    switch ($action) {
        case 'list':
            $controller->listBuildings();
            break;

        case 'get':
            $controller->getBuilding($_GET['id'] ?? null);
            break;

        case 'create':
            $controller->createBuilding($input);
            break;

        case 'update':
            $controller->updateBuilding($input);
            break;

        case 'delete':
            $controller->deleteBuilding($input['id'] ?? $_GET['id'] ?? null);
            break;

        default:
            Response::send(['success' => false, 'message' => 'Invalid action'], Response::HTTP_BAD_REQUEST);
    }
} catch (Exception $e) {
    Logger::error('buildings.php router error', ['action' => $action, 'error' => $e->getMessage()]);
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
