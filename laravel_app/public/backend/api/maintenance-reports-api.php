<?php
/**
 * Maintenance Reports API (MVC Router)
 * Uses ReportController + ReportService for report operations.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$action = $_GET['action'] ?? null;
$reportId = $_GET['report_id'] ?? $_GET['id'] ?? null;
$contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
$isMultipart = strpos($contentType, 'multipart/form-data') !== false;

if ($isMultipart) {
    $input = $_POST;
} else {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

if (!$reportId && isset($input['report_id'])) {
    $reportId = $input['report_id'];
}

if ($isMultipart && isset($_FILES['completion_proof_image'])) {
    $input['completion_proof_image'] = saveCompletionProofImage($_FILES['completion_proof_image'], (int)$reportId);
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

function saveCompletionProofImage(array $file, $reportId) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        Response::error('Failed to upload completion proof image', [], Response::HTTP_BAD_REQUEST);
    }

    $maxBytes = 5 * 1024 * 1024;
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        Response::error('Completion proof image must be 5MB or smaller', [], Response::HTTP_BAD_REQUEST);
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        Response::error('Invalid completion proof upload', [], Response::HTTP_BAD_REQUEST);
    }

    $mime = (string)(mime_content_type($tmpName) ?: '');
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        Response::error('Only JPG, PNG, WEBP, or GIF images are allowed for completion proof', [], Response::HTTP_BAD_REQUEST);
    }

    $uploadDir = ROOT_DIR . '/frontend/uploads/completion-proofs';
    if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        Response::error('Unable to prepare upload directory for completion proof', [], Response::HTTP_INTERNAL_ERROR);
    }

    $safeReportId = max(0, (int)$reportId);
    $fileName = 'report-' . $safeReportId . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $destination = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        Response::error('Failed to save completion proof image', [], Response::HTTP_INTERNAL_ERROR);
    }

    return '/School_Facility_Maintenance_System/laravel_app/public/frontend/uploads/completion-proofs/' . $fileName;
}
