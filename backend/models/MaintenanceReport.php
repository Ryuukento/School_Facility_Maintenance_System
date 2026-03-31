<?php
/**
 * MaintenanceReport Model
 * Represents a maintenance report
 */

class MaintenanceReport {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function create($data) {
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
            $data['due_date'] ?? null
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    public function findById($reportId) {
        $query = "SELECT r.*, u.full_name as creator_name, u.email as creator_email, 
                  a.full_name as assigned_name, a.email as assigned_email,
                  d.name as department_name
                  FROM maintenance_reports r
                  LEFT JOIN users u ON r.created_by = u.user_id
                  LEFT JOIN users a ON r.assigned_to = a.user_id
                  LEFT JOIN departments d ON r.department_id = d.department_id
                  WHERE r.report_id = ?";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$reportId]);
        return $stmt->fetch();
    }
    
    public function update($reportId, $data) {
        $updates = [];
        $values = [];
        
        $allowedFields = ['title', 'description', 'location', 'priority', 'status', 
                         'assigned_to', 'due_date', 'completed_date'];
        
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
