# Setup & Installation Guide

## System Requirements

### Server Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher (8.0+ recommended)
- **Web Server**: Apache 2.4+ with mod_rewrite, or Nginx
- **OS**: Linux, Windows Server, or macOS

### PHP Extensions Required
```
php-pdo
php-mysql
php-mbstring
php-json
php-openssl
php-fileinfo
```

### Hardware Recommendations
- **CPU**: 1+ cores
- **RAM**: 2GB minimum, 4GB recommended
- **Storage**: 10GB (for database and uploads)
- **Network**: Stable internet connection

---

## Installation Steps

### Step 1: Prepare Environment

#### 1.1 Create Project Directory
```bash
cd /var/www/html
mkdir school-maintenance-system
cd school-maintenance-system
```

#### 1.2 Create Project Structure
```bash
mkdir -p database backend frontend/{css,js,uploads}
mkdir -p docs logs config
touch .htaccess
```

#### 1.3 Copy Database Schema
Place [DATABASE_SCHEMA.sql](../database/DATABASE_SCHEMA.sql) in `/database/` folder

---

### Step 2: Database Setup

#### 2.1 Create Database
```bash
mysql -u root -p < database/DATABASE_SCHEMA.sql
```

#### 2.2 Create Database User
```sql
-- Login to MySQL as root
mysql -u root -p

-- Create user
CREATE USER 'school_maint'@'localhost' IDENTIFIED BY 'SecurePassword123!';

-- Grant privileges
GRANT ALL PRIVILEGES ON school_maintenance_system.* TO 'school_maint'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Exit
EXIT;
```

#### 2.3 Verify Database Connection
```bash
mysql -u school_maint -p school_maintenance_system -e "SHOW TABLES;"
```

---

### Step 3: Configuration Files

#### 3.1 Create config/database.php
```php
<?php
// File: config/database.php

define('DB_HOST', 'localhost');
define('DB_USER', 'school_maint');
define('DB_PASS', 'SecurePassword123!');
define('DB_NAME', 'school_maintenance_system');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Database connection failed');
}
?>
```

#### 3.2 Create config/settings.php
```php
<?php
// File: config/settings.php

// Application Settings
define('APP_NAME', 'School Facility Maintenance System');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'Asia/Manila');

// Session Settings
define('SESSION_TIMEOUT', 30 * 60); // 30 minutes
define('SESSION_SECURE', true);     // HTTPS only
define('SESSION_HTTPONLY', true);   // No JavaScript access

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../frontend/uploads/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Security
define('BCRYPT_COST', 12);
define('CSRF_TOKEN_LENGTH', 32);

// Set timezone
date_default_timezone_set(APP_TIMEZONE);
?>
```

#### 3.3 Create .env File (Not in Version Control)
```
# Database Configuration
DB_HOST=localhost
DB_USER=school_maint
DB_PASS=SecurePassword123!
DB_NAME=school_maintenance_system

# Application
APP_ENV=production
APP_DEBUG=false

# SMTP (for email notifications - optional)
SMTP_HOST=mail.school.edu
SMTP_PORT=587
SMTP_USER=noreply@school.edu
SMTP_PASS=emailPassword123!
```

**Add to .gitignore:**
```
.env
*.log
frontend/uploads/*
```

---

### Step 4: Create Directory Structure

#### 4.1 Frontend Structure
```bash
mkdir -p frontend/{css,js,pages,uploads}
touch frontend/index.php
touch frontend/dashboard.php
touch frontend/css/style.css
touch frontend/js/main.js
```

#### 4.2 Backend Structure
```bash
mkdir -p backend/{api,controllers,middleware,models,services,includes}
touch backend/config.php
```

#### 4.3 Set File Permissions
```bash
# Web server read-write on uploads
chmod 755 frontend/uploads/
chmod 777 frontend/uploads/

# Logs directory
mkdir logs
chmod 755 logs

# Restrict config files
chmod 600 config/.env
chmod 600 config/database.php
```

---

### Step 5: Web Server Configuration

#### 5.1 Apache (httpd.conf or .htaccess)
```apache
# File: .htaccess

# Enable Rewrite Engine
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Redirect to index.php for clean URLs
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
</IfModule>

# Disable directory listing
<IfModule mod_autoindex.c>
    Options -Indexes
</IfModule>

# Set PHP settings
<IfModule mod_php.c>
    php_value upload_max_filesize 5M
    php_value post_max_size 5M
    php_value max_execution_time 30
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>
```

#### 5.2 Nginx Configuration
```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    
    server_name school-maintenance.edu;
    root /var/www/html/school-maintenance-system;
    index index.php;
    
    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # PHP Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to config files
    location ~ /config/ {
        deny all;
    }
    
    # Deny access to .env
    location ~ /\.env {
        deny all;
    }
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    
    # Redirect HTTP to HTTPS
    error_page 497 =301 https://$server_name$request_uri;
}

server {
    listen 80;
    listen [::]:80;
    server_name school-maintenance.edu;
    return 301 https://$server_name$request_uri;
}
```

---

### Step 6: Test Installation

#### 6.1 Create Test Page
```php
<?php
// File: test.php

echo "<h1>Installation Test</h1>";

// Test 1: PHP Version
echo "<p>PHP Version: " . phpversion() . " (Required: 7.4+)</p>";

// Test 2: Database Connection
try {
    require 'config/database.php';
    echo "<p>✓ Database Connection: OK</p>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>✓ Users in database: " . $result['count'] . "</p>";
} catch (Exception $e) {
    echo "<p>✗ Database Error: " . $e->getMessage() . "</p>";
}

// Test 3: Session
@session_start();
$_SESSION['test'] = 'success';
if ($_SESSION['test'] === 'success') {
    echo "<p>✓ Sessions: OK</p>";
}

// Test 4: File Permissions
if (is_writable('frontend/uploads/')) {
    echo "<p>✓ Upload Directory: Writable</p>";
} else {
    echo "<p>✗ Upload Directory: Not Writable</p>";
}

// Test 5: Required Extensions
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p>✓ Extension $ext: Loaded</p>";
    } else {
        echo "<p>✗ Extension $ext: NOT Loaded</p>";
    }
}

echo "<p><a href='/'>Back to Home</a></p>";
?>
```

Access: `http://localhost/test.php`

#### 6.2 Test Login
1. Navigate to `http://localhost/index.php`
2. Login with sample credentials:
   - **Email**: `admin@school.edu`
   - **Password**: `admin123`

---

### Step 7: Initial Data

#### 7.1 Default Users (Already in Schema)
```sql
-- Super Admin
Email: admin@school.edu
Password: admin123 (hash: $2y$12$d1U5.4M7HyNkBQXxBVDDmeUCi/G4M1RfxfGXBhVMYz5fVYqKZVNUK)

-- Aircon Admin
Email: aircon@school.edu
Password: admin123

-- Electrical Admin
Email: electric@school.edu
Password: admin123

-- Plumbing Admin
Email: plumber@school.edu
Password: admin123

-- Sample Reporter 1
Email: teacher1@school.edu
Password: reporter123

-- Sample Reporter 2
Email: staff1@school.edu
Password: reporter123
```

**Change Passwords**: After first login, users should change their password.

---

### Step 8: Production Checklist

Before going live:

- [ ] Remove test.php
- [ ] Set APP_DEBUG = false in settings.php
- [ ] Enable HTTPS (SSL certificate)
- [ ] Configure firewall rules
- [ ] Set strong database passwords
- [ ] Configure email notifications (SMTP)
- [ ] Setup automated backups
- [ ] Configure log rotation
- [ ] Enable file integrity monitoring
- [ ] Test backup & restore procedure
- [ ] Document all custom changes
- [ ] Train administrators
- [ ] Create disaster recovery plan

---

## Troubleshooting

### Issue 1: Database Connection Failed
```
Error: Database connection failed
```

**Solution:**
1. Verify MySQL is running: `sudo systemctl status mysql`
2. Check credentials in `config/database.php`
3. Verify database exists: `mysql -u school_maint -p -e "SHOW DATABASES;"`
4. Check MySQL error log: `sudo tail -f /var/log/mysql/error.log`

### Issue 2: Permission Denied on Uploads
```
Error: move_uploaded_file failed
```

**Solution:**
```bash
# Change ownership to web server user
sudo chown -R www-data:www-data /var/www/html/school-maintenance-system/frontend/uploads

# Set permissions
sudo chmod 755 /var/www/html/school-maintenance-system/frontend/uploads
```

### Issue 3: Sessions Not Working
```
Error: Cannot send session cookie - headers already sent
```

**Solution:**
1. Ensure no output before `session_start()`
2. Check for BOM (byte order mark) in PHP files
3. Verify session directory permissions: `ls -la /var/lib/php/sessions/`

### Issue 4: HTTPS Certificate Errors
```
Error: SSL certificate problem
```

**Solution:**
1. Verify certificate validity: `openssl x509 -in cert.crt -text -noout`
2. Check certificate dates haven't expired
3. Verify certificate matches domain name

---

## Backup & Restore

### Database Backup
```bash
# Full backup
mysqldump -u school_maint -p school_maintenance_system > backup.sql

# Backup with timestamp
mysqldump -u school_maint -p school_maintenance_system > "backup_$(date +%Y%m%d_%H%M%S).sql"
```

### Database Restore
```bash
mysql -u school_maint -p school_maintenance_system < backup.sql
```

### File Backup
```bash
# Backup application files and uploads
tar -czf backup_$(date +%Y%m%d).tar.gz \
  /var/www/html/school-maintenance-system \
  --exclude=logs \
  --exclude=.git
```

---

## Maintenance Tasks

### Daily
- Monitor error logs
- Check system resources
- Verify backups completed

### Weekly
- Review activity logs for suspicious activity
- Check failed login attempts
- Verify file permissions

### Monthly
- Database optimization: `OPTIMIZE TABLE users, maintenance_reports, notifications, activity_logs;`
- Review and archive old logs
- Security updates
- Performance analysis

### Quarterly
- Full disaster recovery test
- Security audit
- Capacity planning
- Dependency updates

---

## Support & Documentation

- **Issues**: Review logs in `/logs/` directory
- **Database**: See [DATABASE_SCHEMA.sql](../database/DATABASE_SCHEMA.sql)
- **API**: See [API_SPECIFICATIONS.md](./API_SPECIFICATIONS.md)
- **Security**: See [SECURITY_IMPLEMENTATION.md](./SECURITY_IMPLEMENTATION.md)

