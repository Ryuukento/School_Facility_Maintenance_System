<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$pageTitle = 'Buildings Overview - SFMS';
include __DIR__ . '/../includes/header.php';

$user = $_SESSION['user'];
?>

<main class="container">
    <div class="card">
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

<style>
/* reuse existing modal styles from dashboard; could inline or include same file */
.modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
.modal.show { display: block; }
.modal-content { background: #fff; max-width: 500px; margin: 100px auto; border-radius: 8px; overflow: hidden; }
.modal-header, .modal-footer { padding: 15px; background: #f5f5f5; }
.modal-body { padding: 15px; }
.modal-close { float: right; cursor: pointer; font-size: 24px; }
.header-actions { display: flex; gap: 10px; align-items: center; }
.breadcrumb { background: none; padding: 0; margin-bottom: 15px; }
.card-delete {
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 16px;
    cursor: pointer;
    opacity: 0.6;
}
.card-delete:hover { opacity: 1; }
.summary-card { position: relative; }
.card-actions {
    position: absolute;
    right: 10px;
    bottom: 10px;
    display: flex;
    gap: 8px;
}
.card-actions .btn { padding: 4px 10px; font-size: 12px; }
.btn-danger { background: #c0392b; color: #fff; }
.btn-danger:hover { background: #a93226; }
</style>

<script>
let currentLevel = 'building';
let currentBuildingId = null;
let currentBuildingName = '';
let currentFloorId = null;
let currentFloorName = '';
let currentRoomId = null;
let currentEditBuildingId = null;
let buildingsCache = {};

async function initOverview() {
    loadBuildings();
}

function clearActions() {
    document.getElementById('overviewActions').innerHTML = '';
}

function addActionButton(text, onClick) {
    const btn = document.createElement('button');
    btn.className = 'btn btn-secondary';
    btn.textContent = text;
    btn.onclick = onClick;
    document.getElementById('overviewActions').appendChild(btn);
}

async function loadBuildings() {
    currentLevel = 'building';
    currentBuildingId = null; currentBuildingName = '';
    currentFloorId = null; currentFloorName = '';
    currentRoomId = null;
    buildingsCache = {};
    document.getElementById('overviewTitle').textContent = 'Buildings';
    document.getElementById('overviewSubtitle').textContent = 'Select a building to view floors';
    clearActions();
    // replaced refresh with a back button that navigates to previous page
    addActionButton('Back', () => {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            // fallback to the dashboard if there is no history
            window.location.href = '/School_Facility_Maintenance_System/frontend/pages/dashboard.php';
        }
    });
    
    const container = document.getElementById('overviewContainer');
    container.innerHTML = '';

    try {
        const res = await fetch('/School_Facility_Maintenance_System/backend/api/buildings.php?action=list', {
            credentials: 'same-origin'
        });
        const data = await res.json();
        if (data.success) {
            data.buildings.forEach(b => {
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
    document.getElementById('overviewTitle').textContent = 'Floors of ' + buildingName;
    document.getElementById('overviewSubtitle').textContent = 'Select a floor to view rooms';
    clearActions();
    addActionButton('Add Floor', () => openFloorModal());
    addActionButton('Back', loadBuildings);

    const container = document.getElementById('overviewContainer');
    container.innerHTML = '';

    try {
        const res = await fetch('/School_Facility_Maintenance_System/backend/api/floors.php?action=getByBuilding&building_id=' + encodeURIComponent(buildingId), {
            credentials: 'same-origin'
        });
        if (!res.ok) {
            const txt = await res.text();
            console.error('Floor API responded with non-OK status', res.status, txt);
            alert('Error loading floors: ' + (txt || res.statusText));
            return;
        }
        const data = await res.json();
        if (data.success) {
            data.floors.forEach(f => {
                const card = createEntityCard(f.name, '', '📐', 'floor', f.id);
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
    document.getElementById('overviewTitle').textContent = 'Rooms of ' + floorName;
    document.getElementById('overviewSubtitle').textContent = 'Select a room to view items';
    clearActions();
    addActionButton('Add Room', () => {
        openRoomModal();
        // after buildings list is loaded we set the selects
        setTimeout(() => {
            document.getElementById('roomBuildingSelect').value = currentBuildingId;
            loadFloorsForRoom(currentBuildingId);
            setTimeout(() => {
                document.getElementById('roomFloorSelect').value = currentFloorId;
            }, 200);
        }, 200);
    });
    addActionButton('Back', () => loadFloors(currentBuildingId, currentBuildingName));

    const container = document.getElementById('overviewContainer');
    container.innerHTML = '';

    try {
        const res = await fetch('/School_Facility_Maintenance_System/backend/api/rooms.php?action=getByFloor&floor_id=' + floorId, {
            credentials: 'same-origin'
        });
        const data = await res.json();
        if (data.success) {
            data.rooms.forEach(r => {
                const card = createEntityCard(r.name, '', '🚪', 'room', r.id);
                card.onclick = () => loadItems(r.id, r.name);
                container.appendChild(card);
            });
        } else {
            alert(data.message || 'Failed to load rooms');
        }
    } catch (err) {
        console.error('Error loading rooms', err);
        alert('Error loading rooms. Check console for details.');
    }
}

async function loadItems(roomId, roomName) {
    currentLevel = 'items'; currentRoomId = roomId;
    document.getElementById('overviewTitle').textContent = 'Items in ' + roomName;
    document.getElementById('overviewSubtitle').textContent = '';
    clearActions();
    addActionButton('Add Item', () => openItemModal());
    addActionButton('Back', () => loadRooms(currentFloorId, currentFloorName));

    const container = document.getElementById('overviewContainer');
    container.innerHTML = '';

    try {
        const res = await fetch('/School_Facility_Maintenance_System/backend/api/items.php?action=getByRoom&room_id=' + roomId, {
            credentials: 'same-origin'
        });
        const data = await res.json();
        if (data.success) {
            data.items.forEach(i => {
                const card = createEntityCard(i.name, `Qty: ${i.quantity || 0}, Status: ${i.status}`, '📦', 'item', i.id);
                container.appendChild(card);
            });
        } else {
            alert(data.message || 'Failed to load items');
        }
    } catch (err) {
        console.error('Error loading items', err);
        alert('Error loading items. Check console for details.');
    }
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
        const desc = document.createElement('p');
        desc.className = 'summary-card-desc';
        desc.textContent = subtitle;
        content.appendChild(desc);
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
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn btn-danger';
            deleteBtn.textContent = 'Delete';
            deleteBtn.onclick = (event) => {
                event.stopPropagation();
                deleteEntity(type, id);
            };
            actions.appendChild(editBtn);
            actions.appendChild(deleteBtn);
            div.appendChild(actions);
        } else {
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

// Delete helper
async function deleteEntity(type, id) {
    if (!confirm('Are you sure you want to delete this ' + type + '?')) return;
    let endpoint = '';
    switch(type) {
        case 'building': endpoint = '/School_Facility_Maintenance_System/backend/api/buildings.php?action=delete'; break;
        case 'floor': endpoint = '/School_Facility_Maintenance_System/backend/api/floors.php?action=delete'; break;
        case 'room': endpoint = '/School_Facility_Maintenance_System/backend/api/rooms.php?action=delete'; break;
        case 'item': endpoint = '/School_Facility_Maintenance_System/backend/api/items.php?action=delete'; break;
    }
    try {
        const isJsonDelete = (type === 'building' || type === 'room');
        const resp = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': isJsonDelete ? 'application/json' : 'application/x-www-form-urlencoded'
            },
            body: isJsonDelete ? JSON.stringify({ id }) : ('id=' + encodeURIComponent(id))
        });
        const data = await resp.json();
        if (data.success) {
            // reload current view
            switch(currentLevel) {
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
        const res = await fetch('/School_Facility_Maintenance_System/backend/api/buildings.php?action=update', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: currentEditBuildingId, name, description })
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
            '/School_Facility_Maintenance_System/backend/api/floors.php?action=create',
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ building_id: currentBuildingId, name })
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
    const res = await fetch('/School_Facility_Maintenance_System/backend/api/items.php?action=create', {
        method:'POST', headers:{'Content-Type':'application/json'},
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



