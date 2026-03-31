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

        $role = $this->resolveSessionRole();
        if ($role !== ROLE_MAINTENANCE_STAFF) {
            Response::error('Only maintenance staff can create reports', [], Response::HTTP_FORBIDDEN);
        }
        
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

    public function listLegacyReports() {
        SessionMiddleware::initialize();
        AuthMiddleware::protect();

        $userId = $_SESSION['user_id'] ?? null;
        $role = $this->resolveSessionRole();

        if (!$userId) {
            Response::error('User ID not found in session', [], Response::HTTP_UNAUTHORIZED);
        }

        $result = $this->reportService->getReportsForLegacyDashboard($userId, $role);

        if ($result['success']) {
            Response::success('Reports retrieved successfully', $result['data']);
        } else {
            Response::error($result['message'], [], Response::HTTP_INTERNAL_ERROR);
        }
    }

    public function listFilteredReports($filters = []) {
        SessionMiddleware::initialize();
        AuthMiddleware::protect();

        $userId = $_SESSION['user_id'];
        $role = $this->resolveSessionRole();

        $result = $this->reportService->getReportsWithFilters($userId, $role, $filters);

        if ($result['success']) {
            Response::success('Reports retrieved', $result['data']);
        } else {
            Response::error($result['message'], [], Response::HTTP_INTERNAL_ERROR);
        }
    }

    public function listRecentReports($limit = 10) {
        SessionMiddleware::initialize();
        AuthMiddleware::protect();

        $role = $this->resolveSessionRole();

        $result = $this->reportService->getRecentReports($_SESSION['user_id'], $role, $limit);

        if ($result['success']) {
            Response::success('Recent reports retrieved', $result['data']);
        } else {
            Response::error($result['message'], [], Response::HTTP_INTERNAL_ERROR);
        }
    }

    public function delete($reportId, $allowOwnerDelete = false) {
        SessionMiddleware::initialize();
        AuthMiddleware::protect();

        $userId = $_SESSION['user_id'];
        $role = $this->resolveSessionRole();

        $result = $this->reportService->deleteReport($reportId, $userId, $role, $allowOwnerDelete);

        if ($result['success']) {
            Response::success($result['message']);
        } else {
            $code = $result['code'] ?? Response::HTTP_BAD_REQUEST;
            Response::error($result['message'], [], $code);
        }
    }

    public function stats() {
        SessionMiddleware::initialize();
        AuthMiddleware::protect();

        $result = $this->reportService->getStats();

        if ($result['success']) {
            Response::success('Statistics retrieved successfully', $result['data']);
        } else {
            Response::error($result['message'], [], Response::HTTP_INTERNAL_ERROR);
        }
    }

    private function resolveSessionRole() {
        $rawRole = $_SESSION['user']['role']
            ?? $_SESSION['role']
            ?? ROLE_SUPER_ADMIN;

        $role = strtolower(trim((string)$rawRole));

        if ($role === 'admin_maintenance') {
            return 'maintenance_admin';
        }

        if ($role === 'eelab_staff' || $role === 'maintenance_personnel') {
            return ROLE_MAINTENANCE_STAFF;
        }

        if ($role === '') {
            return ROLE_MAINTENANCE_STAFF;
        }

        return $role;
    }
}
