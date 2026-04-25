<?php
/**
 * Legacy Reports API (MVC Router)
 * Backward-compatible endpoint for frontend calls to reports.php
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
        case 'list':
            $reportController->listLegacyReports();
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
            // Keep legacy endpoint aligned with maintenance-reports-api owner-delete behavior.
            $reportController->delete($reportId, true);
            break;

        case 'stats':
            $reportController->stats();
            break;

        default:
            Response::error('Invalid action', [], Response::HTTP_BAD_REQUEST);
    }
} catch (Throwable $e) {
    Logger::error('reports.php router error', [
        'action' => $action,
        'report_id' => $reportId,
        'error' => $e->getMessage()
    ]);
    Response::error('Internal server error', [], Response::HTTP_INTERNAL_ERROR);
}
