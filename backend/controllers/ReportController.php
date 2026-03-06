<?php
/**
 * Report Controller
 * Handles maintenance report endpoints
 */

class ReportController {
    private $reportService;
    
    public function __construct($pdo) {
        $this->reportService = new ReportService($pdo);
    }
    
    public function create($data) {
        SessionMiddleware::initialize();
        AuthMiddleware::protect();
        
        $result = $this->reportService->createReport($data, $_SESSION['user_id']);
        
        if ($result['success']) {
            Response::success($result['message'], ['report_id' => $result['report_id']], Response::HTTP_CREATED);
        } else {
            $statusCode = isset($result['errors']) ? Response::HTTP_BAD_REQUEST : Response::HTTP_CONFLICT;
            Response::error($result['message'], $result['errors'] ?? [], $statusCode);
        }
    }
    
    public function update($reportId, $data) {
        SessionMiddleware::initialize();
        AuthMiddleware::protect();
        
        $result = $this->reportService->updateReport($reportId, $data, $_SESSION['user_id']);
        
        if ($result['success']) {
            Response::success($result['message']);
        } else {
            Response::error($result['message'], [], Response::HTTP_BAD_REQUEST);
        }
    }
    
    public function getReport($reportId) {
        SessionMiddleware::initialize();
        AuthMiddleware::protect();
        
        $result = $this->reportService->getReport($reportId);
        
        if ($result['success']) {
            Response::success('Report retrieved', ['report' => $result['data']]);
        } else {
            Response::error($result['message'], [], Response::HTTP_NOT_FOUND);
        }
    }
    
    public function assign($reportId, $data) {
        SessionMiddleware::initialize();
        AuthMiddleware::protect();
        RoleMiddleware::requireRole([ROLE_SUPER_ADMIN, ROLE_DEPARTMENT_ADMIN]);
        
        $result = $this->reportService->assignReport($reportId, $data['assigned_to'], $_SESSION['user_id']);
        
        if ($result['success']) {
            Response::success($result['message']);
        } else {
            Response::error($result['message'], [], Response::HTTP_BAD_REQUEST);
        }
    }
    
    public function listReports() {
        SessionMiddleware::initialize();
        AuthMiddleware::protect();
        
        $result = $this->reportService->getAllReports();
        
        if ($result['success']) {
            Response::success('Reports retrieved', $result['data']);
        } else {
            Response::error($result['message'], [], Response::HTTP_INTERNAL_ERROR);
        }
    }
}
