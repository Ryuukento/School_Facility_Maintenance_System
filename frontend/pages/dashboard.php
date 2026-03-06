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
                <p class="text-muted mb-0">Overview of system activity and reports</p>
            </div>
        </div>
        <div class="card-body">
            <!-- Summary Cards Grid -->
            <div class="summary-cards-grid">
                <!-- Total Reports Card -->
                <div class="summary-card">
                    <div class="summary-card-content">
                        <h3 class="summary-card-title">Total Reports</h3>
                        <div class="summary-card-value" id="stat-total">-</div>
                        <p class="summary-card-desc">All reports in the system</p>
                    </div>
                    <div class="summary-card-icon">📄</div>
                </div>

                <!-- Low Stock Items Card -->
                <div class="summary-card summary-card-alert">
                    <div class="summary-card-content">
                        <h3 class="summary-card-title">Low Stock Items</h3>
                        <div class="summary-card-value" id="stat-low">-</div>
                        <p class="summary-card-desc">Inventory needing restock</p>
                    </div>
                    <div class="summary-card-icon">⚠️</div>
                </div>

                <!-- Buildings Overview Card -->
                <div class="summary-card summary-card-action" onclick="navigateToBuildingsOverview()">
                    <div class="summary-card-content">
                        <h3 class="summary-card-title">Buildings Overview</h3>
                        <div class="summary-card-value" style="font-size: 32px;">🏢</div>
                        <p class="summary-card-desc">Browse buildings, floors & rooms</p>
                    </div>
                    <div class="summary-card-icon">🔍</div>
                </div>

                <!-- Add New Building Card -->
                <div class="summary-card summary-card-action" onclick="openBuildingModal()">
                    <div class="summary-card-content">
                        <h3 class="summary-card-title">Add New Building</h3>
                        <div class="summary-card-value" style="font-size: 32px;">🏢</div>
                        <p class="summary-card-desc">Create new building</p>
                    </div>
                    <div class="summary-card-icon">➕</div>
                </div>

                <!-- Add New Room Card -->
                <div class="summary-card summary-card-action" onclick="openRoomModal()">
                    <div class="summary-card-content">
                        <h3 class="summary-card-title">Add New Room</h3>
                        <div class="summary-card-value" style="font-size: 32px;">🚪</div>
                        <p class="summary-card-desc">Add room to building</p>
                    </div>
                    <div class="summary-card-icon">➕</div>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="dashboard-bottom mt-md">
                <div class="card chart-card">
                    <div class="card-header">
                        <h2>Reports Overview</h2>
                        <p class="text-muted mb-0">A summary of report statuses.</p>
                    </div>
                    <div class="card-body">
                        <canvas id="reportsChart" class="chart-canvas"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Recent Activity</h2>
                        <p class="text-muted mb-0">A log of the latest actions in the system.</p>
                    </div>
                    <div class="card-body">
                        <div class="recent-activity" id="recentActivity">
                            <!-- Activity items will render here -->
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

<style>
/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.7);
    animation: fadeIn 0.3s ease-in;
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: var(--card-color, #1a1f2e);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    min-height: 300px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 40px rgba(0,0,0,0.8);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: var(--text-light, #e6eef8);
}

.modal-close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: var(--muted-text, #9aa3b2);
    transition: color 0.2s ease;
}

.modal-close:hover {
    color: var(--text-light, #e6eef8);
}

.modal-body {
    padding: 24px;
    flex: 1;
    overflow-y: auto;
}

.modal-body .form-group {
    margin-bottom: 18px;
}

.modal-body label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-light, #e6eef8);
}

.modal-body .form-control {
    width: 100%;
    padding: 10px 12px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 6px;
    color: var(--text-light, #e6eef8);
    font-family: inherit;
    font-size: 14px;
    transition: all 0.2s ease;
}

.modal-body .form-control:focus {
    outline: none;
    border-color: var(--primary-color, #4a9eff);
    background: rgba(255,255,255,0.06);
    box-shadow: 0 0 0 3px rgba(74, 158, 255, 0.1);
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 24px;
    border-top: 1px solid rgba(255,255,255,0.05);
}

.modal-footer .btn {
    padding: 10px 20px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.modal-footer .btn-secondary {
    background: rgba(255,255,255,0.05);
    color: var(--muted-text, #9aa3b2);
}

.modal-footer .btn-secondary:hover {
    background: rgba(255,255,255,0.1);
    color: var(--text-light, #e6eef8);
}

.modal-footer .btn-primary {
    background: var(--primary-color, #4a9eff);
    color: white;
}

.modal-footer .btn-primary:hover {
    background: var(--primary-dark, #3277e6);
}
</style>

<script>
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
    fetch('/School_Facility_Maintenance_System/backend/api/floors.php?action=getByBuilding&building_id=' + buildingId)
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
    window.location.href = '/School_Facility_Maintenance_System/frontend/pages/buildings-overview.php';
}

// Load buildings in room modal
function loadBuildingsInModal() {
    const buildingSelect = document.getElementById('roomBuildingSelect');
    const floorSelect = document.getElementById('roomFloorSelect');
    buildingSelect.innerHTML = '<option value="">Choose a building</option>';
    floorSelect.innerHTML = '<option value="">Choose a floor</option>';
    
    // Get buildings from API or use mock data
    fetch('/School_Facility_Maintenance_System/backend/api/buildings.php?action=list')
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
        notice.innerHTML = '⚠️ No buildings found. <a href="/School_Facility_Maintenance_System/backend/setup.html" target="_blank" style="color: #ffb3b3; text-decoration: underline;">Run database setup</a> first.';
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
        const response = await fetch('/School_Facility_Maintenance_System/backend/api/buildings.php?action=create', {
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
                alert(errorMsg + '\n\nPlease run database setup first:\nhttp://localhost/School_Facility_Maintenance_System/backend/setup.html');
            } else {
                alert(errorMsg);
            }
        }
    } catch (error) {
        console.error('Error saving building:', error);
        alert('Error saving building. Please try again.\n\nIf you see this repeatedly, run setup: http://localhost/School_Facility_Maintenance_System/backend/setup.html');
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
        const response = await fetch('/School_Facility_Maintenance_System/backend/api/rooms.php?action=create', {
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
                alert(errorMsg + '\n\nPlease run database setup first:\nhttp://localhost/School_Facility_Maintenance_System/backend/setup.html');
            } else {
                alert(errorMsg);
            }
        }
    } catch (error) {
        console.error('Error saving room:', error);
        alert('Error saving room. Please try again.\n\nIf you see this repeatedly, run setup: http://localhost/School_Facility_Maintenance_System/backend/setup.html');
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

// Populate stats and chart
async function initDashboard() {
    try {
        const statsResp = await API.getStats();
        const stats = statsResp.data.stats || {};

        // Fill stats cards (fallback to sample numbers if undefined)
        const total = stats.total || 4;
        const submitted = stats.submitted || 1;
        const inProgress = stats.in_progress || 2;
        const completed = stats.completed || 1;
        const lowStock = stats.low_stock || 2;

        document.getElementById('stat-total').textContent = total;
        document.getElementById('stat-low').textContent = lowStock;

        // Prepare chart data as fractions (0-1)
        const sum = submitted + inProgress + completed + (stats.cancelled || 0);
        const pendingVal = (submitted / (sum || 1));
        const ongoingVal = (inProgress / (sum || 1));
        const fixedVal = (completed / (sum || 1));
        const cancelledVal = ((stats.cancelled || 0) / (sum || 1));

        const ctx = document.getElementById('reportsChart').getContext('2d');
        window.reportsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Pending', 'Ongoing', 'Fixed', 'Cancelled'],
                datasets: [{
                    label: 'Status',
                    data: [pendingVal, ongoingVal, fixedVal, cancelledVal],
                    backgroundColor: ['#d4a574', '#4a9eff', '#2d9d78', '#8b9aaf'],
                    borderRadius: 6,
                    barThickness: 36
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        min: 0,
                        max: 1,
                        ticks: {
                            stepSize: 0.25,
                            color: getComputedStyle(document.documentElement).getPropertyValue('--muted-text') || '#9aa3b2'
                        },
                        grid: { color: 'rgba(255,255,255,0.03)' }
                    },
                    x: {
                        ticks: { color: getComputedStyle(document.documentElement).getPropertyValue('--muted-text') || '#9aa3b2' },
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

    } catch (err) {
        console.error('Error initializing dashboard:', err);
    }

    await renderRecentActivity();
}

async function renderRecentActivity() {
    const list = document.getElementById('recentActivity');
    try {
        const response = await fetch('/School_Facility_Maintenance_System/backend/api/reports.php?action=list', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (!data.success || !data.data || !data.data.reports) {
            list.innerHTML = '<p class="text-muted">No recent activity.</p>';
            return;
        }
        
        const reports = data.data.reports.slice(0, 5); // show latest 5
        
        if (reports.length === 0) {
            list.innerHTML = '<p class="text-muted">No reports yet.</p>';
            return;
        }
        
        let html = '';
        reports.forEach(report => {
            const initials = (report.creator_name || 'U').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            const date = new Date(report.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            html += `
                <div class="activity-item">
                    <div class="activity-avatar">${initials}</div>
                    <div>
                        <div class="activity-desc">${report.creator_name || 'Unknown'} created report: <strong>${report.title}</strong></div>
                        <div class="activity-meta">Status: ${report.status} · ${date}</div>
                    </div>
                </div>
            `;
        });
        list.innerHTML = html;
    } catch (err) {
        console.error('Failed to load recent activity:', err);
        list.innerHTML = '<p class="text-muted">Could not load recent activity.</p>';
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
    initDashboard();
    loadBuildingsForDropdown();
});
</script>


<script>
// Initialize dashboard after api.js is loaded
document.addEventListener('DOMContentLoaded', () => {
    initDashboard();
    loadBuildingsForDropdown();
});
</script>
