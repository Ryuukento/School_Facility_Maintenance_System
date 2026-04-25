<?php
/**
 * Inventory Page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

if (!isset($_SESSION['user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$user = $_SESSION['user'];
$allowedRoles = ['super_admin', 'admin_maintenance', 'maintenance_admin', 'maintenance_staff'];
if (!in_array($user['role'] ?? '', $allowedRoles, true)) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php');
    exit;
}

$isSuperAdmin = (($user['role'] ?? '') === 'super_admin');
$isMaintenanceStaff = (($user['role'] ?? '') === 'maintenance_staff');
$canAddItems = $isSuperAdmin || $isMaintenanceStaff;
$canManageCategories = $isSuperAdmin;

$pageTitle = 'Inventory - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-dashboard.css">
<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-dashboard.inline.css">
<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/inventory.inline.css?v=20260413-1">

<main class="container maintenance-admin-dashboard-page inventory-page">
    <div class="page-header mb-lg inventory-header-row">
        <div>
            <h1 class="inventory-title">Inventory</h1>
            <p class="text-muted inventory-subtitle">Inventory room stock and reserve equipment only.</p>
        </div>
        <div class="inventory-header-actions">
            <a href="/School_Facility_Maintenance_System/frontend/pages/replacement-tracking.php" class="btn btn-secondary">Replacement Tracking</a>
            <?php if ($canAddItems): ?>
            <button type="button" class="btn btn-primary" id="addItemsButton">Add Item</button>
            <?php endif; ?>
            <?php if ($canManageCategories): ?>
            <button type="button" class="btn btn-secondary" id="manageCategoriesButton">Manage Categories</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card inventory-category-browser-card">
        <div class="card-body">
            <div class="inventory-category-search-wrap">
                <input type="search" id="inventory-category-search" class="form-control inventory-category-search" placeholder="Search items..." autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
            </div>
            <div class="inventory-browser-heading-row">
                <h3 id="inventory-browser-heading" class="inventory-category-heading">Browse by Category</h3>
                <?php if ($canAddItems): ?>
                <button type="button" class="btn btn-secondary inventory-room-add-btn" id="addInventoryRoomButton" hidden>Add Inventory Room</button>
                <?php endif; ?>
            </div>
            <div id="inventory-category-cards" class="inventory-category-cards"></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-between align-center">
            <h3 class="inventory-section-title">Inventory Items</h3>
            <div class="inventory-items-actions">
                <span id="inventory-count" class="text-muted">0 items</span>
            </div>
        </div>
        <div class="card-body">
            <div id="inventory-container">
                <div class="ui-empty-state ui-fade-in">
                    <strong>Loading inventory...</strong>
                    <div class="ui-skeleton-list" style="margin-top: 12px;">
                        <div class="ui-skeleton-row w-90"></div>
                        <div class="ui-skeleton-row w-75"></div>
                        <div class="ui-skeleton-row w-55"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="addItemsModal" class="inventory-modal" aria-hidden="true">
    <div class="inventory-modal-content">
        <div class="inventory-modal-header">
            <h2>Add Item</h2>
            <button type="button" class="inventory-modal-close" id="closeAddItemsModalButton" aria-label="Close">&times;</button>
        </div>
        <div class="inventory-modal-body">
            <div id="addItemsModalMessage" class="inventory-modal-message" hidden></div>
            <form id="addItemsForm">
                <div class="inventory-form-grid">
                    <div class="form-group inventory-form-group">
                        <label for="inventoryRoomSelect">Inventory Room *</label>
                        <select id="inventoryRoomSelect" class="form-control" required>
                            <option value="">Loading inventory rooms...</option>
                        </select>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryItemNameInput">Item Name *</label>
                        <input type="text" id="inventoryItemNameInput" class="form-control" placeholder="e.g., Whiteboard Marker" required>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryQuantityInput">Quantity *</label>
                        <input type="number" id="inventoryQuantityInput" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryCategorySelect">Category</label>
                        <select id="inventoryCategorySelect" class="form-control">
                            <option value="">Uncategorized</option>
                        </select>
                    </div>
                </div>
                <div class="form-group inventory-form-group inventory-full-width" style="margin-top: 12px;">
                    <label for="inventoryThresholdOverrideInput">Low Stock Threshold</label>
                    <input type="number" id="inventoryThresholdOverrideInput" class="form-control" min="0" placeholder="Required item threshold for this stock item.">
                </div>
                <p class="text-muted" style="margin-top: 10px; margin-bottom: 0;">The threshold shown in inventory will follow the exact value you enter here.</p>
                <div class="form-group inventory-form-group inventory-full-width">
                    <label for="inventoryDescriptionInput">Description</label>
                    <textarea id="inventoryDescriptionInput" class="form-control" rows="3" placeholder="Optional notes about the stock intake"></textarea>
                </div>
            </form>
        </div>
        <div class="inventory-modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelAddItemsButton">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveAddItemsButton">Save Item</button>
        </div>
    </div>
</div>

<?php if ($canAddItems): ?>
<div id="addInventoryRoomModal" class="inventory-modal" aria-hidden="true">
    <div class="inventory-modal-content" style="width:min(560px, 100%);">
        <div class="inventory-modal-header">
            <h2>Add Inventory Room</h2>
            <button type="button" class="inventory-modal-close" id="closeAddInventoryRoomModalButton" aria-label="Close">&times;</button>
        </div>
        <div class="inventory-modal-body">
            <div id="addInventoryRoomMessage" class="inventory-modal-message" hidden></div>
            <form id="addInventoryRoomForm">
                <div class="inventory-form-grid">
                    <div class="form-group inventory-form-group">
                        <label for="inventoryRoomNameInput">Room Name *</label>
                        <input type="text" id="inventoryRoomNameInput" class="form-control" placeholder="e.g., Maritime Inventory Room" required>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryRoomCodeInput">Code</label>
                        <input type="text" id="inventoryRoomCodeInput" class="form-control" placeholder="e.g., maritime">
                    </div>
                </div>
                <div class="form-group inventory-form-group inventory-full-width">
                    <label for="inventoryRoomDescriptionInput">Description</label>
                    <textarea id="inventoryRoomDescriptionInput" class="form-control" rows="3" placeholder="Optional notes about this inventory room"></textarea>
                </div>
            </form>
        </div>
        <div class="inventory-modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelAddInventoryRoomButton">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveInventoryRoomButton">Save Inventory Room</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canManageCategories): ?>
<div id="manageCategoriesModal" class="inventory-modal" aria-hidden="true">
    <div class="inventory-modal-content">
        <div class="inventory-modal-header">
            <h2>Manage Categories</h2>
            <button type="button" class="inventory-modal-close" id="closeManageCategoriesModalButton" aria-label="Close">&times;</button>
        </div>
        <div class="inventory-modal-body">
            <div id="manageCategoriesMessage" class="inventory-modal-message" hidden></div>
            <form id="categoryForm">
                <input type="hidden" id="categoryIdInput" value="">
                <div class="inventory-form-grid">
                    <div class="form-group inventory-form-group">
                        <label for="categoryNameInput">Category Name *</label>
                        <input type="text" id="categoryNameInput" class="form-control" placeholder="e.g., Consumables" required>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="categoryCodeInput">Code</label>
                        <input type="text" id="categoryCodeInput" class="form-control" placeholder="e.g., consumables">
                    </div>
                    <div class="form-group inventory-form-group" hidden>
                        <label for="categoryThresholdInput">Default Low Stock Threshold</label>
                        <input type="number" id="categoryThresholdInput" class="form-control" min="0" placeholder="Optional">
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="categorySortOrderInput">Sort Order</label>
                        <input type="number" id="categorySortOrderInput" class="form-control" value="0">
                    </div>
                </div>
                <div class="inventory-form-grid" style="margin-top: 12px;">
                    <label class="inventory-checkbox-label" hidden>
                        <input type="checkbox" id="categoryAllowOverrideInput" checked>
                        Allow item threshold override
                    </label>
                    <label class="inventory-checkbox-label">
                        <input type="checkbox" id="categoryIsActiveInput" checked>
                        Active
                    </label>
                </div>
            </form>
            <div style="margin-top: 16px;">
                <h3 class="inventory-section-title" style="font-size: 16px;">Existing Categories</h3>
                <div id="categoriesListContainer" class="inventory-categories-list"></div>
            </div>
        </div>
        <div class="inventory-modal-footer">
            <button type="button" class="btn btn-secondary" id="resetCategoryFormButton">Reset</button>
            <button type="button" class="btn btn-primary" id="saveCategoryButton">Save Category</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
const ITEMS_API_BASE = '/School_Facility_Maintenance_System/backend/api/items.php';
const INVENTORY_STOCK_API_BASE = '/School_Facility_Maintenance_System/backend/api/inventory-stock-api.php';
const INVENTORY_ROOMS_API_BASE = '/School_Facility_Maintenance_System/backend/api/inventory-rooms-api.php';
const ROOMS_API_BASE = '/School_Facility_Maintenance_System/backend/api/rooms.php';
const CATEGORIES_API_BASE = '/School_Facility_Maintenance_System/backend/api/inventory-categories.php';
const CAN_MANAGE_CATEGORIES = <?php echo $canManageCategories ? 'true' : 'false'; ?>;
const CAN_ADD_ITEMS = <?php echo $canAddItems ? 'true' : 'false'; ?>;
const HIDDEN_CATEGORY_NAMES = new Set(['janitorial', 'medical']);

let inventoryRoomsCache = [];
let inventoryCategoriesCache = [];
let inventoryAllItems = [];
let inventorySelectedCategoryId = null;
let inventorySelectedInventoryRoomId = null;
let inventorySearchTerm = '';
let inventoryStatusFilter = (new URLSearchParams(window.location.search).get('status_filter') || '').toLowerCase();
let inventoryBrowseMode = 'category';

if (inventoryStatusFilter !== 'low_stock' && inventoryStatusFilter !== 'out_of_stock' && inventoryStatusFilter !== 'available') {
    inventoryStatusFilter = '';
}

function getStatusClass(status) {
    const value = normalizeInventoryStatus(status);
    if (value === 'low_stock') return 'badge-warning';
    if (value === 'out_of_stock') return 'badge-priority-critical';
    return 'badge-success';
}

function normalizeInventoryStatus(status) {
    const value = String(status || '').toLowerCase();
    if (value === 'out_of_stock') return 'out_of_stock';
    if (value === 'low_stock') return 'low_stock';
    return 'available';
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
}

function setAddItemsMessage(message, type = 'info') {
    const box = document.getElementById('addItemsModalMessage');
    if (!box) return;

    if (!message) {
        box.hidden = true;
        box.textContent = '';
        box.className = 'inventory-modal-message';
        return;
    }

    box.hidden = false;
    box.textContent = message;
    box.className = `inventory-modal-message inventory-modal-message-${type}`;
}

function setManageCategoriesMessage(message, type = 'info') {
    const box = document.getElementById('manageCategoriesMessage');
    if (!box) return;

    if (!message) {
        box.hidden = true;
        box.textContent = '';
        box.className = 'inventory-modal-message';
        return;
    }

    box.hidden = false;
    box.textContent = message;
    box.className = `inventory-modal-message inventory-modal-message-${type}`;
}

function setAddInventoryRoomMessage(message, type = 'info') {
    const box = document.getElementById('addInventoryRoomMessage');
    if (!box) return;

    if (!message) {
        box.hidden = true;
        box.textContent = '';
        box.className = 'inventory-modal-message';
        return;
    }

    box.hidden = false;
    box.textContent = message;
    box.className = `inventory-modal-message inventory-modal-message-${type}`;
}

function getCategoryById(id) {
    return inventoryCategoriesCache.find((category) => Number(category.id) === Number(id)) || null;
}

function resolveItemThreshold(item) {
    if (item.reorder_level !== null && item.reorder_level !== undefined && item.reorder_level !== '') {
        return Number(item.reorder_level);
    }

    return null;
}

function formatThreshold(value) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return '-';
    }
    return String(Number(value));
}

function isHiddenCategoryName(name) {
    return HIDDEN_CATEGORY_NAMES.has(String(name || '').trim().toLowerCase());
}

function getCategoryIconMarkup(categoryName) {
    const key = String(categoryName || '').trim().toLowerCase();

    if (key === 'all items') {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 8.5v7a2 2 0 0 1-2 2h-1.5V10a1 1 0 0 0-1-1h-9a1 1 0 0 0-1 1v7.5H5a2 2 0 0 1-2-2v-7l9-5 9 5z"></path><path d="M8.5 20V11h7v9h-7z"></path></svg>';
    }

    if (key === 'electrical') {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2L5 13h6l-1 9 9-13h-6l0-7z"></path></svg>';
    }

    if (key === 'it & electronics') {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z"></path><path d="M9 20h6"></path><path d="M10.5 17v3"></path><path d="M13.5 17v3"></path></svg>';
    }

    if (key === 'furniture') {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 6a2 2 0 1 1 4 0v3H7V6z"></path><path d="M5 11h14v4H5v-4z"></path><path d="M6 15v4"></path><path d="M18 15v4"></path></svg>';
    }

    if (key === 'plumbing') {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 3h4v4h-4z"></path><path d="M11 7v5.2l-3.6 3.6a2.5 2.5 0 1 0 3.5 3.5l3.6-3.6H20v-4h-5.5L11 7z"></path></svg>';
    }

    if (key === 'classroom') {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18v11H3z"></path><path d="M7 20h10"></path><path d="M12 16v4"></path><path d="M6 8h8"></path></svg>';
    }

    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4z"></path><path d="M8 10h8"></path><path d="M8 14h5"></path></svg>';
}

function getCategoryToneClass(categoryName) {
    const key = String(categoryName || '').trim().toLowerCase();
    if (key === 'all items') return 'tone-all';
    if (key === 'electrical') return 'tone-electrical';
    if (key === 'it & electronics') return 'tone-it';
    if (key === 'furniture') return 'tone-furniture';
    if (key === 'plumbing') return 'tone-plumbing';
    if (key === 'classroom') return 'tone-classroom';
    return 'tone-default';
}

function getInventoryRoomById(id) {
    return inventoryRoomsCache.find((room) => Number(room.id) === Number(id)) || null;
}

async function fetchInventoryRooms() {
    const response = await fetch(`${INVENTORY_ROOMS_API_BASE}?action=list`, { credentials: 'same-origin' });
    const payload = await response.json();

    if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'Failed to load inventory rooms');
    }

    inventoryRoomsCache = Array.isArray(payload.data?.rooms) ? payload.data.rooms : [];
}

async function loadInventoryRoomsForSelect() {
    const select = document.getElementById('inventoryRoomSelect');
    if (!select) return;

    select.innerHTML = '<option value="">Choose inventory room</option>';

    inventoryRoomsCache.forEach((room) => {
        const option = document.createElement('option');
        option.value = String(room.id);
        option.textContent = room.name || 'Unnamed Inventory Room';
        select.appendChild(option);
    });

    if (inventoryRoomsCache.length === 0) {
        select.innerHTML = '<option value="">No inventory rooms available</option>';
        select.disabled = true;
        setAddItemsMessage('Add an inventory room first before saving stock items.', 'warning');
    } else {
        select.disabled = false;
    }
}

function getVisibleInventoryItems() {
    const term = inventorySearchTerm;

    return inventoryAllItems.filter((item) => {
        const inventoryRoomId = item.inventory_room_id === null || item.inventory_room_id === undefined ? null : Number(item.inventory_room_id);
        const categoryId = item.category_id === null || item.category_id === undefined ? null : Number(item.category_id);
        const category = categoryId === null ? null : getCategoryById(categoryId);
        if (category && isHiddenCategoryName(category.name)) {
            return false;
        }

        if (inventoryStatusFilter && normalizeInventoryStatus(item.status) !== inventoryStatusFilter) {
            return false;
        }

        if (inventorySelectedCategoryId !== null && categoryId !== inventorySelectedCategoryId) {
            return false;
        }

        if (inventorySelectedInventoryRoomId !== null && inventoryRoomId !== inventorySelectedInventoryRoomId) {
            return false;
        }

        if (!term) {
            return true;
        }

        const haystack = [
            item.name,
            item.description,
            item.room_name,
            item.category_name,
        ].map((value) => String(value || '').toLowerCase()).join(' ');

        return haystack.includes(term);
    });
}

function getItemsForCategoryCards() {
    const term = inventorySearchTerm;

    return inventoryAllItems.filter((item) => {
        const inventoryRoomId = item.inventory_room_id === null || item.inventory_room_id === undefined ? null : Number(item.inventory_room_id);
        const categoryId = item.category_id === null || item.category_id === undefined ? null : Number(item.category_id);
        const category = categoryId === null ? null : getCategoryById(categoryId);
        if (category && isHiddenCategoryName(category.name)) {
            return false;
        }

        if (inventorySelectedInventoryRoomId !== null && inventoryRoomId !== inventorySelectedInventoryRoomId) {
            return false;
        }

        if (!term) {
            return true;
        }

        const haystack = [
            item.name,
            item.description,
            item.room_name,
            item.category_name,
        ].map((value) => String(value || '').toLowerCase()).join(' ');

        return haystack.includes(term);
    });
}

function getItemsForInventoryRoomCards() {
    const term = inventorySearchTerm;

    return inventoryAllItems.filter((item) => {
        const categoryId = item.category_id === null || item.category_id === undefined ? null : Number(item.category_id);
        const category = categoryId === null ? null : getCategoryById(categoryId);
        if (category && isHiddenCategoryName(category.name)) {
            return false;
        }

        if (inventoryStatusFilter && normalizeInventoryStatus(item.status) !== inventoryStatusFilter) {
            return false;
        }

        if (!term) {
            return true;
        }

        const haystack = [
            item.name,
            item.description,
            item.inventory_room_name,
            item.category_name,
        ].map((value) => String(value || '').toLowerCase()).join(' ');

        return haystack.includes(term);
    });
}

function getItemsForStatusCards() {
    const scopedItems = getItemsForCategoryCards();

    if (inventorySelectedCategoryId === null) {
        return scopedItems;
    }

    return scopedItems.filter((item) => Number(item.category_id || 0) === inventorySelectedCategoryId);
}

function setInventoryStatusFilter(nextStatus) {
    const normalized = String(nextStatus || '').toLowerCase();
    if (normalized !== '' && normalized !== 'low_stock' && normalized !== 'out_of_stock' && normalized !== 'available') {
        return;
    }

    inventoryStatusFilter = normalized;

    const currentUrl = new URL(window.location.href);
    if (!normalized) {
        currentUrl.searchParams.delete('status_filter');
    } else {
        currentUrl.searchParams.set('status_filter', normalized);
    }

    window.history.replaceState({}, '', currentUrl.toString());
    renderInventoryOverview();
}

function renderInventoryStatusCards() {
    const container = document.getElementById('inventory-status-cards');
    if (!container) return;

    const scopedItems = getItemsForStatusCards();
    const totalCount = scopedItems.length;
    const outCount = scopedItems.filter((item) => normalizeInventoryStatus(item.status) === 'out_of_stock').length;
    const lowCount = scopedItems.filter((item) => normalizeInventoryStatus(item.status) === 'low_stock').length;
    const inCount = scopedItems.filter((item) => normalizeInventoryStatus(item.status) === 'available').length;

    const cards = [
        {
            status: '',
            title: 'All Items',
            value: totalCount,
            subtitle: 'All statuses',
            tone: 'tone-all'
        },
        {
            status: 'out_of_stock',
            title: 'Out of Stock',
            value: outCount,
            subtitle: 'Needs restocking',
            tone: 'tone-out'
        },
        {
            status: 'low_stock',
            title: 'Low Stock',
            value: lowCount,
            subtitle: 'Near threshold',
            tone: 'tone-low'
        },
        {
            status: 'available',
            title: 'In Stock',
            value: inCount,
            subtitle: 'Healthy inventory',
            tone: 'tone-in'
        }
    ];

    container.innerHTML = cards.map((card) => {
        const activeClass = inventoryStatusFilter === card.status ? 'is-active' : '';
        return `
            <button type="button" class="summary-card inventory-status-card ${card.tone} ${activeClass}" data-status="${card.status}">
                <span class="summary-card-title">${card.title}</span>
                <span class="summary-card-value">${card.value}</span>
                <span class="summary-card-desc">${card.subtitle}</span>
            </button>
        `;
    }).join('');

    container.querySelectorAll('[data-status]').forEach((card) => {
        card.addEventListener('click', () => {
            const status = card.getAttribute('data-status') || '';
            setInventoryStatusFilter(status);
        });
    });
}

function renderInventoryCategoryCards() {
    const container = document.getElementById('inventory-category-cards');
    if (!container) return;

    if (inventoryBrowseMode === 'inventory_room') {
        renderInventoryRoomCards(container);
        return;
    }

    const categories = inventoryCategoriesCache.filter((category) => Number(category.is_active || 0) === 1 && !isHiddenCategoryName(category.name));
    const cardItems = getItemsForCategoryCards();

    const lowCount = cardItems.filter((item) => normalizeInventoryStatus(item.status) === 'low_stock').length;
    const outCount = cardItems.filter((item) => normalizeInventoryStatus(item.status) === 'out_of_stock').length;
    const okCount = cardItems.filter((item) => normalizeInventoryStatus(item.status) === 'available').length;

    const cards = [];
    const allActiveClass = inventorySelectedCategoryId === null ? 'is-active' : '';
    cards.push(`
        <button type="button" class="inventory-category-card ${getCategoryToneClass('all items')} ${allActiveClass}" data-category="all">
            <div class="inventory-category-card-head">
                <span class="inventory-category-card-icon">${getCategoryIconMarkup('all items')}</span>
                <div class="inventory-category-card-name">All Items</div>
            </div>
            <div class="inventory-category-card-count">${cardItems.length} ${cardItems.length === 1 ? 'Item' : 'Items'}</div>
            <div class="inventory-category-chip-row">
                ${lowCount > 0 ? `<span class="inventory-category-chip is-low">${lowCount} low</span>` : ''}
                ${outCount > 0 ? `<span class="inventory-category-chip is-out">${outCount} out</span>` : ''}
                ${okCount > 0 ? `<span class="inventory-category-chip is-ok">${okCount} ok</span>` : ''}
            </div>
        </button>
    `);

    const inventoryRoomCardMarkup = `
        <button
            type="button"
            class="inventory-category-card inventory-room-card tone-classroom"
            data-inventory-room="true"
            aria-label="Browse inventory rooms"
            title="Browse inventory rooms"
        >
            <div class="inventory-category-card-head">
                <span class="inventory-category-card-icon">${getCategoryIconMarkup('classroom')}</span>
                <div class="inventory-category-card-name">Inventory Room</div>
            </div>
            <div class="inventory-category-card-count">Browse inventory room names</div>
            <div class="inventory-category-chip-row">
                <span class="inventory-category-chip is-ok">${inventoryRoomsCache.length} room${inventoryRoomsCache.length === 1 ? '' : 's'}</span>
            </div>
        </button>
    `;

    let inventoryRoomCardInserted = false;

    categories.forEach((category) => {
        const categoryItems = cardItems.filter((item) => Number(item.category_id || 0) === Number(category.id));
        const catLow = categoryItems.filter((item) => normalizeInventoryStatus(item.status) === 'low_stock').length;
        const catOut = categoryItems.filter((item) => normalizeInventoryStatus(item.status) === 'out_of_stock').length;
        const catOk = categoryItems.filter((item) => normalizeInventoryStatus(item.status) === 'available').length;
        const activeClass = inventorySelectedCategoryId === Number(category.id) ? 'is-active' : '';

        cards.push(`
            <button type="button" class="inventory-category-card ${getCategoryToneClass(category.name || '')} ${activeClass}" data-category="${Number(category.id)}">
                <div class="inventory-category-card-head">
                    <span class="inventory-category-card-icon">${getCategoryIconMarkup(category.name || '')}</span>
                    <div class="inventory-category-card-name">${category.name || 'Unnamed Category'}</div>
                </div>
                <div class="inventory-category-card-count">${categoryItems.length} ${categoryItems.length === 1 ? 'Item' : 'Items'}</div>
                <div class="inventory-category-chip-row">
                    ${catLow > 0 ? `<span class="inventory-category-chip is-low">${catLow} low</span>` : ''}
                    ${catOut > 0 ? `<span class="inventory-category-chip is-out">${catOut} out</span>` : ''}
                    ${catOk > 0 ? `<span class="inventory-category-chip is-ok">${catOk} ok</span>` : ''}
                </div>
            </button>
        `);

        if (String(category.name || '').trim().toLowerCase() === 'classroom') {
            cards.push(inventoryRoomCardMarkup);
            inventoryRoomCardInserted = true;
        }
    });

    if (!inventoryRoomCardInserted) {
        cards.push(inventoryRoomCardMarkup);
    }

    container.innerHTML = cards.join('');

    container.querySelectorAll('[data-category]').forEach((card) => {
        card.addEventListener('click', () => {
            const value = card.getAttribute('data-category');
            if (value === 'all') {
                inventorySelectedCategoryId = null;
            } else {
                inventorySelectedCategoryId = Number(value);
            }

            setInventoryStatusFilter('');
        });
    });

    container.querySelectorAll('[data-inventory-room="true"]').forEach((card) => {
        card.addEventListener('click', async () => {
            inventoryBrowseMode = 'inventory_room';
            inventorySelectedCategoryId = null;
            inventorySelectedInventoryRoomId = null;
            updateInventoryBrowserHeading();
            toggleInventoryRoomActionButton();
            if (inventoryRoomsCache.length === 0) {
                await fetchInventoryRooms();
            }
            renderInventoryOverview();
        });
    });
}

function renderInventoryRoomCards(container) {
    const rooms = Array.isArray(inventoryRoomsCache) ? inventoryRoomsCache : [];
    const roomItems = getItemsForInventoryRoomCards();

    if (rooms.length === 0) {
        container.innerHTML = `
            <button type="button" class="inventory-category-card tone-all" data-room-view="back">
                <div class="inventory-category-card-head">
                    <span class="inventory-category-card-icon">${getCategoryIconMarkup('all items')}</span>
                    <div class="inventory-category-card-name">Back to Categories</div>
                </div>
                <div class="inventory-category-card-count">Return to category view</div>
                <div class="inventory-category-chip-row">
                    <span class="inventory-category-chip is-ok">Browse all</span>
                </div>
            </button>
            <div class="ui-empty-state ui-fade-in">
                <strong>No inventory rooms yet.</strong>
                <span>Add an inventory room to start grouping stockroom equipment.</span>
            </div>
        `;
    } else {
        const cards = [`
            <button type="button" class="inventory-category-card tone-all" data-room-view="back">
                <div class="inventory-category-card-head">
                    <span class="inventory-category-card-icon">${getCategoryIconMarkup('all items')}</span>
                    <div class="inventory-category-card-name">Back to Categories</div>
                </div>
                <div class="inventory-category-card-count">Return to category view</div>
                <div class="inventory-category-chip-row">
                    <span class="inventory-category-chip is-ok">Browse all</span>
                </div>
            </button>
        `];

        rooms.forEach((room) => {
            const roomId = Number(room.id);
            const matchingItems = roomItems.filter((item) => Number(item.inventory_room_id || 0) === roomId);
            const lowCount = matchingItems.filter((item) => normalizeInventoryStatus(item.status) === 'low_stock').length;
            const outCount = matchingItems.filter((item) => normalizeInventoryStatus(item.status) === 'out_of_stock').length;
            const okCount = matchingItems.filter((item) => normalizeInventoryStatus(item.status) === 'available').length;
            const activeClass = inventorySelectedInventoryRoomId === roomId ? 'is-active' : '';

            cards.push(`
                <button type="button" class="inventory-category-card tone-classroom ${activeClass}" data-inventory-room-card="${roomId}">
                    <div class="inventory-category-card-head">
                        <span class="inventory-category-card-icon">${getCategoryIconMarkup('classroom')}</span>
                        <div class="inventory-category-card-name">${room.name || 'Unnamed Inventory Room'}</div>
                    </div>
                    <div class="inventory-category-card-count">${matchingItems.length} ${matchingItems.length === 1 ? 'Item' : 'Items'}</div>
                    <div class="inventory-category-chip-row">
                        ${okCount > 0 ? `<span class="inventory-category-chip is-ok">${okCount} ok</span>` : ''}
                        ${lowCount > 0 ? `<span class="inventory-category-chip is-low">${lowCount} low</span>` : ''}
                        ${outCount > 0 ? `<span class="inventory-category-chip is-out">${outCount} out</span>` : ''}
                        ${matchingItems.length === 0 ? `<span class="inventory-category-chip">No items</span>` : ''}
                    </div>
                </button>
            `);
        });

        container.innerHTML = cards.join('');
    }

    container.querySelectorAll('[data-room-view="back"]').forEach((card) => {
        card.addEventListener('click', () => {
            inventoryBrowseMode = 'category';
            inventorySelectedInventoryRoomId = null;
            updateInventoryBrowserHeading();
            toggleInventoryRoomActionButton();
            renderInventoryOverview();
        });
    });

    container.querySelectorAll('[data-inventory-room-card]').forEach((card) => {
        card.addEventListener('click', () => {
            inventorySelectedInventoryRoomId = Number(card.getAttribute('data-inventory-room-card') || 0) || null;
            renderInventoryOverview();
        });
    });
}

function updateInventoryBrowserHeading() {
    const heading = document.getElementById('inventory-browser-heading');
    if (!heading) return;
    heading.textContent = inventoryBrowseMode === 'inventory_room' ? 'Inventory Rooms' : 'Browse by Category';
}

function toggleInventoryRoomActionButton() {
    const button = document.getElementById('addInventoryRoomButton');
    if (!button) return;
    button.hidden = inventoryBrowseMode !== 'inventory_room';
}

function renderInventoryOverview() {
    const visibleItems = getVisibleInventoryItems();
    updateInventoryBrowserHeading();
    toggleInventoryRoomActionButton();
    renderInventoryCategoryCards();
    renderItems(visibleItems);
}

function resetCategoryForm() {
    const idInput = document.getElementById('categoryIdInput');
    const nameInput = document.getElementById('categoryNameInput');
    const codeInput = document.getElementById('categoryCodeInput');
    const thresholdInput = document.getElementById('categoryThresholdInput');
    const sortOrderInput = document.getElementById('categorySortOrderInput');
    const allowOverrideInput = document.getElementById('categoryAllowOverrideInput');
    const isActiveInput = document.getElementById('categoryIsActiveInput');

    if (idInput) idInput.value = '';
    if (nameInput) nameInput.value = '';
    if (codeInput) codeInput.value = '';
    if (thresholdInput) thresholdInput.value = '';
    if (sortOrderInput) sortOrderInput.value = '0';
    if (allowOverrideInput) allowOverrideInput.checked = true;
    if (isActiveInput) isActiveInput.checked = true;
}

function fillCategoryForm(category) {
    const idInput = document.getElementById('categoryIdInput');
    const nameInput = document.getElementById('categoryNameInput');
    const codeInput = document.getElementById('categoryCodeInput');
    const thresholdInput = document.getElementById('categoryThresholdInput');
    const sortOrderInput = document.getElementById('categorySortOrderInput');
    const allowOverrideInput = document.getElementById('categoryAllowOverrideInput');
    const isActiveInput = document.getElementById('categoryIsActiveInput');

    if (idInput) idInput.value = String(category.id || '');
    if (nameInput) nameInput.value = category.name || '';
    if (codeInput) codeInput.value = category.code || '';
    if (thresholdInput) thresholdInput.value = category.default_low_stock_threshold ?? '';
    if (sortOrderInput) sortOrderInput.value = String(category.sort_order ?? 0);
    if (allowOverrideInput) allowOverrideInput.checked = Number(category.allow_threshold_override || 0) === 1;
    if (isActiveInput) isActiveInput.checked = Number(category.is_active || 0) === 1;
}

function renderCategoriesList() {
    const container = document.getElementById('categoriesListContainer');
    if (!container) return;

    if (!Array.isArray(inventoryCategoriesCache) || inventoryCategoriesCache.length === 0) {
        container.innerHTML = '<div class="ui-empty-state ui-fade-in"><strong>No categories yet.</strong><span>Create one above to start grouping inventory items.</span></div>';
        return;
    }

    let html = '<div style="overflow:auto;">';
    html += '<table class="table">';
    html += '<thead><tr><th>Name</th><th>Status</th><th>Actions</th></tr></thead><tbody>';

    inventoryCategoriesCache.forEach((category) => {
        const active = Number(category.is_active || 0) === 1 ? 'Active' : 'Inactive';

        html += '<tr>';
        html += `<td><strong>${category.name || '-'}</strong></td>`;
        html += `<td>${active}</td>`;
        html += `<td><button type="button" class="btn btn-secondary btn-sm" onclick="startEditCategory(${Number(category.id)})">Edit</button> <button type="button" class="btn btn-danger btn-sm" onclick="deleteCategory(${Number(category.id)})">Delete</button></td>`;
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

async function loadInventoryCategoriesForSelect() {
    const select = document.getElementById('inventoryCategorySelect');
    if (!select) return;

    const active = inventoryCategoriesCache.filter((category) => Number(category.is_active || 0) === 1);
    select.innerHTML = '<option value="">Uncategorized</option>';

    active.forEach((category) => {
        const option = document.createElement('option');
        option.value = String(category.id);
        option.textContent = category.name || 'Unnamed Category';
        select.appendChild(option);
    });
}

function applySelectedCategoryRules() {
    const thresholdInput = document.getElementById('inventoryThresholdOverrideInput');
    if (!thresholdInput) return;

    thresholdInput.disabled = false;
    thresholdInput.placeholder = 'Required item threshold for this stock item.';
}

async function fetchInventoryCategories() {
    const response = await fetch(`${CATEGORIES_API_BASE}?action=list`, { credentials: 'same-origin' });
    const payload = await response.json();

    if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'Failed to load categories');
    }

    inventoryCategoriesCache = (Array.isArray(payload.categories) ? payload.categories : [])
        .filter((category) => !isHiddenCategoryName(category.name));
    inventoryCategoriesCache.sort((a, b) => {
        const sortA = Number(a.sort_order || 0);
        const sortB = Number(b.sort_order || 0);
        if (sortA !== sortB) return sortA - sortB;
        return String(a.name || '').localeCompare(String(b.name || ''));
    });
}

async function refreshCategoriesUI() {
    try {
        await fetchInventoryCategories();
        await loadInventoryCategoriesForSelect();
        renderCategoriesList();
        applySelectedCategoryRules();
    } catch (error) {
        console.error('Category load error:', error);
            setAddItemsMessage('Could not load inventory categories.', 'warning');
        setManageCategoriesMessage(error.message || 'Could not load categories.', 'danger');
    }
}

function openAddInventoryRoomModal() {
    const modal = document.getElementById('addInventoryRoomModal');
    if (!modal) return;

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    setAddInventoryRoomMessage('');
    document.getElementById('addInventoryRoomForm')?.reset();
    document.getElementById('inventoryRoomNameInput')?.focus();
}

function closeAddInventoryRoomModal() {
    const modal = document.getElementById('addInventoryRoomModal');
    if (!modal) return;

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.getElementById('addInventoryRoomForm')?.reset();
    setAddInventoryRoomMessage('');
}

function openAddItemsModal() {
    const modal = document.getElementById('addItemsModal');
    if (!modal) return;

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    setAddItemsMessage('');
    document.getElementById('addItemsForm').reset();
    document.getElementById('inventoryQuantityInput').value = '1';
    const thresholdInput = document.getElementById('inventoryThresholdOverrideInput');
    if (thresholdInput) {
        thresholdInput.value = '';
        thresholdInput.disabled = false;
    }

    Promise.all([refreshCategoriesUI(), fetchInventoryRooms()])
        .then(() => {
            loadInventoryRoomsForSelect();
            const firstEditable = document.getElementById('inventoryRoomSelect');
            if (firstEditable && !firstEditable.disabled) {
                firstEditable.focus();
            }
        })
        .catch((error) => {
            console.error('Add item modal bootstrap error:', error);
            setAddItemsMessage(error.message || 'Could not load inventory room options.', 'danger');
        });
}

function closeAddItemsModal() {
    const modal = document.getElementById('addItemsModal');
    if (!modal) return;

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.getElementById('addItemsForm').reset();
    document.getElementById('inventoryQuantityInput').value = '1';
    const thresholdInput = document.getElementById('inventoryThresholdOverrideInput');
    if (thresholdInput) {
        thresholdInput.value = '';
        thresholdInput.disabled = false;
    }
    setAddItemsMessage('');
}

function openManageCategoriesModal() {
    const modal = document.getElementById('manageCategoriesModal');
    if (!modal) return;

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    setManageCategoriesMessage('');
    resetCategoryForm();
    refreshCategoriesUI();
}

function closeManageCategoriesModal() {
    const modal = document.getElementById('manageCategoriesModal');
    if (!modal) return;

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    setManageCategoriesMessage('');
    resetCategoryForm();
}

function startEditCategory(id) {
    const category = getCategoryById(id);
    if (!category) {
        setManageCategoriesMessage('Category not found.', 'danger');
        return;
    }

    fillCategoryForm(category);
    setManageCategoriesMessage(`Editing category: ${category.name}`, 'info');
}

async function saveCategory() {
    const id = Number(document.getElementById('categoryIdInput')?.value || 0);
    const name = (document.getElementById('categoryNameInput')?.value || '').trim();
    const code = (document.getElementById('categoryCodeInput')?.value || '').trim().toLowerCase();
    const thresholdRaw = document.getElementById('categoryThresholdInput')?.value;
    const sortOrderRaw = document.getElementById('categorySortOrderInput')?.value;
    const allowOverride = document.getElementById('categoryAllowOverrideInput')?.checked ? 1 : 0;
    const isActive = document.getElementById('categoryIsActiveInput')?.checked ? 1 : 0;

    if (!name) {
        setManageCategoriesMessage('Category name is required.', 'danger');
        return;
    }

    const payload = {
        name,
        code: code || null,
        default_low_stock_threshold: thresholdRaw === '' ? null : Number(thresholdRaw),
        sort_order: sortOrderRaw === '' ? 0 : Number(sortOrderRaw),
        allow_threshold_override: allowOverride,
        is_active: isActive
    };

    if (payload.default_low_stock_threshold !== null && payload.default_low_stock_threshold < 0) {
        setManageCategoriesMessage('Default threshold must be zero or greater.', 'danger');
        return;
    }

    const action = id > 0 ? 'update' : 'create';
    if (id > 0) {
        payload.id = id;
    }

    const saveButton = document.getElementById('saveCategoryButton');
    const originalLabel = saveButton ? saveButton.textContent : 'Save Category';
    if (saveButton) {
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';
    }

    try {
        const response = await fetch(`${CATEGORIES_API_BASE}?action=${action}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to save category');
        }

        resetCategoryForm();
        setManageCategoriesMessage(result.message || 'Category saved.', 'success');
        await refreshCategoriesUI();
        await loadInventory();
    } catch (error) {
        console.error('Save category error:', error);
        setManageCategoriesMessage(error.message || 'Failed to save category.', 'danger');
    } finally {
        if (saveButton) {
            saveButton.disabled = false;
            saveButton.textContent = originalLabel;
        }
    }
}

async function deleteCategory(id) {
    if (!id) return;
    if (!confirm('Delete this category?')) return;

    try {
        const response = await fetch(`${CATEGORIES_API_BASE}?action=delete`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to delete category');
        }

        setManageCategoriesMessage(result.message || 'Category deleted.', 'success');
        await refreshCategoriesUI();
        await loadInventory();
    } catch (error) {
        console.error('Delete category error:', error);
        setManageCategoriesMessage(error.message || 'Failed to delete category.', 'danger');
    }
}

async function saveAddItems() {
    const inventoryRoomId = Number(document.getElementById('inventoryRoomSelect')?.value || 0);
    const categoryIdRaw = document.getElementById('inventoryCategorySelect')?.value || '';
    const name = document.getElementById('inventoryItemNameInput').value.trim();
    const quantity = Number(document.getElementById('inventoryQuantityInput').value || 0);
    const reorderLevelRaw = document.getElementById('inventoryThresholdOverrideInput')?.value || '';
    const description = document.getElementById('inventoryDescriptionInput').value.trim();

    if (!inventoryRoomId) {
        setAddItemsMessage('Please choose an inventory room.', 'danger');
        return;
    }

    if (!name) {
        setAddItemsMessage('Please enter an item name.', 'danger');
        return;
    }

    if (!quantity || quantity <= 0) {
        setAddItemsMessage('Please enter a quantity greater than zero.', 'danger');
        return;
    }

    if (reorderLevelRaw === '') {
        setAddItemsMessage('Please enter a low stock threshold.', 'danger');
        return;
    }

    if (Number(reorderLevelRaw) < 0) {
        setAddItemsMessage('Reorder level must be zero or greater.', 'danger');
        return;
    }

    const payload = {
        inventory_room_id: inventoryRoomId,
        name,
        quantity,
        description,
        category_id: categoryIdRaw === '' ? null : Number(categoryIdRaw),
        reorder_level: Number(reorderLevelRaw)
    };

    const saveButton = document.getElementById('saveAddItemsButton');
    const originalLabel = saveButton.textContent;
    saveButton.disabled = true;
    saveButton.textContent = 'Saving...';
    setAddItemsMessage('Saving item...');

    try {
        const response = await fetch(`${INVENTORY_STOCK_API_BASE}?action=add_stock`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to add item');
        }

        closeAddItemsModal();
        await loadInventory();
    } catch (error) {
        console.error('Add item error:', error);
        setAddItemsMessage(error.message || 'Failed to add item.', 'danger');
    } finally {
        saveButton.disabled = false;
        saveButton.textContent = originalLabel;
    }
}

async function saveInventoryRoom() {
    const name = (document.getElementById('inventoryRoomNameInput')?.value || '').trim();
    const code = (document.getElementById('inventoryRoomCodeInput')?.value || '').trim().toLowerCase();
    const description = (document.getElementById('inventoryRoomDescriptionInput')?.value || '').trim();

    if (!name) {
        setAddInventoryRoomMessage('Please enter an inventory room name.', 'danger');
        return;
    }

    const saveButton = document.getElementById('saveInventoryRoomButton');
    const originalLabel = saveButton?.textContent || 'Save Inventory Room';
    if (saveButton) {
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';
    }

    try {
        const response = await fetch(`${INVENTORY_ROOMS_API_BASE}?action=create`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name,
                code: code || null,
                description
            })
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to save inventory room');
        }

        await fetchInventoryRooms();
        closeAddInventoryRoomModal();
        renderInventoryOverview();
    } catch (error) {
        console.error('Save inventory room error:', error);
        setAddInventoryRoomMessage(error.message || 'Failed to save inventory room.', 'danger');
    } finally {
        if (saveButton) {
            saveButton.disabled = false;
            saveButton.textContent = originalLabel;
        }
    }
}

function renderItems(items) {
    const container = document.getElementById('inventory-container');
    if (!container) return;

    if (!Array.isArray(items) || items.length === 0) {
        container.innerHTML = '<div class="ui-empty-state ui-fade-in"><strong>No inventory items found.</strong><span>Inventory records will appear here after items are added.</span></div>';
        setText('inventory-count', '0 items');
        return;
    }

    let html = '<div style="overflow:auto;">';
    html += '<table class="table">';
    html += '<thead><tr>';
    html += '<th>ID</th><th>Item</th><th>Category</th><th>Location</th><th>Quantity</th><th>Threshold</th><th>Status</th><th>Description</th><th>Updated</th>';
    html += '</tr></thead><tbody>';

    items.forEach((item) => {
        const normalizedStatus = normalizeInventoryStatus(item.status);
        const status = normalizedStatus === 'out_of_stock'
            ? 'OUT OF STOCK'
            : (normalizedStatus === 'low_stock' ? 'LOW STOCK' : 'IN STOCK');
        const statusClass = getStatusClass(normalizedStatus);
        const updated = item.updated_at ? new Date(String(item.updated_at).replace(' ', 'T')).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
        const threshold = resolveItemThreshold(item);

        html += '<tr>';
        html += `<td>#${item.id ?? '-'}</td>`;
        html += `<td><strong>${item.name || 'Unnamed Item'}</strong></td>`;
        html += `<td>${item.category_name || 'Uncategorized'}</td>`;
        html += `<td>${item.inventory_room_name || 'Inventory Room'}</td>`;
        html += `<td>${Number(item.quantity ?? 0)}</td>`;
        html += `<td>${formatThreshold(threshold)}</td>`;
        html += `<td><span class="badge ${statusClass}">${status}</span></td>`;
        html += `<td>${item.description || '-'}</td>`;
        html += `<td>${updated}</td>`;
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
    setText('inventory-count', `${items.length} items`);
}

async function loadInventory() {
    const container = document.getElementById('inventory-container');
    if (container) {
        container.innerHTML = '<div class="ui-empty-state ui-fade-in"><strong>Loading inventory...</strong><div class="ui-skeleton-list" style="margin-top: 12px;"><div class="ui-skeleton-row w-90"></div><div class="ui-skeleton-row w-75"></div><div class="ui-skeleton-row w-55"></div></div></div>';
    }

    try {
        const response = await fetch(`${INVENTORY_STOCK_API_BASE}?action=list_stock`, {
            credentials: 'same-origin'
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to load inventory');
        }

        inventoryAllItems = Array.isArray(payload.data?.items) ? payload.data.items : [];
        renderInventoryOverview();
    } catch (error) {
        if (container) {
            container.innerHTML = '<div class="alert alert-danger ui-fade-in">Could not load inventory right now.</div>';
        }
        console.error('Inventory load error:', error);
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        await refreshCategoriesUI();
        await fetchInventoryRooms();
    } catch (error) {
        console.error('Inventory bootstrap error:', error);
    }
    await loadInventory();

    const addButton = document.getElementById('addItemsButton');
    const addInventoryRoomButton = document.getElementById('addInventoryRoomButton');
    const closeButton = document.getElementById('closeAddItemsModalButton');
    const cancelButton = document.getElementById('cancelAddItemsButton');
    const saveButton = document.getElementById('saveAddItemsButton');
    const addItemsModal = document.getElementById('addItemsModal');
    const addInventoryRoomModal = document.getElementById('addInventoryRoomModal');
    const closeAddInventoryRoomButton = document.getElementById('closeAddInventoryRoomModalButton');
    const cancelAddInventoryRoomButton = document.getElementById('cancelAddInventoryRoomButton');
    const saveInventoryRoomButton = document.getElementById('saveInventoryRoomButton');
    const categorySelect = document.getElementById('inventoryCategorySelect');
    const inventorySearchInput = document.getElementById('inventory-category-search');

    if (addButton) addButton.addEventListener('click', openAddItemsModal);
    if (addInventoryRoomButton) addInventoryRoomButton.addEventListener('click', openAddInventoryRoomModal);
    if (closeButton) closeButton.addEventListener('click', closeAddItemsModal);
    if (cancelButton) cancelButton.addEventListener('click', closeAddItemsModal);
    if (saveButton) saveButton.addEventListener('click', saveAddItems);
    if (closeAddInventoryRoomButton) closeAddInventoryRoomButton.addEventListener('click', closeAddInventoryRoomModal);
    if (cancelAddInventoryRoomButton) cancelAddInventoryRoomButton.addEventListener('click', closeAddInventoryRoomModal);
    if (saveInventoryRoomButton) saveInventoryRoomButton.addEventListener('click', saveInventoryRoom);
    if (categorySelect) categorySelect.addEventListener('change', applySelectedCategoryRules);
    if (inventorySearchInput) {
        inventorySearchInput.addEventListener('input', () => {
            inventorySearchTerm = inventorySearchInput.value.trim().toLowerCase();
            renderInventoryOverview();
        });
    }

    if (addItemsModal) {
        addItemsModal.addEventListener('click', (event) => {
            if (event.target === addItemsModal) {
                closeAddItemsModal();
            }
        });
    }

    if (addInventoryRoomModal) {
        addInventoryRoomModal.addEventListener('click', (event) => {
            if (event.target === addInventoryRoomModal) {
                closeAddInventoryRoomModal();
            }
        });
    }

    if (CAN_MANAGE_CATEGORIES) {
        const manageButton = document.getElementById('manageCategoriesButton');
        const closeManageButton = document.getElementById('closeManageCategoriesModalButton');
        const saveCategoryButton = document.getElementById('saveCategoryButton');
        const resetCategoryButton = document.getElementById('resetCategoryFormButton');
        const manageModal = document.getElementById('manageCategoriesModal');

        if (manageButton) manageButton.addEventListener('click', openManageCategoriesModal);
        if (closeManageButton) closeManageButton.addEventListener('click', closeManageCategoriesModal);
        if (saveCategoryButton) saveCategoryButton.addEventListener('click', saveCategory);
        if (resetCategoryButton) resetCategoryButton.addEventListener('click', resetCategoryForm);

        if (manageModal) {
            manageModal.addEventListener('click', (event) => {
                if (event.target === manageModal) {
                    closeManageCategoriesModal();
                }
            });
        }
    }
});
</script>

</body>
</html>
