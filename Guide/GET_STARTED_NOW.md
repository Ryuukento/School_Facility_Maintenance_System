# ⚡ GET STARTED NOW - 5 Minutes!

> **Your system is 100% ready!** Just follow these 5 simple steps.

---

## Step 1️⃣: Start XAMPP (60 seconds)

1. **Open XAMPP Control Panel**
   - Windows Start → Search "xampp" → Click XAMPP Control Panel

2. **Start Services:**
   - ✅ Click "Start" next to Apache
   - ✅ Click "Start" next to MySQL
   - Wait for both to show "Running" (green)

---

## Step 2️⃣: Create Database (90 seconds)

### Option A: Using phpMyAdmin (Easiest)

1. Open: `http://localhost/phpmyadmin`

2. Click **"New"** in left sidebar

3. **Database Name:** `school_facility_maintenance`
   - Collation: `utf8mb4_unicode_ci`
   - Click **"Create"**

4. Click on the new database

5. Click **"Import"** tab

6. Click **"Choose File"**
   - Browse to: `c:\xampp\htdocs\School_Facility_Maintenance_System\database\SINGLE_IMPORT.sql`
   - Click **"Go"**

7. **Wait for success message ✅**

---

## Step 3️⃣: Clear Browser Cache (60 seconds)

**Chrome/Chromium/Edge:**
```
Press: Ctrl + Shift + Delete
Select: "All time"
Click: "Clear data"
```

**Firefox:**
```
Press: Ctrl + Shift + Delete
Click: "Clear Now"
```

**Then:**
- ✅ Close browser completely
- ✅ Reopen browser fresh

---

## Step 4️⃣: Visit Your System (30 seconds)

**In browser address bar, paste:**
```
http://localhost/School_Facility_Maintenance_System/
```

**You should see:**
- ✅ Login page ("School Facility Maintenance System")
- ✅ Email field
- ✅ Password field
- ❌ NOT the XAMPP default page!

---

## Step 5️⃣: Login (10 seconds)

**Enter these credentials:**

| Field | Value |
|-------|-------|
| Email | `admin@school.edu` |
| Password | `admin123` |

**Click "Login"**

---

## 🎉 Success! You're In!

You should now see:
- ✅ **Dashboard page**
- ✅ **FacilityFlow sidebar** on the left
- ✅ **Navigation menu:** Dashboard, Reports, Inventory, Users, Settings, Logout
- ✅ **Responsive design** (resize browser to see mobile view)

---

## 🆘 Didn't Work? (3-Step Troubleshoot)

### Issue: Still Seeing XAMPP Page?

**Try This:**

1. **Restart Apache:**
   - XAMPP Control Panel → Stop Apache
   - Wait 2 seconds
   - Click Start Apache

2. **Clear Cache Again:**
   - `Ctrl + Shift + Delete` → "All time" → "Clear data"

3. **Direct URL Test:**
   ```
   http://localhost/School_Facility_Maintenance_System/frontend/pages/index.php
   ```

### Still Not Working?

**See:** [XAMPP_TROUBLESHOOTING.md](XAMPP_TROUBLESHOOTING.md) for detailed debugging

---

## 🎮 Next: Explore Features!

### Try These:

1. **View Dashboard**
   - Click "Dashboard" in sidebar

2. **Create Maintenance Report**
   - Click "Reports" → "Create New Report"

3. **Manage Users**
   - Click "User Management"

4. **Settings**
   - Click "Settings" (bottom of sidebar)

5. **Test Responsive Design**
   - Resize browser window
   - Watch sidebar collapse on mobile
   - Click hamburger icon to expand

---

## 👥 Additional Test Accounts

Log out and try these:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@school.edu | admin123 |
| Department Admin | elec.admin@school.edu | admin123 |
| Staff | john.smith@school.edu | admin123 |
| Regular User | sarah.johnson@school.edu | admin123 |

---

## ✅ What's Working

Your system includes:
- ✅ User authentication (login/register/logout)
- ✅ Role-based access control
- ✅ Dashboard with statistics
- ✅ Maintenance report management
- ✅ Inventory tracking
- ✅ User management
- ✅ Modern responsive sidebar
- ✅ Mobile-optimized design
- ✅ Database persistence
- ✅ Activity logging

---

## 📚 Full Documentation

- **Setup Details:** [XAMPP_TROUBLESHOOTING.md](XAMPP_TROUBLESHOOTING.md)
- **Sidebar Features:** [DOCUMENTATION_INDEX.md](DOCUMENTATION_INDEX.md)
- **Technical Overview:** [TECHNICAL_SUMMARY.md](TECHNICAL_SUMMARY.md)
- **API Reference:** [API Routes](#quick-access-urls)

---

## 🚨 Quick Reference

| Need | Command |
|------|---------|
| **View Logs** | `c:\xampp\htdocs\School_Facility_Maintenance_System\logs\` |
| **Database GUI** | `http://localhost/phpmyadmin` |
| **Restart Apache** | XAMPP Control Panel → Apache → Stop/Start |
| **Check MySQL** | XAMPP Control Panel → MySQL → Status |

---

## ⚡ That's It!

Your system is live and ready to use. 

**Questions?** See [XAMPP_TROUBLESHOOTING.md](XAMPP_TROUBLESHOOTING.md)

**Happy maintaining! 🏢**
