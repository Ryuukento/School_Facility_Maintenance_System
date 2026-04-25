<?php
/**
 * Reports API
 * Handles maintenance report endpoints
 */

require_once dirname(__DIR__) . '/bootstrap.php';

// Get action from query string
$action = $_GET['action'] ?? null;
$reportId = $_GET['report_id'] ?? $_GET['id'] ?? null;

// Get request body
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    $reportController = new ReportController($pdo);
    
    switch ($action) {
        case 'create':
            $reportController->create($input);
            break;
        
        case 'list':
            $reportController->listReports();
            break;
        
        case 'update':
            if (!$reportId) {
                Response::error('Report ID required', [], Response::HTTP_BAD_REQUEST);
            }
            $reportController->update($reportId, $input);
            break;
        
        case 'get':
            if (!$reportId) {
                Response::error('Report ID required', [], Response::HTTP_BAD_REQUEST);
            }
            $reportController->getReport($reportId);
            break;
        
        case 'assign':
            if (!$reportId) {
                Response::error('Report ID required', [], Response::HTTP_BAD_REQUEST);
            }
            $reportController->assign($reportId, $input);
            break;
        
        default:
            Response::error('Invalid action', [], Response::HTTP_BAD_REQUEST);
    }
} catch (Exception $e) {
    Logger::error('API error', ['action' => $action, 'report_id' => $reportId, 'error' => $e->getMessage()]);
    Response::error('Internal server error', [], Response::HTTP_INTERNAL_ERROR);
}
