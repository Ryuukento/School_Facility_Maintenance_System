<?php
/**
 * Notification Model
 */

class Notification {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function create($data) {
        $query = "INSERT INTO notifications 
                  (user_id, report_id, title, message, is_read, created_at) 
                  VALUES (?, ?, ?, ?, 0, NOW())";
        
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([
            $data['user_id'],
            $data['report_id'] ?? null,
            $data['title'],
            $data['message']
        ]);
    }
    
    public function getUnread($userId, $limit = 10) {
        $query = "SELECT n.*, r.title as report_title, r.priority
                  FROM notifications n
                  LEFT JOIN maintenance_reports r ON n.report_id = r.report_id
                  WHERE n.user_id = ? AND n.is_read = 0
                  ORDER BY n.created_at DESC
                  LIMIT ?";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function getAll($userId, $limit = 50, $offset = 0) {
        $query = "SELECT n.*, r.title as report_title, r.priority
                  FROM notifications n
                  LEFT JOIN maintenance_reports r ON n.report_id = r.report_id
                  WHERE n.user_id = ?
                  ORDER BY n.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function markAsRead($notificationId) {
        $query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$notificationId]);
    }
    
    public function markAllAsRead($userId) {
        $query = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$userId]);
    }
    
    public function countUnread($userId) {
        $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    
    public function delete($notificationId) {
        $query = "DELETE FROM notifications WHERE notification_id = ?";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute([$notificationId]);
    }
}