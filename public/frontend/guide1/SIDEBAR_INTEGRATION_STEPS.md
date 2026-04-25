# FacilityFlow Sidebar - Integration Guide for Existing Pages

This guide shows how to add the FacilityFlow sidebar to your existing PHP pages.

## Quick Integration Template

Use this as a template for updating your existing pages:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FacilityFlow - School Facility Maintenance System">
    
    <title>Page Title - FacilityFlow</title>
    
    <!-- IMPORTANT: Add Sidebar CSS -->
    <link rel="stylesheet" href="assets/css/sidebar.css">
    
    <!-- Your other stylesheets -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    
    <style>
        main {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            transition: margin-left var(--transition-speed) ease-in-out;
        }
        
        @media (max-width: 768px) {
            main {
                margin-left: var(--sidebar-width-collapsed);
            }
        }
    </style>
</head>
<body>
    <!-- IMPORTANT: Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Wrap page content in <main> -->
    <main class="main-content">
        <!-- Your page content goes here -->
        <h1>Page Title</h1>
        <!-- ... rest of your content ... -->
    </main>
    
    <!-- IMPORTANT: Add Sidebar JavaScript -->
    <script src="assets/js/sidebar.js"></script>
    
    <!-- Your other scripts -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/api.js"></script>
</body>
</html>
```

## Step-by-Step Integration for Each Page

### 1. dashboard.php

```php
<?php
// Your existing PHP code
include 'includes/sidebar.php'; // Add this include
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FacilityFlow</title>
    
    <!-- ADD THIS LINE -->
    <link rel="stylesheet" href="assets/css/sidebar.css">
    
    <!-- Your existing stylesheets -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <!-- ADD THIS LINE -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- WRAP YOUR EXISTING CONTENT -->
    <main class="main-content">
        <!-- Your existing dashboard content -->
    </main>
    
    <!-- ADD THIS BEFORE CLOSING </body> -->
    <script src="assets/js/sidebar.js"></script>
    
    <!-- Your existing scripts -->
    <script src="assets/js/main.js"></script>
</body>
</html>
```

### 2. reports.php

Same pattern as dashboard.php - add the sidebar include and wrap content in `<main>`.

### 3. users.php

Same pattern - add sidebar CSS, include sidebar.php, wrap content in `<main>`, add sidebar.js.

### 4. settings.php

Same pattern as above.

## Fixing Existing Content Display

If your content is currently affected by the sidebar, you have two options:

### Option A: Use CSS Wrapper (Recommended)

```css
/* In your existing stylesheet or add to sidebar.css */
main {
    margin-left: var(--sidebar-width);
    padding: 2rem;
    transition: margin-left var(--transition-speed) ease-in-out;
}

@media (max-width: 768px) {
    main {
        margin-left: var(--sidebar-width-collapsed);
    }
}
```

### Option B: Manually Adjust Content Container

If you're using a custom container name (not `<main>`):

```css
.your-content-class {
    margin-left: var(--sidebar-width);
    transition: margin-left var(--transition-speed) ease-in-out;
}

@media (max-width: 768px) {
    .your-content-class {
        margin-left: var(--sidebar-width-collapsed);
    }
}
```

## Integrating with Existing PHP Navigation

If you currently have navigation in your header.php or includes:

### Old way (remove this):
```php
<!-- REMOVE: <nav class="navbar">... </nav> -->
<!-- The sidebar replaces this -->
```

### New way (add to your layout):
```php
<?php 
    // In your header or layout file
    include 'includes/sidebar.php'; 
?>

<!-- Your header can stay but adjust positioning -->
<header class="top-header">
    <!-- Your header content (logo, user profile, etc.) -->
</header>
```

Then adjust header CSS:
```css
.top-header {
    margin-left: var(--sidebar-width);
    transition: margin-left var(--transition-speed) ease-in-out;
}

@media (max-width: 768px) {
    .top-header {
        margin-left: var(--sidebar-width-collapsed);
    }
}
```

## Combining with Existing Header

If you have both sidebar and header:

```
┌─ Sidebar ────────┐
│                  │ ┌─ Header ────────────────┐
│                  │ │                         │
│   Menu Items     │ │  Logo | User Profile   │
│                  │ │                         │
│                  │ └─────────────────────────┘
│                  │ ┌─ Main Content ──────────┐
│                  │ │                         │
│                  │ │  Your Page Content      │
│                  │ │                         │
│                  │ └─────────────────────────┘
└──────────────────┘
```

CSS structure:
```css
/* Sidebar already handles its own positioning (fixed) */
.top-header {
    margin-left: var(--sidebar-width);
}

main {
    margin-left: var(--sidebar-width);
}
```

## PHP Include Paths

Make sure your include paths are correct from each page's perspective:

```php
<!-- If file is in frontend/pages/dashboard.php -->
<?php include '../includes/sidebar.php'; ?>
<link rel="stylesheet" href="../assets/css/sidebar.css">
<script src="../assets/js/sidebar.js"></script>

<!-- If file is in frontend/dashboard.php -->
<?php include 'includes/sidebar.php'; ?>
<link rel="stylesheet" href="assets/css/sidebar.css">
<script src="assets/js/sidebar.js"></script>
```

## Setting Active Page

The sidebar automatically detects the current page using PHP. If it's not working:

### Option 1: Let PHP auto-detect (default)
```php
<?php
$current_page = basename($_SERVER['PHP_SELF']);
// sidebar.php automatically uses this
include 'includes/sidebar.php';
?>
```

### Option 2: Explicitly set active page with JavaScript
```javascript
<script src="assets/js/sidebar.js"></script>
<script>
    // Set active page after sidebar loads
    FacilityFlowSidebar.setActivePage('dashboard');
</script>
```

### Option 3: Pass PHP variable to sidebar
Modify [includes/sidebar.php](includes/sidebar.php) line 7:
```php
<?php
$current_page = isset($_GET['page']) ? $_GET['page'] : basename($_SERVER['PHP_SELF']);
// Now use: href="file.php?page=file"
?>
```

## Handling Special Cases

### Pages without sidebar
If you have pages that shouldn't show the sidebar (login, error pages, etc.):

```php
<?php if ($userIsLoggedIn): ?>
    <?php include 'includes/sidebar.php'; ?>
    <link rel="stylesheet" href="assets/css/sidebar.css">
<?php endif; ?>
```

Or use condition in sidebar.php:
```php
<?php 
if (isset($_SESSION['user_id'])) {
    include 'includes/sidebar.php';
}
?>
```

### Modal or overlay content
Make sure modals have higher z-index:
```css
.modal {
    z-index: 1100; /* Higher than sidebar's 1000 */
}
```

## Testing Integration

After integration, test:

1. **Desktop View**
   - [ ] Sidebar visible on left
   - [ ] Content properly spaced
   - [ ] Active menu item highlighted
   - [ ] Hover effects work
   - [ ] Clicking menu items works

2. **Tablet View (768px)**
   - [ ] Sidebar collapses to icon-only
   - [ ] Toggle button appears
   - [ ] Clicking toggle expands sidebar
   - [ ] Clicking menu item closes sidebar

3. **Mobile View (480px)**
   - [ ] Sidebar is minimal width
   - [ ] Toggle button is accessible
   - [ ] Touch interactions work smoothly
   - [ ] No content overlap

4. **Keyboard Navigation**
   - [ ] Tab through menu items
   - [ ] Arrow up/down navigates menu
   - [ ] Enter activates link
   - [ ] Escape closes sidebar (mobile)

5. **PHP Integration**
   - [ ] Current page is marked active
   - [ ] Links navigate correctly
   - [ ] PHP includes work from all pages
   - [ ] Logout link works

## Troubleshooting Integration

### Problem: Content is hidden or overlapped
**Solution**: Make sure main content is wrapped in `<main>` with proper CSS margins.

### Problem: Sidebar CSS not applying
**Solution**: Check CSS file path. If page is in `pages/` subfolder, use `../assets/css/sidebar.css`

### Problem: Active page not highlighting
**Solution**: Confirm filename in href matches actual PHP filename exactly.

### Problem: JavaScript errors in console
**Solution**: Check JavaScript file path. Verify `assets/js/sidebar.js` location is correct.

### Problem: Mobile toggle not showing
**Solution**: Ensure viewport meta tag is in `<head>`:
```html
<meta name="viewport" content="width=device-width, initial-scale=1.0">
```

### Problem: Sidebar overlaps content
**Solution**: Add proper margins to content wrapper:
```css
main {
    margin-left: var(--sidebar-width); /* Desktop */
}

@media (max-width: 768px) {
    main {
        margin-left: var(--sidebar-width-collapsed); /* Mobile */
    }
}
```

## Complete Page Template

Here's a complete ready-to-use template:

```php
<?php
// Authentication check
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FacilityFlow - School Facility Maintenance System">
    <title>Page Title - FacilityFlow</title>
    
    <!-- Sidebar CSS -->
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <!-- Your styles -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="page-container">
            <h1>Page Title</h1>
            <!-- Your content here -->
        </div>
    </main>

    <!-- Scripts -->
    <script src="assets/js/sidebar.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
```

## Summary

✅ **3 simple steps for each page:**
1. Add `<link rel="stylesheet" href="assets/css/sidebar.css">` to `<head>`
2. Add `<?php include 'includes/sidebar.php'; ?>` at start of `<body>`
3. Wrap content in `<main class="main-content">` and add `<script src="assets/js/sidebar.js"></script>` before `</body>`

That's it! The sidebar will work automatically.
