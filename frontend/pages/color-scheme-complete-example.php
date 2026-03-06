<?php
$pageTitle = 'Complete Color Scheme Example - SFMS';
// Demo page without requiring login
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/styles.css">
    <link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/color-scheme.css">
    <style>
        body {
            background-color: #f8fafc;
        }
        
        .navbar-demo {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        
        .navbar-demo .brand {
            font-size: 20px;
            font-weight: 700;
            color: white;
        }
        
        .navbar-demo .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .navbar-demo a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            opacity: 0.9;
            transition: opacity 0.3s;
        }
        
        .navbar-demo a:hover {
            opacity: 1;
        }
        
        .navbar-demo .user-info {
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .main-demo {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #2563eb;
        }
        
        .demo-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .button-demo {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .button-demo .btn {
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .badge-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        h3 {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- NAVIGATION BAR DEMO -->
    <div class="navbar-demo">
        <div class="brand">🏫 SFMS</div>
        <div class="nav-links">
            <a href="#">Dashboard</a>
            <a href="#">Reports</a>
            <a href="#">New Report</a>
            <a href="#">Users</a>
        </div>
        <div class="user-info">
            <span>👤 Mr. Admin</span>
        </div>
    </div>

    <div class="main-demo">
        <!-- BUTTON STYLES DEMO -->
        <div class="demo-section">
            <h2 class="section-title">Button Styles</h2>
            
            <h3>Primary Buttons</h3>
            <div class="button-demo">
                <button class="btn btn-primary">Primary Button</button>
                <button class="btn btn-primary" style="opacity: 0.7;">Primary Hover</button>
                <button class="btn btn-primary" disabled>Disabled</button>
            </div>
            
            <h3>Secondary Buttons</h3>
            <div class="button-demo">
                <button class="btn btn-secondary">Secondary Button</button>
                <button class="btn btn-secondary" style="opacity: 0.7;">Secondary Hover</button>
            </div>
            
            <h3>Danger Buttons</h3>
            <div class="button-demo">
                <button class="btn btn-danger">Delete</button>
                <button class="btn btn-danger" style="opacity: 0.7;">Delete Hover</button>
            </div>
            
            <h3>Outline Buttons</h3>
            <div class="button-demo">
                <button class="btn btn-outline-primary">Primary Outline</button>
                <button class="btn btn-outline-secondary">Secondary Outline</button>
            </div>
        </div>

        <!-- BADGE STYLES DEMO -->
        <div class="demo-section">
            <h2 class="section-title">Status & Priority Badges</h2>
            
            <h3>Status Badges</h3>
            <div class="badge-grid">
                <span class="badge badge-submitted">Submitted</span>
                <span class="badge badge-assigned">Assigned</span>
                <span class="badge badge-in-progress">In Progress</span>
                <span class="badge badge-completed">Completed</span>
                <span class="badge badge-pending">Pending</span>
            </div>
            
            <h3>Priority Badges</h3>
            <div class="badge-grid">
                <span class="badge badge-low">Low</span>
                <span class="badge badge-medium">Medium</span>
                <span class="badge badge-high">High</span>
                <span class="badge badge-urgent">Urgent</span>
                <span class="badge badge-critical">Critical</span>
            </div>
        </div>

        <!-- TABLE EXAMPLE DEMO -->
        <div class="demo-section">
            <h2 class="section-title">Maintenance Reports Table</h2>
            <p style="margin-bottom: 20px; color: #64748b;">View and manage all maintenance reports</p>
            
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Location</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#6</td>
                            <td><strong>Broken Light Fixture</strong></td>
                            <td>Building A - Room 101</td>
                            <td><span class="badge badge-medium">Medium</span></td>
                            <td><span class="badge badge-assigned">Assigned</span></td>
                            <td>Sarah Johnson</td>
                            <td>Feb 6, 2026</td>
                            <td><button class="btn btn-primary" style="padding: 6px 16px; font-size: 12px;">View</button></td>
                        </tr>
                        <tr>
                            <td>#1</td>
                            <td><strong>Leaking Faucet</strong></td>
                            <td>Building B - 2nd Floor Bathroom</td>
                            <td><span class="badge badge-high">High</span></td>
                            <td><span class="badge badge-in-progress">In Progress</span></td>
                            <td>Michael Brown</td>
                            <td>Feb 6, 2026</td>
                            <td><button class="btn btn-primary" style="padding: 6px 16px; font-size: 12px;">View</button></td>
                        </tr>
                        <tr>
                            <td>#2</td>
                            <td><strong>Air Conditioning Issue</strong></td>
                            <td>Building C - Cafeteria</td>
                            <td><span class="badge badge-urgent">Urgent</span></td>
                            <td><span class="badge badge-assigned">Assigned</span></td>
                            <td>Emily Davis</td>
                            <td>Feb 6, 2026</td>
                            <td><button class="btn btn-primary" style="padding: 6px 16px; font-size: 12px;">View</button></td>
                        </tr>
                        <tr>
                            <td>#3</td>
                            <td><strong>Painting Required</strong></td>
                            <td>Building A - Main Hallway</td>
                            <td><span class="badge badge-low">Low</span></td>
                            <td><span class="badge badge-submitted">Submitted</span></td>
                            <td>Sarah Johnson</td>
                            <td>Feb 6, 2026</td>
                            <td><button class="btn btn-primary" style="padding: 6px 16px; font-size: 12px;">View</button></td>
                        </tr>
                        <tr>
                            <td>#4</td>
                            <td><strong>Door Lock Repair</strong></td>
                            <td>Building A - Main Entrance</td>
                            <td><span class="badge badge-high">High</span></td>
                            <td><span class="badge badge-completed">Completed</span></td>
                            <td>Michael Brown</td>
                            <td>Feb 6, 2026</td>
                            <td><button class="btn btn-secondary" style="padding: 6px 16px; font-size: 12px;">View</button></td>
                        </tr>
                        <tr>
                            <td>#5</td>
                            <td><strong>Window Glass Broken</strong></td>
                            <td>Building B - Room 205</td>
                            <td><span class="badge badge-high">High</span></td>
                            <td><span class="badge badge-pending">Pending</span></td>
                            <td>Emily Davis</td>
                            <td>Feb 5, 2026</td>
                            <td><button class="btn btn-primary" style="padding: 6px 16px; font-size: 12px;">View</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TEXT & BACKGROUND COLORS -->
        <div class="demo-section">
            <h2 class="section-title">Text & Background Colors</h2>
            
            <h3>Text Colors</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
                <div>
                    <p class="text-primary"><strong>Primary Text</strong></p>
                    <code style="font-size: 12px; color: #64748b;">--text-primary: #1e293b</code>
                </div>
                <div>
                    <p class="text-secondary"><strong>Secondary Text</strong></p>
                    <code style="font-size: 12px; color: #64748b;">--text-secondary: #64748b</code>
                </div>
                <div>
                    <p class="text-muted"><strong>Muted Text</strong></p>
                    <code style="font-size: 12px; color: #64748b;">--text-tertiary: #94a3b8</code>
                </div>
            </div>
            
            <h3>Background Colors</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div style="background: #f8fafc; padding: 20px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <strong>Primary Background</strong>
                    <code style="font-size: 12px; color: #64748b; display: block; margin-top: 5px;">#f8fafc</code>
                </div>
                <div style="background: #e0f2fe; padding: 20px; border-radius: 6px; border: 1px solid #bfdbfe;">
                    <strong style="color: #0c4a6e;">Table Header BG</strong>
                    <code style="font-size: 12px; color: #0c4a6e; display: block; margin-top: 5px;">#e0f2fe</code>
                </div>
                <div style="background: #10b981; padding: 20px; border-radius: 6px; color: white;">
                    <strong>Success Background</strong>
                    <code style="font-size: 12px; display: block; margin-top: 5px; opacity: 0.9;">#10b981</code>
                </div>
                <div style="background: #ef4444; padding: 20px; border-radius: 6px; color: white;">
                    <strong>Danger Background</strong>
                    <code style="font-size: 12px; display: block; margin-top: 5px; opacity: 0.9;">#ef4444</code>
                </div>
            </div>
        </div>

        <!-- COLOR REFERENCE -->
        <div class="demo-section">
            <h2 class="section-title">Color Reference Guide</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <!-- Primary Colors -->
                <div>
                    <h3 style="border-bottom: 2px solid #2563eb; padding-bottom: 10px;">Primary Blue</h3>
                    <div style="margin-top: 15px;">
                        <div style="background: #2563eb; height: 60px; border-radius: 4px; color: white; display: flex; align-items: center; padding: 10px; margin-bottom: 10px; font-weight: 600;">#2563eb</div>
                        <small style="color: #64748b;">Headers, primary buttons, navigation</small>
                    </div>
                </div>
                
                <!-- Secondary Colors -->
                <div>
                    <h3 style="border-bottom: 2px solid #059669; padding-bottom: 10px;">Secondary Green</h3>
                    <div style="margin-top: 15px;">
                        <div style="background: #059669; height: 60px; border-radius: 4px; color: white; display: flex; align-items: center; padding: 10px; margin-bottom: 10px; font-weight: 600;">#059669</div>
                        <small style="color: #64748b;">Success states, completed tasks</small>
                    </div>
                </div>
                
                <!-- Accent Colors -->
                <div>
                    <h3 style="border-bottom: 2px solid #f59e0b; padding-bottom: 10px;">Accent Amber</h3>
                    <div style="margin-top: 15px;">
                        <div style="background: #f59e0b; height: 60px; border-radius: 4px; color: white; display: flex; align-items: center; padding: 10px; margin-bottom: 10px; font-weight: 600;">#f59e0b</div>
                        <small style="color: #64748b;">Pending items, warnings, alerts</small>
                    </div>
                </div>
                
                <!-- Danger Colors -->
                <div>
                    <h3 style="border-bottom: 2px solid #dc2626; padding-bottom: 10px;">Danger Red</h3>
                    <div style="margin-top: 15px;">
                        <div style="background: #dc2626; height: 60px; border-radius: 4px; color: white; display: flex; align-items: center; padding: 10px; margin-bottom: 10px; font-weight: 600;">#dc2626</div>
                        <small style="color: #64748b;">Urgent repairs, critical issues</small>
                    </div>
                </div>
                
                <!-- Table Header Background -->
                <div>
                    <h3 style="border-bottom: 2px solid #0284c7; padding-bottom: 10px;">Table Header BG</h3>
                    <div style="margin-top: 15px;">
                        <div style="background: #e0f2fe; height: 60px; border-radius: 4px; color: #0c4a6e; display: flex; align-items: center; padding: 10px; margin-bottom: 10px; font-weight: 600; border: 1px solid #bfdbfe;">#e0f2fe</div>
                        <small style="color: #64748b;">Table header backgrounds</small>
                    </div>
                </div>
                
                <!-- Surface/White -->
                <div>
                    <h3 style="border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">White Surface</h3>
                    <div style="margin-top: 15px;">
                        <div style="background: #ffffff; height: 60px; border-radius: 4px; color: #1e293b; display: flex; align-items: center; padding: 10px; margin-bottom: 10px; font-weight: 600; border: 1px solid #e2e8f0;">#ffffff</div>
                        <small style="color: #64748b;">Cards, panels, modals</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- BADGE COLOR MAPPING -->
        <div class="demo-section">
            <h2 class="section-title">Badge Color Mapping Reference</h2>
            
            <table class="table" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th>Badge Type</th>
                        <th>Background Color</th>
                        <th>Text Color</th>
                        <th>Hex Values</th>
                        <th>Example</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>URGENT</strong></td>
                        <td style="background: #fee2e2;"></td>
                        <td style="background: #dc2626; color: white;"></td>
                        <td><code style="font-size: 11px;">#fee2e2 / #dc2626</code></td>
                        <td><span class="badge badge-urgent">Urgent</span></td>
                    </tr>
                    <tr>
                        <td><strong>HIGH</strong></td>
                        <td style="background: #fecaca;"></td>
                        <td style="background: #b91c1c; color: white;"></td>
                        <td><code style="font-size: 11px;">#fecaca / #b91c1c</code></td>
                        <td><span class="badge badge-high">High</span></td>
                    </tr>
                    <tr>
                        <td><strong>MEDIUM</strong></td>
                        <td style="background: #fef3c7;"></td>
                        <td style="background: #d97706; color: white;"></td>
                        <td><code style="font-size: 11px;">#fef3c7 / #d97706</code></td>
                        <td><span class="badge badge-medium">Medium</span></td>
                    </tr>
                    <tr>
                        <td><strong>LOW</strong></td>
                        <td style="background: #dbeafe;"></td>
                        <td style="background: #2563eb; color: white;"></td>
                        <td><code style="font-size: 11px;">#dbeafe / #2563eb</code></td>
                        <td><span class="badge badge-low">Low</span></td>
                    </tr>
                    <tr>
                        <td><strong>SUBMITTED</strong></td>
                        <td style="background: #dbeafe;"></td>
                        <td style="background: #1d4ed8; color: white;"></td>
                        <td><code style="font-size: 11px;">#dbeafe / #1d4ed8</code></td>
                        <td><span class="badge badge-submitted">Submitted</span></td>
                    </tr>
                    <tr>
                        <td><strong>ASSIGNED</strong></td>
                        <td style="background: #fef3c7;"></td>
                        <td style="background: #d97706; color: white;"></td>
                        <td><code style="font-size: 11px;">#fef3c7 / #d97706</code></td>
                        <td><span class="badge badge-assigned">Assigned</span></td>
                    </tr>
                    <tr>
                        <td><strong>IN PROGRESS</strong></td>
                        <td style="background: #f3f4f6;"></td>
                        <td style="background: #4b5563; color: white;"></td>
                        <td><code style="font-size: 11px;">#f3f4f6 / #4b5563</code></td>
                        <td><span class="badge badge-in-progress">In Progress</span></td>
                    </tr>
                    <tr>
                        <td><strong>COMPLETED</strong></td>
                        <td style="background: #d1fae5;"></td>
                        <td style="background: #059669; color: white;"></td>
                        <td><code style="font-size: 11px;">#d1fae5 / #059669</code></td>
                        <td><span class="badge badge-completed">Completed</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- FOOTER -->
        <div style="text-align: center; margin-top: 60px; padding: 40px; color: #64748b; border-top: 1px solid #e2e8f0;">
            <p><strong>School Facility Maintenance Reporting System</strong></p>
            <p>Professional Blue & Green Color Scheme - Complete Implementation Guide</p>
            <p style="font-size: 12px; margin-top: 10px;">View the actual pages at: /frontend/pages/</p>
        </div>
    </div>
</body>
</html>
