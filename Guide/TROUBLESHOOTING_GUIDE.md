# 🔧 Troubleshooting & Setup Guide

## ✅ Step-by-Step Setup

### Step 1: Verify XAMPP is Running
```
1. Open XAMPP Control Panel
2. Check Apache is GREEN and running
3. Check MySQL is GREEN and running
4. If not, click START buttons
```

### Step 2: Enable mod_rewrite (Required for API routing)
```
1. Click "Config" next to Apache
2. Select "Apache (httpd.conf)"
3. Find line: LoadModule rewrite_module modules/mod_rewrite.so
4. If it has # at the start, remove it
5. Save file
6. Restart Apache (Stop then Start)
```

### Step 3: Import Database
```
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click "Import" tab
3. Choose file: database/SETUP_WITH_TEST_DATA.sql
4. Click "Go" button
5. Wait for "Success" message
```

### Step 4: Verify Database Connection
Edit `backend/config/database.php`:
```php
define('DB_HOST', 'localhost');     // Usually correct
define('DB_USER', 'root');          // Default XAMPP user
define('DB_PASSWORD', '');          // Empty by default
define('DB_NAME', 'school_facility_maintenance');  // Must match
define('DB_PORT', 3306);            // Default MySQL port
```

### Step 5: Access the Application
```
Login Page: http://localhost/School_Facility_Maintenance_System/
```

### Step 6: Test Login
```
Email:    admin@school.edu
Password: Admin@123
```

---

## 🐛 Common Issues & Solutions

### Issue 1: 404 Page Not Found Error

**Problem**: Getting "Page Not Found" when accessing the system

**Solutions**:
1. **Check correct URL**: `http://localhost/School_Facility_Maintenance_System/`
2. **Enable mod_rewrite**:
   - XAMPP → Config → Apache (httpd.conf)
   - Search: `LoadModule rewrite_module`
   - Remove # if present
   - Restart Apache
3. **Check .htaccess exists**:
   - File should be at: `c:\xampp\htdocs\School_Facility_Maintenance_System\.htaccess`
4. **Check directory permissions**:
   - Folder should be readable by Apache

---

### Issue 2: Database Connection Error

**Problem**: "Database connection failed" message

**Solutions**:
1. **Check MySQL is running**:
   - XAMPP Control Panel → MySQL should be GREEN
   - If RED, click START
2. **Verify credentials**:
   - Edit: `backend/config/database.php`
   - Default: user=root, password=(empty)
3. **Check database exists**:
   - phpMyAdmin → Check `school_facility_maintenance` database exists
   - If not, import `SETUP_WITH_TEST_DATA.sql`
4. **Test MySQL connection**:
   ```bash
   mysql -u root -p
   # Press ENTER when prompted for password (empty)
   # Type: show databases;
   # Should see: school_facility_maintenance
   ```

---

### Issue 3: Login Not Working

**Problem**: Credentials rejected or blank page after login

**Solutions**:
1. **Verify test users exist**:
   - phpMyAdmin → school_facility_maintenance → users table
   - Should have at least one user: admin@school.edu
2. **Check password hash**:
   - All test users have password: `Admin@123`
   - Hash: `$2y$12$E8qdHZ2zb2WKM4PZUG1Yre9R6vL0kL5H8K0V2L3R0S5T6U7V8W9X0`
3. **Clear browser cache**:
   - Chrome: Ctrl+Shift+Delete
   - Firefox: Ctrl+Shift+Delete
4. **Check session folder permissions**:
   - PHP needs write access to sessions folder
5. **View error logs**:
   - Apache: `c:\xampp\apache\logs\error.log`
   - PHP: Check XAMPP logs

---

### Issue 4: CSS/JavaScript Not Loading

**Problem**: Page shows but styling is missing or not responsive

**Solutions**:
1. **Check file paths**:
   - Right-click → "Inspect" in browser
   - Check Console tab for 404 errors
   - All paths should start with: `/School_Facility_Maintenance_System/`
2. **Clear browser cache**:
   - Ctrl+Shift+Delete
   - Clear cache
   - Reload page
3. **Verify file exists**:
   - File should be at: `c:\xampp\htdocs\School_Facility_Maintenance_System\frontend\assets\css\styles.css`
4. **Check permissions**:
   - Files should be readable by Apache

---

### Issue 5: API Endpoints Not Working

**Problem**: API calls returning 404 or connection errors

**Solutions**:
1. **Verify mod_rewrite is enabled** (see Issue 1)
2. **Test API directly**:
   ```bash
   # Using PowerShell
   $body = @{
       email = "admin@school.edu"
       password = "Admin@123"
   } | ConvertTo-Json
   
   Invoke-WebRequest -Uri "http://localhost/School_Facility_Maintenance_System/api/auth?action=login" `
       -Method POST `
       -Headers @{"Content-Type"="application/json"} `
       -Body $body
   ```
3. **Check .htaccess routing**:
   - File: `c:\xampp\htdocs\School_Facility_Maintenance_System\.htaccess`
   - Should have: `RewriteRule ^api/(.*)$ backend/api/router.php`
4. **Check router.php**:
   - File: `backend/api/router.php`
   - Should exist and be readable

---

### Issue 6: Include/Require File Not Found

**Problem**: "Failed to open stream" error with include paths

**Solutions**:
1. **Check full paths in include statements**:
   ```php
   // Correct:
   include $_SERVER['DOCUMENT_ROOT'] . '/School_Facility_Maintenance_System/...php';
   
   // Wrong:
   include $_SERVER['DOCUMENT_ROOT'] . '/frontend/...php';
   ```
2. **Verify file exists**:
   - Check file is at exact path shown in error
3. **Check DOCUMENT_ROOT**:
   - phpinfo() should show: `C:\xampp\htdocs`

---

### Issue 7: Blank Page After Login

**Problem**: Login appears to work but dashboard is blank

**Solutions**:
1. **Check session creation**:
   - Browser DevTools → Application → Cookies
   - Should have session cookie
2. **Check PHP errors**:
   - Add to page: `<?php error_reporting(E_ALL); ini_set('display_errors', 1); ?>`
3. **Verify database connection**:
   - Test with: `phpmyadmin` → Check tables exist
4. **Check include files**:
   - dashboard.php should include header and footer correctly

---

### Issue 8: Form Submission Not Working

**Problem**: Form submits but nothing happens or error appears

**Solutions**:
1. **Check browser console**:
   - Right-click → Inspect → Console tab
   - Look for JavaScript errors
2. **Verify API endpoint**:
   - Check API path is correct
   - Test manually with curl or Postman
3. **Check CORS headers** (if needed):
   - XAMPP localhost doesn't usually need CORS
4. **Verify form validation**:
   - All required fields filled
   - Email format correct
   - Password long enough

---

### Issue 9: Permission Denied Errors

**Problem**: "Permission denied" or file access errors

**Solutions**:
1. **Check folder permissions**:
   ```bash
   # PowerShell as Admin
   icacls "c:\xampp\htdocs\School_Facility_Maintenance_System" /grant Everyone:F /t
   ```
2. **Check file ownership**:
   - Right-click folder → Properties → Security
   - Make sure Apache user has Full Control
3. **Restart Apache**:
   - XAMPP Control Panel → Stop Apache → Start Apache

---

### Issue 10: Port Already in Use

**Problem**: Apache won't start - port 80 in use

**Solutions**:
1. **Find what's using port 80**:
   ```bash
   netstat -ano | findstr :80
   ```
2. **Change Apache port** (if needed):
   - XAMPP → Config → Apache (httpd.conf)
   - Find: `Listen 80`
   - Change to: `Listen 8080`
   - Access at: `http://localhost:8080/...`
3. **Stop conflicting service**:
   - Identify process from netstat
   - Stop it in Task Manager

---

## 🧪 Testing Checklist

```
□ XAMPP Apache is running
□ XAMPP MySQL is running
□ Database imported successfully
□ Test user created (admin@school.edu)
□ Can access http://localhost/School_Facility_Maintenance_System/
□ Login page loads with proper styling
□ Can login with admin@school.edu / Admin@123
□ Dashboard page loads
□ Can create a new report
□ Can view report list
□ Can update user profile
□ API endpoint works (test in Postman)
□ No JavaScript errors in console
□ No PHP errors in logs
```

---

## 📊 Useful Commands

### MySQL CLI Access
```bash
# Open MySQL command line
mysql -u root

# In MySQL:
USE school_facility_maintenance;
SELECT * FROM users;
SELECT * FROM maintenance_reports;
```

### Check Apache Error Log
```bash
type c:\xampp\apache\logs\error.log
```

### Check PHP Version
```bash
# Access phpinfo page
http://localhost/phpmyadmin/
# Click: phpMyAdmin → Tools → PHP Info
```

### Test Database Connection
```bash
# Create test.php in htdocs folder:
<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=school_facility_maintenance', 'root', '');
    echo "Database connected!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

---

## 📞 Getting Help

### Check These Files
1. **Error logs**: `c:\xampp\apache\logs\error.log`
2. **Database schema**: `database/SETUP_WITH_TEST_DATA.sql`
3. **Backend config**: `backend/config/database.php`
4. **Setup guide**: `COMPLETE_SETUP_GUIDE.md`

### Review Documentation
- **START_HERE.md** - Basic overview
- **COMPLETE_SETUP_GUIDE.md** - Full setup instructions
- **API_SPECIFICATIONS.md** - API endpoints
- **SECURITY_IMPLEMENTATION.md** - Security details

---

## 💡 Pro Tips

1. **Keep XAMPP running**: Always run before testing
2. **Test in private/incognito**: Avoids cache issues
3. **Use Postman**: Test API endpoints before frontend
4. **Check logs first**: Always check error logs when issues arise
5. **Clear cache**: Ctrl+Shift+Delete if CSS/JS not updating
6. **Use DevTools**: Inspect element to debug frontend
7. **Read error messages**: They usually tell you what's wrong

---

## ✅ Quick Start After Setup

1. Open browser: `http://localhost/School_Facility_Maintenance_System/`
2. Login with:
   - Email: `admin@school.edu`
   - Password: `Admin@123`
3. Click "Dashboard" to see system overview
4. Click "Reports" to view all reports
5. Click "New Report" to create a report
6. Explore other pages

---

## 🎯 Test Users Available

```
Super Admin:
  Email: admin@school.edu
  Password: Admin@123

Department Admin:
  Email: elec.admin@school.edu
  Password: Admin@123

Maintenance Staff:
  Email: john.smith@school.edu
  Password: Admin@123

Regular User:
  Email: sarah.johnson@school.edu
  Password: Admin@123
```

All test users have the same password: `Admin@123`

---

**Last Updated**: January 30, 2026
**Version**: 1.0
**Status**: Ready for Use ✅
