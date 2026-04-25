<?php
$pageTitle = 'Color Scheme Guide - SFMS';
// Don't include header.php since this is a demo page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/color-scheme.css">
        <link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/color-guide.inline.css">
</head>
<body>
    <div class="guide-container">
        <!-- Header -->
        <div class="guide-header">
            <h1>🎨 Color Scheme Guide</h1>
            <p>School Facility Maintenance Reporting System - Professional Blue & Green Theme</p>
        </div>

        <!-- Primary Colors -->
        <div class="section">
            <h2 class="section-title">Primary Colors</h2>
            <div class="color-grid">
                <div class="color-card">
                    <div class="color-sample" style="background-color: #2563eb;">Blue</div>
                    <div class="color-info">
                        <div class="color-name">Primary Blue</div>
                        <div class="color-hex">#2563eb</div>
                        <div class="var-code">--primary</div>
                    </div>
                </div>
                <div class="color-card">
                    <div class="color-sample" style="background-color: #3b82f6;">Light Blue</div>
                    <div class="color-info">
                        <div class="color-name">Primary Light</div>
                        <div class="color-hex">#3b82f6</div>
                        <div class="var-code">--primary-light</div>
                    </div>
                </div>
                <div class="color-card">
                    <div class="color-sample" style="background-color: #1d4ed8;">Dark Blue</div>
                    <div class="color-info">
                        <div class="color-name">Primary Dark</div>
                        <div class="color-hex">#1d4ed8</div>
                        <div class="var-code">--primary-dark</div>
                    </div>
                </div>
                <div class="color-card">
                    <div class="color-sample" style="background-color: #eff6ff; color: #2563eb;">Light BG</div>
                    <div class="color-info">
                        <div class="color-name">Primary Background</div>
                        <div class="color-hex">#eff6ff</div>
                        <div class="var-code">--primary-bg</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Colors -->
        <div class="section">
            <h2 class="section-title">Secondary Colors (Success/Green)</h2>
            <div class="color-grid">
                <div class="color-card">
                    <div class="color-sample" style="background-color: #059669;">Green</div>
                    <div class="color-info">
                        <div class="color-name">Secondary Green</div>
                        <div class="color-hex">#059669</div>
                        <div class="var-code">--secondary</div>
                    </div>
                </div>
                <div class="color-card">
                    <div class="color-sample" style="background-color: #10b981;">Light Green</div>
                    <div class="color-info">
                        <div class="color-name">Secondary Light</div>
                        <div class="color-hex">#10b981</div>
                        <div class="var-code">--secondary-light</div>
                    </div>
                </div>
                <div class="color-card">
                    <div class="color-sample" style="background-color: #047857;">Dark Green</div>
                    <div class="color-info">
                        <div class="color-name">Secondary Dark</div>
                        <div class="color-hex">#047857</div>
                        <div class="var-code">--secondary-dark</div>
                    </div>
                </div>
                <div class="color-card">
                    <div class="color-sample" style="background-color: #ecfdf5; color: #059669;">Light BG</div>
                    <div class="color-info">
                        <div class="color-name">Secondary Background</div>
                        <div class="color-hex">#ecfdf5</div>
                        <div class="var-code">--secondary-bg</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accent Colors -->
        <div class="section">
            <h2 class="section-title">Accent Colors (Amber/Warning)</h2>
            <div class="color-grid">
                <div class="color-card">
                    <div class="color-sample" style="background-color: #f59e0b;">Amber</div>
                    <div class="color-info">
                        <div class="color-name">Accent Amber</div>
                        <div class="color-hex">#f59e0b</div>
                        <div class="var-code">--accent</div>
                    </div>
                </div>
                <div class="color-card">
                    <div class="color-sample" style="background-color: #fbbf24;">Light Amber</div>
                    <div class="color-info">
                        <div class="color-name">Accent Light</div>
                        <div class="color-hex">#fbbf24</div>
                        <div class="var-code">--accent-light</div>
                    </div>
                </div>
                <div class="color-card">
                    <div class="color-sample" style="background-color: #d97706;">Dark Amber</div>
                    <div class="color-info">
                        <div class="color-name">Accent Dark</div>
                        <div class="color-hex">#d97706</div>
                        <div class="var-code">--accent-dark</div>
                    </div>
                </div>
                <div class="color-card">
                    <div class="color-sample" style="background-color: #fffbeb; color: #f59e0b;">Light BG</div>
                    <div class="color-info">
                        <div class="color-name">Accent Background</div>
                        <div class="color-hex">#fffbeb</div>
                        <div class="var-code">--accent-bg</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danger Colors -->
        <div class="section">
            <h2 class="section-title">Danger Colors (Red)</h2>
            <div class="color-grid">
                <div class="color-card">
                    <div class="color-sample" style="background-color: #dc2626;">Red</div>
                    <div class="color-info">
                        <div class="color-name">Danger Red</div>
                        <div class="color-hex">#dc2626</div>
                        <div class="var-code">--danger</div>
                    </div>
                </div>
                <div class="color-card">
                    <div class="color-sample" style="background-color: #ef4444;">Light Red</div>
                    <div class="color-info">
                        <div class="color-name">Danger Light</div>
                        <div class="color-hex">#ef4444</div>
                        <div class="var-code">--danger-light</div>
                    </div>
                </div>
                <div class="color-card">
                    <div class="color-sample" style="background-color: #b91c1c;">Dark Red</div>
                    <div class="color-info">
                        <div class="color-name">Danger Dark</div>
                        <div class="color-hex">#b91c1c</div>
                        <div class="var-code">--danger-dark</div>
                    </div>
                </div>
                <div class="color-card">
                    <div class="color-sample" style="background-color: #fef2f2; color: #dc2626;">Light BG</div>
                    <div class="color-info">
                        <div class="color-name">Danger Background</div>
                        <div class="color-hex">#fef2f2</div>
                        <div class="var-code">--danger-bg</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buttons Demo -->
        <div class="section">
            <h2 class="section-title">Button Styles</h2>
            <div class="component-demo">
                <div class="component-title">Filled Buttons</div>
                <div class="button-group">
                    <button class="btn btn-primary">Primary Button</button>
                    <button class="btn btn-secondary">Secondary Button</button>
                    <button class="btn btn-accent">Accent Button</button>
                    <button class="btn btn-danger">Danger Button</button>
                </div>
                
                <div class="component-title" style="margin-top: 20px;">Outline Buttons</div>
                <div class="button-group">
                    <button class="btn btn-outline-primary">Primary Outline</button>
                    <button class="btn btn-outline-secondary">Secondary Outline</button>
                </div>

                <div class="component-title" style="margin-top: 20px;">Disabled State</div>
                <div class="button-group">
                    <button class="btn btn-primary" disabled>Disabled Primary</button>
                    <button class="btn btn-secondary" disabled>Disabled Secondary</button>
                </div>
            </div>
        </div>

        <!-- Status Badges -->
        <div class="section">
            <h2 class="section-title">Status Badges</h2>
            <div class="component-demo">
                <div class="badge-row">
                    <span class="badge badge-pending">Pending</span>
                    <span class="badge badge-progress">In Progress</span>
                    <span class="badge badge-completed">Completed</span>
                    <span class="badge badge-urgent">Urgent</span>
                    <span class="badge badge-critical">Critical</span>
                </div>
                
                <div class="component-title">Priority Badges</div>
                <div class="badge-row">
                    <span class="badge badge-low">Low</span>
                    <span class="badge badge-medium">Medium</span>
                    <span class="badge badge-high">High</span>
                </div>
            </div>
        </div>

        <!-- Status Indicators -->
        <div class="section">
            <h2 class="section-title">Status Indicators</h2>
            <div class="component-demo">
                <div class="status-indicator pending">● Pending Review</div>
                <div class="status-indicator progress">● In Progress</div>
                <div class="status-indicator completed">● Completed</div>
                <div class="status-indicator urgent">● Urgent - Requires Action</div>
            </div>
        </div>

        <!-- Alert Boxes -->
        <div class="section">
            <h2 class="section-title">Alert Boxes</h2>
            <div class="alert-box alert-primary">
                <div class="alert-icon">ℹ️</div>
                <div class="alert-content">
                    <strong>Information</strong>
                    This is a primary information alert message
                </div>
            </div>
            <div class="alert-box alert-secondary">
                <div class="alert-icon">✓</div>
                <div class="alert-content">
                    <strong>Success</strong>
                    Operation completed successfully
                </div>
            </div>
            <div class="alert-box alert-warning">
                <div class="alert-icon">⚠</div>
                <div class="alert-content">
                    <strong>Warning</strong>
                    Please review before proceeding
                </div>
            </div>
            <div class="alert-box alert-danger">
                <div class="alert-icon">✕</div>
                <div class="alert-content">
                    <strong>Error</strong>
                    An error occurred. Please try again.
                </div>
            </div>
        </div>

        <!-- Report Card Component -->
        <div class="section">
            <h2 class="section-title">Maintenance Report Card Component</h2>
            <div class="demo-grid">
                <div class="report-card">
                    <div class="report-card-header">
                        <h3 class="report-card-title">Broken Light Fixture</h3>
                        <div class="report-card-priority">
                            <span class="badge badge-medium">Medium</span>
                            <span class="badge badge-progress">In Progress</span>
                        </div>
                    </div>
                    <div class="report-card-body">
                        <div class="report-card-location">📍 Building A - Room 101</div>
                        <div class="report-card-description">The ceiling light fixture is not functioning properly. Bulb has been tested and is working, so the issue appears to be in the wiring or socket connection.</div>
                    </div>
                    <div class="report-card-meta">
                        <span>Created: Feb 4, 2026</span>
                        <span>Assigned to: John Smith</span>
                    </div>
                    <div class="report-card-action">
                        <button class="btn btn-primary" style="width: 100%;">View Details</button>
                    </div>
                </div>

                <div class="report-card">
                    <div class="report-card-header">
                        <h3 class="report-card-title">Leaking Faucet</h3>
                        <div class="report-card-priority">
                            <span class="badge badge-high">High</span>
                            <span class="badge badge-pending">Pending</span>
                        </div>
                    </div>
                    <div class="report-card-body">
                        <div class="report-card-location">📍 Building B - 2nd Floor Bathroom</div>
                        <div class="report-card-description">Bathroom faucet is constantly dripping water. Estimated water waste is about 5 gallons per day. Requires urgent attention.</div>
                    </div>
                    <div class="report-card-meta">
                        <span>Created: Feb 5, 2026</span>
                        <span>Assigned: Not yet</span>
                    </div>
                    <div class="report-card-action">
                        <button class="btn btn-primary" style="width: 100%;">View Details</button>
                    </div>
                </div>

                <div class="report-card">
                    <div class="report-card-header">
                        <h3 class="report-card-title">Door Lock Repair</h3>
                        <div class="report-card-priority">
                            <span class="badge badge-high">High</span>
                            <span class="badge badge-completed">Completed</span>
                        </div>
                    </div>
                    <div class="report-card-body">
                        <div class="report-card-location">📍 Building A - Main Entrance</div>
                        <div class="report-card-description">Main entrance door lock was malfunctioning. The lock has been repaired and is now functioning normally.</div>
                    </div>
                    <div class="report-card-meta">
                        <span>Created: Feb 3, 2026</span>
                        <span>Fixed by: John Smith</span>
                    </div>
                    <div class="report-card-action">
                        <button class="btn btn-secondary" style="width: 100%;">View Details</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Text & Background Utilities -->
        <div class="section">
            <h2 class="section-title">Text & Background Utilities</h2>
            <div class="demo-grid">
                <div style="padding: 20px; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div class="text-primary">Text Primary Color</div>
                    <div class="text-secondary">Text Secondary Color</div>
                    <div class="text-danger">Text Danger Color</div>
                    <div class="text-muted">Text Muted Color</div>
                </div>
                <div style="padding: 20px; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div class="bg-primary" style="padding: 10px; margin-bottom: 10px; border-radius: 4px; color: white;">Primary Background</div>
                    <div class="bg-secondary-bg" style="padding: 10px; margin-bottom: 10px; border-radius: 4px; color: #059669;">Secondary BG</div>
                    <div class="bg-accent-bg" style="padding: 10px; border-radius: 4px; color: #f59e0b;">Accent Background</div>
                </div>
            </div>
        </div>

        <!-- Border Utilities -->
        <div class="section">
            <h2 class="section-title">Border Utilities</h2>
            <div class="demo-grid">
                <div class="card-colored primary" style="padding: 20px;">
                    <strong>Primary Left Border</strong>
                    <p>Card with left border primary color</p>
                </div>
                <div class="card-colored secondary" style="padding: 20px;">
                    <strong>Secondary Left Border</strong>
                    <p>Card with left border secondary color</p>
                </div>
                <div class="card-colored accent" style="padding: 20px;">
                    <strong>Accent Left Border</strong>
                    <p>Card with left border accent color</p>
                </div>
                <div class="card-colored danger" style="padding: 20px;">
                    <strong>Danger Left Border</strong>
                    <p>Card with left border danger color</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 60px; padding-top: 40px; border-top: 2px solid #e2e8f0; color: #64748b;">
            <p>School Facility Maintenance Reporting System - Color Scheme Guide v1.0</p>
            <p style="font-size: 12px;">Generated with Professional Blue & Green Theme</p>
        </div>
    </div>
</body>
</html>


