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
        
        $currentUserId = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? 0);
        $result = $this->reportService->createReport($data, $currentUserId);
        
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

        $role = $this->resolveSessionRole();
        $currentUserId = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? 0);
        $reportLookup = $this->reportService->getReport($reportId);
        if (empty($reportLookup['success']) || empty($reportLookup['data'])) {
            Response::error('Report not found', [], Response::HTTP_NOT_FOUND);
        }

        $reportOwnerId = (int)($reportLookup['data']['created_by'] ?? 0);

        $payloadKeys = array_keys((array)$data);
        $statusFlowKeys = ['status', 'comment', 'assigned_to', 'report_id', 'completion_proof_image'];
        $approvalKeys = ['approve_need_change', 'comment', 'report_id'];
        $rejectKeys = ['reject_need_change', 'comment', 'report_id'];
        $isStatusFlowUpdate = isset($data['status'])
            && count(array_diff($payloadKeys, $statusFlowKeys)) === 0;
        $isNeedChangeApproval = !empty($data['approve_need_change'])
            && count(array_diff($payloadKeys, $approvalKeys)) === 0;
        $isNeedChangeRejection = !empty($data['reject_need_change'])
            && count(array_diff($payloadKeys, $rejectKeys)) === 0;

        if ($isNeedChangeApproval || $isNeedChangeRejection) {
            if ($role !== ROLE_SUPER_ADMIN) {
                Response::error('Only Super Admin can approve/reject Need Change requests', [], Response::HTTP_FORBIDDEN);
            }
        } elseif ($isStatusFlowUpdate) {
            $nextStatus = strtolower(trim((string)($data['status'] ?? '')));

            if ($role === ROLE_SUPER_ADMIN) {
                if ($nextStatus !== 'assigned') {
                    Response::error('Super Admin can only set status to Assigned', [], Response::HTTP_FORBIDDEN);
                }

                if (empty($data['assigned_to'])) {
                    Response::error('Assigned user is required when setting status to Assigned', [], Response::HTTP_BAD_REQUEST);
                }
            } elseif ($role === 'maintenance_admin') {
                if (!in_array($nextStatus, ['assigned', 'in_progress', 'completed', 'closed'], true)) {
                    Response::error('Maintenance Admin can only set status to Assigned, In Progress, Completed, or Closed', [], Response::HTTP_FORBIDDEN);
                }

                if ($nextStatus === 'assigned' && empty($data['assigned_to'])) {
                    Response::error('Assigned user is required when setting status to Assigned', [], Response::HTTP_BAD_REQUEST);
                }
            } elseif ($role === ROLE_MAINTENANCE_STAFF) {
                if (!in_array($nextStatus, ['in_progress', 'completed', 'closed'], true)) {
                    Response::error('Maintenance Staff can only set status to In Progress, Completed, or Closed', [], Response::HTTP_FORBIDDEN);
                }
            } else {
                Response::error('You are not allowed to update report status', [], Response::HTTP_FORBIDDEN);
            }
        } else {
            if ($role !== ROLE_MAINTENANCE_STAFF) {
                Response::error('Only maintenance staff can edit report details', [], Response::HTTP_FORBIDDEN);
            }

            $requestedStatus = strtolower(trim((string)($data['status'] ?? '')));
            if ($requestedStatus === 'assigned') {
                Response::error('Only Super Admin or Admin Maintenance can set status to Assigned', [], Response::HTTP_FORBIDDEN);
            }

            if ($currentUserId <= 0 || $reportOwnerId !== $currentUserId) {
                Response::error('You can only edit reports that you created', [], Response::HTTP_FORBIDDEN);
            }
        }
        
        $result = $this->reportService->updateReport($reportId, $data, $currentUserId);
        
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

        if ($role === 'super admin' || $role === 'superadmin') {
            return ROLE_SUPER_ADMIN;
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
