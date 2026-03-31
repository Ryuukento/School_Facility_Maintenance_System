<?php
/**
 * Bootstrap file
 * Initializes all components and sets up autoloading
 */

// Define root directory
define('ROOT_DIR', dirname(__DIR__));

// Load configuration
require_once ROOT_DIR . '/backend/config/settings.php';
require_once ROOT_DIR . '/backend/config/database.php';

// Establish database connection
$pdo = getDBConnection();

// Load utilities
require_once ROOT_DIR . '/backend/utils/Logger.php';
require_once ROOT_DIR . '/backend/utils/Response.php';
require_once ROOT_DIR . '/backend/utils/Validator.php';

// Load middleware
require_once ROOT_DIR . '/backend/middleware/SessionMiddleware.php';
require_once ROOT_DIR . '/backend/middleware/AuthMiddleware.php';
require_once ROOT_DIR . '/backend/middleware/RoleMiddleware.php';

// Load models
require_once ROOT_DIR . '/backend/models/User.php';
require_once ROOT_DIR . '/backend/models/MaintenanceReport.php';
require_once ROOT_DIR . '/backend/models/ActivityLog.php';
require_once ROOT_DIR . '/backend/models/Notification.php';
require_once ROOT_DIR . '/backend/models/Building.php';
require_once ROOT_DIR . '/backend/models/Floor.php';
require_once ROOT_DIR . '/backend/models/Room.php';
require_once ROOT_DIR . '/backend/models/Item.php';

// Load services
require_once ROOT_DIR . '/backend/services/AuthenticationService.php';
require_once ROOT_DIR . '/backend/services/ReportService.php';
require_once ROOT_DIR . '/backend/services/FacilityService.php';
require_once ROOT_DIR . '/backend/services/EmailService.php';

// Load controllers
require_once ROOT_DIR . '/backend/controllers/AuthController.php';
require_once ROOT_DIR . '/backend/controllers/ReportController.php';
require_once ROOT_DIR . '/backend/controllers/FacilityController.php';

// Initialize logger
Logger::init();

// Set headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
