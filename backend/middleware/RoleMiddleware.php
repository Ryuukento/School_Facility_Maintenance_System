<?php
/**
 * Role Middleware
 * Checks user role-based access control
 */

class RoleMiddleware {
    
    private static $permissions = [
        ROLE_SUPER_ADMIN => [
            'manage_users', 'manage_departments', 'view_all_reports', 
            'manage_system', 'view_analytics'
        ],
        ROLE_DEPARTMENT_ADMIN => [
            'manage_staff', 'view_department_reports', 'assign_reports',
            'view_department_analytics'
        ],
        ROLE_MAINTENANCE_STAFF => [
            'view_assigned_reports', 'update_report_status', 'add_comments'
        ],
        ROLE_USER => [
            'create_report', 'view_own_reports', 'view_dashboard'
        ]
    ];
    
    public static function require($permission) {
        if (!self::isAuthenticated()) {
            Response::error('Unauthorized', [], Response::HTTP_UNAUTHORIZED);
        }
        
        $userRole = $_SESSION['role'] ?? null;
        if (!self::hasPermission($userRole, $permission)) {
            Response::error('Forbidden: Insufficient permissions', [], Response::HTTP_FORBIDDEN);
        }
    }
    
    public static function requireRole($role) {
        if (!self::isAuthenticated()) {
            Response::error('Unauthorized', [], Response::HTTP_UNAUTHORIZED);
        }
        
        $userRole = $_SESSION['role'] ?? null;
        $allowedRoles = is_array($role) ? $role : [$role];
        
        if (!in_array($userRole, $allowedRoles)) {
            Response::error('Forbidden: Access denied', [], Response::HTTP_FORBIDDEN);
        }
    }
    
    private static function isAuthenticated() {
        return SessionMiddleware::isAuthenticated();
    }
    
    private static function hasPermission($role, $permission) {
        $rolePermissions = self::$permissions[$role] ?? [];
        return in_array($permission, $rolePermissions);
    }
}
