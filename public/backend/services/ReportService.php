<?php
/**
 * Report Service
 * Handles maintenance report business logic
 */

class ReportService {
    private $pdo;
    private $reportModel;
    private $activityLog;
    private $notificationModel;
    private $supportsNeedChange;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureCompletionProofColumns();
        $this->reportModel = new MaintenanceReport($pdo);
        $this->activityLog = new ActivityLog($pdo);
        $this->notificationModel = class_exists('Notification') ? new Notification($pdo) : null;
        $this->supportsNeedChange = $this->detectNeedChangeSupport();
    }

    private function ensureCompletionProofColumns() {
        try {
            $proofColumnStmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_reports' AND COLUMN_NAME = 'completion_proof_image'");
            $proofColumnStmt->execute();
            $hasProofColumn = (int)($proofColumnStmt->fetchColumn() ?: 0) > 0;

            if (!$hasProofColumn) {
                $this->pdo->exec("ALTER TABLE maintenance_reports ADD COLUMN completion_proof_image VARCHAR(500) NULL AFTER completed_date");
            }

            $proofUploadedAtStmt = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_reports' AND COLUMN_NAME = 'completion_proof_uploaded_at'");
            $proofUploadedAtStmt->execute();
            $hasProofUploadedAt = (int)($proofUploadedAtStmt->fetchColumn() ?: 0) > 0;

            if (!$hasProofUploadedAt) {
                $this->pdo->exec("ALTER TABLE maintenance_reports ADD COLUMN completion_proof_uploaded_at TIMESTAMP NULL DEFAULT NULL AFTER completion_proof_image");
            }
        } catch (Throwable $e) {
            Logger::error('Failed to ensure completion proof columns', ['error' => $e->getMessage()]);
        }
    }

    private function detectNeedChangeSupport() {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM maintenance_reports LIKE 'need_change_item_id'");
            return (bool)$stmt->fetch();
        } catch (Throwable $e) {
            Logger::error('Failed to detect need-change columns in reports table', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function createReport($data, $userId) {
        error_log('📋 CREATE REPORT - Received data: ' . json_encode($data));
        // Validate input
        $validator = new Validator();
        if (!$validator->validate($data, [
            'title' => 'required|max:255',
            'description' => 'required|max:2000',
            'location' => 'required|max:255',
            'priority' => 'in:' . implode(',', [PRIORITY_LOW, PRIORITY_MEDIUM, PRIORITY_HIGH, PRIORITY_URGENT, PRIORITY_CRITICAL])
        ])) {
            return [
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->getErrors()
            ];
        }

        $needChangeItemId = null;
        if (isset($data['need_change_item_id']) && $data['need_change_item_id'] !== '') {
            $needChangeItemId = (int)$data['need_change_item_id'];
            if ($needChangeItemId <= 0) {
                return [
                    'success' => false,
                    'message' => 'Selected Need Change item is invalid'
                ];
            }

            $itemStmt = $this->pdo->prepare("SELECT id, name, quantity FROM items WHERE id = ? LIMIT 1");
            $itemStmt->execute([$needChangeItemId]);
            $needChangeItem = $itemStmt->fetch(PDO::FETCH_ASSOC);

            if (!$needChangeItem) {
                return [
                    'success' => false,
                    'message' => 'Selected Need Change item was not found in inventory'
                ];
            }

            $data['need_change_item_id'] = $needChangeItemId;
            $data['need_change_quantity'] = 1;
            $data['need_change_status'] = 'pending';
            $data['need_change_approved_by'] = null;
            $data['need_change_approved_at'] = null;
            $data['need_change_deducted_at'] = null;
        } else {
            $data['need_change_item_id'] = null;
            $data['need_change_quantity'] = 1;
            $data['need_change_status'] = null;
            $data['need_change_approved_by'] = null;
            $data['need_change_approved_at'] = null;
            $data['need_change_deducted_at'] = null;
        }
        
        $data['created_by'] = $userId;

        // Auto-assign department from creator's profile
        if (empty($data['department_id'])) {
            $deptStmt = $this->pdo->prepare("SELECT department_id FROM users WHERE user_id = ? LIMIT 1");
            $deptStmt->execute([$userId]);
            $deptId = $deptStmt->fetchColumn();
            if ($deptId) {
                $data['department_id'] = (int)$deptId;
            }
        }
        
        try {
            $reportId = $this->reportModel->create($data);
            $createdReport = $this->reportModel->findById($reportId);
            error_log('✅ REPORT CREATED - ID: ' . $reportId . ', need_change_item_id: ' . (isset($createdReport['need_change_item_id']) ? $createdReport['need_change_item_id'] : 'NULL'));
            $this->activityLog->log($userId, 'CREATE_REPORT', 'report', $reportId);
            $this->notifyAdminsForNewReport($reportId, $data, $createdReport, $userId);
            
            return [
                'success' => true,
                'message' => 'Report created successfully',
                'report_id' => $reportId
            ];
        } catch (Exception $e) {
            Logger::error('Failed to create report', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to create report'
            ];
        }
    }
    
    public function updateReport($reportId, $data, $userId) {
        // Check if report exists
        $report = $this->reportModel->findById($reportId);
        if (!$report) {
            return [
                'success' => false,
                'message' => 'Report not found'
            ];
        }
        
        // Validate update
        $allowedUpdates = ['title', 'description', 'location', 'priority', 'status', 'assigned_to', 'due_date'];
        $updateData = array_filter($data, function($key) use ($allowedUpdates) {
            return in_array($key, $allowedUpdates);
        }, ARRAY_FILTER_USE_KEY);

        if (array_key_exists('completion_proof_image', $data)) {
            $updateData['completion_proof_image'] = $data['completion_proof_image'];
            $updateData['completion_proof_uploaded_at'] = date('Y-m-d H:i:s');
        }

        if ($this->supportsNeedChange && array_key_exists('need_change_item_id', $data)) {
            $rawNeedChangeItemId = $data['need_change_item_id'];
            $normalizedNeedChangeItemId = $rawNeedChangeItemId;

            if ($rawNeedChangeItemId === '' || $rawNeedChangeItemId === null) {
                $normalizedNeedChangeItemId = null;
            } else {
                $normalizedNeedChangeItemId = (int)$rawNeedChangeItemId;
                if ($normalizedNeedChangeItemId <= 0) {
                    return [
                        'success' => false,
                        'message' => 'Selected Need Change item is invalid'
                    ];
                }

                $itemStmt = $this->pdo->prepare("SELECT id FROM items WHERE id = ? LIMIT 1");
                $itemStmt->execute([$normalizedNeedChangeItemId]);
                if (!$itemStmt->fetch(PDO::FETCH_ASSOC)) {
                    return [
                        'success' => false,
                        'message' => 'Selected Need Change item was not found in inventory'
                    ];
                }
            }

            $updateData['need_change_item_id'] = $normalizedNeedChangeItemId;
            $updateData['need_change_quantity'] = 1;
            if ($normalizedNeedChangeItemId === null) {
                $updateData['need_change_status'] = null;
            } else {
                $updateData['need_change_status'] = 'pending';
            }
            $updateData['need_change_approved_by'] = null;
            $updateData['need_change_approved_at'] = null;
            $updateData['need_change_deducted_at'] = null;
        }
        
        $previousAssigneeId = (int)($report['assigned_to'] ?? 0);
        $nextStatus = strtolower(trim((string)($updateData['status'] ?? '')));
        $role = strtolower(trim((string)($_SESSION['user']['role'] ?? $_SESSION['role'] ?? ROLE_MAINTENANCE_STAFF)));
        if ($role === 'admin_maintenance') {
            $role = 'maintenance_admin';
        }
        if ($role === 'super admin' || $role === 'superadmin') {
            $role = ROLE_SUPER_ADMIN;
        }
        $isNeedChangeApproval = !empty($data['approve_need_change']);
        $isNeedChangeRejection = !empty($data['reject_need_change']);
        $isApprovalTransition = $role === ROLE_SUPER_ADMIN && $nextStatus === 'assigned';
        $shouldProcessNeedChange = $isNeedChangeApproval || $isNeedChangeRejection || $isApprovalTransition;

        if (empty($updateData) && !$shouldProcessNeedChange) {
            return [
                'success' => false,
                'message' => 'No valid fields to update'
            ];
        }

        if ($isNeedChangeApproval && $role !== ROLE_SUPER_ADMIN) {
            return [
                'success' => false,
                'message' => 'Only Super Admin can approve Need Change requests'
            ];
        }

        try {
            if ($shouldProcessNeedChange) {
                $this->pdo->beginTransaction();

                $lockedReport = $this->lockReportForUpdate($reportId);
                if (!$lockedReport) {
                    throw new Exception('Report not found');
                }

                if (($isNeedChangeApproval || $isNeedChangeRejection) && empty($lockedReport['need_change_item_id'])) {
                    throw new Exception('This report has no Need Change request to process');
                }

                if ($isNeedChangeRejection) {
                    // Handle rejection
                    $updateData['need_change_status'] = 'rejected';
                    $updateData['need_change_approved_by'] = $userId;
                    $updateData['need_change_approved_at'] = date('Y-m-d H:i:s');
                    // Note: need_change_deducted_at remains null for rejected requests
                } elseif ($isNeedChangeApproval && !empty($lockedReport['need_change_deducted_at'])) {
                    $this->pdo->commit();
                    return [
                        'success' => true,
                        'message' => 'Need Change is already approved'
                    ];
                } elseif ($isNeedChangeApproval && !empty($lockedReport['need_change_item_id']) && empty($lockedReport['need_change_deducted_at'])) {
                    // Handle approval
                    $deductionResult = $this->deductNeedChangeInventory($lockedReport, $userId);
                    $updateData['need_change_status'] = $deductionResult['status'];
                    $updateData['need_change_approved_by'] = $userId;
                    $updateData['need_change_approved_at'] = $deductionResult['approved_at'];
                    $updateData['need_change_deducted_at'] = $deductionResult['deducted_at'];
                }

                if (!empty($updateData)) {
                    $this->reportModel->update($reportId, $updateData);
                }
                $this->pdo->commit();
            } else {
                if (($updateData['status'] ?? '') !== 'completed' && array_key_exists('completion_proof_image', $updateData) && empty($updateData['completion_proof_image'])) {
                    $updateData['completion_proof_uploaded_at'] = null;
                }
                $this->reportModel->update($reportId, $updateData);
            }

            $newAssigneeId = isset($updateData['assigned_to']) ? (int)$updateData['assigned_to'] : 0;
            if ($newAssigneeId > 0 && $newAssigneeId !== $previousAssigneeId) {
                $this->notifyAssigneeForAssignment($reportId, $newAssigneeId, $userId, $report['title'] ?? null);
            }

            if ($isNeedChangeApproval) {
                $this->activityLog->log($userId, 'APPROVE_NEED_CHANGE', 'report', $reportId, [
                    'need_change_item_id' => (int)($report['need_change_item_id'] ?? 0),
                    'need_change_status' => $updateData['need_change_status'] ?? null
                ]);
            } elseif ($isNeedChangeRejection) {
                $this->activityLog->log($userId, 'REJECT_NEED_CHANGE', 'report', $reportId, [
                    'need_change_item_id' => (int)($report['need_change_item_id'] ?? 0),
                    'need_change_status' => 'rejected'
                ]);
            } else {
                $this->activityLog->log($userId, 'UPDATE_REPORT', 'report', $reportId, $updateData);
            }
            
            Logger::info('Report updated', ['report_id' => $reportId, 'user_id' => $userId]);
            
            return [
                'success' => true,
                'message' => $isNeedChangeApproval
                    ? 'Need Change approved and inventory deducted successfully'
                    : ($isNeedChangeRejection ? 'Need Change rejected successfully' : 'Report updated successfully')
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            Logger::error('Failed to update report', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function lockReportForUpdate($reportId) {
        $stmt = $this->pdo->prepare("SELECT * FROM maintenance_reports WHERE report_id = ? FOR UPDATE");
        $stmt->execute([$reportId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function deductNeedChangeInventory(array $report, $userId) {
        $itemId = (int)($report['need_change_item_id'] ?? 0);
        $quantity = max(1, (int)($report['need_change_quantity'] ?? 1));

        if ($itemId <= 0) {
            return [
                'status' => null,
                'approved_at' => null,
                'deducted_at' => null
            ];
        }

        $itemStmt = $this->pdo->prepare(
            "SELECT i.id, i.name, i.quantity, i.status, i.low_stock_threshold_override,
                    c.default_low_stock_threshold
             FROM items i
             LEFT JOIN inventory_categories c ON i.category_id = c.id
             WHERE i.id = ? FOR UPDATE"
        );
        $itemStmt->execute([$itemId]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new Exception('Need Change item was not found in inventory');
        }

        $available = (int)$item['quantity'];
        if ($available < $quantity) {
            throw new Exception('Insufficient stock for approval. Available: ' . $available . ', required: ' . $quantity);
        }

        $newQuantity = $available - $quantity;
        $itemThreshold = array_key_exists('low_stock_threshold_override', $item) && $item['low_stock_threshold_override'] !== null
            ? (int)$item['low_stock_threshold_override']
            : null;
        $categoryThreshold = array_key_exists('default_low_stock_threshold', $item) && $item['default_low_stock_threshold'] !== null
            ? (int)$item['default_low_stock_threshold']
            : null;

        $effectiveThreshold = $itemThreshold !== null ? $itemThreshold : $categoryThreshold;

        $newStatus = 'available';
        if ($newQuantity <= 0) {
            $newStatus = 'out_of_stock';
        } elseif ($effectiveThreshold !== null && $newQuantity <= $effectiveThreshold) {
            $newStatus = 'low_stock';
        }

        $updateItemStmt = $this->pdo->prepare("UPDATE items SET quantity = ?, status = ?, updated_at = NOW() WHERE id = ?");
        $updateItemStmt->execute([$newQuantity, $newStatus, $itemId]);

        $note = 'Auto-deducted after Super Admin approval for report #' . (int)$report['report_id'] . ' - ' . $quantity . ' item(s)';
        $transactionStmt = $this->pdo->prepare(
            "INSERT INTO inventory_transactions (item_id, report_id, room_id, transaction_type, quantity, reference_note, performed_by, created_at)
                     VALUES (?, ?, NULL, 'adjustment', ?, ?, ?, NOW())"
        );
        $transactionStmt->execute([
            $itemId,
            (int)$report['report_id'],
            $quantity,
            $note,
            (int)$userId
        ]);

        return [
            'status' => 'deducted',
            'approved_at' => date('Y-m-d H:i:s'),
            'deducted_at' => date('Y-m-d H:i:s')
        ];
    }
    
    public function getReport($reportId) {
        $report = $this->reportModel->findById($reportId);
        
        if (!$report) {
            return [
                'success' => false,
                'message' => 'Report not found'
            ];
        }
        
        return [
            'success' => true,
            'data' => $this->formatReportDates($report)
        ];
    }
    
    public function assignReport($reportId, $assigneeId, $userId) {
        $report = $this->reportModel->findById($reportId);
        if (!$report) {
            return [
                'success' => false,
                'message' => 'Report not found'
            ];
        }

        $previousAssigneeId = (int)($report['assigned_to'] ?? 0);
        $assigneeId = (int)$assigneeId;
        if ($assigneeId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid assignee'
            ];
        }
        
        try {
            $this->reportModel->update($reportId, [
                'assigned_to' => $assigneeId,
                'status' => 'assigned'
            ]);

            if ($assigneeId !== $previousAssigneeId) {
                $this->notifyAssigneeForAssignment($reportId, $assigneeId, $userId, $report['title'] ?? null);
            }
            
            $this->activityLog->log($userId, 'ASSIGN_REPORT', 'report', $reportId, 
                ['assigned_to' => $assigneeId]);
            
            Logger::info('Report assigned', ['report_id' => $reportId, 'assignee_id' => $assigneeId]);
            
            return [
                'success' => true,
                'message' => 'Report assigned successfully'
            ];
        } catch (Exception $e) {
            Logger::error('Failed to assign report', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to assign report'
            ];
        }
    }

    private function notifyAssigneeForAssignment($reportId, $assigneeId, $actorUserId, $reportTitle = null) {
        if (!$this->notificationModel || $assigneeId <= 0) {
            return;
        }

        try {
            $actorName = null;
            $actorStmt = $this->pdo->prepare("SELECT full_name FROM users WHERE user_id = ? LIMIT 1");
            $actorStmt->execute([(int)$actorUserId]);
            $actorName = $actorStmt->fetchColumn() ?: null;

            $title = 'Report Assigned to You';
            $displayTitle = trim((string)($reportTitle ?? 'Untitled'));
            $message = ($actorName ? $actorName . ' assigned' : 'A report was assigned')
                . ' report #' . (int)$reportId
                . ' (' . $displayTitle . ') to you.';

            $this->notificationModel->create([
                'user_id' => (int)$assigneeId,
                'report_id' => (int)$reportId,
                'title' => $title,
                'message' => $message
            ]);
        } catch (Throwable $e) {
            Logger::error('Failed to create assignment notification', [
                'report_id' => $reportId,
                'assignee_id' => $assigneeId,
                'actor_id' => $actorUserId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getAllReports($limit = 100, $offset = 0) {
        try {
            $reports = $this->reportModel->getAll($limit, $offset);
            
            // Format dates for all reports
            $formattedReports = array_map([$this, 'formatReportDates'], $reports);
            
            return [
                'success' => true,
                'data' => [
                    'reports' => $formattedReports,
                    'total' => count($formattedReports)
                ]
            ];
        } catch (Exception $e) {
            Logger::error('Failed to fetch reports', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to fetch reports'
            ];
        }
    }

    public function getRecentReports($userId, $role, $limit = 10) {
        try {
            if ($this->supportsNeedChange) {
                $sql = "SELECT r.report_id, r.title, r.description, r.priority, r.status, r.location,
                               r.created_at, r.created_by, r.assigned_to, u.full_name as assigned_name,
                               c.full_name as creator_name, r.need_change_item_id,
                               r.need_change_status, i.name as need_change_item_name,
                               r.department_id, d.name as department_name
                        FROM maintenance_reports r
                        LEFT JOIN users u ON r.assigned_to = u.user_id
                        LEFT JOIN users c ON r.created_by = c.user_id
                        LEFT JOIN items i ON r.need_change_item_id = i.id
                        LEFT JOIN departments d ON r.department_id = d.department_id";
            } else {
                $sql = "SELECT r.report_id, r.title, r.description, r.priority, r.status, r.location,
                               r.created_at, r.created_by, r.assigned_to, u.full_name as assigned_name,
                               c.full_name as creator_name, NULL as need_change_item_id,
                               NULL as need_change_status, NULL as need_change_item_name
                        FROM maintenance_reports r
                        LEFT JOIN users u ON r.assigned_to = u.user_id
                        LEFT JOIN users c ON r.created_by = c.user_id";
            }

            $params = [];
            $explicitDept = isset($filters['department_id']) ? trim((string)$filters['department_id']) : null;

            if ($role === ROLE_SUPER_ADMIN || $role === ROLE_MAINTENANCE_STAFF) {
                $sql .= " WHERE DATE(r.created_at) = CURDATE() ORDER BY r.created_at DESC LIMIT ?";
                $params[] = (int)$limit;
            } elseif ($role === 'maintenance_admin') {
                $deptStmt = $this->pdo->prepare("SELECT department_id FROM users WHERE user_id = ? LIMIT 1");
                $deptStmt->execute([$userId]);
                $deptId = $deptStmt->fetchColumn();

                if ($deptId) {
                    $sql .= " WHERE r.department_id = ? AND DATE(r.created_at) = CURDATE() ORDER BY r.created_at DESC LIMIT ?";
                    $params[] = $deptId;
                    $params[] = (int)$limit;
                } else {
                    $sql .= " WHERE DATE(r.created_at) = CURDATE() ORDER BY r.created_at DESC LIMIT ?";
                    $params[] = (int)$limit;
                }
            } else {
                $sql .= " WHERE (r.created_by = ? OR r.assigned_to = ?) AND DATE(r.created_at) = CURDATE() ORDER BY r.created_at DESC LIMIT ?";
                $params[] = $userId;
                $params[] = $userId;
                $params[] = (int)$limit;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $formattedReports = array_map([$this, 'formatReportDates'], $reports);

            return [
                'success' => true,
                'data' => [
                    'reports' => $formattedReports
                ]
            ];
        } catch (Exception $e) {
            Logger::error('Failed to fetch recent reports', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to fetch recent reports'
            ];
        }
    }

    public function getReportsForLegacyDashboard($userId, $role) {
        try {
            $query = "SELECT
                        r.*,
                        creator.full_name as creator_name,
                        creator.email as creator_email,
                        assigned.full_name as assigned_name,
                        d.name as department_name
                    FROM maintenance_reports r
                    LEFT JOIN users creator ON r.created_by = creator.user_id
                    LEFT JOIN users assigned ON r.assigned_to = assigned.user_id
                    LEFT JOIN departments d ON r.department_id = d.department_id";

            $params = [];

            if ($role === 'user' || $role === 'reporter') {
                $query .= " WHERE r.created_by = ? ORDER BY r.created_at DESC";
                $params[] = $userId;
            } elseif ($role === ROLE_MAINTENANCE_STAFF) {
                $query .= " ORDER BY r.created_at DESC";
            } elseif ($role === ROLE_DEPARTMENT_ADMIN) {
                $deptStmt = $this->pdo->prepare("SELECT department_id FROM users WHERE user_id = ?");
                $deptStmt->execute([$userId]);
                $departmentId = $deptStmt->fetchColumn();

                if (!$departmentId) {
                    return [
                        'success' => true,
                        'data' => [
                            'reports' => [],
                            'count' => 0
                        ]
                    ];
                }

                $query .= " WHERE r.department_id = ? ORDER BY r.created_at DESC";
                $params[] = $departmentId;
            } else {
                $query .= " ORDER BY r.created_at DESC";
            }

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return [
                'success' => true,
                'data' => [
                    'reports' => $reports,
                    'count' => count($reports)
                ]
            ];
        } catch (Exception $e) {
            Logger::error('Failed to fetch legacy reports list', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to retrieve reports'
            ];
        }
    }

    public function getReportsWithFilters($userId, $role, $filters = []) {
        try {
            $status = $filters['status'] ?? null;
            $priority = $filters['priority'] ?? null;
            $search = $filters['search'] ?? null;
            $statusGroup = strtolower(trim((string)($filters['status_group'] ?? '')));
            $dateFrom = $this->normalizeFilterDate($filters['date_from'] ?? null);
            $dateTo = $this->normalizeFilterDate($filters['date_to'] ?? null);
            $explicitDept = isset($filters['department_id']) ? trim((string)$filters['department_id']) : null;
            $page = max(1, (int)($filters['page'] ?? 1));
            $perPage = max(1, min(200, (int)($filters['per_page'] ?? 20)));
            $offset = ($page - 1) * $perPage;

                 if ($this->supportsNeedChange) {
                  $sql = "SELECT r.report_id, r.title, r.priority, r.status, r.location,
                           r.created_at, r.created_by, r.assigned_to, u.full_name as assigned_name,
                           c.full_name as creator_name, r.need_change_item_id,
                           r.need_change_status, i.name as need_change_item_name,
                               r.department_id, d.name as department_name
                       FROM maintenance_reports r
                       LEFT JOIN users u ON r.assigned_to = u.user_id
                       LEFT JOIN users c ON r.created_by = c.user_id
                       LEFT JOIN items i ON r.need_change_item_id = i.id
                        LEFT JOIN departments d ON r.department_id = d.department_id";
                 } else {
                  $sql = "SELECT r.report_id, r.title, r.priority, r.status, r.location,
                           r.created_at, r.created_by, r.assigned_to, u.full_name as assigned_name,
                           c.full_name as creator_name, NULL as need_change_item_id,
                           NULL as need_change_status, NULL as need_change_item_name
                       FROM maintenance_reports r
                       LEFT JOIN users u ON r.assigned_to = u.user_id
                       LEFT JOIN users c ON r.created_by = c.user_id";
                 }

            $params = [];

            if ($statusGroup === 'assigned_to_me') {
                $sql .= " WHERE r.assigned_to = ?";
                $params[] = $userId;
            } elseif ($statusGroup === 'overdue') {
                $sql .= " WHERE r.assigned_to = ? AND r.due_date < CURRENT_DATE AND r.status NOT IN ('completed', 'closed')";
                $params[] = $userId;
            } elseif ($statusGroup === 'due_soon') {
                $sql .= " WHERE r.assigned_to = ? AND r.due_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY) AND r.status NOT IN ('completed', 'closed')";
                $params[] = $userId;
            } elseif ($statusGroup === 'recent_assignments') {
                $sql .= " WHERE r.assigned_to = ? AND r.status = 'assigned' AND DATE(r.updated_at) >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)";
                $params[] = $userId;
            } elseif ($role === ROLE_MAINTENANCE_STAFF) {
                if ($explicitDept !== null && $explicitDept !== '' && $explicitDept !== 'all') {
                    $sql .= " WHERE r.department_id = ?";
                    $params[] = (int)$explicitDept;
                } else {
                    $sql .= " WHERE 1=1";
                }
            } elseif ($role === 'maintenance_admin') {
                // If a specific department_id filter is passed (including 'all' = empty), respect it
                // Only auto-filter by own department when no explicit department filter is provided
                if ($explicitDept !== null && $explicitDept !== '' && $explicitDept !== 'all') {
                    // Specific department selected
                    $sql .= " WHERE r.department_id = ?";
                    $params[] = (int)$explicitDept;
                } else {
                    // 'All Departments' selected or no filter — show all
                    $sql .= " WHERE 1=1";
                }
            } elseif ($role !== ROLE_SUPER_ADMIN) {
                $sql .= " WHERE (r.created_by = ? OR r.assigned_to = ?)";
                $params[] = $userId;
                $params[] = $userId;
            } else {
                if ($explicitDept !== null && $explicitDept !== '' && $explicitDept !== 'all') {
                    $sql .= " WHERE r.department_id = ?";
                    $params[] = (int)$explicitDept;
                } else {
                    $sql .= " WHERE 1=1";
                }
            }

            if ($status !== null && $status !== '') {
                $sql .= " AND r.status = ?";
                $params[] = $status;
            }

            if ($priority !== null && $priority !== '') {
                $sql .= " AND r.priority = ?";
                $params[] = $priority;
            }

            if ($search !== null && $search !== '') {
                $searchTerm = '%' . $search . '%';
                $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR r.location LIKE ?)";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if ($dateFrom !== null && $dateFrom !== '') {
                $sql .= " AND DATE(r.created_at) >= ?";
                $params[] = $dateFrom;
            }

            if ($dateTo !== null && $dateTo !== '') {
                $sql .= " AND DATE(r.created_at) <= ?";
                $params[] = $dateTo;
            }

            $sql .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $formattedReports = array_map([$this, 'formatReportDates'], $reports);

            return [
                'success' => true,
                'data' => [
                    'reports' => $formattedReports,
                    'page' => $page,
                    'per_page' => $perPage
                ]
            ];
        } catch (Exception $e) {
            Logger::error('Failed to fetch reports with filters', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to list reports'
            ];
        }
    }

    public function getStats() {
        try {
            $stats = [
                'total' => 0,
                'submitted' => 0,
                'assigned' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'closed' => 0,
                'cancelled' => 0,
                'by_priority' => [
                    'low' => 0,
                    'medium' => 0,
                    'high' => 0,
                    'urgent' => 0,
                    'critical' => 0
                ]
            ];

            $statusStmt = $this->pdo->query("SELECT status, COUNT(*) as count FROM maintenance_reports GROUP BY status");
            while ($row = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
                $status = $row['status'];
                $count = (int)$row['count'];
                if (array_key_exists($status, $stats)) {
                    $stats[$status] = $count;
                }
                $stats['total'] += $count;
            }

            $priorityStmt = $this->pdo->query("SELECT priority, COUNT(*) as count FROM maintenance_reports GROUP BY priority");
            while ($row = $priorityStmt->fetch(PDO::FETCH_ASSOC)) {
                $priority = $row['priority'];
                if (array_key_exists($priority, $stats['by_priority'])) {
                    $stats['by_priority'][$priority] = (int)$row['count'];
                }
            }

            return [
                'success' => true,
                'data' => [
                    'stats' => $stats
                ]
            ];
        } catch (Exception $e) {
            Logger::error('Failed to get report stats', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to retrieve statistics'
            ];
        }
    }

    public function deleteReport($reportId, $userId, $role, $allowOwnerDelete = false) {
        $report = $this->reportModel->findById($reportId);
        if (!$report) {
            return [
                'success' => false,
                'message' => 'Report not found',
                'code' => Response::HTTP_NOT_FOUND
            ];
        }

        $isSuperAdmin = ($role === ROLE_SUPER_ADMIN);
        $isOwner = ((int)($report['created_by'] ?? 0) === (int)$userId);

        if (!$isSuperAdmin && !($allowOwnerDelete && $isOwner)) {
            return [
                'success' => false,
                'message' => 'Unauthorized',
                'code' => Response::HTTP_FORBIDDEN
            ];
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM maintenance_reports WHERE report_id = ?");
            $stmt->execute([$reportId]);
            $this->activityLog->log($userId, 'DELETE_REPORT', 'report', $reportId, "Deleted report #$reportId");

            return [
                'success' => true,
                'message' => 'Report deleted successfully'
            ];
        } catch (Exception $e) {
            Logger::error('Failed to delete report', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Failed to delete report',
                'code' => Response::HTTP_INTERNAL_ERROR
            ];
        }
    }

    private function notifyAdminsForNewReport($reportId, $inputData, $createdReport, $userId) {
        try {
            $adminRecipients = $this->fetchAdminNotificationRecipients();
            if (empty($adminRecipients)) {
                return;
            }

            $submitterName = $_SESSION['full_name']
                ?? $_SESSION['user']['full_name']
                ?? ($createdReport['creator_name'] ?? 'A staff member');

            if ($this->notificationModel) {
                foreach ($adminRecipients as $admin) {
                    try {
                        $this->notificationModel->create([
                            'user_id' => (int)$admin['user_id'],
                            'report_id' => $reportId,
                            'title' => 'New Maintenance Report Submitted',
                            'message' => $submitterName . ' submitted a new report: ' . ($inputData['title'] ?? 'Untitled') . ' (Report #' . $reportId . ')'
                        ]);
                    } catch (Throwable $notificationError) {
                        // Keep sending email even if in-app notification insert fails for one admin.
                        Logger::error('Failed to create new-report in-app notification', [
                            'report_id' => $reportId,
                            'target_user_id' => (int)($admin['user_id'] ?? 0),
                            'error' => $notificationError->getMessage()
                        ]);
                    }
                }
            }

            if (!class_exists('EmailService')) {
                $emailServiceFile = ROOT_DIR . '/backend/services/EmailService.php';
                if (file_exists($emailServiceFile)) {
                    require_once $emailServiceFile;
                }
            }

            if (class_exists('EmailService')) {
                $payload = array_merge($createdReport ?: [], [
                    'report_id' => $reportId,
                    'title' => $inputData['title'] ?? ($createdReport['title'] ?? 'Untitled'),
                    'description' => $inputData['description'] ?? ($createdReport['description'] ?? ''),
                    'location' => $inputData['location'] ?? ($createdReport['location'] ?? ''),
                    'priority' => $inputData['priority'] ?? ($createdReport['priority'] ?? PRIORITY_MEDIUM),
                    'submitted_by' => $submitterName
                ]);

                $superAdminEmailRecipients = $this->fetchSuperAdminEmailRecipients();
                if (!empty($superAdminEmailRecipients)) {
                    // Send synchronously on report creation so super admins reliably receive email,
                    // especially on local Windows/XAMPP where detached background tasks may not run.
                    $sent = EmailService::sendNewReportNotification($payload, $superAdminEmailRecipients);
                    if ($sent) {
                        Logger::info('New report email notification sent to Administrator', [
                            'report_id' => $reportId,
                            'recipient_count' => count($superAdminEmailRecipients)
                        ]);
                    } else {
                        Logger::warning('New report email notification was not sent', [
                            'report_id' => $reportId,
                            'recipient_count' => count($superAdminEmailRecipients)
                        ]);
                    }
                } else {
                    Logger::warning('No Administrator email recipients found for new report notification', [
                        'report_id' => $reportId
                    ]);
                }
            } else {
                Logger::warning('EmailService class unavailable during new report notification', [
                    'report_id' => $reportId
                ]);
            }
        } catch (Throwable $e) {
            Logger::error('Report notification error (non-fatal)', [
                'report_id' => $reportId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function dispatchNewReportEmailAsync(array $payload, array $recipients) {
        if (empty($recipients)) {
            return;
        }

        try {
            $taskScript = ROOT_DIR . '/backend/tasks/send_new_report_email.php';
            if (!file_exists($taskScript)) {
                EmailService::sendNewReportNotification($payload, $recipients);
                return;
            }

            $encoded = base64_encode(json_encode([
                'payload' => $payload,
                'recipients' => $recipients
            ]));

            $phpExecutable = $this->resolvePhpExecutablePath();
            $phpBinary = escapeshellarg($phpExecutable);
            $scriptArg = escapeshellarg($taskScript);
            $dataArg = escapeshellarg($encoded);

            if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
                $cmd = "cmd /C start /B \"\" {$phpBinary} {$scriptArg} {$dataArg} >NUL 2>&1";
            } else {
                $cmd = "{$phpBinary} {$scriptArg} {$dataArg} > /dev/null 2>&1 &";
            }

            $launched = false;
            if (function_exists('popen') && function_exists('pclose')) {
                $proc = @popen($cmd, 'r');
                if (is_resource($proc)) {
                    @pclose($proc);
                    $launched = true;
                }
            }

            if (!$launched && function_exists('exec')) {
                @exec($cmd);
                $launched = true;
            }

            if (!$launched) {
                Logger::warning('Async email dispatch unavailable, using synchronous fallback');
                EmailService::sendNewReportNotification($payload, $recipients);
                return;
            }

            Logger::info('Queued async new report email notification', [
                'recipient_count' => count($recipients)
            ]);
        } catch (Throwable $e) {
            Logger::error('Async email dispatch failed, falling back to sync send', [
                'error' => $e->getMessage()
            ]);

            EmailService::sendNewReportNotification($payload, $recipients);
        }
    }

    private function resolvePhpExecutablePath() {
        $binary = PHP_BINARY;

        if (!empty($binary) && stripos(basename($binary), 'php') !== false && file_exists($binary)) {
            return $binary;
        }

        return 'php';
    }

    private function fetchAdminNotificationRecipients() {
        $recipientsById = [];

        $primaryStmt = $this->pdo->prepare(
            "SELECT user_id, email, full_name
             FROM users
                         WHERE role IN ('super_admin', 'admin', 'maintenance_admin', 'admin_maintenance', 'department_admin')
               AND status = 'active'"
        );
        $primaryStmt->execute();

        foreach ($primaryStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $uid = (int)($row['user_id'] ?? 0);
            if ($uid > 0) {
                $recipientsById[$uid] = $row;
            }
        }

        $fallbackStmt = $this->pdo->prepare(
            "SELECT user_id, email, full_name
             FROM users
               WHERE role IN ('super_admin', 'admin', 'admin_maintenance')"
        );
        $fallbackStmt->execute();

        foreach ($fallbackStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $uid = (int)($row['user_id'] ?? 0);
            if ($uid > 0) {
                $recipientsById[$uid] = $row;
            }
        }

        return array_values($recipientsById);
    }

    private function fetchSuperAdminEmailRecipients() {
        $stmt = $this->pdo->prepare(
            "SELECT user_id, email, full_name
             FROM users
             WHERE (LOWER(TRIM(role)) = 'super_admin' OR LOWER(TRIM(role)) = 'super admin')
               AND (status IS NULL OR LOWER(TRIM(status)) = 'active')
               AND email IS NOT NULL
               AND email <> ''"
        );
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!empty($rows)) {
            $unique = [];
            foreach ($rows as $row) {
                $email = strtolower(trim((string)($row['email'] ?? '')));
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                if (!isset($unique[$email])) {
                    $unique[$email] = [
                        'user_id' => (int)($row['user_id'] ?? 0),
                        'email' => $email,
                        'full_name' => $row['full_name'] ?? 'Super Admin'
                    ];
                }
            }

            return array_values($unique);
        }

        $fallbackStmt = $this->pdo->prepare(
            "SELECT user_id, email, full_name
             FROM users
             WHERE LOWER(TRIM(role)) IN ('super_admin', 'super admin')"
        );
        $fallbackStmt->execute();
        $fallbackRows = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $uniqueFallback = [];
        foreach ($fallbackRows as $row) {
            $email = strtolower(trim((string)($row['email'] ?? '')));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if (!isset($uniqueFallback[$email])) {
                $uniqueFallback[$email] = [
                    'user_id' => (int)($row['user_id'] ?? 0),
                    'email' => $email,
                    'full_name' => $row['full_name'] ?? 'Super Admin'
                ];
            }
        }

        return array_values($uniqueFallback);
    }

    private function normalizeFilterDate($value) {
        if (!$value) {
            return null;
        }

        $value = trim((string)$value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }
    
    /**
     * Format report dates to readable format
     * Converts database DATETIME to "Feb 10, 2025" format
     */
    private function formatReportDates($report) {
        if (!is_array($report)) {
            $report = (array) $report;
        }
        
        // Format created_at
        if (!empty($report['created_at'])) {
            try {
                $date = new DateTime($report['created_at']);
                $report['created_at_formatted'] = $date->format('M d, Y');
                $report['created_at_full'] = $date->format('M d, Y h:i A');
            } catch (Exception $e) {
                $report['created_at_formatted'] = $report['created_at'];
                $report['created_at_full'] = $report['created_at'];
            }
        }
        
        // Format due_date if exists
        if (!empty($report['due_date'])) {
            try {
                $date = new DateTime($report['due_date']);
                $report['due_date_formatted'] = $date->format('M d, Y');
            } catch (Exception $e) {
                $report['due_date_formatted'] = $report['due_date'];
            }
        }
        
        // Format completed_date if exists
        if (!empty($report['completed_date'])) {
            try {
                $date = new DateTime($report['completed_date']);
                $report['completed_date_formatted'] = $date->format('M d, Y h:i A');
            } catch (Exception $e) {
                $report['completed_date_formatted'] = $report['completed_date'];
            }
        }
        
        // Format updated_at if exists
        if (!empty($report['updated_at'])) {
            try {
                $date = new DateTime($report['updated_at']);
                $report['updated_at_formatted'] = $date->format('M d, Y h:i A');
            } catch (Exception $e) {
                $report['updated_at_formatted'] = $report['updated_at'];
            }
        }
        
        return $report;
    }
}
