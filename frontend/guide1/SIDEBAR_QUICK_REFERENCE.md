# FacilityFlow Sidebar - Quick Reference Card

## 📋 Files At a Glance

| File | Location | Type | Purpose |
|------|----------|------|---------|
| `sidebar.php` | `includes/` | PHP | HTML structure & dynamic active state |
| `sidebar.css` | `assets/css/` | CSS | All styling & responsive design |
| `sidebar.js` | `assets/js/` | JS | Functionality & interactivity |

## ⚡ Quick Integration (Copy-Paste)

### For Your Dashboard Page:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FacilityFlow</title>
    
    <!-- STEP 1: Add this line -->
    <link rel="stylesheet" href="assets/css/sidebar.css">
    
    <!-- Your other styles -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    
    <!-- STEP 2: Add this line (PHP include) -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- STEP 3: Wrap your content -->
    <main class="main-content">
        <h1>Your Page Title</h1>
        <!-- Your existing content here -->
    </main>
    
    <!-- STEP 4: Add this line before closing body -->
    <script src="assets/js/sidebar.js"></script>
    
    <!-- Your other scripts -->
    <script src="assets/js/main.js"></script>

</body>
</html>
```

## 🎨 Customization Cheat Sheet

### Change Active Color
```css
/* At top of sidebar.css, find and change: */
--sidebar-active: #2563eb;         /* Change this hex code */
--sidebar-active-border: #3b82f6;  /* And this one */
```

### Change Sidebar Width
```css
--sidebar-width: 240px;            /* Change this */
--sidebar-width-collapsed: 70px;   /* And this */
```

### Change Animation Speed
```css
--transition-speed: 0.3s;          /* Change to 0.5s for slower */
```

### Change Background Gradient
```css
--sidebar-bg: linear-gradient(135deg, #color1 0%, #color2 100%);
```

## 🔧 JavaScript API Quick Reference

```javascript
// Highlight a menu item as active
FacilityFlowSidebar.setActivePage('dashboard');

// Toggle sidebar open/closed (mobile)
FacilityFlowSidebar.toggle();

// Open sidebar (mobile)
FacilityFlowSidebar.open();

// Close sidebar (mobile)
FacilityFlowSidebar.close();

// Check if sidebar is collapsed
if (FacilityFlowSidebar.isCollapsed()) {
    console.log('Sidebar is collapsed');
}

// Get current active page
const page = FacilityFlowSidebar.getActivePage();
console.log('Active:', page);
```

## 🎯 Menu Items in Sidebar

```
Top Section:
├─ Dashboard (📊 default active)
├─ All Reports (📄)
├─ Inventory (📦)
└─ User Management (👥)

Bottom Section:
├─ Settings (⚙️)
└─ Logout (🚪)
```

## 📱 Responsive Breakpoints

| Device | Width | Sidebar | Behavior |
|--------|-------|---------|----------|
| Desktop | 769px+ | 240px | Full with text |
| Tablet | 480-768px | 70px | Icon-only, toggleable |
| Mobile | <480px | 60px | Icon-only, toggleable |

## ✅ Testing Checklist

- [ ] Sidebar visible on desktop
- [ ] Content has proper left margin
- [ ] Active menu item is highlighted
- [ ] Hover effects smooth
- [ ] Mobile toggle appears at 768px
- [ ] Keyboard navigation works (arrows, enter)
- [ ] Escape key closes sidebar
- [ ] Sidebar preference saved (collapsed state)
- [ ] No JavaScript errors in console
- [ ] Looks good on phone, tablet, desktop
- [ ] Logout button works
- [ ] All links navigate correctly

## 🎨 Color Quick Reference

```
Primary Background: #0f1419 to #1a1f2e (dark gradient)
Active Item Color:  #2563eb (bright blue)
Active Border:      #3b82f6
Text Default:       #e5e7eb (light gray)
Text Active:        #ffffff (white)
Hover Background:   rgba(255,255,255,0.1)
Glow Effect:        rgba(37,99,235,0.4)
```

## 🔑 Key Classes

| Class | Purpose |
|-------|---------|
| `.sidebar` | Main sidebar container |
| `.nav-link` | Individual menu items |
| `.nav-link.active` | Currently active menu item |
| `.sidebar.collapsed` | Sidebar in icon-only mode |
| `.sidebar.mobile-open` | Sidebar open on mobile |
| `.main-content` | Your page content wrapper |

## 💾 Files Created Summary

```
frontend/
├── includes/sidebar.php                    ← Include in all pages
├── assets/css/sidebar.css                  ← Link in <head>
├── assets/js/sidebar.js                    ← Link before </body>
│
├── SIDEBAR_DOCUMENTATION.md                ← Complete reference
├── SIDEBAR_INTEGRATION_CHECKLIST.md        ← Quick start
├── SIDEBAR_INTEGRATION_STEPS.md            ← Step-by-step guide
├── SIDEBAR_VISUAL_REFERENCE.md             ← Design specs
├── SIDEBAR_EXAMPLE.html                    ← Working example
├── SIDEBAR_DELIVERY_SUMMARY.md             ← Project summary
└── SIDEBAR_QUICK_REFERENCE.md              ← This file
```

## 🚀 First Time Setup (5 Minutes)

1. **Copy files** (already done)
   - sidebar.php → includes/
   - sidebar.css → assets/css/
   - sidebar.js → assets/js/

2. **Update one page** (e.g., dashboard.php)
   - Add CSS link to `<head>`
   - Add sidebar.php `<?php include ... ?>`
   - Wrap content in `<main>`
   - Add JS script tag

3. **Test in browser**
   - Open page
   - Check sidebar appears
   - Click menu items
   - Test responsive (resize window)

4. **Repeat for other pages**
   - reports.php
   - users.php
   - settings.php
   - etc.

5. **Customize** (optional)
   - Adjust colors in CSS
   - Add/remove menu items
   - Change widths or animations

## 🎓 Learn More

- Full Documentation: `SIDEBAR_DOCUMENTATION.md`
- Integration Guide: `SIDEBAR_INTEGRATION_STEPS.md`
- Visual Design: `SIDEBAR_VISUAL_REFERENCE.md`
- Working Example: `SIDEBAR_EXAMPLE.html`

## ❓ Troubleshooting

### Sidebar not showing?
→ Check CSS file path is correct
→ Verify CSS is linked before closing `</head>`

### Content overlapping sidebar?
→ Wrap content in `<main class="main-content">`
→ Check CSS is loaded (DevTools → Elements → Styles)

### Menu items not highlighting?
→ Check PHP filename matches href in sidebar.php
→ Verify current_page variable is set correctly

### Mobile toggle not visible?
→ Add viewport meta tag: `<meta name="viewport">`
→ Check window width is actually ≤ 768px

### JavaScript not working?
→ Check sidebar.js file is linked
→ Open DevTools console (F12) for errors
→ Verify file path is correct

## 📞 Quick Support

**Problem**: Links don't work
**Solution**: Check href paths match actual PHP filenames

**Problem**: Sidebar won't collapse on desktop
**Solution**: Double-click the sidebar header to toggle

**Problem**: Preference not saving
**Solution**: Check browser localStorage is enabled
**Note**: Preference only saves on desktop (not mobile)

**Problem**: Looks broken on mobile
**Solution**: Refresh page (cache issue)
**Or**: Hard refresh with Ctrl+Shift+R or Cmd+Shift+R

## 🎉 You're All Set!

The sidebar is ready to integrate. Start with step-by-step in:
→ **SIDEBAR_INTEGRATION_STEPS.md**

---

**Quick Reference Card v1.0**
February 11, 2026
