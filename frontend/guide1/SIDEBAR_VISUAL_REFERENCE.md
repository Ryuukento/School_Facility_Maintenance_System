# FacilityFlow Sidebar - Visual Reference & Features

## 🎨 Visual Layout

```
┌──────────────────────────────────────────────────────────────────┐
│                                                                  │
│  ┌──────────────────┐  ┌─────────────────────────────────────┐  │
│  │   SIDEBAR        │  │                                     │  │
│  │ (Fixed, 240px)   │  │         MAIN CONTENT               │  │
│  │                  │  │   (Margin-left: 240px)              │  │
│  │ ┌──────────────┐ │  │                                     │  │
│  │ │ ⬚ FacilityFlow│ │  │                                     │  │
│  │ └──────────────┘ │  │                                     │  │
│  │ ─────────────────│  │                                     │  │
│  │                  │  │                                     │  │
│  │ 📊 Dashboard ✓   │  │                                     │  │
│  │    (active)      │  │                                     │  │
│  │                  │  │                                     │  │
│  │ 📄 All Reports   │  │                                     │  │
│  │                  │  │                                     │  │
│  │ 📦 Inventory     │  │                                     │  │
│  │                  │  │                                     │  │
│  │ 👥 User Mgmt     │  │                                     │  │
│  │                  │  │                                     │  │
│  │ ─────────────────│  │                                     │  │
│  │                  │  │                                     │  │
│  │ ⚙️  Settings     │  │                                     │  │
│  │                  │  │                                     │  │
│  │ 🚪 Logout        │  │                                     │  │
│  └──────────────────┘  └─────────────────────────────────────┘  │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

## 🎯 Color Palette

### Sidebar Gradient
```
Linear Gradient (135deg)
├─ Start: #0f1419 (Dark Navy)
└─ End: #1a1f2e (Dark Blue-Black)
```

### Accent Colors
```
Primary Active: #2563eb (Bright Blue)
Active Border:  #3b82f6 (Light Blue)
Glow Effect:    rgba(37, 99, 235, 0.4) (Blue with transparency)
```

### Text Colors
```
Default Text:  #e5e7eb (Light Gray)
Hover Text:    #ffffff (White)
Active Text:   #ffffff (White)
```

### Interactive States
```
Hover Background:  rgba(255, 255, 255, 0.1) (Semi-transparent white)
Active Background: rgba(37, 99, 235, 0.15) (Semi-transparent blue)
```

## 📐 Dimensions

### Sidebar Widths
```
Desktop:      240px (full width with text)
Tablet:       70px  (icon-only, expandable)
Mobile:       60px  (icon-only, expandable)
Collapsed:    70px  (icon-only, desktop double-click)
```

### Component Sizes
```
Logo Icon:     28px × 28px
Nav Icon:      20px × 20px
Hamburger:     2.5rem × 2.5rem (40px)
System Name:   1.25rem font size
Nav Text:      0.95rem font size
```

### Spacing
```
Padding (Header):       1.5rem
Padding (Items):        0.875rem
Gap (Logo):             0.875rem
Gap (Menu Items):       0.5rem
Border Radius:          8px
```

## 🎬 Animations & Transitions

### Transition Properties
```css
Speed: 0.3s (default)
Timing: ease-in-out
Properties:
  - width (sidebar collapse)
  - background-color (hover)
  - transform (icons)
  - opacity (text in collapsed mode)
  - box-shadow (glow effect)
```

### Hover Effects
```
Icon:           Scale 1.0 → 1.1 (10% larger)
Background:     Transparent → rgba(255,255,255,0.1)
Text Color:     #e5e7eb → #ffffff
Duration:       0.3s smooth
```

### Active State Animation
```
Slide-in effect when link becomes active
Duration: 0.3s
Direction: Left to right
Accompanied by glow effect
```

### Hamburger Animation (Mobile)
```
Top span:      Rotate 45deg, translate up
Middle span:   Fade out (opacity 0)
Bottom span:   Rotate -45deg, translate down
Duration:      0.3s
```

## 📱 Responsive Breakpoints

### Desktop (≥ 769px)
```
Layout:         Full sidebar + content
Sidebar Width:  240px
Menu Display:   Icon + Text
Toggle Button:  Hidden
Collapse Mode:  Double-click header
Preference:     Saved to localStorage
Features:       Full functionality
```

### Tablet (480px - 768px)
```
Layout:         Collapsed sidebar + content
Sidebar Width:  70px
Menu Display:   Icon only (on hover: tooltip)
Toggle Button:  Visible (hamburger icon)
Mobile Open:    Expands on toggle click
Overlay:        Semi-transparent background
Features:       Closes sidebar after link click
Preference:     Not saved (defaults to collapsed)
```

### Mobile (< 480px)
```
Layout:         Icon-only sidebar + content
Sidebar Width:  60px (optimized)
Menu Display:   Icon only
Toggle Button:  Larger touch target
Mobile Open:    Full-width expansion
Overlay:        Dark overlay when open
Features:       Touch-friendly spacing
Preference:     Not saved
Hamburger:      Responsive sizing
```

## ✨ Visual Effects

### Glow Effect
```css
box-shadow: 0 0 20px rgba(37, 99, 235, 0.4)
Active items have subtle glow
Increases slightly on hover
Creates modern "neon" appearance
```

### Left Accent Bar
```
Position:       Left edge of active item
Width:          4px
Height:         60%
Color:          #3b82f6 (Light Blue)
Border Radius:  0 2px 2px 0 (right corners rounded)
Animated in:    When item becomes active
```

### Border Highlight
```
Type:           2px solid border
Color:          #3b82f6 (Light Blue)
Style:          Applied to active link
Radius:         8px (matches container)
Active Only:    Not present on inactive items
```

### Icon Scaling
```
Default:   1.0x (100%)
Hover:     1.1x (110%)
Duration:  0.3s
Easing:    ease-in-out
Smooth:    GPU accelerated
```

## 🎯 Active State Indicators

The active menu item displays multiple visual indicators:

```
┌─ Active Link Example ─────────────────┐
│                                       │
│ ┌ Blue Border                        │
│ │ ┌──────────────────────────────┐  │
│ │ │ 📊 Dashboard      ← Blue Icon │  │
│ │ └──────────────────────────────┘  │
│ │       ↑                            │
│ └ Blue Left Bar (4px)               │
│
│ Additional Visual Effects:
│ • Background: Light blue (rgba...)
│ • Text: White (bright)
│ • Glow: Blue box-shadow
│ • Icon: Bright blue color
│
└───────────────────────────────────────┘
```

## 🎵 Interaction Feedback

### Click Feedback
- Visual highlight appears immediately
- Active state persists
- Smooth transition to new state
- No lag or delay

### Hover Feedback
- Background brightens slightly
- Icon subtly enlarges
- Text color becomes brighter
- All changes smooth over 0.3s

### Toggle Feedback
- Hamburger icon animates
- Sidebar slides smoothly
- Overlay appears/fades
- Content adjusts smoothly

## ♿ Accessibility Features

### Visual Indicators
```
✓ Color contrast: 4.5:1 (WCAG AA)
✓ Focus ring: 2px solid border with offset
✓ Active state: Multiple visual cues (not just color)
✓ Icon + text: Redundant information coding
```

### Keyboard Navigation
```
Tab:        Focus next menu item
Shift+Tab:  Focus previous menu item
Arrow Up:   Move to previous menu item
Arrow Down: Move to next menu item
Enter:      Activate focused menu item
Space:      Activate focused menu item (alternate)
Escape:     Close sidebar (mobile only)
```

### Screen Reader Support
```
• Semantic HTML (<nav>, <aside>, etc.)
• ARIA labels on buttons
• Proper heading hierarchy
• Link purpose is clear
• Icons have text alternatives
```

## 🌓 Dark Theme Design

### Why Dark Theme?
```
✓ Reduces eye strain (especially in low light)
✓ Modern, professional appearance
✓ Popular in modern UIs (macOS, Windows 11)
✓ Good energy efficiency on OLED screens
✓ Blue accent color provides good contrast
```

### Color Theory
```
Background:  Dark (low luminance)
Accent:      Blue (cool, professional)
Text:        Light Gray (good readability)
Contrast:    High (meets WCAG standards)
Psychology: Trustworthy, modern, calm
```

## 📊 Menu Item Breakdown

### Navigation Items (Top Section)
```
1. Dashboard
   Icon: Grid/Dashboard icon
   Purpose: Main system overview
   Default Active: Yes
   
2. All Reports
   Icon: Document/File icon
   Purpose: View and manage maintenance reports
   
3. Inventory
   Icon: Box/Package icon
   Purpose: Equipment and supply management
   
4. User Management
   Icon: People/Users icon
   Purpose: User account administration
```

### Footer Items (Bottom Section)
```
5. Settings
   Icon: Gear/Cog icon
   Purpose: System configuration
   
6. Logout
   Icon: Exit/Door icon
   Purpose: User session termination
```

## 🔄 State Flow

```
                   ┌─────────────────┐
                   │   Page Loaded   │
                   └────────┬────────┘
                            │
                   ┌────────▼────────┐
                   │ PHP Detects     │
                   │ Current Page    │
                   └────────┬────────┘
                            │
                   ┌────────▼────────┐
                   │ Set Active      │
                   │ Class (HTML)    │
                   └────────┬────────┘
                            │
                   ┌────────▼────────┐
                   │ JavaScript      │
                   │ Applies Styles  │
                   └────────┬────────┘
                            │
                   ┌────────▼────────┐
                   │ Sidebar Visible │
                   │ with Active     │
                   │ Item Highlighted│
                   └─────────────────┘
```

## 🖱️ User Interactions

### Desktop User
```
1. Sidebar always visible
2. Hover over items for visual feedback
3. Click to navigate
4. Double-click header to toggle collapse
5. Preference saved automatically
6. Smooth animations provide feedback
```

### Tablet User
```
1. Sidebar appears as icon-only strip
2. Click hamburger to expand full sidebar
3. Click menu item to navigate
4. Sidebar auto-collapses after selection
5. Semi-transparent overlay for context
```

### Mobile User
```
1. Sidebar hidden as icon-only strip
2. Tap hamburger to reveal full menu
3. Tap item to navigate
4. Sidebar automatically closes
5. Large touch targets for ease
6. Smooth animations prevent disorientation
```

## 🎨 CSS Variables Reference

```css
/* Colors */
--sidebar-bg:              linear-gradient(...)
--sidebar-hover:           rgba(255, 255, 255, 0.1)
--sidebar-active:          #2563eb
--sidebar-active-border:   #3b82f6
--sidebar-text:            #e5e7eb
--sidebar-text-hover:      #ffffff
--glow-color:              rgba(37, 99, 235, 0.4)

/* Dimensions */
--sidebar-width:           240px
--sidebar-width-collapsed: 70px

/* Animation */
--transition-speed:        0.3s
```

## 🚀 Performance Metrics

```
CSS Size:        ~700 lines / ~20KB
JS Size:         ~400 lines / ~12KB
Total Impact:    ~32KB (minimal)

Animations:      GPU accelerated
Rendering:       60fps smooth
Memory Usage:    Minimal (no heavy libraries)
Load Time:       Negligible impact
```

---

**Visual Reference Complete**
All design specifications documented for implementation consistency.
