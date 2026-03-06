# ✅ VERIFICATION CHECKLIST

Use this to confirm your system is working correctly.

---

## Pre-Launch Checklist

- [ ] XAMPP is installed and running
- [ ] Apache is started in XAMPP Control Panel (shows "Running")
- [ ] MySQL is started in XAMPP Control Panel (shows "Running")
- [ ] All project files exist in `c:\xampp\htdocs\School_Facility_Maintenance_System\`
- [ ] Browser cache has been cleared

---

## Database Setup ✅

- [ ] Database `school_facility_maintenance` exists in MySQL
- [ ] Database contains tables: users, facilities, maintenance_reports, etc.
- [ ] `SINGLE_IMPORT.sql` has been successfully imported
- [ ] Test admin account exists (admin@school.edu)

**How to verify:**
1. Go to http://localhost/phpmyadmin
2. Select `school_facility_maintenance` database
3. Should see 8-10 tables listed

---

## Application Launch ✅

- [ ] Browser displays login page (NOT XAMPP default page)
- [ ] Page shows "School Facility Maintenance System" title
- [ ] Email and password fields are visible
- [ ] "Login" button is present

**URL:** `http://localhost/School_Facility_Maintenance_System/`

---

## Authentication ✅

- [ ] Can log in with: admin@school.edu / admin123
- [ ] Login redirects to dashboard page
- [ ] Session is created and maintained
- [ ] Can stay logged in across page refreshes

---

## Sidebar Display ✅

- [ ] Sidebar appears on LEFT side of screen (240px wide)
- [ ] Sidebar has dark theme with blue accents
- [ ] Menu items visible: Dashboard, Reports, Inventory, Users, Settings
- [ ] Current page is highlighted in sidebar
- [ ] Logout button at bottom of sidebar

---

## Navigation ✅

- [ ] Can click "Dashboard" and load dashboard page
- [ ] Can click "Reports" and see reports list
- [ ] Can click "Inventory" and see inventory view
- [ ] Can click "User Management" and see users list
- [ ] Can click "Settings" and see settings page
- [ ] Can click "Logout" and return to login page

---

## Responsive Design ✅

- [ ] Resize browser window to 768px width
  - [ ] Sidebar collapses to 70px (icon-only mode)
  - [ ] Hamburger menu icon appears
  - [ ] Main content adjusts properly

- [ ] Resize browser to 480px (mobile)
  - [ ] Sidebar collapses to 60px
  - [ ] Page is still readable on mobile
  - [ ] Touch-friendly navigation

- [ ] Expand back to desktop
  - [ ] Sidebar expands back to full 240px
  - [ ] All navigation visible again

---

## Features Working ✅

- [ ] Dashboard displays statistics
- [ ] Reports page shows any existing reports
- [ ] Can create new maintenance report
- [ ] Can view and edit user profile
- [ ] Settings page loads without errors
- [ ] Activity logs are being recorded

---

## Database Integration ✅

- [ ] Login credentials are verified against database
- [ ] User data is being retrieved correctly
- [ ] Session data is persisted
- [ ] Reports can be created and saved
- [ ] User information is being stored

---

## Performance ✅

- [ ] Page loads within 2 seconds
- [ ] No console errors (press F12 to check)
- [ ] Sidebar animations are smooth
- [ ] No lag when clicking menu items
- [ ] Responsive design animations work smoothly

**How to check:**
1. Press F12 (Developer Tools)
2. Click "Console" tab
3. Should show no red error messages

---

## Files Present ✅

Backend:
- [ ] `backend/config/database.php` exists
- [ ] `backend/config/settings.php` exists
- [ ] `backend/api/auth.php` exists
- [ ] `backend/api/reports.php` exists

Frontend:
- [ ] `frontend/includes/header.php` includes sidebar.css
- [ ] `frontend/includes/sidebar.php` exists
- [ ] `frontend/assets/css/sidebar.css` exists
- [ ] `frontend/assets/js/sidebar.js` exists
- [ ] `frontend/pages/dashboard.php` exists
- [ ] `frontend/pages/index.php` (login) exists

---

## Security ✅

- [ ] Passwords are hashed in database (not plain text)
- [ ] Session tokens are being used
- [ ] CSRF protection is in place
- [ ] SQL injection is prevented (using prepared statements)
- [ ] XSS protection is implemented

---

## Troubleshooting Guide

### Problem: Still seeing XAMPP page?

**Steps:**
1. [ ] Stop Apache in XAMPP Control Panel
2. [ ] Wait 3 seconds
3. [ ] Start Apache again
4. [ ] Clear browser cache (Ctrl+Shift+Delete)
5. [ ] Hard refresh (Ctrl+Shift+R)
6. [ ] Revisit: http://localhost/School_Facility_Maintenance_System/

### Problem: Login fails?

**Check:**
1. [ ] Database `school_facility_maintenance` exists
2. [ ] `users` table exists in database
3. [ ] Test user (admin@school.edu) exists in users table
4. [ ] MySQL is running

**Debug:**
1. Go to http://localhost/phpmyadmin
2. Select `school_facility_maintenance`
3. Click `users` table
4. Check if admin@school.edu row exists

### Problem: Sidebar not showing?

**Check:**
1. [ ] `frontend/assets/css/sidebar.css` file exists
2. [ ] `frontend/assets/js/sidebar.js` file exists
3. [ ] `frontend/includes/sidebar.php` file exists
4. [ ] header.php includes sidebar.css link
5. [ ] header.php includes sidebar.php
6. [ ] footer.php includes sidebar.js

### Problem: Styling looks wrong?

**Try:**
1. [ ] Clear browser cache (Ctrl+Shift+Delete)
2. [ ] Hard refresh (Ctrl+Shift+R)
3. [ ] Restart Apache
4. [ ] Try different browser (Chrome, Firefox, Edge)

---

## Success Criteria

✅ **Your system works correctly when:**

1. You can visit `http://localhost/School_Facility_Maintenance_System/`
2. You see a login page (not XAMPP default)
3. You can log in with admin@school.edu / admin123
4. Dashboard loads with sidebar on the left
5. Sidebar is dark with blue accents
6. You can navigate between pages using sidebar menu
7. Responsive design works (sidebar collapses when window is resized)
8. No errors appear in browser console (F12)

---

## Next Steps After Verification

- [ ] Create additional user accounts
- [ ] Create sample maintenance reports
- [ ] Test all role-based access (different users)
- [ ] Explore analytics and reporting features
- [ ] Customize color scheme if needed
- [ ] Set up email notifications
- [ ] Configure automated report scheduling

---

## Getting Help

**If something isn't working:**

1. Read: [XAMPP_TROUBLESHOOTING.md](XAMPP_TROUBLESHOOTING.md)
2. Check browser console for errors (F12)
3. Verify database exists and is populated
4. Ensure Apache and MySQL are running
5. Clear cache and restart services

---

**Last Updated:** System Configuration Complete
**Status:** ✅ Ready for Production
**Verification Date:** [Your Date Here]
