# FacilityFlow Sidebar Navigation Documentation

## Overview
Modern, responsive dark-themed sidebar navigation for the School Facility Maintenance System. Features smooth animations, mobile responsiveness, keyboard accessibility, and localStorage preference management.

## Files Included

### 1. **sidebar.php** - HTML Structure
Location: `frontend/includes/sidebar.php`

The main sidebar component that can be included in any PHP page using:
```php
<?php include 'includes/sidebar.php'; ?>
```

**Features:**
- Dynamic active state detection based on current PHP file
- SVG icons (no external icon library required)
- Mobile toggle button with hamburger animation
- Accessible HTML structure
- ARIA labels for screen readers

### 2. **sidebar.css** - Styling
Location: `frontend/assets/css/sidebar.css`

Complete styling including:
- Dark gradient background
- Smooth hover and active effects
- Glow effect on active items
- Responsive design (collapses to icon-only on mobile)
- CSS variables for easy customization
- Scrollbar styling
- Print-friendly styles

### 3. **sidebar.js** - Functionality
Location: `frontend/assets/js/sidebar.js`

JavaScript functionality:
- Active state management
- Mobile toggle functionality
- Click handlers for navigation
- LocalStorage preference persistence
- Keyboard accessibility (Arrow keys, Enter)
- Responsive mode detection
- Public API for external control

## Integration Steps

### Step 1: Include Sidebar in HTML/PHP Pages

Add this to your page header (inside `<head>`):
```html
<link rel="stylesheet" href="assets/css/sidebar.css">
```

Add this before closing `</body>`:
```html
<script src="assets/js/sidebar.js"></script>
```

Add this in the `<body>` where you want the sidebar:
```php
<?php include 'includes/sidebar.php'; ?>
```

### Step 2: Adjust Main Content Area

Wrap your main content in an element with class `main-content`, `content`, or use semantic `<main>`:

```html
<main class="main-content">
    <!-- Your page content goes here -->
</main>
```

The CSS automatically adds left margin (240px on desktop, 70px on mobile) to accommodate the sidebar.

### Step 3: Update Active Links (If Needed)

The sidebar automatically detects the current page using PHP `$_SERVER['PHP_SELF']`. For custom detection:

```javascript
// Programmatically set active page
FacilityFlowSidebar.setActivePage('dashboard');
```

## Usage Examples

### Adding New Menu Items

Edit `sidebar.php` and add to the `.nav-menu` in the navigation section:

```html
<li class="nav-item">
    <a href="new-page.php" class="nav-link" data-page="new-page">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <!-- SVG path here -->
        </svg>
        <span class="nav-text">New Page</span>
    </a>
</li>
```

### Using the JavaScript API

The sidebar exposes a public API via `window.FacilityFlowSidebar`:

```javascript
// Set active page programmatically
FacilityFlowSidebar.setActivePage('reports');

// Toggle sidebar visibility
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
const activePage = FacilityFlowSidebar.getActivePage();
console.log('Current page:', activePage);
```

### Custom Styling

All colors are defined as CSS variables at the top of `sidebar.css`:

```css
:root {
    --sidebar-bg: linear-gradient(135deg, #0f1419 0%, #1a1f2e 100%);
    --sidebar-active: #2563eb;
    --sidebar-text: #e5e7eb;
    --sidebar-width: 240px;
    --transition-speed: 0.3s;
    /* ... more variables ... */
}
```

Override these in your own stylesheet to customize colors:

```css
:root {
    --sidebar-active: #10b981; /* Change active color to green */
    --sidebar-width: 280px;    /* Change sidebar width */
}
```

## Responsive Behavior

### Desktop (769px and above)
- Full-width sidebar (240px)
- Collapsed on double-click of header (persisted in localStorage)
- Icon-and-text display

### Tablets & Small Laptops (769px - below)
- Icon-only sidebar (70px)
- Hamburger toggle button visible
- Click toggle to expand to full width
- Sidebar closes after clicking a link
- Overlay appears when sidebar is open

### Mobile Phones (480px and below)
- Smaller sidebar width (60px)
- Optimized touch targets
- Larger hamburger button

## Accessibility Features

✓ **Semantic HTML** - Uses `<nav>`, `<aside>`, proper heading hierarchy
✓ **ARIA Labels** - Toggle button has `aria-label`
✓ **Keyboard Navigation**:
  - Tab through links
  - Arrow Up/Down to navigate menu items
  - Enter/Space to activate links
  - Escape to close sidebar (mobile)
✓ **Focus Indicators** - Visible outline on focus
✓ **Color Contrast** - Meets WCAG AA standards
✓ **Screen Reader Friendly** - Proper semantic structure

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Android)

## Customization Guide

### Change Sidebar Width
Edit the CSS variable at the top of `sidebar.css`:
```css
--sidebar-width: 240px;
--sidebar-width-collapsed: 70px;
```

### Change Colors
```css
--sidebar-bg: linear-gradient(135deg, #your-color-1 0%, #your-color-2 100%);
--sidebar-active: #your-accent-color;
--sidebar-text: #your-text-color;
```

### Change Animation Speed
```css
--transition-speed: 0.5s; /* Slower animations */
```

### Change Breakpoints
Edit the media queries in `sidebar.css`:
```css
@media (max-width: 992px) { /* Change from 768px */
    /* responsive styles */
}
```

## Troubleshooting

### Sidebar Not Showing
1. Check CSS file is included before closing `</head>`
2. Check JavaScript file is included before closing `</body>`
3. Verify `sidebar.php` include path is correct
4. Check browser console for errors

### Active State Not Working
1. Ensure the page filename matches the href in sidebar.php
2. Check that PHP is correctly comparing `$current_page`
3. Use browser DevTools to verify the "active" class is present

### Content Overlapping Sidebar
1. Wrap main content in `<main class="main-content">` or similar
2. Verify CSS file is loaded (check DevTools Styles panel)
3. Ensure styles aren't being overridden by other CSS

### Mobile Toggle Not Appearing
1. Verify viewport meta tag is in page `<head>`:
   ```html
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   ```
2. Check media query breakpoint in sidebar.css
3. Verify JavaScript file is loaded

### Sidebar Text Not Hiding on Mobile
1. Check that viewport is actually ≤ 768px
2. Clear browser cache (might be using old CSS)
3. Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)

## Performance Tips

1. **Lazy Load Icons** - If using many custom SVGs, consider lazy loading
2. **CSS Variables** - Reduces need for preprocessors
3. **Hardware Acceleration** - Smooth transitions are GPU accelerated
4. **LocalStorage** - Remembers user preference (sidebar collapsed state)

## Future Enhancements

- Add sidebar collapse animation (slide-in/out)
- Add sub-menu support (nested items)
- Add user profile section at bottom
- Add search functionality
- Add notification badges to menu items
- Dark/Light theme toggle
- Custom theme builder UI

## Support & Maintenance

For issues or feature requests:
1. Check the troubleshooting section above
2. Review browser console for JavaScript errors
3. Validate HTML structure in DevTools
4. Check CSS is properly cascading in DevTools Styles panel

## Version

**Version 1.0.0** - Initial Release
- February 11, 2026
- Compatible with PHP 7.4+
- No external dependencies (no jQuery, Bootstrap, etc.)

## License

This component is part of the School Facility Maintenance System project.
