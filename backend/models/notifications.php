<?php
require_once __DIR__ . '/../bootstrap.php';

// Ensure session middleware has initialized the session
if (class_exists('SessionMiddleware')) {
    SessionMiddleware::initialize();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) && !isset($_SESSION['user']['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$userId = (int)($_SESSION['user_id'] ?? $_SESSION['user']['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Allow all authenticated users to access notifications
// if ($user['role'] !== 'super_admin') {
//     http_response_code(403);
//     die(json_encode(['success' => false, 'message' => 'Forbidden']));
// }

require_once __DIR__ . '/../models/Notification.php';
$notification = new Notification($pdo);

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'getUnread':
            $limit = $_GET['limit'] ?? 10;
            $unread = $notification->getUnread($userId, (int)$limit);
            echo json_encode([
                'success' => true,
                'data' => [
                    'notifications' => $unread,
                    'count' => count($unread)
                ]
            ]);
            break;
            
        case 'getAll':
            $limit = $_GET['limit'] ?? 50;
            $offset = $_GET['offset'] ?? 0;
            $all = $notification->getAll($userId, (int)$limit, (int)$offset);
            $count = $notification->countUnread($userId);
            echo json_encode([
                'success' => true,
                'data' => [
                    'notifications' => $all,
                    'unread_count' => $count
                ]
            ]);
            break;
            
        case 'markAsRead':
            $notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
            if ($notificationId <= 0) {
                throw new Exception('Notification ID is required');
            }
            $notification->markAsRead($notificationId);
            echo json_encode(['success' => true, 'message' => 'Marked as read']);
            break;
            
        case 'markAllAsRead':
            $notification->markAllAsRead($userId);
            echo json_encode(['success' => true, 'message' => 'All marked as read']);
            break;
            
        case 'delete':
            $notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
            if ($notificationId <= 0) {
                throw new Exception('Notification ID is required');
            }
            $notification->delete($notificationId);
            echo json_encode(['success' => true, 'message' => 'Notification deleted']);
            break;
            
        case 'count':
            $count = $notification->countUnread($userId);
            echo json_encode([
                'success' => true,
                'data' => ['count' => $count]
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}