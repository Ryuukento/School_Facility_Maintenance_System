<?php
/**
 * MaintenanceReport Model
 * Represents a maintenance report
 */

class MaintenanceReport {
    private $pdo;
    private $supportsNeedChange;
    private $supportsCompletionProof;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->supportsNeedChange = $this->detectNeedChangeSupport();
        $this->supportsCompletionProof = $this->detectCompletionProofSupport();
    }

    private function detectNeedChangeSupport() {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM maintenance_reports LIKE 'need_change_item_id'");
            $result = (bool)$stmt->fetch();
            error_log('Need Change Support Detection: ' . ($result ? 'ENABLED' : 'DISABLED'));
            return $result;
        } catch (Throwable $e) {
            error_log('Need Change Support Detection Error: ' . $e->getMessage());
            return false;
        }
    }

    private function detectCompletionProofSupport() {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM maintenance_reports LIKE 'completion_proof_image'");
            return (bool)$stmt->fetch();
        } catch (Throwable $e) {
            error_log('Completion proof support detection error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function create($data) {
        if ($this->supportsNeedChange) {
            $query = "INSERT INTO maintenance_reports 
                      (title, description, location, priority, status, created_by, assigned_to, 
                       department_id, due_date, need_change_item_id, need_change_quantity, need_change_status, 
                       need_change_approved_by, need_change_approved_at, need_change_deducted_at, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['location'],
                $data['priority'] ?? PRIORITY_MEDIUM,
                $data['status'] ?? 'submitted',
                $data['created_by'],
                $data['assigned_to'] ?? null,
                $data['department_id'] ?? null,
                $data['due_date'] ?? null,
                $data['need_change_item_id'] ?? null,
                $data['need_change_quantity'] ?? 1,
                $data['need_change_status'] ?? 'pending',
                $data['need_change_approved_by'] ?? null,
                $data['need_change_approved_at'] ?? null,
                $data['need_change_deducted_at'] ?? null,
            ]);
        } else {
            $query = "INSERT INTO maintenance_reports 
                      (title, description, location, priority, status, created_by, assigned_to, 
                       department_id, due_date, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['location'],
                $data['priority'] ?? PRIORITY_MEDIUM,
                $data['status'] ?? 'submitted',
                $data['created_by'],
                $data['assigned_to'] ?? null,
                $data['department_id'] ?? null,
                $data['due_date'] ?? null,
            ]);
        }
        
        return $this->pdo->lastInsertId();
    }
    
    public function findById($reportId) {
        if ($this->supportsNeedChange) {
            $query = "SELECT r.*, u.full_name as creator_name, u.email as creator_email, 
                      a.full_name as assigned_name, a.email as assigned_email,
                      d.name as department_name,
                      i.name as need_change_item_name,
                      i.quantity as need_change_item_quantity,
                      i.status as need_change_item_status
                      FROM maintenance_reports r
                      LEFT JOIN users u ON r.created_by = u.user_id
                      LEFT JOIN users a ON r.assigned_to = a.user_id
                      LEFT JOIN departments d ON r.department_id = d.department_id
                      LEFT JOIN items i ON r.need_change_item_id = i.id
                      WHERE r.report_id = ?";
        } else {
            $query = "SELECT r.*, u.full_name as creator_name, u.email as creator_email, 
                      a.full_name as assigned_name, a.email as assigned_email,
                      d.name as department_name,
                      NULL as completion_proof_image,
                      NULL as completion_proof_uploaded_at,
                      NULL as need_change_item_id,
                      NULL as need_change_status,
                      NULL as need_change_deducted_at,
                      NULL as need_change_item_name,
                      NULL as need_change_item_quantity,
                      NULL as need_change_item_status
                      FROM maintenance_reports r
                      LEFT JOIN users u ON r.created_by = u.user_id
                      LEFT JOIN users a ON r.assigned_to = a.user_id
                      LEFT JOIN departments d ON r.department_id = d.department_id
                      WHERE r.report_id = ?";
        }
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$reportId]);
        return $stmt->fetch();
    }
    
    public function update($reportId, $data) {
        $updates = [];
        $values = [];
        
        $allowedFields = ['title', 'description', 'location', 'priority', 'status', 
                         'assigned_to', 'due_date', 'completed_date'];

        if ($this->supportsNeedChange) {
            $allowedFields = array_merge($allowedFields, [
                'need_change_item_id', 'need_change_quantity', 'need_change_status',
                'need_change_approved_by', 'need_change_approved_at', 'need_change_deducted_at'
            ]);
        }

        if ($this->supportsCompletionProof) {
            $allowedFields = array_merge($allowedFields, [
                'completion_proof_image', 'completion_proof_uploaded_at'
            ]);
        }
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "{$key} = ?";
                $values[] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $values[] = $reportId;
        $query = "UPDATE maintenance_reports SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE report_id = ?";
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($values);
    }
    
    public function getByCreator($userId, $limit = 50, $offset = 0) {
        $query = "SELECT report_id, title, priority, status, location, created_at, due_date
                  FROM maintenance_reports 
                  WHERE created_by = ? 
                  ORDER BY created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function getByAssignee($userId, $limit = 50, $offset = 0) {
        $query = "SELECT report_id, title, priority, status, location, created_at, due_date
                  FROM maintenance_reports 
                  WHERE assigned_to = ? 
                  ORDER BY priority DESC, created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function getByDepartment($departmentId, $limit = 100, $offset = 0) {
        $query = "SELECT report_id, title, priority, status, location, created_at, due_date
                  FROM maintenance_reports 
                  WHERE department_id = ? 
                  ORDER BY created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$departmentId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function getAll($limit = 100, $offset = 0) {
        $query = "SELECT r.report_id, r.title, r.priority, r.status, r.location, r.created_at, 
                  r.due_date, r.updated_at, r.completed_date, u.full_name as creator_name
                  FROM maintenance_reports r
                  LEFT JOIN users u ON r.created_by = u.user_id
                  ORDER BY r.created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
}
