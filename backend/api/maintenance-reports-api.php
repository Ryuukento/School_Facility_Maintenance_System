<?php
/**
 * Maintenance Reports API (MVC Router)
 * Uses ReportController + ReportService for report operations.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$action = $_GET['action'] ?? null;
$reportId = $_GET['report_id'] ?? $_GET['id'] ?? null;
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (!$reportId && isset($input['report_id'])) {
    $reportId = $input['report_id'];
}

try {
    $reportController = new ReportController($pdo);

    switch ($action) {
        case 'recent':
            $limit = (int)($_GET['limit'] ?? 10);
            $reportController->listRecentReports($limit > 0 ? $limit : 10);
            break;

        case 'list':
            $reportController->listFilteredReports($_GET);
            break;

        case 'get':
            if (!$reportId) {
                Response::error('Report ID required', [], Response::HTTP_BAD_REQUEST);
            }
            $reportController->getReport($reportId);
            break;

        case 'create':
            $reportController->create($input);
            break;

        case 'update':
            if (!$reportId) {
                Response::error('Report ID required', [], Response::HTTP_BAD_REQUEST);
            }
            $reportController->update($reportId, $input);
            break;

        case 'delete':
            if (!$reportId) {
                Response::error('Report ID required', [], Response::HTTP_BAD_REQUEST);
            }
            $reportController->delete($reportId, true);
            break;

        default:
            Response::error('Invalid action', [], Response::HTTP_BAD_REQUEST);
    }
} catch (Throwable $e) {
    Logger::error('maintenance-reports-api.php router error', [
        'action' => $action,
        'report_id' => $reportId,
        'error' => $e->getMessage()
    ]);
    Response::error('Internal server error', [], Response::HTTP_INTERNAL_ERROR);
}
