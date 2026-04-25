<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$pageTitle = 'Dashboard - SFMS';
include __DIR__ . '/../includes/header.php';

$user = $_SESSION['user'];
?>

<main class="container">
    <div class="card">
        <div class="card-header">
            <div>
                <h2>Dashboard</h2>
            </div>
        </div>
        <div class="card-body">
            <!-- Summary Cards Grid -->
            <div class="summary-cards-grid">
                <!-- Total Reports Card -->
                <div class="summary-card summary-card-action" onclick="navigateToReportsCard('total')">
                    <div class="summary-card-content">
                        <h3 class="summary-card-title">Total reports</h3>
                        <div class="summary-card-value" id="stat-total">-</div>
                        <p class="summary-card-desc summary-trend-positive">all reports in the system</p>
                    </div>
                    <div class="summary-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #a78bfa; stroke: #a78bfa;">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <path d="M14 2v6h6"></path>
                        </svg>
                    </div>
                </div>

                <!-- Reports Today Card -->
                <div class="summary-card summary-card-action" onclick="navigateToReportsCard('today')">
                    <div class="summary-card-content">
                        <h3 class="summary-card-title">Reports today</h3>
                        <div class="summary-card-value" id="stat-today">-</div>
                        <p class="summary-card-desc summary-trend-positive">submitted today</p>
                    </div>
                    <div class="summary-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #22d3ee; stroke: #22d3ee;">
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            <path d="M16 2v4M8 2v4M3 10h18"></path>
                        </svg>
                    </div>
                </div>

                <!-- Pending Tasks Card -->
                <div class="summary-card summary-card-action" onclick="navigateToReportsCard('pending_tasks')">
                    <div class="summary-card-content">
                        <h3 class="summary-card-title">Pending tasks</h3>
                        <div class="summary-card-value" id="stat-pending">-</div>
                        <p class="summary-card-desc summary-trend-warning">needs attention</p>
                    </div>
                    <div class="summary-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #fbbf24; stroke: #fbbf24;">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 7v6l4 2"></path>
                        </svg>
                    </div>
                </div>

                <!-- In Progress Card -->
                <div class="summary-card summary-card-action" onclick="navigateToReportsCard('in_progress')">
                    <div class="summary-card-content">
                        <h3 class="summary-card-title">In progress</h3>
                        <div class="summary-card-value" id="stat-in-progress">-</div>
                        <p class="summary-card-desc summary-trend-positive">on track</p>
                    </div>
                    <div class="summary-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #c084fc; stroke: #c084fc;">
                            <path d="M20 7a5 5 0 0 1-7 4.6L7.6 17A2 2 0 1 1 5 14.4l5.4-5.4A5 5 0 1 1 20 7z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Completed Card -->
                <div class="summary-card summary-card-action" onclick="navigateToReportsCard('completed')">
                    <div class="summary-card-content">
                        <h3 class="summary-card-title">Completed</h3>
                        <div class="summary-card-value" id="stat-completed">-</div>
                        <p class="summary-card-desc summary-trend-positive">this month</p>
                    </div>
                    <div class="summary-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #34d399; stroke: #34d399;">
                            <path d="M20 6L9 17l-5-5"></path>
                        </svg>
                    </div>
                </div>

                <!-- Low Stock Items Card -->
                <div class="summary-card summary-card-alert summary-card-action" onclick="navigateToReportsCard('low_stock')">
                    <div class="summary-card-content">
                        <h3 class="summary-card-title">Low stock</h3>
                        <div class="summary-card-value" id="stat-low">-</div>
                        <p class="summary-card-desc summary-trend-danger">restock now</p>
                    </div>
                    <div class="summary-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #fb7185; stroke: #fb7185;">
                            <path d="M10.29 3.86l-8 14A1 1 0 0 0 3.14 19h17.72a1 1 0 0 0 .85-1.5l-8-14a1 1 0 0 0-1.72 0z"></path>
                            <path d="M12 9v4"></path>
                            <path d="M12 17h.01"></path>
                        </svg>
                    </div>
                </div>

                <!-- Buildings Overview Card -->
                <div class="summary-card summary-card-action" onclick="navigateToBuildingsOverview()">
                    <div class="summary-card-content">
                        <h3 class="summary-card-title">Buildings overview</h3>
                        <div class="summary-card-value" style="font-size: 32px;">Open</div>
                        <p class="summary-card-desc">browse buildings and rooms</p>
                    </div>
                    <div class="summary-card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #60a5fa; stroke: #60a5fa;">
                            <path d="M3 21h18"></path>
                            <path d="M5 21V7l7-4 7 4v14"></path>
                            <path d="M9 9h6"></path>
                            <path d="M9 13h6"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="dashboard-bottom mt-md">
                <div class="card chart-card status-chart-card">
                    <div class="card-header">
                        <div class="status-chart-head-row">
                            <h2>Reports by Status</h2>
                            <span class="status-live-badge">Live</span>
                        </div>
                        <p class="text-muted mb-0">Current distribution of report statuses.</p>
                    </div>
                    <div class="card-body status-chart-body">
                        <canvas id="reportsStatusChart" class="chart-canvas"></canvas>
                    </div>
                </div>

                <div class="card chart-card priority-chart-card">
                    <div class="card-header">
                        <h2>Reports by Priority</h2>
                        <p class="text-muted mb-0">Priority levels across all reports.</p>
                    </div>
                    <div class="card-body">
                        <canvas id="reportsPriorityChart" class="chart-canvas"></canvas>
                    </div>
                </div>

                <div class="card recent-reports-card">
                    <div class="card-header">
                        <h2>Recent reports</h2>
                        <a href="/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/reports.php" class="recent-reports-link">View all</a>
                    </div>
                    <div class="card-body">
                        <div class="recent-activity" id="recentActivity">
                            <!-- Recent reports list will render here -->
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<!-- Building Modal -->
<div id="buildingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Building</h2>
            <span class="modal-close" onclick="closeBuildingModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="buildingForm">
                <div class="form-group">
                    <label for="buildingNameInput">Building Name *</label>
                    <input type="text" id="buildingNameInput" class="form-control" placeholder="e.g., Science Wing" required>
                </div>
                <div class="form-group">
                    <label for="buildingDescInput">Description</label>
                    <textarea id="buildingDescInput" class="form-control" placeholder="Building description (optional)" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeBuildingModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveBuildingData()">Save Building</button>
        </div>
    </div>
</div>

<!-- Room Modal -->
<div id="roomModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Room</h2>
            <span class="modal-close" onclick="closeRoomModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="roomForm">
                <div class="form-group">
                    <label for="roomBuildingSelect">Building *</label>
                    <select id="roomBuildingSelect" class="form-control" required onchange="loadFloorsForRoom(this.value)">
                        <option value="">Choose a building</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="roomFloorSelect">Floor *</label>
                    <select id="roomFloorSelect" class="form-control" required>
                        <option value="">Choose a floor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="roomNameInput">Room Name / Number *</label>
                    <input type="text" id="roomNameInput" class="form-control" placeholder="e.g., Room 304 or Biology Lab" required>
                </div>
                <div class="form-group">
                    <label for="roomCapacityInput">Capacity</label>
                    <input type="number" id="roomCapacityInput" class="form-control" placeholder="e.g., 50" min="1">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeRoomModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveRoomData()">Save Room</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/laravel_app/public/frontend/assets/css/dashboard.inline.css?v=20260419-4">

<script>

function updateDashboardKicker() {
    const kickerEl = document.getElementById('headerDashboardKicker');
    if (!kickerEl) return;

    const now = new Date();
    const hour = now.getHours();

    let greeting = 'Good evening';
    if (hour < 12) {
        greeting = 'Good morning';
    } else if (hour < 18) {
        greeting = 'Good afternoon';
    }

    const dateText = new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric'
    }).format(now);

    const timeText = new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit'
    }).format(now);

    kickerEl.textContent = `${dateText} · ${timeText} ${greeting}`;
    kickerEl.classList.add('is-visible');
}

let reportsStatusChart;
let reportsPriorityChart;
const LARAVEL_API_BASE = '/School_Facility_Maintenance_System/laravel_app/public/api';
const LARAVEL_BRIDGE_BASE = '/School_Facility_Maintenance_System/laravel_app/public/backend/api';

function extractReportsFromResponse(payload) {
    const data = payload && payload.data ? payload.data : {};

    if (Array.isArray(data.reports)) {
        return data.reports;
    }

    if (Array.isArray(data.data)) {
        return data.data;
    }

    return [];
}
// Modal Functions
function openBuildingModal() {
    document.getElementById('buildingModal').classList.add('show');
    document.getElementById('buildingNameInput').focus();
}

function closeBuildingModal() {
    document.getElementById('buildingModal').classList.remove('show');
    document.getElementById('buildingForm').reset();
}
function openRoomModal() {
    document.getElementById('roomModal').classList.add('show');
    loadBuildingsInModal();
}

function loadFloorsForRoom(buildingId) {
    const floorSelect = document.getElementById('roomFloorSelect');
    floorSelect.innerHTML = '<option value="">Choose a floor</option>';
    if (!buildingId) return;
    fetch('/School_Facility_Maintenance_System/laravel_app/public/backend/api/floors.php?action=getByBuilding&building_id=' + buildingId)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.floors) {
                data.floors.forEach(f => {
                    const opt = document.createElement('option');
                    opt.value = f.id;
                    opt.textContent = f.name;
                    floorSelect.appendChild(opt);
                });
            }
        });
}

function closeRoomModal() {
    document.getElementById('roomModal').classList.remove('show');
    document.getElementById('roomForm').reset();
}

// Navigate to buildings overview page
function navigateToBuildingsOverview() {
    window.location.href = '/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/buildings-overview.php';
}

function navigateToReportsCard(cardKey) {
    if (cardKey === 'low_stock') {
        window.location.href = '/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/inventory.php?status_filter=low_stock';
        return;
    }

    const targetUrl = new URL('/School_Facility_Maintenance_System/laravel_app/public/frontend/pages/reports.php', window.location.origin);

    if (cardKey === 'today') {
        targetUrl.searchParams.set('date_scope', 'today');
    } else if (cardKey === 'pending_tasks') {
        targetUrl.searchParams.set('status_group', 'pending_tasks');
    } else if (cardKey === 'in_progress') {
        targetUrl.searchParams.set('status', 'in_progress');
    } else if (cardKey === 'completed') {
        targetUrl.searchParams.set('status', 'completed');
    }

    window.location.href = targetUrl.toString();
}

// Load buildings in room modal
function loadBuildingsInModal() {
    const buildingSelect = document.getElementById('roomBuildingSelect');
    const floorSelect = document.getElementById('roomFloorSelect');
    buildingSelect.innerHTML = '<option value="">Choose a building</option>';
    floorSelect.innerHTML = '<option value="">Choose a floor</option>';
    
    // Get buildings from API or use mock data
    fetch('/School_Facility_Maintenance_System/laravel_app/public/backend/api/buildings.php?action=list')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.buildings && data.buildings.length > 0) {
                data.buildings.forEach(building => {
                    const option = document.createElement('option');
                    option.value = building.id;
                    option.textContent = building.name;
                    buildingSelect.appendChild(option);
                });
            } else {
                console.log('No buildings found or API error:', data.message);
                // Fall back to mock data
                loadMockBuildings();
            }
        })
        .catch(err => {
            console.log('Error loading buildings, using mock data:', err);
            loadMockBuildings();
        });
}

function loadMockBuildings() {
    const buildingSelect = document.getElementById('roomBuildingSelect');
    buildingSelect.innerHTML = '<option value="">Choose a building</option><option value="" disabled style="color: #999;">--- Setup database first ---</option>';
    buildingSelect.disabled = true;
    
    // Show setup link
    const roomForm = document.getElementById('roomForm');
    if (roomForm && !roomForm.querySelector('.setup-notice')) {
        const notice = document.createElement('div');
        notice.className = 'setup-notice';
        notice.style.cssText = 'background: #8b2020; color: #ffcccc; padding: 12px; border-radius: 6px; margin-top: 12px; font-size: 12px;';
        notice.innerHTML = '⚠️ No buildings found. <a href="/School_Facility_Maintenance_System/laravel_app/public/backend/setup.html" target="_blank" style="color: #ffb3b3; text-decoration: underline;">Run database setup</a> first.';
        roomForm.appendChild(notice);
    }
}

// Save building data
async function saveBuildingData() {
    const buildingName = document.getElementById('buildingNameInput').value.trim();
    const buildingDesc = document.getElementById('buildingDescInput').value.trim();
    
    if (!buildingName) {
        alert('Please enter a building name');
        return;
    }
    
    try {
        const response = await fetch('/School_Facility_Maintenance_System/laravel_app/public/backend/api/buildings.php?action=create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name: buildingName,
                description: buildingDesc
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Building added successfully!');
            closeBuildingModal();
            // Refresh buildings list
            loadBuildingsInModal();
        } else {
            const errorMsg = result.message || 'Failed to add building';
            
            // Check if it's a table not found error
            if (errorMsg.includes('table not found')) {
                alert(errorMsg + '\n\nPlease run database setup first:\nhttp://localhost/School_Facility_Maintenance_System/laravel_app/public/backend/setup.html');
            } else {
                alert(errorMsg);
            }
        }
    } catch (error) {
        console.error('Error saving building:', error);
        alert('Error saving building. Please try again.\n\nIf you see this repeatedly, run setup: http://localhost/School_Facility_Maintenance_System/laravel_app/public/backend/setup.html');
    }
}

// Save room data
async function saveRoomData() {
    const buildingId = document.getElementById('roomBuildingSelect').value.trim();
    const floorId = document.getElementById('roomFloorSelect').value.trim();
    const roomName = document.getElementById('roomNameInput').value.trim();
    const roomCapacity = document.getElementById('roomCapacityInput').value.trim();
    
    if (!buildingId) {
        alert('Please select a building');
        return;
    }
    if (!floorId) {
        alert('Please select a floor');
        return;
    }
    
    if (!roomName) {
        alert('Please enter a room name/number');
        return;
    }
    
    try {
        const response = await fetch('/School_Facility_Maintenance_System/laravel_app/public/backend/api/rooms.php?action=create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                building_id: buildingId,
                floor_id: floorId,
                name: roomName,
                capacity: roomCapacity || null
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Room added successfully!');
            closeRoomModal();
        } else {
            const errorMsg = result.message || 'Failed to add room';
            
            // Check if it's a table not found error
            if (errorMsg.includes('table not found')) {
                alert(errorMsg + '\n\nPlease run database setup first:\nhttp://localhost/School_Facility_Maintenance_System/laravel_app/public/backend/setup.html');
            } else {
                alert(errorMsg);
            }
        }
    } catch (error) {
        console.error('Error saving room:', error);
        alert('Error saving room. Please try again.\n\nIf you see this repeatedly, run setup: http://localhost/School_Facility_Maintenance_System/laravel_app/public/backend/setup.html');
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const buildingModal = document.getElementById('buildingModal');
    const roomModal = document.getElementById('roomModal');
    
    if (event.target === buildingModal) {
        closeBuildingModal();
    }
    if (event.target === roomModal) {
        closeRoomModal();
    }
});

</script>

        </div>
    </div>
</main>

<script>

function buildStatsFromReports(reports) {
    const stats = {
        total: 0,
        reports_today: 0,
        submitted: 0,
        assigned: 0,
        in_progress: 0,
        completed: 0,
        closed: 0,
        cancelled: 0,
        by_priority: {
            low: 0,
            medium: 0,
            high: 0,
            critical: 0
        }
    };

    const now = new Date();
    const todayKey = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

    reports.forEach((report) => {
        const rawStatus = (report.status || '').toLowerCase().trim();
        const hasAssignee = Number(report.assigned_to || 0) > 0;
        // Normalize legacy rows: once a report has an assignee, treat it as assigned unless actively in progress/completed/closed/cancelled.
        let status = rawStatus;
        if (hasAssignee && (rawStatus === '' || rawStatus === 'submitted')) {
            status = 'assigned';
        }
        const priority = (report.priority || '').toLowerCase();
        const normalizedPriority = priority === 'urgent' ? 'critical' : priority;
        const createdAt = (report.created_at || '').toString().slice(0, 10);

        stats.total += 1;

        if (createdAt === todayKey) {
            stats.reports_today += 1;
        }

        if (Object.prototype.hasOwnProperty.call(stats, status)) {
            stats[status] += 1;
        }

        if (Object.prototype.hasOwnProperty.call(stats.by_priority, normalizedPriority)) {
            stats.by_priority[normalizedPriority] += 1;
        }
    });

    return stats;
}

async function fetchDashboardStats() {
    // Primary source: Laravel dashboard stats endpoint
    const statsResp = await fetch(`${LARAVEL_API_BASE}/dashboard/stats`, {
        credentials: 'include'
    });
    const statsJson = await statsResp.json();

    if (statsJson.success && statsJson.data) {
        const stats = {
            total: Number(statsJson.data.total_reports || 0),
            reports_today: Number(statsJson.data.reports_today || 0),
            submitted: Number(statsJson.data.pending || 0),
            assigned: 0,
            in_progress: Number(statsJson.data.in_progress || 0),
            completed: Number(statsJson.data.completed || 0),
            closed: 0,
            cancelled: 0,
            low_stock: Number(statsJson.data.low_stock || 0),
            by_priority: {
                low: 0,
                medium: 0,
                high: 0,
                critical: 0
            }
        };

        // Fill extended status and priority values from report list endpoint.
        try {
            const reportsResp = await fetch(`${LARAVEL_BRIDGE_BASE}/reports-api.php?action=list&per_page=200`, {
                credentials: 'include'
            });
            const reportsJson = await reportsResp.json();
            if (reportsJson.success) {
                const derived = buildStatsFromReports(extractReportsFromResponse(reportsJson));
                stats.reports_today = derived.reports_today;
                stats.assigned = derived.assigned;
                stats.closed = derived.closed;
                stats.cancelled = derived.cancelled;
                stats.by_priority = {
                    low: derived.by_priority.low,
                    medium: derived.by_priority.medium,
                    high: derived.by_priority.high,
                    critical: derived.by_priority.critical
                };
            }
        } catch (deriveErr) {
            console.error('Could not derive reports_today from list endpoint:', deriveErr);
            stats.reports_today = stats.reports_today || 0;
            stats.by_priority = stats.by_priority || { low: 0, medium: 0, high: 0, critical: 0 };
        }

        return stats;
    }

    // Fallback source: compute from report list endpoint
    const reportsResp = await fetch(`${LARAVEL_BRIDGE_BASE}/reports-api.php?action=list&per_page=200`, {
        credentials: 'include'
    });
    const reportsJson = await reportsResp.json();

    if (reportsJson.success) {
        return buildStatsFromReports(extractReportsFromResponse(reportsJson));
    }

    return {
        total: 0,
        reports_today: 0,
        submitted: 0,
        assigned: 0,
        in_progress: 0,
        completed: 0,
        closed: 0,
        cancelled: 0,
        by_priority: {
            low: 0,
            medium: 0,
            high: 0,
            critical: 0
        }
    };
}

// Populate stats and chart
async function initDashboard() {
    try {
        const stats = await fetchDashboardStats();

        // Fill stats cards with real data
        const total      = stats.total       || 0;
        const todayCount = stats.reports_today || 0;
        const submitted  = stats.submitted   || 0;
        const assigned   = stats.assigned    || 0;
        const inProgress = stats.in_progress || 0;
        const completed  = stats.completed   || 0;
        const closed     = stats.closed      || 0;
        const cancelled  = stats.cancelled   || 0;
        const priorityLow = stats.by_priority?.low || 0;
        const priorityMedium = stats.by_priority?.medium || 0;
        const priorityHigh = stats.by_priority?.high || 0;
        const priorityCritical = stats.by_priority?.critical || 0;
        const isLightMode = document.documentElement.getAttribute('data-theme-resolved') === 'light';
        const chartPrimaryText = isLightMode ? '#111827' : '#f8fafc';
        const chartMutedText = isLightMode ? '#374151' : '#94a3b8';
        const chartGridY = isLightMode ? 'rgba(17, 24, 39, 0.12)' : 'rgba(148, 163, 184, 0.35)';
        const chartGridX = isLightMode ? 'rgba(17, 24, 39, 0.08)' : 'rgba(148, 163, 184, 0.25)';
        const doughnutBorder = isLightMode ? '#ffffff' : '#0f172a';

        document.getElementById('stat-total').textContent = total;
        document.getElementById('stat-today').textContent = todayCount;
        const pendingEl = document.getElementById('stat-pending');
        if (pendingEl) pendingEl.textContent = submitted + assigned;
        const inProgressEl = document.getElementById('stat-in-progress');
        if (inProgressEl) inProgressEl.textContent = inProgress;
        const completedEl = document.getElementById('stat-completed');
        if (completedEl) completedEl.textContent = completed;
        const lowEl = document.getElementById('stat-low');
        lowEl.textContent = (stats.low_stock !== undefined) ? stats.low_stock : '—';

        // Render chart separately so card values do not fail if chart has issues.
        try {
            if (typeof Chart !== 'undefined') {
                const statusCanvas = document.getElementById('reportsStatusChart');
                if (statusCanvas) {
                    const statusCtx = statusCanvas.getContext('2d');
                    if (reportsStatusChart) reportsStatusChart.destroy();
                    const statusTotal = submitted + inProgress + completed;

                    const statusCenterTextPlugin = {
                        id: 'statusCenterTextPlugin',
                        beforeDraw(chart) {
                            const meta = chart.getDatasetMeta(0);
                            if (!meta || !meta.data || !meta.data.length) {
                                return;
                            }

                            const point = meta.data[0];
                            const x = point.x;
                            const y = point.y;
                            const ctx = chart.ctx;

                            ctx.save();
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';

                            ctx.font = '700 22px "Segoe UI", sans-serif';
                            ctx.fillStyle = chartPrimaryText;
                            ctx.fillText(String(statusTotal), x, y - 4);

                            ctx.font = '500 11px "Segoe UI", sans-serif';
                            ctx.fillStyle = chartMutedText;
                            ctx.fillText('total', x, y + 14);
                            ctx.restore();
                        }
                    };

                    reportsStatusChart = new Chart(statusCtx, {
                        type: 'doughnut',
                        plugins: [statusCenterTextPlugin],
                        data: {
                            labels: ['Submitted', 'In Progress', 'Completed'],
                            datasets: [{
                                data: [submitted, inProgress, completed],
                                backgroundColor: ['#3b82f6', '#8b5cf6', '#10b981'],
                                borderColor: doughnutBorder,
                                borderWidth: 2,
                                hoverOffset: 3,
                                spacing: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '68%',
                            plugins: {
                                legend: {
                                    position: 'right',
                                    align: 'center',
                                    labels: {
                                        color: chartMutedText,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        boxWidth: 8,
                                        boxHeight: 8,
                                        padding: 14
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: context => ` ${context.formattedValue} report${Number(context.formattedValue) !== 1 ? 's' : ''}`
                                    }
                                }
                            }
                        }
                    });
                }

                const priorityCanvas = document.getElementById('reportsPriorityChart');
                if (priorityCanvas) {
                    const priorityCtx = priorityCanvas.getContext('2d');
                    if (reportsPriorityChart) reportsPriorityChart.destroy();
                    reportsPriorityChart = new Chart(priorityCtx, {
                        type: 'bar',
                        data: {
                            labels: ['Low', 'Medium', 'High', 'Critical'],
                            datasets: [{
                                label: 'Reports',
                                data: [priorityLow, priorityMedium, priorityHigh, priorityCritical],
                                backgroundColor: ['#94a3b8', '#3b82f6', '#f59e0b', '#991b1b'],
                                borderRadius: 6,
                                barThickness: 64,
                                maxBarThickness: 72
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0,
                                        stepSize: 2,
                                        color: chartMutedText
                                    },
                                    grid: {
                                        color: chartGridY,
                                        drawBorder: false
                                    }
                                },
                                x: {
                                    ticks: { color: chartMutedText },
                                    grid: {
                                        color: chartGridX,
                                        drawBorder: false
                                    }
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: c => ` ${c.parsed.y} report${c.parsed.y !== 1 ? 's' : ''}`
                                    }
                                }
                            }
                        }
                    });
                }
            }
        } catch (chartErr) {
            console.error('Chart render error:', chartErr);
        }

    } catch (err) {
        console.error('Error initializing dashboard:', err);
        document.getElementById('stat-total').textContent = '0';
        const todayEl = document.getElementById('stat-today');
        if (todayEl) todayEl.textContent = '0';
    }

    await renderRecentActivity();
}

async function renderRecentActivity() {
    const list = document.getElementById('recentActivity');
    try {
        const response = await fetch(`${LARAVEL_BRIDGE_BASE}/maintenance-reports-api.php?action=recent&limit=5`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (!data.success) {
            list.innerHTML = '<p class="text-muted">No recent reports.</p>';
            return;
        }
        
        const reports = extractReportsFromResponse(data);
        
        if (reports.length === 0) {
            list.innerHTML = '<p class="text-muted">No reports yet.</p>';
            return;
        }

        const statusClassMap = {
            submitted: 'status-submitted',
            assigned: 'status-assigned',
            in_progress: 'status-in-progress',
            completed: 'status-completed',
            cancelled: 'status-cancelled',
            closed: 'status-completed'
        };
        const priorityClassMap = {
            low: 'priority-low',
            medium: 'priority-medium',
            high: 'priority-high',
            urgent: 'priority-urgent',
            critical: 'priority-critical'
        };
        
        let html = '';
        reports.forEach(report => {
            const date = new Date(report.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const status = (report.status || 'submitted').toLowerCase();
            const priority = (report.priority || 'medium').toLowerCase();
            const statusClass = statusClassMap[status] || 'status-submitted';
            const priorityClass = priorityClassMap[priority] || 'priority-medium';
            const reportTitle = report.title || 'Untitled report';
            const statusLabel = status.replace('_', ' ');
            html += `
                <div class="recent-report-row">
                    <div>
                        <div class="recent-report-title">${reportTitle}</div>
                        <div class="recent-report-date">${date}</div>
                    </div>
                    <span class="status-badge ${priorityClass}">${priority}</span>
                    <span class="status-badge ${statusClass}">${statusLabel}</span>
                </div>
            `;
        });
        list.innerHTML = html;
    } catch (err) {
        console.error('Failed to load recent reports:', err);
        list.innerHTML = '<p class="text-muted">Could not load recent reports.</p>';
    }
}


// Load buildings for room dropdown
function loadBuildingsForDropdown() {
    // Placeholder - load from API or mock data
    const buildingSelect = document.getElementById('buildingSelect');
    const mockBuildings = [
        { id: 1, name: 'Science Wing' },
        { id: 2, name: 'Technology Building' },
        { id: 3, name: 'Administration Block' }
    ];
    
    mockBuildings.forEach(building => {
        const option = document.createElement('option');
        option.value = building.id;
        option.textContent = building.name;
        buildingSelect.appendChild(option);
    });
}

// Add new building
async function addBuilding() {
    const buildingName = document.getElementById('buildingName').value.trim();
    
    if (!buildingName) {
        alert('Please enter a building name');
        return;
    }
    
    try {
        // Add API call here for backend
        console.log('Adding building:', buildingName);
        alert('Building added successfully!');
        document.getElementById('buildingName').value = '';
    } catch (error) {
        console.error('Error adding building:', error);
        alert('Failed to add building');
    }
}

// Add new room
async function addRoom() {
    const buildingId = document.getElementById('buildingSelect').value.trim();
    const roomName = document.getElementById('roomName').value.trim();
    
    if (!buildingId) {
        alert('Please select a building');
        return;
    }
    
    if (!roomName) {
        alert('Please enter a room name/number');
        return;
    }
    
    try {
        // Add API call here for backend
        console.log('Adding room:', { buildingId, roomName });
        alert('Room added successfully!');
        document.getElementById('roomName').value = '';
        document.getElementById('buildingSelect').value = '';
    } catch (error) {
        console.error('Error adding room:', error);
        alert('Failed to add room');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
// Initialize dashboard after api.js is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (typeof updateGlobalHeaderKicker === 'function') {
        updateGlobalHeaderKicker();
    } else {
        updateDashboardKicker();
        setInterval(updateDashboardKicker, 60000);
    }
    initDashboard();
    loadBuildingsForDropdown();
});
</script>


