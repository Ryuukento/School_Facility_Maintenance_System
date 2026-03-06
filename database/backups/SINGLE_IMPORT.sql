-- ============================================================
-- DATABASE SETUP - SAFE MODE (Does NOT drop existing database)
-- ============================================================
-- This file uses CREATE TABLE IF NOT EXISTS so it only adds missing tables.
-- Existing data will NEVER be deleted by this script.
-- ============================================================

CREATE DATABASE IF NOT EXISTS school_facility_maintenance 
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_facility_maintenance;

-- ============================================================
-- ============================================================
-- 2. DEPARTMENTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS departments (
	department_id INT PRIMARY KEY AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL UNIQUE,
	description TEXT,
	head_user_id INT,
	status ENUM('active', 'inactive') DEFAULT 'active',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BUILDINGS TABLE (added to support frontend 'Add Building')
-- ============================================================
CREATE TABLE IF NOT EXISTS buildings (
	id INT PRIMARY KEY AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL,
	description TEXT,
	address VARCHAR(500),
	status ENUM('active','inactive') DEFAULT 'active',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FLOORS TABLE (hierarchy under buildings)
-- ============================================================
CREATE TABLE IF NOT EXISTS floors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    building_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE CASCADE,
    UNIQUE KEY unique_floor_per_building (building_id, name),
    INDEX idx_building (building_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ROOMS TABLE (places inside floors and buildings)
-- ============================================================
CREATE TABLE IF NOT EXISTS rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    building_id INT NOT NULL,
    floor_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    capacity INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE CASCADE,
    FOREIGN KEY (floor_id) REFERENCES floors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_per_building (building_id, name),
    UNIQUE KEY unique_room_per_floor (floor_id, name),
    INDEX idx_building (building_id),
    INDEX idx_floor (floor_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ITEMS TABLE (inventory stored per room)
-- ============================================================
CREATE TABLE IF NOT EXISTS items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    status ENUM('available','damaged','low_stock','out_of_stock','maintenance') DEFAULT 'available',
    quantity INT DEFAULT 1,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_room (room_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample building so UI has an initial entry (only if not already present)
INSERT IGNORE INTO buildings (name, description, address, status) VALUES
('Lourdes Building 4', 'Initial building added during import', 'Main Campus', 'active');

-- ============================================================
-- ============================================================
-- 3. USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
	user_id INT PRIMARY KEY AUTO_INCREMENT,
	full_name VARCHAR(255) NOT NULL,
	email VARCHAR(255) UNIQUE NOT NULL,
	password VARCHAR(255) NOT NULL,
	role ENUM('super_admin', 'department_admin', 'maintenance_admin', 'maintenance_staff', 'user') NOT NULL DEFAULT 'user',
	department_id INT,
	status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
	INDEX idx_email (email),
	INDEX idx_role (role),
	INDEX idx_status (status),
	INDEX idx_department_id (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add head_user_id foreign key to departments
ALTER TABLE departments ADD CONSTRAINT fk_dept_head FOREIGN KEY (head_user_id) REFERENCES users(user_id) ON DELETE SET NULL;

-- ============================================================
-- ============================================================
-- 4. MAINTENANCE REPORTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS maintenance_reports (
	report_id INT PRIMARY KEY AUTO_INCREMENT,
	title VARCHAR(255) NOT NULL,
	description LONGTEXT NOT NULL,
	location VARCHAR(255) NOT NULL,
	priority ENUM('low', 'medium', 'high', 'urgent', 'critical') DEFAULT 'medium',
	status ENUM('draft', 'submitted', 'assigned', 'in_progress', 'completed', 'closed') DEFAULT 'submitted',
	created_by INT NULL,
	assigned_to INT,
	department_id INT,
	image_path VARCHAR(500),
	due_date DATE,
	completed_date DATETIME,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
	FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL,
	FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
	INDEX idx_status (status),
	INDEX idx_priority (priority),
	INDEX idx_created_by (created_by),
	INDEX idx_assigned_to (assigned_to),
	INDEX idx_department_id (department_id),
	INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ============================================================
-- 4b. SIMPLE REPORTS TABLE (for persistent simple reports API)
-- ============================================================
CREATE TABLE IF NOT EXISTS reports (
	id INT PRIMARY KEY AUTO_INCREMENT,
	title VARCHAR(255) NOT NULL,
	description TEXT NOT NULL,
	created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample reports (only if not already present)
INSERT IGNORE INTO reports (title, description, created_at) VALUES
('Sample: Broken Window', 'Window in Room 12 is cracked and needs replacement.', NOW()),
('Sample: Clogged Drain', 'The sink in room 204 drains slowly and overflows.', NOW());

-- ============================================================
-- ============================================================
-- 5. ACTIVITY LOGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_logs (
	log_id INT PRIMARY KEY AUTO_INCREMENT,
	user_id INT NOT NULL,
	action VARCHAR(100) NOT NULL,
	entity_type VARCHAR(50),
	entity_id INT,
	details TEXT,
	ip_address VARCHAR(45),
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
	INDEX idx_user_id (user_id),
	INDEX idx_action (action),
	INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ============================================================
-- 6. NOTIFICATIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
	notification_id INT PRIMARY KEY AUTO_INCREMENT,
	user_id INT NOT NULL,
	report_id INT,
	title VARCHAR(255) NOT NULL,
	message TEXT NOT NULL,
	is_read BOOLEAN DEFAULT FALSE,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	read_at TIMESTAMP NULL,
	FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
	FOREIGN KEY (report_id) REFERENCES maintenance_reports(report_id) ON DELETE CASCADE,
	INDEX idx_user_id (user_id),
	INDEX idx_report_id (report_id),
	INDEX idx_is_read (is_read),
	INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ============================================================
-- 7. REPORT COMMENTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS report_comments (
	comment_id INT PRIMARY KEY AUTO_INCREMENT,
	report_id INT NOT NULL,
	user_id INT NULL,
	comment_text TEXT NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (report_id) REFERENCES maintenance_reports(report_id) ON DELETE CASCADE,
	FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
	INDEX idx_report_id (report_id),
	INDEX idx_user_id (user_id),
	INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ============================================================
-- 8. INSERT DEPARTMENTS (only if not already present)
-- ============================================================
INSERT IGNORE INTO departments (name, description, status) VALUES
('Electrical', 'Electrical systems and maintenance', 'active'),
('Plumbing', 'Plumbing systems and water management', 'active'),
('HVAC', 'Heating, Ventilation, and Air Conditioning', 'active'),
('Facilities', 'General building facilities and grounds', 'active');

-- ============================================================
-- ============================================================
-- 9. INSERT USERS (only if not already present)
-- ============================================================
-- All test users have password: Admin@123
-- IMPORTANT: These hashes use bcrypt cost 12 (as required by the system)
-- If you need to reset passwords, run: database/PASSWORD_RESET.php

-- Super Admin User
INSERT IGNORE INTO users (full_name, email, password, role, department_id, status) VALUES
('Mr. Admin', 'admin@school.edu', '$2y$12$thIZHPu74DGT2MGOFHQP6e5rMLQSvM20oqWAmZVfPkH1JnXN4TnPC', 'super_admin', 1, 'active');

-- Maintenance Admin User
INSERT IGNORE INTO users (full_name, email, password, role, department_id, status) VALUES
('Mr. Maintenance Admin', 'maintenance.admin@school.edu', '$2y$12$thIZHPu74DGT2MGOFHQP6e5rMLQSvM20oqWAmZVfPkH1JnXN4TnPC', 'maintenance_admin', 1, 'active');

-- Department Admins
INSERT IGNORE INTO users (full_name, email, password, role, department_id, status) VALUES
('Mr. Electrical Admin', 'elec.admin@school.edu', '$2y$12$thIZHPu74DGT2MGOFHQP6e5rMLQSvM20oqWAmZVfPkH1JnXN4TnPC', 'department_admin', 1, 'active'),
('Mr. Plumbing Admin', 'plumb.admin@school.edu', '$2y$12$thIZHPu74DGT2MGOFHQP6e5rMLQSvM20oqWAmZVfPkH1JnXN4TnPC', 'department_admin', 2, 'active'),
('Mr. HVAC Admin', 'hvac.admin@school.edu', '$2y$12$thIZHPu74DGT2MGOFHQP6e5rMLQSvM20oqWAmZVfPkH1JnXN4TnPC', 'department_admin', 3, 'active');

-- Maintenance Staff
INSERT IGNORE INTO users (full_name, email, password, role, department_id, status) VALUES
('John Smith', 'john.smith@school.edu', '$2y$12$thIZHPu74DGT2MGOFHQP6e5rMLQSvM20oqWAmZVfPkH1JnXN4TnPC', 'maintenance_staff', 1, 'active');

-- Regular Users (Reporters)
INSERT IGNORE INTO users (full_name, email, password, role, department_id, status) VALUES
('Sarah Johnson', 'sarah.johnson@school.edu', '$2y$12$thIZHPu74DGT2MGOFHQP6e5rMLQSvM20oqWAmZVfPkH1JnXN4TnPC', 'user', 4, 'active');


-- ============================================================
-- Additional placeholder users to satisfy foreign key references
-- These create user rows so `created_by`/`assigned_to` IDs used below exist
INSERT IGNORE INTO users (full_name, email, password, role, department_id, status) VALUES
('Imported User 8', 'importer8@school.edu', '$2y$12$thIZHPu74DGT2MGOFHQP6e5rMLQSvM20oqWAmZVfPkH1JnXN4TnPC', 'user', 4, 'active'),
('Imported User 9', 'importer9@school.edu', '$2y$12$thIZHPu74DGT2MGOFHQP6e5rMLQSvM20oqWAmZVfPkH1JnXN4TnPC', 'user', 2, 'active'),
('Imported User 10', 'importer10@school.edu', '$2y$12$thIZHPu74DGT2MGOFHQP6e5rMLQSvM20oqWAmZVfPkH1JnXN4TnPC', 'user', 3, 'active');

-- ============================================================
-- 10. INSERT SAMPLE MAINTENANCE REPORTS (only if not already present)
-- ============================================================
INSERT IGNORE INTO maintenance_reports (title, description, location, priority, status, created_by, assigned_to, department_id, due_date) VALUES
('Broken Light Fixture', 'Ceiling light in Room 101 is not working properly. The bulb appears to be functional but the fixture may have wiring issues.', 'Building A - Room 101', 'medium', 'assigned', 1, 6, 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY)),

('Leaking Faucet', 'Bathroom faucet in 2nd floor restroom is constantly dripping. Water waste estimated at 5 gallons per day.', 'Building B - 2nd Floor Bathroom', 'high', 'in_progress', 2, 6, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY)),

('Air Conditioning Issue', 'AC unit in cafeteria not cooling properly. Temperature stays around 28°C even when set to 22°C.', 'Building C - Cafeteria', 'urgent', 'assigned', 1, 6, 3, DATE_ADD(CURDATE(), INTERVAL 2 DAY)),

('Painting Required', 'Main hallway walls showing wear and tear. Fresh paint needed for better appearance.', 'Building A - Main Hallway', 'low', 'submitted', 7, NULL, 4, DATE_ADD(CURDATE(), INTERVAL 7 DAY)),

('Door Lock Repair', 'Main entrance door lock is malfunctioning. Key gets stuck and door sometimes wont lock properly.', 'Building A - Main Entrance', 'high', 'completed', 2, 6, 1, CURDATE());

-- ============================================================
-- ============================================================
-- 11. INSERT SAMPLE ACTIVITY LOGS
-- ============================================================
INSERT IGNORE INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES
(1, 'LOGIN', 'user', 1, 'Admin user logged in', '127.0.0.1'),
(1, 'CREATE_REPORT', 'report', 1, 'Created new maintenance report: Broken Light Fixture', '127.0.0.1'),
(1, 'ASSIGN_REPORT', 'report', 1, 'Assigned report to John Smith', '127.0.0.1'),
(2, 'UPDATE_REPORT', 'report', 1, 'Updated report status to assigned', '127.0.0.1'),
(7, 'CREATE_REPORT', 'report', 2, 'Created new maintenance report: Leaking Faucet', '127.0.0.1'),
(1, 'UPDATE_REPORT', 'report', 2, 'Updated report status to in_progress', '127.0.0.1');

-- ============================================================
-- ============================================================
-- 12. INSERT SAMPLE NOTIFICATIONS
-- ============================================================
INSERT IGNORE INTO notifications (user_id, report_id, title, message, is_read) VALUES
(1, 1, 'New Report', 'A new maintenance report has been submitted: Broken Light Fixture', FALSE),
(6, 1, 'Report Assigned', 'You have been assigned to maintenance report: Broken Light Fixture', FALSE),
(6, 2, 'Report Assigned', 'You have been assigned to maintenance report: Leaking Faucet', FALSE),
(6, 3, 'Report Assigned', 'You have been assigned to maintenance report: Air Conditioning Issue', TRUE);

-- ============================================================
-- 13. VERIFY SETUP
-- ============================================================
SELECT 'Database setup completed successfully!' AS Status;
SELECT CONCAT('Total Departments: ', COUNT(*)) AS DepartmentCount FROM departments;
SELECT CONCAT('Total Users: ', COUNT(*)) AS UserCount FROM users;
SELECT CONCAT('Total Reports: ', COUNT(*)) AS ReportCount FROM maintenance_reports;
SELECT CONCAT('Total Activity Logs: ', COUNT(*)) AS ActivityLogCount FROM activity_logs;

-- ============================================================
-- TEST CREDENTIALS
-- ============================================================
-- Super Admin Login:
--   Email: admin@school.edu
--   Password: Admin@123
--
-- Maintenance Admin Login:
--   Email: maintenance.admin@school.edu
--   Password: Admin@123
--
-- Other Test Users (same password: Admin@123):
--   Department Admin: elec.admin@school.edu
--   Maintenance Staff: john.smith@school.edu
--   Regular User: sarah.johnson@school.edu
--
-- Database Name: school_facility_maintenance
-- All tables are ready to use!
-- ============================================================
