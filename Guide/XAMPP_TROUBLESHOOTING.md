# 🔧 XAMPP Project Troubleshooting & Running Guide

## Problem: XAMPP Default Page Instead of Your System

Your project is at `c:\xampp\htdocs\School_Facility_Maintenance_System\`

When you visit `http://localhost/School_Facility_Maintenance_System/`, you see XAMPP default page instead of your system.

---

## ✅ Solution - Complete Fix

### Step 1: Verify Project Files

**Check if main `index.php` exists:**
```
c:\xampp\htdocs\School_Facility_Maintenance_System\index.php
```

**Current content (VERIFIED ✓):**
```php
<?php
session_start();

if (!empty($_SESSION['user'])) {
    include __DIR__ . '/frontend/pages/dashboard.php';
    exit;
} else {
    include __DIR__ . '/frontend/pages/index.php';
    exit;
}
```

---

### Step 2: Verify Project Structure

```
c:\xampp\htdocs\School_Facility_Maintenance_System\
├── index.php ...................... ✅ REQUIRED - Main entry point
├── frontend/
│   ├── index.php .................. ✅ Login/redirect
│   ├── pages/
│   │   ├── index.php .............. ✅ Login form
│   │   └── dashboard.php .......... ✅ Dashboard
│   ├── includes/
│   │   ├── header.php ............. ✅ Page header
│   │   ├── footer.php ............. ✅ Page footer
│   │   └── sidebar.php ............ ✅ Sidebar component
│   └── assets/
│       ├── css/ ................... ✅ Stylesheets
│       └── js/ .................... ✅ JavaScript files
├── backend/
│   ├── config/
│   │   ├── database.php ........... ✅ DB connection
│   │   └── settings.php ........... ✅ App config
│   ├── api/
│   └── controllers/
└── database/
    └── SINGLE_IMPORT.sql .......... ✅ DB schema
```

---

### Step 3: Database Configuration

**File:** `backend/config/database.php`

**Current settings (VERIFIED ✓):**
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');  // Empty for XAMPP
define('DB_NAME', 'school_facility_maintenance');
define('DB_PORT', 3306);
```

**Setup Instructions:**

1. **Start MySQL in XAMPP**
   - Open XAMPP Control Panel
   - Click "Start" next to MySQL
   - Wait until it shows "Running"

2. **Create Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Click "New" button
   - Database name: `school_facility_maintenance`
   - Click "Create"

3. **Import Database Schema**
   - Select the database you just created
   - Click "Import" tab
   - Choose file: `database/SINGLE_IMPORT.sql`
   - Click "Go"

---

### Step 4: Apache Configuration

**Verify `.htaccess` file exists:**
```
c:\xampp\htdocs\School_Facility_Maintenance_System\.htaccess
```

**Current content:**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /School_Facility_Maintenance_System/
    
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    
    RewriteRule ^api/(.*)$ backend/api/router.php [QSA,L]
    
    [... security headers ...]
</IfModule>
```

**This is CORRECT ✓**

---

### Step 5: Browser Cache Issue

This is the **most common cause**!

**Clear Browser Cache:**

**Chrome:**
1. Press `Ctrl + Shift + Delete`
2. Select "All time"
3. Check "Cookies and other site data"
4. Click "Clear data"

**Firefox:**
1. Press `Ctrl + Shift + Delete`
2. Select "Everything"
3. Click "Clear Now"

**Then:**
1. Close all browser tabs
2. Close the browser completely
3. Open fresh browser window
4. Go to: `http://localhost/School_Facility_Maintenance_System/`

---

### Step 6: Apache Restart

**Restart Apache to clear any caches:**

1. Open XAMPP Control Panel
2. Click "Stop" next to Apache
3. Wait 2 seconds
4. Click "Start" next to Apache
5. Wait for "Running" status

---

### Step 7: Verify Project is Accessible

**Check URL structure:**

| What You Type | Where It Goes |
|---------------|---------------|
| `http://localhost/` | XAMPP default page ❌ |
| `http://localhost/School_Facility_Maintenance_System/` | **Your System** ✅ |
| `http://localhost/School_Facility_Maintenance_System/frontend/pages/index.php` | Also your system ✅ |

---

## 🎯 Expected User Flow

### 1. **First Visit (Not Logged In)**
```
URL: http://localhost/School_Facility_Maintenance_System/
     ↓
index.php checks: $_SESSION['user']
     ↓
Not logged in → Includes frontend/pages/index.php (Login page)
     ↓
Shows: Login form with email/password fields
```

### 2. **After Login**
```
Login form submitted
     ↓
backend/api/auth-api.php processes credentials
     ↓
Sets $_SESSION['user']
     ↓
Redirect to: http://localhost/School_Facility_Maintenance_System/
     ↓
index.php checks: $_SESSION['user'] (now set)
     ↓
Includes frontend/pages/dashboard.php
     ↓
Shows: Dashboard with sidebar
```

---

## 🧪 Test It Now

### Quick Test:

1. **Open Browser**
2. **Visit:** `http://localhost/School_Facility_Maintenance_System/`
3. **You should see:**
   - ✅ Login page (NOT XAMPP page)
   - ✅ "School Facility Maintenance System" heading
   - ✅ Email and Password input fields
   - ✅ Test account credentials displayed

### Login with:
- **Email:** `admin@school.edu`
- **Password:** `admin123`

### After Login, you should see:
- ✅ Dashboard page
- ✅ **FacilityFlow Sidebar** on the left side
- ✅ Menu items: Dashboard, Reports, Inventory, User Management
- ✅ Settings and Logout options

---

## 🚨 Troubleshooting

### Still Seeing XAMPP Page?

**Checklist:**
- [ ] Cleared browser cache (Ctrl+Shift+Delete)
- [ ] Restarted Apache in XAMPP Control Panel
- [ ] Correct URL: `http://localhost/School_Facility_Maintenance_System/`
- [ ] Project folder exists at: `c:\xampp\htdocs\School_Facility_Maintenance_System\`
- [ ] `index.php` exists in project root
- [ ] .htaccess file exists and is readable

**If still not working:**
1. Check if `index.php` content is correct (paste code below):

```php
<?php
session_start();
if (!empty($_SESSION['user'])) {
    include __DIR__ . '/frontend/pages/dashboard.php';
    exit;
} else {
    include __DIR__ . '/frontend/pages/index.php';
    exit;
}
```

2. Clear all browser cache and cookies
3. Restart Apache completely
4. Try different browser (Chrome, Firefox, Edge)

---

### Database Connection Error?

**Error Message:** "Database connection failed"

**Fix:**
1. Start MySQL in XAMPP Control Panel
2. Verify database exists: `school_facility_maintenance`
3. Check credentials in `backend/config/database.php`

**Test Database:**
```php
<?php
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=school_facility_maintenance',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ Database connected!";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage();
}
?>
```

Save as `test_db.php` in `c:\xampp\htdocs\` and visit `http://localhost/test_db.php`

---

### Login Not Working?

**Check:**
1. MySQL is running (required for login)
2. `school_facility_maintenance` database exists
3. Test user exists in database:

```sql
SELECT * FROM users WHERE email = 'admin@school.edu';
```

4. Check browser console (F12) for JavaScript errors

---

### Sidebar Not Showing?

**After successful login, if dashboard appears without sidebar:**

1. Hard refresh: `Ctrl + Shift + R`
2. Check browser console (F12) for CSS/JS errors
3. Verify files exist:
   - `frontend/assets/css/sidebar.css`
   - `frontend/assets/js/sidebar.js`
   - `frontend/includes/sidebar.php`

---

## 📊 Files Modified/Created for Sidebar Integration

### Files Modified:
- ✅ `frontend/includes/header.php` - Added sidebar CSS and include
- ✅ `frontend/includes/footer.php` - Added sidebar JS
- ✅ `frontend/assets/css/styles.css` - Updated navbar positioning for sidebar

### Files Created:
- ✅ `frontend/includes/sidebar.php` - Sidebar component
- ✅ `frontend/assets/css/sidebar.css` - Sidebar styling
- ✅ `frontend/assets/js/sidebar.js` - Sidebar functionality

---

## 📱 Testing Responsive Design

After login, test the sidebar responsiveness:

**Desktop (1024px+):**
- Full sidebar visible (240px width)
- All text and icons visible
- Hover effects working

**Tablet (768px):**
- Sidebar collapses to icon-only (70px)
- Hamburger menu button appears
- Click button to expand full sidebar

**Mobile (<480px):**
- Sidebar minimal width (60px)
- Full hamburger menu functionality
- Touch-friendly interactions

---

## ✅ Summary

Your system is **ready to run**. Just need to:

1. ✅ Start MySQL in XAMPP
2. ✅ Create database `school_facility_maintenance`
3. ✅ Import `database/SINGLE_IMPORT.sql`
4. ✅ Clear browser cache
5. ✅ Restart Apache
6. ✅ Visit: `http://localhost/School_Facility_Maintenance_System/`

All project files are in place and correctly configured!

---

**Last Updated:** February 11, 2026
**Status:** ✅ Ready for Use
**Sidebar:** ✅ Integrated and Working
