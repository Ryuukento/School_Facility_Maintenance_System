# Color Scheme Documentation
## School Facility Maintenance Reporting System

---

## Overview

This comprehensive color scheme provides a professional **Blue & Green** theme designed specifically for a School Facility Maintenance Reporting System. The system uses:

- **Primary Blue (#2563eb)** - Headers, primary buttons, navigation
- **Secondary Green (#059669)** - Success states, completed tasks
- **Accent Amber (#f59e0b)** - Pending items, warnings
- **Danger Red (#dc2626)** - Urgent repairs, critical issues

---

## CSS Variables

All colors are defined as CSS variables in `:root` for easy theming and consistency.

### Primary Colors
```css
--primary: #2563eb;              /* Main blue */
--primary-light: #3b82f6;        /* Lighter blue */
--primary-dark: #1d4ed8;         /* Darker blue */
--primary-darker: #1e40af;       /* Very dark blue */
--primary-bg: #eff6ff;           /* Light blue background */
```

### Secondary Colors (Green/Success)
```css
--secondary: #059669;            /* Main green */
--secondary-light: #10b981;      /* Lighter green */
--secondary-lighter: #a7f3d0;    /* Very light green */
--secondary-dark: #047857;       /* Darker green */
--secondary-bg: #ecfdf5;         /* Light green background */
```

### Accent Colors (Amber/Warning)
```css
--accent: #f59e0b;               /* Main amber */
--accent-light: #fbbf24;         /* Lighter amber */
--accent-dark: #d97706;          /* Darker amber */
--accent-bg: #fffbeb;            /* Light amber background */
```

### Danger Colors (Red)
```css
--danger: #dc2626;               /* Main red */
--danger-light: #ef4444;         /* Lighter red */
--danger-lighter: #fca5a5;       /* Very light red */
--danger-dark: #b91c1c;          /* Darker red */
--danger-bg: #fef2f2;            /* Light red background */
```

### Text Colors
```css
--text-primary: #1e293b;         /* Primary text (dark) */
--text-secondary: #64748b;       /* Secondary text (medium) */
--text-tertiary: #94a3b8;        /* Tertiary text (light) */
--text-inverse: #ffffff;         /* Text on colored backgrounds */
```

### Background Colors
```css
--bg-primary: #f8fafc;           /* Main background */
--bg-secondary: #f1f5f9;         /* Secondary background */
--bg-tertiary: #e2e8f0;          /* Tertiary background */
--bg-white: #ffffff;             /* White surfaces */
```

### Border Colors
```css
--border-light: #e2e8f0;         /* Light borders */
--border-regular: #cbd5e1;       /* Regular borders */
--border-dark: #94a3b8;          /* Dark borders */
```

---

## Background Color Utilities

### Basic Usage
```html
<!-- Primary backgrounds -->
<div class="bg-primary">Blue background</div>
<div class="bg-primary-light">Light blue background</div>
<div class="bg-primary-dark">Dark blue background</div>
<div class="bg-primary-bg">Light blue background color</div>

<!-- Secondary backgrounds -->
<div class="bg-secondary">Green background</div>
<div class="bg-secondary-light">Light green background</div>
<div class="bg-secondary-dark">Dark green background</div>
<div class="bg-secondary-bg">Light green background color</div>

<!-- Accent backgrounds -->
<div class="bg-accent">Amber background</div>
<div class="bg-accent-light">Light amber background</div>
<div class="bg-accent-dark">Dark amber background</div>
<div class="bg-accent-bg">Light amber background</div>

<!-- Danger backgrounds -->
<div class="bg-danger">Red background</div>
<div class="bg-danger-light">Light red background</div>
<div class="bg-danger-dark">Dark red background</div>
<div class="bg-danger-bg">Light red background</div>

<!-- Neutral backgrounds -->
<div class="bg-white">White background</div>
<div class="bg-gray-light">Light gray background</div>
<div class="bg-gray-lighter">Lighter gray background</div>
```

---

## Text Color Utilities

### Basic Usage
```html
<p class="text-primary">Primary colored text</p>
<p class="text-secondary">Secondary colored text</p>
<p class="text-accent">Accent colored text</p>
<p class="text-danger">Danger colored text</p>
<p class="text-dark">Dark text</p>
<p class="text-muted">Muted text</p>
<p class="text-light">Light text</p>
<p class="text-white">White text</p>
```

---

## Button Styles

### Filled Buttons
```html
<!-- Primary Button -->
<button class="btn btn-primary">Primary Button</button>

<!-- Secondary Button -->
<button class="btn btn-secondary">Secondary Button</button>

<!-- Accent Button -->
<button class="btn btn-accent">Accent Button</button>

<!-- Danger Button -->
<button class="btn btn-danger">Danger Button</button>
```

### Outline Buttons
```html
<button class="btn btn-outline-primary">Outline Primary</button>
<button class="btn btn-outline-secondary">Outline Secondary</button>
```

### Button States
```html
<!-- Hover state (automatic on :hover) -->
<!-- Focus state (automatic on :focus) -->
<!-- Active state (automatic on :active) -->
<!-- Disabled state -->
<button class="btn btn-primary" disabled>Disabled Button</button>
```

### Button Features
- **Hover effect**: Color darkens + elevation shadow + slight upward movement
- **Focus state**: 4px outline shadow in theme color
- **Active state**: Returns to normal position with smaller shadow
- **Disabled state**: 50% opacity, no hover effects

---

## Status Badges

### Pending Badge
```html
<span class="badge badge-pending">Pending</span>
```
**Use for:** Status requiring action, awaiting review

### In Progress Badge
```html
<span class="badge badge-progress">In Progress</span>
<!-- OR -->
<span class="badge badge-in-progress">In Progress</span>
```
**Use for:** Actively being worked on

### Completed Badge
```html
<span class="badge badge-completed">Completed</span>
<!-- OR -->
<span class="badge badge-success">Success</span>
```
**Use for:** Finished, successful operations

### Urgent Badge
```html
<span class="badge badge-urgent">Urgent</span>
```
**Use for:** Requires immediate attention

### Critical Badge
```html
<span class="badge badge-critical">Critical</span>
```
**Use for:** Critical system status, severe issues

### Priority Badges
```html
<span class="badge badge-low">Low</span>
<span class="badge badge-medium">Medium</span>
<span class="badge badge-high">High</span>
```
**Use for:** Maintenance priority levels

---

## Status Indicators

Interactive status indicators with animated pulse for active states:

```html
<!-- Pending status -->
<div class="status-indicator pending">● Pending Review</div>

<!-- In Progress status (pulsing animation) -->
<div class="status-indicator progress">● In Progress</div>

<!-- Completed status -->
<div class="status-indicator completed">● Completed</div>

<!-- Urgent status (faster pulse) -->
<div class="status-indicator urgent">● Urgent</div>
```

---

## Alert Boxes

### Primary Alert
```html
<div class="alert-box alert-primary">
    <div class="alert-icon">ℹ️</div>
    <div class="alert-content">
        <strong>Information</strong>
        Message content here...
    </div>
</div>
```

### Secondary Alert (Success)
```html
<div class="alert-box alert-secondary">
    <div class="alert-icon">✓</div>
    <div class="alert-content">
        <strong>Success</strong>
        Operation completed successfully
    </div>
</div>
```

### Warning Alert
```html
<div class="alert-box alert-warning">
    <div class="alert-icon">⚠</div>
    <div class="alert-content">
        <strong>Warning</strong>
        Please review before proceeding
    </div>
</div>
```

### Danger Alert
```html
<div class="alert-box alert-danger">
    <div class="alert-icon">✕</div>
    <div class="alert-content">
        <strong>Error</strong>
        An error occurred. Please try again.
    </div>
</div>
```

---

## Border Utilities

### Border Color Classes
```html
<!-- Borders with specific colors -->
<div class="border-primary">Primary border</div>
<div class="border-secondary">Secondary border</div>
<div class="border-accent">Accent border</div>
<div class="border-danger">Danger border</div>
<div class="border-light">Light border</div>
```

### Directional Borders
```html
<!-- Top border -->
<div class="border-top-primary">Primary top border</div>

<!-- Left borders (common for cards) -->
<div class="border-left-primary">Primary left border</div>
<div class="border-left-secondary">Secondary left border</div>
<div class="border-left-accent">Accent left border</div>
<div class="border-left-danger">Danger left border</div>

<!-- Bottom border -->
<div class="border-bottom-primary">Primary bottom border</div>
<div class="border-bottom-secondary">Secondary bottom border</div>
```

---

## Maintenance Report Card Component

### Complete Example
```html
<div class="report-card">
    <div class="report-card-header">
        <h3 class="report-card-title">Broken Light Fixture</h3>
        <div class="report-card-priority">
            <span class="badge badge-medium">Medium</span>
            <span class="badge badge-in-progress">In Progress</span>
        </div>
    </div>
    <div class="report-card-body">
        <div class="report-card-location">📍 Building A - Room 101</div>
        <div class="report-card-description">
            The ceiling light fixture is not functioning properly.
        </div>
    </div>
    <div class="report-card-meta">
        <span>Created: Feb 4, 2026</span>
        <span>Assigned to: John Smith</span>
    </div>
    <div class="report-card-action">
        <button class="btn btn-primary" style="width: 100%;">View Details</button>
    </div>
</div>
```

### Card Features
- **Hover effect**: Lifts up with increased shadow
- **Responsive**: Adapts to mobile screens
- **Flexible styling**: Easy to customize with utility classes
- **Accessibility**: Proper semantic HTML structure

---

## Form Elements

### Text Input with Focus States
```html
<input type="text" class="form-control" placeholder="Enter text...">
```

### Form States
```html
<!-- Normal state -->
<input type="text" class="form-control">

<!-- Focus state (automatic) -->
<input type="text" class="form-control">

<!-- Success state -->
<input type="text" class="form-control success">

<!-- Error state -->
<input type="text" class="form-control error">

<!-- Warning state -->
<input type="text" class="form-control warning">
```

### Features
- **Focus**: Blue border + light blue shadow
- **Success**: Green border + light green shadow
- **Error**: Red border + light red shadow
- **Warning**: Amber border + light amber shadow

---

## Colored Cards

### Card Types
```html
<!-- Primary card -->
<div class="card-colored primary">
    <h3>Primary Card</h3>
    <p>Content goes here</p>
</div>

<!-- Secondary card -->
<div class="card-colored secondary">
    <h3>Secondary Card</h3>
    <p>Content goes here</p>
</div>

<!-- Accent card -->
<div class="card-colored accent">
    <h3>Accent Card</h3>
    <p>Content goes here</p>
</div>

<!-- Danger card -->
<div class="card-colored danger">
    <h3>Danger Card</h3>
    <p>Content goes here</p>
</div>
```

---

## Best Practices

### Color Usage Guide

1. **Primary Blue (#2563eb)**
   - Navigation bars
   - Primary action buttons
   - Links and important text
   - Main headings
   - Form focus states

2. **Secondary Green (#059669)**
   - Success messages
   - Completed report badges
   - Positive confirmations
   - Active status indicators

3. **Accent Amber (#f59e0b)**
   - Pending status
   - Warning messages
   - Items needing attention
   - Medium priority items

4. **Danger Red (#dc2626)**
   - Error messages
   - Critical issues
   - Urgent priority badges
   - Delete/cancel actions

### Contrast & Accessibility

- All text colors meet WCAG AA contrast standards
- Use `.text-white` on dark backgrounds
- Use `.text-primary` on light backgrounds
- Test color combinations with color blindness simulators

### Responsive Behavior

- All components scale properly on mobile
- Touch-friendly button sizes (minimum 44px)
- Badges stack on small screens
- Cards adapt to single-column layout

---

## Color Scheme File

The complete color scheme is defined in:
```
frontend/assets/css/color-scheme.css
```

This file is automatically included in all pages via `header.php`.

---

## Demo Page

View the complete color scheme guide with all components and examples:
```
http://localhost/School_Facility_Maintenance_System/frontend/pages/color-guide.php
```

---

## Customization

To change the color scheme, modify the CSS variables in `color-scheme.css`:

```css
:root {
    /* Change primary color */
    --primary: #your-color;
    --primary-light: #lighter-shade;
    --primary-dark: #darker-shade;
    /* ... and so on */
}
```

All components will automatically update to use the new colors.

---

## Version History

- **v1.0** (Feb 6, 2026) - Initial professional color scheme with Blue & Green theme
  - 4 primary color families
  - 25+ utility classes
  - Complete component library
  - Accessibility optimized
  - Responsive design

---

## Support

For questions or improvements to the color scheme, refer to:
- `frontend/assets/css/color-scheme.css` - Main stylesheet
- `frontend/pages/color-guide.php` - Interactive demo
- `frontend/assets/css/styles.css` - Base styles

