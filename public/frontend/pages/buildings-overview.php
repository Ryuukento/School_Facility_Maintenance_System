<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$user = $_SESSION['user'];
$allowedRoles = ['super_admin', 'maintenance_admin', 'maintenance_staff'];
if (!in_array($user['role'] ?? '', $allowedRoles, true)) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php');
    exit;
}

$isMaintenanceContext = in_array($user['role'] ?? '', ['maintenance_admin', 'maintenance_staff'], true);
$defaultBackUrl = $isMaintenanceContext
    ? '/School_Facility_Maintenance_System/frontend/pages/maintenance-dashboard.php'
    : '/School_Facility_Maintenance_System/frontend/pages/dashboard.php';

$pageTitle = 'Buildings Overview - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="<?php echo htmlspecialchars(public_url('/frontend/assets/css/buildings-redesign.css?v=20260714-1')); ?>">

<main class="container buildings-page-container">
    <section class="buildings-page-hero">
        <div class="buildings-page-hero-copy">
            <p class="eyebrow">Facility workspace</p>
            <h2>Buildings, rooms, and deployed inventory</h2>
            <p>Navigate the campus inventory map with a clearer, faster view of every building and deployment.</p>
        </div>
        <div class="buildings-page-hero-metrics">
            <div><strong>Rooms</strong><span>Filter instantly</span></div>
            <div><strong>Deployments</strong><span>Print-ready reports</span></div>
            <div><strong>Inventory</strong><span>Room-by-room view</span></div>
        </div>
    </section>

    <div class="card buildings-overview-card">
        <div class="card-header" id="overviewHeader">
            <div>
                <h2 id="overviewTitle">Buildings</h2>
                <p class="text-muted mb-0" id="overviewSubtitle">Select a building to view floors</p>
            </div>
            <div id="overviewActions" class="header-actions">
                <!-- action buttons inserted by JS -->
            </div>
        </div>
        <div class="card-body">
            <div id="roomSearchWrap" class="room-search-wrap" hidden>
                <div class="room-search-toolbar">
                    <input type="search" id="roomSearchInput" class="form-control room-search-input" placeholder="Search rooms..." autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
                    <div class="room-category-filters" id="roomCategoryFilters" aria-label="Filter rooms by category">
                        <button type="button" class="room-category-filter is-active" data-room-category-filter="all">All</button>
                        <button type="button" class="room-category-filter" data-room-category-filter="Office">Office</button>
                        <button type="button" class="room-category-filter" data-room-category-filter="Classroom">Classroom</button>
                        <button type="button" class="room-category-filter" data-room-category-filter="Labs">Labs</button>
                        <button type="button" class="room-category-filter" data-room-category-filter="Utility">Utility</button>
                    </div>
                </div>
            </div>


            <div id="overviewContainer" class="summary-cards-grid">
                <!-- Cards will render here -->
            </div>
        </div>
    </div>
</main>

<!-- Modals for adding floors and items -->
<div id="floorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Floor</h2>
            <span class="modal-close" onclick="closeFloorModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="floorForm">
                <div class="form-group">
                    <label for="floorNameInput">Floor Name *</label>
                    <input type="text" id="floorNameInput" class="form-control" placeholder="e.g., 1st Floor" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeFloorModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveFloorData()">Save Floor</button>
        </div>
    </div>
</div>

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
                    <textarea id="buildingDescInput" class="form-control" rows="3" placeholder="Optional description"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeBuildingModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveBuildingData()">Save Building</button>
        </div>
    </div>
</div>

<div id="editBuildingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Building</h2>
            <span class="modal-close" onclick="closeEditBuildingModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="editBuildingForm">
                <div class="form-group">
                    <label for="editBuildingNameInput">Building Name *</label>
                    <input type="text" id="editBuildingNameInput" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editBuildingDescInput">Description</label>
                    <textarea id="editBuildingDescInput" class="form-control" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeEditBuildingModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveEditBuilding()">Save Changes</button>
        </div>
    </div>
</div>

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
                    <input type="number" id="roomCapacityInput" class="form-control" placeholder="e.g., 50" min="1" max="60">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeRoomModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveRoomData()">Save Room</button>
        </div>
    </div>
</div>

<div id="itemModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Item</h2>
            <span class="modal-close" onclick="closeItemModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="itemForm">
                <div class="form-group">
                    <label for="itemNameInput">Item Name *</label>
                    <input type="text" id="itemNameInput" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="itemStatusSelect">Status</label>
                    <select id="itemStatusSelect" class="form-control">
                        <option value="available">Available</option>
                        <option value="damaged">Damaged</option>
                        <option value="low_stock">Low Stock</option>
                        <option value="out_of_stock">Out of Stock</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="itemQuantityInput">Quantity</label>
                    <input type="number" id="itemQuantityInput" class="form-control" min="1" value="1">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeItemModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveItemData()">Save Item</button>
        </div>
    </div>
</div>

    <div id="printFilterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Print Deployed Items</h2>
                <span class="modal-close" onclick="closePrintFilterModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="printFilterForm">
                    <div class="form-group">
                        <label for="printBuildingSelect">Building</label>
                        <select id="printBuildingSelect" class="form-control" onchange="onPrintBuildingChange()">
                            <option value="">All Buildings</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="printRoomSelect">Room</label>
                        <select id="printRoomSelect" class="form-control">
                            <option value="">All Rooms</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closePrintFilterModal()">Cancel</button>
                <button class="btn btn-primary" onclick="previewPrintItems()">Preview Print</button>
            </div>
        </div>
    </div>

    <div id="printPreviewContainer" class="print-preview-page" hidden>
        <div class="print-preview-sheet">
            <div class="print-preview-header">
                <div class="print-preview-branding">
                    <img src="<?php echo htmlspecialchars(public_url('/frontend/assets/images/logo.png')); ?>" alt="PHILCST Logo" class="print-preview-logo" onerror="this.style.display='none'" />
                    <div>
                        <div class="print-preview-school">PHILCST Centralized School Facility Maintenance Reporting System</div>
                        <div class="print-preview-title">Deployed Items Inventory Report</div>
                    </div>
                </div>
                <div class="print-preview-meta">
                    <div><strong>Building:</strong> <span id="printBuildingLabel">All Buildings</span></div>
                    <div><strong>Room:</strong> <span id="printRoomLabel">All Rooms</span></div>
                    <div><strong>Date Generated:</strong> <span id="printDateLabel"></span></div>
                </div>
            </div>
            <div id="printPreviewBody" class="print-preview-body"></div>
            <div class="print-preview-footer">
                <div class="print-preview-summary">Total Items: <span id="printTotalItems">0</span></div>
                <div class="print-preview-signatures">
                    <div>Prepared by: _______________</div>
                    <div>Noted by: _______________</div>
                    <div>Date: _______________</div>
                </div>
            </div>
            <div class="print-preview-actions screen-only">
                <button class="btn btn-primary" onclick="window.print()">Print</button>
                <button class="btn btn-secondary" onclick="closePrintPreview()">Back</button>
            </div>
        </div>
    </div>
</div>

<script>
function extractList(payload, key) {
    if (!payload || payload.success !== true) {
        return [];
    }

    if (Array.isArray(payload[key])) {
        return payload[key];
    }

    if (payload.data && Array.isArray(payload.data[key])) {
        return payload.data[key];
    }

    return [];
}

function renderEmptyOverview(title, hint = '') {
    const container = document.getElementById('overviewContainer');
    container.innerHTML = '';

    const card = document.createElement('div');
    card.className = 'summary-card';
    card.style.cursor = 'default';

    const content = document.createElement('div');
    content.className = 'summary-card-content';

    const titleEl = document.createElement('h3');
    titleEl.className = 'summary-card-title';
    titleEl.textContent = title;
    content.appendChild(titleEl);

    if (hint) {
        const hintEl = document.createElement('p');
        hintEl.className = 'summary-card-desc';
        hintEl.textContent = hint;
        content.appendChild(hintEl);
    }

    card.appendChild(content);
    container.appendChild(card);
}

async function initOverview() {
    await loadBuildings();
    // TASK 18 — a 'building' notification (Building Updated) lands here via
    // ?highlight=<building_id>. loadBuildings() is the default landing view
    // (currentLevel = 'building'), so the card is already rendered — just
    // find and highlight it, mirroring highlightUserFromQuery() in users.php.
    highlightBuildingFromQuery();
}

function highlightBuildingFromQuery() {
    const params = new URLSearchParams(window.location.search);
    const highlightId = Number(params.get('highlight') || 0);
    if (!highlightId) return;

    const card = document.querySelector(`.summary-card[data-type="building"][data-id="${highlightId}"]`);
    if (!card) return;

    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    card.classList.add('summary-card-highlight');
    setTimeout(() => card.classList.remove('summary-card-highlight'), 3000);
}

function clearActions() {
    document.getElementById('overviewActions').innerHTML = '';
}

function updateRoomCategoryFilterUI() {
    document.querySelectorAll('[data-room-category-filter]').forEach((button) => {
        const isActive = (button.getAttribute('data-room-category-filter') || 'all') === roomCategoryFilter;
        button.classList.toggle('is-active', isActive);
    });
}

function setRoomSearchVisibility(isVisible, placeholder = 'Search rooms...', showCategoryFilters = false) {
    const wrap = document.getElementById('roomSearchWrap');
    const input = document.getElementById('roomSearchInput');
    const filterWrap = document.getElementById('roomCategoryFilters');

    if (!wrap || !input) return;

    wrap.hidden = !isVisible;
    input.placeholder = placeholder;
    if (filterWrap) {
        filterWrap.hidden = !isVisible || !showCategoryFilters;
    }
    if (!isVisible) {
        input.value = '';
        roomSearchTerm = '';
        roomCategoryFilter = 'all';
        updateRoomCategoryFilterUI();
        itemSearchTerm = '';
    }
}

function getFilteredRooms() {
    const term = String(roomSearchTerm || '').trim().toLowerCase();
    const categoryFilter = String(roomCategoryFilter || 'all');
    const rooms = Array.isArray(currentRoomsCache) ? currentRoomsCache : [];

    return rooms.filter((room) => {
        const roomCategory = getRoomCategory(room.name);
        const categoryMatches = categoryFilter === 'all' || roomCategory === categoryFilter;

        if (!categoryMatches) {
            return false;
        }

        const haystack = [room.name, room.code, room.description, room.room_number]
            .map((value) => String(value || '').toLowerCase())
            .join(' ');

        return !term || haystack.includes(term);
    });
}

function getFilteredItems() {
    const term = String(itemSearchTerm || '').trim().toLowerCase();

    if (!term) {
        return Array.isArray(currentItemsCache) ? currentItemsCache : [];
    }

    return (Array.isArray(currentItemsCache) ? currentItemsCache : []).filter((item) => {
        const haystack = [item.name, item.status, item.description, item.quantity]
            .map((value) => String(value || '').toLowerCase())
            .join(' ');

        return haystack.includes(term);
    });
}

function addActionButton(text, onClick, variant = 'secondary') {
    const btn = document.createElement('button');
    btn.className = `btn btn-${variant}`;
    btn.textContent = text;
    btn.onclick = onClick;
    document.getElementById('overviewActions').appendChild(btn);
}

async function openPrintFilterModal() {
    const buildingSelect = document.getElementById('printBuildingSelect');
    const roomSelect = document.getElementById('printRoomSelect');

    buildingSelect.innerHTML = '<option value="">All Buildings</option>';
    roomSelect.innerHTML = '<option value="">All Rooms</option>';

    try {
        const res = await fetch(window.SFMS_PUBLIC_URL('/api/buildings'), {
            credentials: 'include'
        });
        const data = await res.json();
        if (data.success) {
            const buildings = extractList(data, 'buildings');
            buildings.forEach((building) => {
                const option = document.createElement('option');
                option.value = building.id;
                option.textContent = building.name;
                buildingSelect.appendChild(option);
            });

            if (currentBuildingId) {
                buildingSelect.value = String(currentBuildingId);
                await onPrintBuildingChange();
                if (currentRoomId) {
                    roomSelect.value = String(currentRoomId);
                }
            }
        } else {
            alert(data.message || 'Failed to load buildings');
        }
    } catch (err) {
        console.error('Error loading buildings for print filter', err);
        alert('Error loading buildings for print filter. Check console for details.');
    }

    document.getElementById('printFilterModal').classList.add('show');
}

function closePrintFilterModal() {
    document.getElementById('printFilterModal').classList.remove('show');
    document.getElementById('printFilterForm').reset();
}

async function onPrintBuildingChange() {
    const buildingId = document.getElementById('printBuildingSelect').value;
    const roomSelect = document.getElementById('printRoomSelect');
    roomSelect.innerHTML = '<option value="">All Rooms</option>';

    if (!buildingId) {
        return;
    }

    try {
        const res = await fetch(window.SFMS_PUBLIC_URL('/api/rooms?building_id=' + encodeURIComponent(buildingId) + '&per_page=200'), {
            credentials: 'include'
        });
        const data = await res.json();
        if (data.success) {
            const rooms = extractList(data, 'rooms');
            rooms.forEach((room) => {
                const option = document.createElement('option');
                option.value = room.id;
                option.textContent = room.name;
                roomSelect.appendChild(option);
            });
        } else {
            alert(data.message || 'Failed to load rooms');
        }
    } catch (err) {
        console.error('Error loading rooms for print filter', err);
        alert('Error loading rooms for print filter. Check console for details.');
    }
}

function closePrintPreview() {
    const preview = document.getElementById('printPreviewContainer');
    if (preview) {
        preview.hidden = true;
    }
}

async function previewPrintItems() {
    closePrintFilterModal();

    const buildingSelect = document.getElementById('printBuildingSelect');
    const roomSelect = document.getElementById('printRoomSelect');
    const buildingId = buildingSelect.value;
    const roomId = roomSelect.value;
    const buildingName = buildingId ? buildingSelect.options[buildingSelect.selectedIndex].text : 'All Buildings';
    const roomName = roomId ? roomSelect.options[roomSelect.selectedIndex].text : 'All Rooms';
    const previewBody = document.getElementById('printPreviewBody');

    document.getElementById('printBuildingLabel').textContent = buildingName;
    document.getElementById('printRoomLabel').textContent = roomName;
    document.getElementById('printDateLabel').textContent = new Date().toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    document.getElementById('printPreviewContainer').hidden = false;
    previewBody.innerHTML = '<div class="print-preview-loading">Loading deployed items…</div>';

    try {
        const queryParams = [];
        if (buildingId) {
            queryParams.push('building_id=' + encodeURIComponent(buildingId));
        }
        if (roomId) {
            queryParams.push('room_id=' + encodeURIComponent(roomId));
        }
        const queryString = queryParams.length ? '?' + queryParams.join('&') : '';
        const res = await fetch(window.SFMS_PUBLIC_URL('/api/buildings/deployed-items' + queryString), {
            credentials: 'include'
        });
        const data = await res.json();
        if (data.success) {
            const items = extractList(data, 'items');
            renderPrintPreview(items);
        } else {
            alert(data.message || 'Failed to load deployed items');
            previewBody.innerHTML = '<div class="print-preview-empty">Unable to load deployed items.</div>';
            document.getElementById('printTotalItems').textContent = '0';
        }
    } catch (err) {
        console.error('Error loading deployed items', err);
        alert('Error loading deployed items. Check console for details.');
        previewBody.innerHTML = '<div class="print-preview-empty">Error loading deployed items.</div>';
        document.getElementById('printTotalItems').textContent = '0';
    }
}

function renderPrintPreview(items) {
    const previewBody = document.getElementById('printPreviewBody');
    if (!previewBody) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        previewBody.innerHTML = '<div class="print-preview-empty">No deployed items found for the selected scope.</div>';
        document.getElementById('printTotalItems').textContent = '0';
        return;
    }

    previewBody.innerHTML = '';
    const showGrouped = document.getElementById('printRoomSelect').value === '';

    if (showGrouped) {
        const grouped = items.reduce((map, item) => {
            const roomName = item.room_name || 'Unassigned Room';
            if (!map[roomName]) {
                map[roomName] = [];
            }
            map[roomName].push(item);
            return map;
        }, {});

        Object.entries(grouped).forEach(([roomName, roomItems]) => {
            const group = document.createElement('div');
            group.className = 'print-room-group';

            const heading = document.createElement('div');
            heading.className = 'print-room-heading';
            heading.textContent = 'Room: ' + roomName;
            group.appendChild(heading);

            group.appendChild(createPrintTable(roomItems));

            const subtotal = document.createElement('div');
            subtotal.className = 'print-room-subtotal';
            subtotal.textContent = 'Subtotal: ' + roomItems.length + ' item' + (roomItems.length === 1 ? '' : 's');
            group.appendChild(subtotal);

            previewBody.appendChild(group);
        });
    } else {
        previewBody.appendChild(createPrintTable(items));
    }

    document.getElementById('printTotalItems').textContent = String(items.length);
}

function createPrintTable(items) {
    const table = document.createElement('table');
    table.className = 'print-preview-table';

    const thead = document.createElement('thead');
    thead.innerHTML = '<tr><th>#</th><th>Item Name</th><th>Category</th><th>Quantity</th><th>Unit</th><th>Condition</th><th>Date Dispatched</th><th>Dispatch Code</th></tr>';
    table.appendChild(thead);

    const tbody = document.createElement('tbody');
    items.forEach((item, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${escapeHtml(item.item_name)}</td>
            <td>${escapeHtml(item.category || '')}</td>
            <td>${escapeHtml(item.quantity)}</td>
            <td>${escapeHtml(item.unit || '')}</td>
            <td>${escapeHtml(item.condition || '')}</td>
            <td>${escapeHtml(formatPrintDate(item.date_dispatched))}</td>
            <td>${escapeHtml(item.dispatch_code || '')}</td>
        `;
        tbody.appendChild(row);
    });

    table.appendChild(tbody);
    return table;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatPrintDate(value) {
    if (!value) {
        return '';
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

async function loadBuildings() {
    currentLevel = 'building';
    currentBuildingId = null; currentBuildingName = '';
    currentFloorId = null; currentFloorName = '';
    currentRoomId = null;
    currentRoomsCache = [];
    currentItemsCache = [];
    roomSearchTerm = '';
    itemSearchTerm = '';
    setRoomSearchVisibility(false, 'Search rooms...', false);
    buildingsCache = {};
    document.getElementById('overviewTitle').textContent = 'Buildings';
    document.getElementById('overviewSubtitle').textContent = 'Select a building to view floors';
    clearActions();
    addActionButton('Print Deployed Items', () => openPrintFilterModal());
    addActionButton('Add Building', () => openBuildingModal(), 'primary');
    // replaced refresh with a back button that navigates to previous page
    addActionButton('Back', () => {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            // Role-aware fallback when page is opened directly.
            window.location.href = defaultBackUrl;
        }
    });
    
    const container = document.getElementById('overviewContainer');
    container.innerHTML = '';
    container.className = 'summary-cards-grid';

    try {
        const res = await fetch(window.SFMS_PUBLIC_URL('/api/buildings'), {
            credentials: 'include'
        });
        const data = await res.json();
        if (data.success) {
            const buildings = extractList(data, 'buildings');

            if (buildings.length === 0) {
                renderEmptyOverview('No buildings found', 'Create a building first to view floors and rooms.');
                return;
            }

            buildings.forEach(b => {
                buildingsCache[b.id] = b;
                const card = createEntityCard(b.name, `Floors: ${b.floor_count}, Rooms: ${b.room_count}`, '🏢', 'building', b.id);
                card.onclick = () => loadFloors(b.id, b.name);
                container.appendChild(card);
            });
        } else {
            alert(data.message || 'Failed to load buildings');
        }
    } catch (err) {
        console.error('Error loading buildings', err);
        alert('Error loading buildings. Check console for details.');
    }
}

async function loadFloors(buildingId, buildingName) {
    console.log('loadFloors called', {buildingId, buildingName});
    currentLevel = 'floor'; currentBuildingId = buildingId; currentBuildingName = buildingName;
    currentFloorId = null; currentFloorName = '';
    currentRoomId = null;
    currentRoomsCache = [];
    currentItemsCache = [];
    roomSearchTerm = '';
    itemSearchTerm = '';
    setRoomSearchVisibility(false, 'Search rooms...', false);
    document.getElementById('overviewTitle').textContent = 'Floors of ' + buildingName;
    document.getElementById('overviewSubtitle').textContent = 'Select a floor to view rooms';
    clearActions();
    addActionButton('Print Deployed Items', () => openPrintFilterModal());
    addActionButton('Add Floor', () => openFloorModal());
    addActionButton('Back', loadBuildings);

    const container = document.getElementById('overviewContainer');
    container.innerHTML = '';
    container.className = 'floors-grid';

    try {
        const res = await fetch(window.SFMS_PUBLIC_URL(`/api/buildings/${encodeURIComponent(buildingId)}/floors`), {
            credentials: 'include'
        });
        if (!res.ok) {
            const txt = await res.text();
            console.error('Floor API responded with non-OK status', res.status, txt);
            alert('Error loading floors: ' + (txt || res.statusText));
            return;
        }
        const data = await res.json();
        if (data.success) {
            const floors = extractList(data, 'floors');

            if (floors.length === 0) {
                renderEmptyOverview('No floors found', 'Add a floor for this building.');
                return;
            }

            floors.forEach(f => {
                const card = createFloorCard(f);
                card.onclick = () => loadRooms(f.id, f.name);
                container.appendChild(card);
            });
        } else {
            alert(data.message || 'Failed to load floors');
        }
    } catch (err) {
        console.error('Error loading floors', err);
        alert('Error loading floors. Check console for details.');
    }
}

async function loadRooms(floorId, floorName) {
    currentLevel = 'room'; currentFloorId = floorId; currentFloorName = floorName; currentRoomId = null;
    currentRoomsCache = [];
    currentItemsCache = [];
    roomSearchTerm = '';
    roomCategoryFilter = 'all';
    itemSearchTerm = '';
    document.getElementById('overviewTitle').textContent = 'Rooms of ' + floorName;
    document.getElementById('overviewSubtitle').textContent = 'Select a room to view items';
    clearActions();
    addActionButton('Print Deployed Items', () => openPrintFilterModal());
    addActionButton('Add New Room', () => openRoomModal(currentBuildingId, currentFloorId), 'primary');
    addActionButton('Back', () => loadFloors(currentBuildingId, currentBuildingName));
    setRoomSearchVisibility(true, 'Search rooms...', true);

    const container = document.getElementById('overviewContainer');
    container.innerHTML = '';
    container.className = 'summary-cards-grid';

    try {
        const res = await fetch(window.SFMS_PUBLIC_URL('/api/rooms?floor_id=' + floorId), {
            credentials: 'include'
        });
        const data = await res.json();
        if (data.success) {
            const rooms = extractList(data, 'rooms');
            currentRoomsCache = rooms;

            renderRoomsOverview();

            if (rooms.length === 0) {
                renderEmptyOverview('No rooms found', 'Add a room for this floor.');
                return;
            }
        } else {
            alert(data.message || 'Failed to load rooms');
        }
    } catch (err) {
        console.error('Error loading rooms', err);
        alert('Error loading rooms. Check console for details.');
    }
}

function setRoomCategoryFilter(filter) {
    roomCategoryFilter = filter || 'all';
    updateRoomCategoryFilterUI();

    if (currentLevel === 'room') {
        renderRoomsOverview();
    }
}

function getRoomCategory(roomName) {
    const name = String(roomName || '').toLowerCase();
    if (name.includes('lab') || name.includes('laboratory') || name.includes('science lab') || name.includes('computer lab')) {
        return 'Labs';
    }
    if (name.includes('utility') || name.includes('storage') || name.includes('stock') || name.includes('maintenance') || name.includes('janitor') || name.includes('restroom') || name.includes('comfort room')) {
        return 'Utility';
    }
    if (name.includes('office') || name.includes('college') || name.includes('faculty') || name.includes('principal') || name.includes('president') || name.includes('dean') || name.includes('department')) {
        return 'Office';
    }
    return 'Classroom';
}

function getRoomIcon(roomName, category) {
    const name = String(roomName || '').toLowerCase();
    
    if (category === 'Office') {
        if (name.includes('computer') || name.includes('information') || name.includes('system')) {
            return '💻';
        }
        if (name.includes('principal') || name.includes('president')) {
            return '👥';
        }
        if (name.includes('research') || name.includes('graduate') || name.includes('studies')) {
            return '📚';
        }
        if (name.includes('assistant') || name.includes('secretary')) {
            return '📋';
        }
        if (name.includes('college') || name.includes('faculty')) {
            return '🎓';
        }
        return '🗂️';
    }
    
    // Classroom
    return '📚';
}

function renderRoomsOverview() {
    const container = document.getElementById('overviewContainer');
    if (!container) return;

    const rooms = getFilteredRooms();
    const hasActiveRoomFilter = String(roomCategoryFilter || 'all') !== 'all';

    if (!rooms.length) {
        const hasSearchTerm = Boolean(String(roomSearchTerm || '').trim());
        renderEmptyOverview(
            hasSearchTerm || hasActiveRoomFilter ? 'No rooms matched your filters' : 'No rooms found',
            hasSearchTerm || hasActiveRoomFilter ? 'Try another room name, switch category, or clear the filters.' : 'Add a room for this floor.'
        );
        return;
    }

    container.innerHTML = '';
    rooms.forEach((room) => {
        const category = getRoomCategory(room.name);
        const icon = getRoomIcon(room.name, category);
        const card = createEntityCard(room.name, category, icon, 'room', room.id);
        card.onclick = () => loadItems(room.id, room.name);
        container.appendChild(card);
    });
}

async function loadItems(roomId, roomName) {
    currentLevel = 'items'; currentRoomId = roomId;
    currentItemsCache = [];
    roomSearchTerm = '';
    itemSearchTerm = '';
    setRoomSearchVisibility(true, 'Search items...', false);
    document.getElementById('overviewTitle').textContent = 'Items in ' + roomName;
    document.getElementById('overviewSubtitle').textContent = 'Search items in this room';
    clearActions();
    addActionButton('Print Deployed Items', () => openPrintFilterModal());
    addActionButton('Add Item', () => openItemModal());
    addActionButton('Back', () => loadRooms(currentFloorId, currentFloorName));

    const container = document.getElementById('overviewContainer');
    container.innerHTML = '';

    try {
        const res = await fetch(window.SFMS_PUBLIC_URL('/api/items?room_id=' + roomId), {
            credentials: 'same-origin'
        });
        const data = await res.json();
        if (data.success) {
            const items = extractList(data, 'items');
            currentItemsCache = items;

            renderItemsOverview();

            if (items.length === 0) {
                renderEmptyOverview('No items found', 'Add an item for this room.');
                return;
            }
        } else {
            alert(data.message || 'Failed to load items');
        }
    } catch (err) {
        console.error('Error loading items', err);
        alert('Error loading items. Check console for details.');
    }
}

function renderItemsOverview() {
    const container = document.getElementById('overviewContainer');
    if (!container) return;

    const items = getFilteredItems();

    if (!items.length) {
        renderEmptyOverview(itemSearchTerm ? 'No items matched your search' : 'No items found', itemSearchTerm ? 'Try another item name or clear the search.' : 'Add an item for this room.');
        return;
    }

    container.innerHTML = '';
    items.forEach((item) => {
        const card = createEntityCard(item.name, `Qty: ${item.quantity || 0}, Status: ${item.status}`, '📦', 'item', item.id);
        container.appendChild(card);
    });
}

function createEntityCard(title, subtitle = '', icon = '', type = null, id = null) {
    const div = document.createElement('div');
    div.className = 'summary-card summary-card-action';
    div.style.cursor = 'pointer';
    if (type && id) {
        div.dataset.type = type;
        div.dataset.id = id;
    }
    const content = document.createElement('div');
    content.className = 'summary-card-content';
    const titleEl = document.createElement('h3');
    titleEl.className = 'summary-card-title';
    titleEl.textContent = title;
    content.appendChild(titleEl);
    if (subtitle) {
        const isCategoryBadge = ['Office', 'Classroom', 'Labs', 'Utility'].includes(subtitle);
        if (isCategoryBadge) {
            const badge = document.createElement('span');
            badge.className = 'summary-card-badge';
            badge.textContent = subtitle;
            content.appendChild(badge);
        } else {
            const desc = document.createElement('p');
            desc.className = 'summary-card-desc';
            desc.textContent = subtitle;
            content.appendChild(desc);
        }
    }
    div.appendChild(content);
    if (icon) {
        const iconEl = document.createElement('div');
        iconEl.className = 'summary-card-icon';
        iconEl.textContent = icon;
        div.appendChild(iconEl);
    }
    if (type && id) {
        if (type === 'building') {
            const actions = document.createElement('div');
            actions.className = 'card-actions';
            const editBtn = document.createElement('button');
            editBtn.className = 'btn btn-secondary';
            editBtn.textContent = 'Edit';
            editBtn.onclick = (event) => {
                event.stopPropagation();
                openEditBuildingModal(id);
            };
            actions.appendChild(editBtn);
            div.appendChild(actions);
        } else if (type === 'item') {
            const del = document.createElement('div');
            del.className = 'card-delete';
            del.title = 'Delete';
            del.textContent = '🗑️';
            del.onclick = (event) => {
                event.stopPropagation();
                deleteEntity(type, id);
            };
            div.appendChild(del);
        }
    }
    return div;
}

function createFloorCard(floor) {
    const div = document.createElement('div');
    div.className = 'floor-card';
    div.style.cursor = 'pointer';
    div.dataset.type = 'floor';
    div.dataset.id = floor.id;

    // Extract floor number from name (e.g., "1st Floor" -> "1F")
    const floorNumberMatch = floor.name.match(/(\d+)/);
    const floorNumber = floorNumberMatch ? floorNumberMatch[1] + 'F' : '•';

    // Floor badge
    const badge = document.createElement('div');
    badge.className = 'floor-card-badge';
    badge.textContent = floorNumber;
    div.appendChild(badge);

    // Content section
    const content = document.createElement('div');
    content.className = 'floor-card-content';

    const nameEl = document.createElement('h3');
    nameEl.className = 'floor-card-name';
    nameEl.textContent = floor.name;
    content.appendChild(nameEl);

    if (floor.description) {
        const descEl = document.createElement('p');
        descEl.className = 'floor-card-desc';
        descEl.textContent = floor.description;
        content.appendChild(descEl);
    }

    const statsEl = document.createElement('div');
    statsEl.className = 'floor-card-stats';
    statsEl.innerHTML = `
        <span class="floor-stat"><strong>${floor.room_count || 0}</strong> rooms</span>
        <span class="floor-stat"><strong>${floor.item_count || 0}</strong> items tracked</span>
    `;
    content.appendChild(statsEl);

    div.appendChild(content);

    // Arrow button
    const arrow = document.createElement('div');
    arrow.className = 'floor-card-arrow';
    arrow.textContent = '→';
    div.appendChild(arrow);

    return div;
}

document.addEventListener('DOMContentLoaded', () => {
    const roomSearchInput = document.getElementById('roomSearchInput');
    if (roomSearchInput) {
        roomSearchInput.addEventListener('input', () => {
            const value = roomSearchInput.value.trim().toLowerCase();

            if (currentLevel === 'room') {
                roomSearchTerm = value;
                renderRoomsOverview();
            } else if (currentLevel === 'items') {
                itemSearchTerm = value;
                renderItemsOverview();
            }
        });
    }

    document.querySelectorAll('[data-room-category-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            setRoomCategoryFilter(button.getAttribute('data-room-category-filter') || 'all');
        });
    });
});

// Delete helper
async function deleteEntity(type, id) {
    if (!confirm('Are you sure you want to delete this ' + type + '?')) return;

    // Item deletes use the Laravel REST endpoint
    if (type === 'item') {
        try {
            const resp = await fetch(window.SFMS_PUBLIC_URL(`/api/items/${id}`), {
                method: 'DELETE',
                credentials: 'same-origin',
            });
            const data = await resp.json();
            if (data.success) {
                loadItems(currentRoomId, '');
            } else {
                alert('Delete failed: ' + data.message);
            }
        } catch (e) {
            console.error('Delete error', e);
            alert('An error occurred while deleting. See console.');
        }
        return;
    }

    // Building / floor / room deletes — Laravel REST routes
    let deleteUrl = '';
    switch (type) {
        case 'building': deleteUrl = window.SFMS_PUBLIC_URL(`/api/buildings/${id}`); break;
        case 'floor':    deleteUrl = window.SFMS_PUBLIC_URL(`/api/buildings/${currentBuildingId}/floors/${id}`); break;
        case 'room':     deleteUrl = window.SFMS_PUBLIC_URL(`/api/rooms/${id}`); break;
    }
    try {
        const resp = await fetch(deleteUrl, {
            method: 'DELETE',
            credentials: 'same-origin'
        });
        const data = await resp.json();
        if (data.success) {
            switch (currentLevel) {
                case 'building': loadBuildings(); break;
                case 'floor': loadFloors(currentBuildingId, currentBuildingName); break;
                case 'room': loadRooms(currentFloorId, currentFloorName); break;
                case 'items': loadItems(currentRoomId, ''); break;
            }
        } else {
            alert('Delete failed: ' + data.message);
        }
    } catch (e) {
        console.error('Delete error', e);
        alert('An error occurred while deleting. See console.');
    }
}

function openEditBuildingModal(buildingId) {
    const building = buildingsCache[buildingId];
    if (!building) {
        alert('Building not found. Please refresh the list.');
        return;
    }
    currentEditBuildingId = buildingId;
    document.getElementById('editBuildingNameInput').value = building.name || '';
    document.getElementById('editBuildingDescInput').value = building.description || '';
    document.getElementById('editBuildingModal').classList.add('show');
    document.getElementById('editBuildingNameInput').focus();
}

function closeEditBuildingModal() {
    document.getElementById('editBuildingModal').classList.remove('show');
    document.getElementById('editBuildingForm').reset();
    currentEditBuildingId = null;
}

async function saveEditBuilding() {
    const name = document.getElementById('editBuildingNameInput').value.trim();
    const description = document.getElementById('editBuildingDescInput').value.trim();
    if (!currentEditBuildingId) {
        alert('No building selected for edit.');
        return;
    }
    if (!name) {
        alert('Please enter building name');
        return;
    }
    try {
        const res = await fetch(window.SFMS_PUBLIC_URL(`/api/buildings/${currentEditBuildingId}`), {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, description })
        });
        const data = await res.json();
        if (data.success) {
            closeEditBuildingModal();
            loadBuildings();
        } else {
            alert(data.message || 'Failed to update building');
        }
    } catch (err) {
        console.error('Error updating building', err);
        alert('Error updating building. Check console for details.');
    }
}

function openBuildingModal() {
    document.getElementById('buildingModal').classList.add('show');
    document.getElementById('buildingNameInput').focus();
}

function closeBuildingModal() {
    document.getElementById('buildingModal').classList.remove('show');
    document.getElementById('buildingForm').reset();
}

async function saveBuildingData() {
    const buildingName = document.getElementById('buildingNameInput').value.trim();
    const buildingDesc = document.getElementById('buildingDescInput').value.trim();

    if (!buildingName) {
        alert('Please enter a building name');
        return;
    }

    try {
        const response = await fetch(window.SFMS_PUBLIC_URL('/api/buildings'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: buildingName, description: buildingDesc })
        });
        const result = await response.json();
        if (result.success) {
            closeBuildingModal();
            loadBuildings();
        } else {
            alert(result.message || 'Failed to add building');
        }
    } catch (error) {
        console.error('Error saving building:', error);
        alert('Error saving building. Please try again.');
    }
}

// Floor modal functions
function openFloorModal() {
    document.getElementById('floorModal').classList.add('show');
    document.getElementById('floorNameInput').focus();
}
function closeFloorModal() {
    document.getElementById('floorModal').classList.remove('show');
    document.getElementById('floorForm').reset();
}
async function saveFloorData() {
    const name = document.getElementById('floorNameInput').value.trim();
    if (!name) {
        alert('Please enter floor name');
        return;
    }
    if (!currentBuildingId) {
        // should never happen but guard just in case
        alert('No building selected. Please go back and choose a building first.');
        return;
    }
    console.log('Saving floor', {buildingId: currentBuildingId, buildingName: currentBuildingName, name});
    try {
        const res = await fetch(
            window.SFMS_PUBLIC_URL(`/api/buildings/${currentBuildingId}/floors`),
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name })
            }
        );
        if (!res.ok) {
            const txt = await res.text();
            console.error('Floor API responded with non-OK status', res.status, txt);
            alert('Error saving floor (HTTP ' + res.status + '): ' + (txt.substring(0, 100) || res.statusText));
            return;
        }
        const data = await res.json();
        if (data.success) {
            closeFloorModal();
            // reload current building's floors using stored name variable
            loadFloors(currentBuildingId, currentBuildingName);
        } else {
            alert(data.message || 'Failed to save floor');
        }
    } catch (err) {
        console.error('Error in saveFloorData', err);
        alert('An unexpected error occurred while saving the floor. Details: ' + err.message);
    }
}

// Room modal functions
function openRoomModal(defaultBuildingId = '', defaultFloorId = '') {
    document.getElementById('roomModal').classList.add('show');
    loadBuildingsInModal(defaultBuildingId, defaultFloorId);
    document.getElementById('roomNameInput').focus();
}
function closeRoomModal() {
    document.getElementById('roomModal').classList.remove('show');
    document.getElementById('roomForm').reset();
}
function loadBuildingsInModal(preselectBuildingId = '', preselectFloorId = '') {
    const buildingSelect = document.getElementById('roomBuildingSelect');
    const floorSelect = document.getElementById('roomFloorSelect');
    buildingSelect.innerHTML = '<option value="">Choose a building</option>';
    floorSelect.innerHTML = '<option value="">Choose a floor</option>';

    fetch(window.SFMS_PUBLIC_URL('/api/buildings'), {
        credentials: 'same-origin'
    })
        .then(res => res.json())
        .then(data => {
            const buildings = extractList(data, 'buildings');
            if (data.success && buildings.length > 0) {
                buildings.forEach(building => {
                    const option = document.createElement('option');
                    option.value = building.id;
                    option.textContent = building.name;
                    buildingSelect.appendChild(option);
                });

                if (preselectBuildingId) {
                    buildingSelect.value = String(preselectBuildingId);
                    loadFloorsForRoom(preselectBuildingId, preselectFloorId);
                }
            }
        })
        .catch(err => {
            console.error('Error loading buildings for room modal', err);
        });
}
function loadFloorsForRoom(buildingId, preselectFloorId = '') {
    const floorSelect = document.getElementById('roomFloorSelect');
    floorSelect.innerHTML = '<option value="">Choose a floor</option>';
    if (!buildingId) return;
    fetch(window.SFMS_PUBLIC_URL(`/api/buildings/${encodeURIComponent(buildingId)}/floors`), {
        credentials: 'same-origin'
    })
        .then(res => res.json())
        .then(data => {
            const floors = extractList(data, 'floors');
            if (data.success && floors.length > 0) {
                floors.forEach(floor => {
                    const opt = document.createElement('option');
                    opt.value = floor.id;
                    opt.textContent = floor.name;
                    floorSelect.appendChild(opt);
                });

                if (preselectFloorId) {
                    floorSelect.value = String(preselectFloorId);
                }
            }
        })
        .catch(err => {
            console.error('Error loading floors for room modal', err);
        });
}
async function saveRoomData() {
    const buildingId = document.getElementById('roomBuildingSelect').value.trim();
    const floorId = document.getElementById('roomFloorSelect').value.trim();
    const roomName = document.getElementById('roomNameInput').value.trim();
    const roomCapacity = document.getElementById('roomCapacityInput').value.trim();

    if (!buildingId) { alert('Please select a building'); return; }
    if (!floorId) { alert('Please select a floor'); return; }
    if (!roomName) { alert('Please enter a room name/number'); return; }
    if (roomCapacity !== '' && (Number(roomCapacity) < 1 || Number(roomCapacity) > 60)) {
        alert('Room capacity must be between 1 and 60 only.');
        return;
    }

    try {
        const response = await fetch(window.SFMS_PUBLIC_URL('/api/rooms'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                building_id: buildingId,
                floor_id: floorId,
                name: roomName,
                capacity: roomCapacity || null
            })
        });
        const result = await response.json();
        if (result.success) {
            closeRoomModal();
            loadRooms(currentFloorId, currentFloorName);
        } else {
            alert(result.message || 'Failed to add room');
        }
    } catch (error) {
        console.error('Error saving room:', error);
        alert('Error saving room. Please try again.');
    }
}

// Item modal functions
function openItemModal() {
    document.getElementById('itemModal').classList.add('show');
    document.getElementById('itemNameInput').focus();
}
function closeItemModal() {
    document.getElementById('itemModal').classList.remove('show');
    document.getElementById('itemForm').reset();
}
async function saveItemData() {
    const name = document.getElementById('itemNameInput').value.trim();
    const status = document.getElementById('itemStatusSelect').value;
    const quantity = document.getElementById('itemQuantityInput').value;
    if (!name) { alert('Please enter item name'); return; }
    const res = await fetch(window.SFMS_PUBLIC_URL('/api/items'), {
        method:'POST', headers:{'Content-Type':'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify({ room_id: currentRoomId, name, status, quantity })
    });
    const data = await res.json();
    if (data.success) {
        closeItemModal();
        loadItems(currentRoomId, document.getElementById('overviewTitle').textContent.split(' in ')[1]);
    } else {
        alert(data.message);
    }
}

// initialize on load
window.addEventListener('DOMContentLoaded', initOverview);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>





