<?php
/**
 * School Facility Maintenance System - Landing Page
 * Routes to login or dashboard based on session
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// If already logged in, redirect to dashboard
if (!empty($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php', true, 302);
    exit;
} else {
    // Redirect to login page
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php', true, 302);
    exit;
}
