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
$allowedRoles = ['super_admin', 'maintenance_admin', 'maintenance_staff'];
if (!in_array($user['role'] ?? '', $allowedRoles, true)) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/dashboard.php');
    exit;
}

$isSuperAdmin = (($user['role'] ?? '') === 'super_admin');
$isMaintenanceStaff = (($user['role'] ?? '') === 'maintenance_staff');
$canAddItems = $isSuperAdmin;
$canManageCategories = $isSuperAdmin;
$canCreateInventoryEntries = in_array(($user['role'] ?? ''), ['super_admin', 'maintenance_admin'], true);
$canAdjustStock = in_array(($user['role'] ?? ''), ['super_admin', 'maintenance_admin'], true);

$pageTitle = 'Inventory - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-dashboard.css">
<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/maintenance-dashboard.inline.css">
<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/inventory.inline.css?v=20260413-1">
<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/inventory-redesign.css?v=20260714-1">

<main class="container maintenance-admin-dashboard-page inventory-page">
    <div class="card inventory-page-header-card">
        <div class="card-body inventory-page-header-body">
            <div class="page-header inventory-header-row inventory-page-header-row">
                <div>
                    <h1 class="inventory-title">Inventory</h1>
                    <p class="text-muted inventory-subtitle">Inventory room stock and reserve equipment only.<?php if ($isMaintenanceStaff): ?> <strong>(Read-only view)</strong><?php endif; ?></p>
                </div>
                <div class="inventory-header-actions">
                    <a href="/School_Facility_Maintenance_System/frontend/pages/replacement-tracking.php" class="btn btn-secondary">Replacement Tracking</a>
                    <?php if ($canCreateInventoryEntries): ?>
                    <button type="button" class="btn btn-primary" id="openInventoryEntryButton">Inventory Entry</button>
                    <?php endif; ?>
                    <?php if ($canAdjustStock): ?>
                    <button type="button" class="btn btn-secondary" id="openAdjustStockButton">Adjust Stock</button>
                    <?php endif; ?>
                    <?php if ($canManageCategories): ?>
                    <button type="button" class="btn btn-secondary" id="manageCategoriesButton">Manage Categories</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card inventory-toolbar-card">
        <div class="card-body inventory-toolbar-body">
            <div class="inventory-toolbar-grid">
                <div class="inventory-toolbar-search">
                    <svg class="inventory-toolbar-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="search" id="inventory-category-search" class="form-control inventory-category-search"
                           placeholder="Search items by name, category, or location..."
                           autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
                </div>
                <?php if ($canAddItems): ?>
                <button type="button" class="btn btn-secondary inventory-room-add-btn inventory-toolbar-action" id="addInventoryRoomButton" hidden>Add Inventory Room</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card inventory-category-browser-card">
        <div class="card-body inventory-category-browser-body">
            <div class="inventory-category-panel-header">
                <div>
                    <h3 id="inventory-browser-heading" class="inventory-category-heading">Browse by Category</h3>
                    <p class="inventory-panel-subtitle">Quickly filter inventory items by category.</p>
                </div>
            </div>
            <div id="inventory-category-cards" class="inventory-category-cards"></div>
        </div>
    </div>

    <div class="card inventory-items-card">
        <div class="card-header d-flex justify-between align-center inventory-items-header">
            <div>
                <h3 class="inventory-section-title inventory-items-title">Inventory Items</h3>
                <p class="inventory-items-subtitle">Manage stock levels, locations, conditions, and item availability.</p>
            </div>
            <div class="inventory-items-actions">
                <span id="inventory-count" class="inventory-count-badge">0 items</span>
            </div>
        </div>
        <div class="card-body inventory-items-body">
            <div class="inventory-items-filter-grid">
                <select id="inventory-items-status-filter" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="available">In Stock</option>
                    <option value="low_stock">Low Stock</option>
                    <option value="out_of_stock">Out of Stock</option>
                </select>
                <select id="inventory-items-condition-filter" class="form-control">
                    <option value="">All Conditions</option>
                    <option value="new">New</option>
                    <option value="good">Good</option>
                    <option value="fair">Fair</option>
                    <option value="damaged">Damaged</option>
                    <option value="for_repair">For Repair</option>
                </select>
                <button type="button" class="btn btn-secondary inventory-items-clear-btn" id="inventory-items-clear-filters">Clear Filters</button>
            </div>
            <div id="inventory-container" class="inventory-items-table-shell">
                <div class="ui-empty-state ui-fade-in">
                    <strong>Loading inventory...</strong>
                    <div class="ui-skeleton-list inventory-loading-skeleton">
                        <div class="ui-skeleton-row w-90"></div>
                        <div class="ui-skeleton-row w-75"></div>
                        <div class="ui-skeleton-row w-55"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card inventory-entry-history-card">
        <div class="card-header d-flex justify-between align-center inventory-entry-history-header">
            <div>
                <h3 class="inventory-section-title">Inventory Entry History</h3>
                <p class="text-muted inventory-entry-subtitle">Track receipts, receivers, suppliers, and stock entries added to warehouse inventory.</p>
            </div>
            <span id="inventory-entry-count" class="text-muted">0 entries</span>
        </div>
        <div class="card-body">
            <div class="inventory-entry-filters-grid">
                <input type="search" id="inventory-entry-search" class="form-control" placeholder="Search stock entry ID, OR number, supplier, or item..." autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
                <select id="inventory-entry-filter-category" class="form-control">
                    <option value="">All Categories</option>
                </select>
                <select id="inventory-entry-filter-department" class="form-control">
                    <option value="">All Departments</option>
                </select>
                <select id="inventory-entry-filter-room" class="form-control">
                    <option value="">All Laboratories / Rooms</option>
                </select>
                <input type="date" id="inventory-entry-filter-date-from" class="form-control" title="Date received from">
                <input type="date" id="inventory-entry-filter-date-to" class="form-control" title="Date received to">
                <button type="button" class="btn btn-secondary inventory-entry-clear-btn" id="inventory-entry-clear-filters">Clear Filters</button>
            </div>
            <div id="inventory-entry-container">
                <div class="ui-empty-state ui-fade-in">
                    <strong>Loading inventory entries...</strong>
                    <span>Please wait while we collect your stock entry history.</span>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="addItemsModal" class="inventory-modal" aria-hidden="true">
    <div class="inventory-modal-content">
        <div class="inventory-modal-header">
            <h2 id="addItemsModalTitle">Add Item</h2>
            <button type="button" class="inventory-modal-close" id="closeAddItemsModalButton" aria-label="Close">&times;</button>
        </div>
        <div class="inventory-modal-body">
            <div id="addItemsModalMessage" class="inventory-modal-message" hidden></div>
            <form id="addItemsForm">
                <input type="hidden" id="inventoryItemIdInput" value="">
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
                    <div class="form-group inventory-form-group">
                        <label for="inventoryBrandInput">Brand</label>
                        <input type="text" id="inventoryBrandInput" class="form-control" placeholder="e.g., Pilot">
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryModelInput">Model</label>
                        <input type="text" id="inventoryModelInput" class="form-control" placeholder="e.g., Fine Tip">
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryUnitTypeInput">Unit Type *</label>
                        <input type="text" id="inventoryUnitTypeInput" class="form-control" placeholder="e.g., piece, box, set" required>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryConditionSelect">Condition *</label>
                        <select id="inventoryConditionSelect" class="form-control" required>
                            <option value="">Select condition</option>
                            <option value="new">New</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="damaged">Damaged</option>
                            <option value="for_repair">For Repair</option>
                        </select>
                    </div>
                </div>
                <div class="form-group inventory-form-group inventory-full-width" style="margin-top: 12px;">
                    <label for="inventoryThresholdOverrideInput">Low Stock Threshold</label>
                    <input type="number" id="inventoryThresholdOverrideInput" class="form-control" min="0" placeholder="Required item threshold for this stock item.">
                </div>
                <div class="form-group inventory-form-group inventory-full-width">
                    <label for="inventoryStatusPreviewInput">Status</label>
                    <input type="text" id="inventoryStatusPreviewInput" class="form-control" value="Auto-calculated from quantity and threshold" readonly>
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

<div id="inventoryItemHistoryModal" class="inventory-modal" aria-hidden="true">
    <div class="inventory-modal-content">
        <div class="inventory-modal-header">
            <h2 id="inventoryHistoryModalTitle">Item History</h2>
            <button type="button" class="inventory-modal-close" id="closeInventoryHistoryModalButton" aria-label="Close">&times;</button>
        </div>
        <div class="inventory-modal-body">
            <div id="inventoryHistoryModalMessage" class="inventory-modal-message" hidden></div>
            <div id="inventoryHistoryContainer">
                <div class="ui-empty-state ui-fade-in">
                    <strong>Select an item to load history.</strong>
                    <span>Recent stock adjustments and deployments will appear here.</span>
                </div>
            </div>
        </div>
        <div class="inventory-modal-footer">
            <button type="button" class="btn btn-secondary" id="closeInventoryHistoryFooterButton">Close</button>
        </div>
    </div>
</div>

<?php if ($canCreateInventoryEntries): ?>
<div id="inventoryEntryModal" class="inventory-modal" aria-hidden="true">
    <div class="inventory-modal-content">
        <div class="inventory-modal-header">
            <h2>Inventory Entry</h2>
            <button type="button" class="inventory-modal-close" id="closeInventoryEntryModalButton" aria-label="Close">&times;</button>
        </div>
        <div class="inventory-modal-body">
            <div id="inventoryEntryModalMessage" class="inventory-modal-message" hidden></div>
            <form id="inventoryEntryForm">
                <div class="inventory-entry-id-banner">
                    <span class="inventory-entry-id-label">Stock Entry ID</span>
                    <strong id="inventoryEntryIdPreview">Auto-generated when saved</strong>
                </div>
                <div class="inventory-form-grid">
                    <div class="form-group inventory-form-group">
                        <label for="inventoryEntryOrNumber">OR Number / Receipt Number *</label>
                        <input type="text" id="inventoryEntryOrNumber" class="form-control" placeholder="e.g., OR-2026-00124" required>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryEntrySupplierName">Supplier Name *</label>
                        <input type="text" id="inventoryEntrySupplierName" class="form-control" placeholder="e.g., ABC Industrial Supply" required>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryEntryDateReceived">Date Received *</label>
                        <input type="date" id="inventoryEntryDateReceived" class="form-control" required>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryEntryCategory">Category *</label>
                        <select id="inventoryEntryCategory" class="form-control" required>
                            <option value="">Select category</option>
                        </select>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryEntryItemName">Item Name *</label>
                        <input type="text" id="inventoryEntryItemName" class="form-control" placeholder="e.g., Electrical Tape" required>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryEntryQuantity">Quantity *</label>
                        <input type="number" id="inventoryEntryQuantity" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryEntryUnitType">Unit Type *</label>
                        <input type="text" id="inventoryEntryUnitType" class="form-control" placeholder="e.g., box, piece, roll" required>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryEntryDepartment">Department *</label>
                        <select id="inventoryEntryDepartment" class="form-control" required>
                            <option value="">Select department</option>
                        </select>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryEntryRoom">Laboratory / Room</label>
                        <select id="inventoryEntryRoom" class="form-control">
                            <option value="">Select laboratory / room</option>
                        </select>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryEntryInventoryRoom">Warehouse / Main Inventory *</label>
                        <select id="inventoryEntryInventoryRoom" class="form-control" required>
                            <option value="">Select warehouse inventory</option>
                        </select>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryEntryCondition">Condition *</label>
                        <select id="inventoryEntryCondition" class="form-control" required>
                            <option value="">Select condition</option>
                            <option value="new">New</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="damaged">Damaged</option>
                            <option value="for_repair">For Repair</option>
                        </select>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="inventoryEntryReceiver">Receiver</label>
                        <input type="text" id="inventoryEntryReceiver" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" readonly>
                    </div>
                </div>
                <div class="form-group inventory-form-group inventory-full-width">
                    <label for="inventoryEntryDescription">Description</label>
                    <textarea id="inventoryEntryDescription" class="form-control" rows="3" placeholder="Optional notes about the received stock"></textarea>
                </div>
            </form>
        </div>
        <div class="inventory-modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelInventoryEntryButton">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveInventoryEntryButton">Save Entry</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canAdjustStock): ?>
<div id="adjustStockModal" class="inventory-modal" aria-hidden="true">
    <div class="inventory-modal-content">
        <div class="inventory-modal-header">
            <h2>Adjust Stock</h2>
            <button type="button" class="inventory-modal-close" id="closeAdjustStockModalButton" aria-label="Close">&times;</button>
        </div>
        <div class="inventory-modal-body">
            <div id="adjustStockModalMessage" class="inventory-modal-message" hidden></div>
            <form id="adjustStockForm">
                <div class="form-group inventory-form-group inventory-full-width">
                    <label for="adjustStockItemSelect">Inventory Item *</label>
                    <select id="adjustStockItemSelect" class="form-control" required>
                        <option value="">Select an item...</option>
                    </select>
                </div>
                <div class="form-group inventory-form-group inventory-full-width">
                    <label for="adjustStockCurrentQuantity">Current Stock</label>
                    <input type="text" id="adjustStockCurrentQuantity" class="form-control" value="Select an item to view current stock" readonly>
                </div>
                <div class="inventory-form-grid">
                    <div class="form-group inventory-form-group">
                        <label for="adjustStockDirection">Adjustment Type *</label>
                        <select id="adjustStockDirection" class="form-control" required>
                            <option value="increase">Increase</option>
                            <option value="decrease">Decrease</option>
                        </select>
                    </div>
                    <div class="form-group inventory-form-group">
                        <label for="adjustStockQuantity">Adjustment Quantity *</label>
                        <input type="number" id="adjustStockQuantity" class="form-control" min="1" step="1" placeholder="e.g., 5" required>
                    </div>
                </div>
                <div class="form-group inventory-form-group inventory-full-width">
                    <label for="adjustStockReason">Reason *</label>
                    <textarea id="adjustStockReason" class="form-control" rows="3" maxlength="500" placeholder="Explain why you're adjusting stock (e.g., physical recount, damage/loss, restock outside a purchase receipt)" required></textarea>
                </div>
                <div id="adjustStockReview" class="inventory-modal-message" hidden></div>
            </form>
        </div>
        <div class="inventory-modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelAdjustStockButton">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveAdjustStockButton">Save Adjustment</button>
        </div>
    </div>
</div>
<?php endif; ?>

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
                <div class="inventory-category-admin-header">
                    <h3 class="inventory-section-title" style="font-size: 16px;">Existing Categories</h3>
                    <span id="categoriesSummaryText" class="text-muted">0 categories</span>
                </div>
                <div class="inventory-category-admin-filters">
                    <input type="search" id="categorySearchInput" class="form-control" placeholder="Search category name or code..." autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">
                    <select id="categoryStatusFilter" class="form-control">
                        <option value="all">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="with_items">With Items</option>
                        <option value="empty">No Items</option>
                    </select>
                </div>
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
const ITEMS_API_BASE = window.SFMS_PUBLIC_URL('/api/items');
const INVENTORY_STOCK_API_BASE = window.SFMS_PUBLIC_URL('/api/inventory-stock');
const INVENTORY_ROOMS_API_BASE = window.SFMS_PUBLIC_URL('/api/inventory-rooms');
const ROOMS_API_BASE = '/api/rooms';
const CATEGORIES_API_BASE = window.SFMS_PUBLIC_URL('/api/inventory-categories');
const DEPARTMENTS_API_BASE = '/api/departments';
const CAN_MANAGE_CATEGORIES = <?php echo $canManageCategories ? 'true' : 'false'; ?>;
const CAN_ADD_ITEMS = <?php echo $canAddItems ? 'true' : 'false'; ?>;
const CAN_CREATE_INVENTORY_ENTRIES = <?php echo $canCreateInventoryEntries ? 'true' : 'false'; ?>;
const CAN_ADJUST_STOCK = <?php echo $canAdjustStock ? 'true' : 'false'; ?>;
const HIDDEN_CATEGORY_NAMES = new Set(['janitorial', 'medical']);

let inventoryRoomsCache = [];
let inventoryCategoriesCache = [];
let inventoryDepartmentsCache = [];
let inventoryEntryRoomsCache = [];
let inventoryAllItems = [];
let inventoryEntryHistory = [];
let inventorySelectedCategoryId = null;
let inventorySelectedInventoryRoomId = null;
let inventorySearchTerm = '';
let inventoryItemsStatusFilter = '';
let inventoryItemsConditionFilter = '';
let categoryAdminSearchTerm = '';
let categoryAdminStatusFilter = 'all';
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
    box.innerHTML = message;
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

function setInventoryEntryMessage(message, type = 'info') {
    const box = document.getElementById('inventoryEntryModalMessage');
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

function setAdjustStockMessage(message, type = 'info') {
    const box = document.getElementById('adjustStockModalMessage');
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

function populateAdjustStockItemOptions(selectedItemId = null) {
    const select = document.getElementById('adjustStockItemSelect');
    if (!select) return;

    const sortedItems = [...inventoryAllItems].sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));

    select.innerHTML = '<option value="">Select an item...</option>' + sortedItems.map((item) => {
        const label = `${item.name || 'Unnamed Item'} — ${Number(item.quantity ?? 0)} on hand (${item.inventory_room_name || 'Inventory Room'})`;
        return `<option value="${Number(item.id)}">${inventoryEscapeHtml(label)}</option>`;
    }).join('');

    if (selectedItemId) {
        select.value = String(selectedItemId);
    }
}

function updateAdjustStockReview() {
    const itemId = Number(document.getElementById('adjustStockItemSelect')?.value || 0);
    const direction = document.getElementById('adjustStockDirection')?.value || 'increase';
    const quantity = Number(document.getElementById('adjustStockQuantity')?.value || 0);
    const currentQuantityInput = document.getElementById('adjustStockCurrentQuantity');
    const reviewBox = document.getElementById('adjustStockReview');
    if (!currentQuantityInput || !reviewBox) return;

    const item = inventoryAllItems.find((entry) => Number(entry.id) === itemId);
    if (!item) {
        currentQuantityInput.value = 'Select an item to view current stock';
        reviewBox.hidden = true;
        return;
    }

    const onHand = Number(item.quantity ?? 0);
    const available = item.available_quantity !== undefined
        ? Number(item.available_quantity)
        : Math.max(onHand - Number(item.reserved_quantity ?? 0), 0);
    currentQuantityInput.value = `${onHand} on hand (${available} available)`;

    if (!quantity || quantity <= 0) {
        reviewBox.hidden = true;
        return;
    }

    const projected = direction === 'decrease' ? onHand - quantity : onHand + quantity;
    reviewBox.hidden = false;
    reviewBox.className = 'inventory-modal-message inventory-modal-message-info';
    reviewBox.textContent = `Review: ${direction === 'decrease' ? 'Decrease' : 'Increase'} "${item.name}" by ${quantity}. `
        + `Stock will change from ${onHand} to ${projected}.`;
}

function openAdjustStockModal() {
    const modal = document.getElementById('adjustStockModal');
    if (!modal) return;

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    setAdjustStockMessage('');
    document.getElementById('adjustStockForm')?.reset();
    populateAdjustStockItemOptions();
    updateAdjustStockReview();
    document.getElementById('adjustStockItemSelect')?.focus();
}

function closeAdjustStockModal() {
    const modal = document.getElementById('adjustStockModal');
    if (!modal) return;

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.getElementById('adjustStockForm')?.reset();
    setAdjustStockMessage('');
    const reviewBox = document.getElementById('adjustStockReview');
    if (reviewBox) reviewBox.hidden = true;
}

async function saveAdjustStock() {
    const itemId = Number(document.getElementById('adjustStockItemSelect')?.value || 0);
    const direction = document.getElementById('adjustStockDirection')?.value || '';
    const quantity = Number(document.getElementById('adjustStockQuantity')?.value || 0);
    const reason = (document.getElementById('adjustStockReason')?.value || '').trim();

    if (!itemId) {
        setAdjustStockMessage('Please select an inventory item.', 'danger');
        return;
    }

    if (direction !== 'increase' && direction !== 'decrease') {
        setAdjustStockMessage('Please choose an adjustment type.', 'danger');
        return;
    }

    if (!Number.isInteger(quantity) || quantity <= 0) {
        setAdjustStockMessage('Please enter a quantity greater than zero.', 'danger');
        return;
    }

    if (!reason) {
        setAdjustStockMessage('Please enter a reason for this adjustment.', 'danger');
        return;
    }

    const item = inventoryAllItems.find((entry) => Number(entry.id) === itemId);
    const itemLabel = item ? item.name : `item #${itemId}`;
    const confirmed = await Components.confirm(
        `${direction === 'decrease' ? 'Decrease' : 'Increase'} stock of "${itemLabel}" by ${quantity}? This action will be recorded in the inventory ledger and cannot be undone.`,
        'Confirm Adjustment',
        'Cancel'
    );
    if (!confirmed) return;

    const saveButton = document.getElementById('saveAdjustStockButton');
    const originalLabel = saveButton ? saveButton.textContent : '';
    if (saveButton) {
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';
    }
    setAdjustStockMessage('Saving adjustment...');

    try {
        const response = await fetch(`${ITEMS_API_BASE}/${itemId}/adjust-stock`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ direction, quantity, reason })
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to adjust stock');
        }

        closeAdjustStockModal();
        await loadInventory();
        Components.toast(result.message || 'Stock adjusted successfully.', 'success');
    } catch (error) {
        console.error('Adjust stock error:', error);
        setAdjustStockMessage(error.message || 'Failed to adjust stock.', 'danger');
        Components.alert(error.message || 'Failed to adjust stock.', 'danger');
    } finally {
        if (saveButton) {
            saveButton.disabled = false;
            saveButton.textContent = originalLabel;
        }
    }
}

function setInventoryHistoryMessage(message, type = 'info') {
    const box = document.getElementById('inventoryHistoryModalMessage');
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

function inventoryEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
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

function computeInventoryStatusPreview(quantity, threshold) {
    const parsedQuantity = Number(quantity || 0);
    const parsedThreshold = Number(threshold || 0);

    if (parsedQuantity <= 0) {
        return 'OUT OF STOCK';
    }

    if (parsedQuantity <= parsedThreshold) {
        return 'LOW STOCK';
    }

    return 'IN STOCK';
}

function updateInventoryStatusPreview() {
    const previewInput = document.getElementById('inventoryStatusPreviewInput');
    const quantityInput = document.getElementById('inventoryQuantityInput');
    const thresholdInput = document.getElementById('inventoryThresholdOverrideInput');
    if (!previewInput || !quantityInput || !thresholdInput) return;

    previewInput.value = computeInventoryStatusPreview(quantityInput.value, thresholdInput.value || 0);
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
    const response = await fetch(`${INVENTORY_ROOMS_API_BASE}`, { credentials: 'same-origin' });
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

        if (inventoryItemsStatusFilter && normalizeInventoryStatus(item.status) !== inventoryItemsStatusFilter) {
            return false;
        }

        if (inventoryItemsConditionFilter && String(item.item_condition || '').toLowerCase() !== inventoryItemsConditionFilter) {
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
            item.brand,
            item.model,
            item.unit_type,
            item.item_condition,
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
    const allCountClass = cardItems.length === 0 ? 'is-empty' : '';
    cards.push(`
        <button type="button" class="inventory-category-card ${getCategoryToneClass('all items')} ${allActiveClass} ${allCountClass}" data-category="all" aria-pressed="${inventorySelectedCategoryId === null ? 'true' : 'false'}">
            <span class="inventory-category-card-icon">${getCategoryIconMarkup('all items')}</span>
            <span class="inventory-category-card-content">
                <span class="inventory-category-card-name">All Items</span>
                <span class="inventory-category-card-meta">
                    ${lowCount > 0 ? `<span class="inventory-category-chip is-low">${lowCount} low</span>` : ''}
                    ${outCount > 0 ? `<span class="inventory-category-chip is-out">${outCount} out</span>` : ''}
                    ${okCount > 0 ? `<span class="inventory-category-chip is-ok">${okCount} ok</span>` : ''}
                </span>
            </span>
            <span class="inventory-category-card-count-badge">${cardItems.length}</span>
        </button>
    `);


    categories.forEach((category) => {
        const categoryItems = cardItems.filter((item) => Number(item.category_id || 0) === Number(category.id));
        const catLow = categoryItems.filter((item) => normalizeInventoryStatus(item.status) === 'low_stock').length;
        const catOut = categoryItems.filter((item) => normalizeInventoryStatus(item.status) === 'out_of_stock').length;
        const catOk = categoryItems.filter((item) => normalizeInventoryStatus(item.status) === 'available').length;
        const activeClass = inventorySelectedCategoryId === Number(category.id) ? 'is-active' : '';
        const countClass = categoryItems.length === 0 ? 'is-empty' : '';

        cards.push(`
            <button type="button" class="inventory-category-card ${getCategoryToneClass(category.name || '')} ${activeClass} ${countClass}" data-category="${Number(category.id)}" aria-pressed="${inventorySelectedCategoryId === Number(category.id) ? 'true' : 'false'}">
                <span class="inventory-category-card-icon">${getCategoryIconMarkup(category.name || '')}</span>
                <span class="inventory-category-card-content">
                    <span class="inventory-category-card-name">${category.name || 'Unnamed Category'}</span>
                    <span class="inventory-category-card-meta">
                        ${catLow > 0 ? `<span class="inventory-category-chip is-low">${catLow} low</span>` : ''}
                        ${catOut > 0 ? `<span class="inventory-category-chip is-out">${catOut} out</span>` : ''}
                        ${catOk > 0 ? `<span class="inventory-category-chip is-ok">${catOk} ok</span>` : ''}
                    </span>
                </span>
                <span class="inventory-category-card-count-badge">${categoryItems.length}</span>
            </button>
        `);

    });

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

    const backBtnMarkup = `
        <div class="inventory-room-back-wrap">
            <button type="button" class="btn btn-secondary inventory-room-back-btn" data-room-view="back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                Back to Categories
            </button>
        </div>
    `;

    if (rooms.length === 0) {
        container.innerHTML = backBtnMarkup + `
            <div class="ui-empty-state ui-fade-in inventory-room-empty-state">
                <strong>No inventory rooms yet.</strong>
                <span>Add an inventory room to start grouping stockroom equipment.</span>
            </div>
        `;
    } else {
        const cards = [backBtnMarkup];

        rooms.forEach((room) => {
            const roomId = Number(room.id);
            const matchingItems = roomItems.filter((item) => Number(item.inventory_room_id || 0) === roomId);
            const lowCount = matchingItems.filter((item) => normalizeInventoryStatus(item.status) === 'low_stock').length;
            const outCount = matchingItems.filter((item) => normalizeInventoryStatus(item.status) === 'out_of_stock').length;
            const okCount = matchingItems.filter((item) => normalizeInventoryStatus(item.status) === 'available').length;
            const activeClass = inventorySelectedInventoryRoomId === roomId ? 'is-active' : '';
            const countClass = matchingItems.length === 0 ? 'is-empty' : '';

            cards.push(`
                <button type="button" class="inventory-category-card tone-classroom ${activeClass} ${countClass}" data-inventory-room-card="${roomId}" aria-pressed="${inventorySelectedInventoryRoomId === roomId ? 'true' : 'false'}">
                    <span class="inventory-category-card-icon">${getCategoryIconMarkup('classroom')}</span>
                    <span class="inventory-category-card-content">
                        <span class="inventory-category-card-name">${room.name || 'Unnamed Inventory Room'}</span>
                        <span class="inventory-category-card-meta">
                            ${okCount > 0 ? `<span class="inventory-category-chip is-ok">${okCount} ok</span>` : ''}
                            ${lowCount > 0 ? `<span class="inventory-category-chip is-low">${lowCount} low</span>` : ''}
                            ${outCount > 0 ? `<span class="inventory-category-chip is-out">${outCount} out</span>` : ''}
                            ${matchingItems.length === 0 ? `<span class="inventory-category-chip">No items</span>` : ''}
                        </span>
                    </span>
                    <span class="inventory-category-card-count-badge">${matchingItems.length}</span>
                </button>
            `);
        });

        container.innerHTML = cards.join('');
    }

    container.querySelectorAll('[data-room-view="back"]').forEach((btn) => {
        btn.addEventListener('click', () => {
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

    const categories = Array.isArray(inventoryCategoriesCache) ? inventoryCategoriesCache.filter((category) => {
        const name = String(category.name || '').toLowerCase();
        const code = String(category.code || '').toLowerCase();
        const matchesSearch = categoryAdminSearchTerm === ''
            || name.includes(categoryAdminSearchTerm)
            || code.includes(categoryAdminSearchTerm);

        if (!matchesSearch) {
            return false;
        }

        const isActive = Number(category.is_active || 0) === 1;
        const hasItems = Number(category.total_items || 0) > 0;

        if (categoryAdminStatusFilter === 'active') return isActive;
        if (categoryAdminStatusFilter === 'inactive') return !isActive;
        if (categoryAdminStatusFilter === 'with_items') return hasItems;
        if (categoryAdminStatusFilter === 'empty') return !hasItems;
        return true;
    }) : [];

    const summary = document.getElementById('categoriesSummaryText');
    if (summary) {
        const total = Array.isArray(inventoryCategoriesCache) ? inventoryCategoriesCache.length : 0;
        summary.textContent = categories.length === total
            ? `${total} ${total === 1 ? 'category' : 'categories'}`
            : `${categories.length} of ${total} categories`;
    }

    if (!categories.length) {
        const hasFilters = categoryAdminSearchTerm !== '' || categoryAdminStatusFilter !== 'all';
        container.innerHTML = hasFilters
            ? '<div class="ui-empty-state ui-fade-in"><strong>No matching categories found.</strong><span>Try changing the search keyword or status filter.</span></div>'
            : '<div class="ui-empty-state ui-fade-in"><strong>No categories yet.</strong><span>Create one above to start grouping inventory items.</span></div>';
        return;
    }

    let html = '<div class="inventory-category-admin-grid">';

    categories.forEach((category) => {
        const active = Number(category.is_active || 0) === 1 ? 'Active' : 'Inactive';
        const activeClass = Number(category.is_active || 0) === 1 ? 'is-active' : 'is-inactive';
        const totalItems = Number(category.total_items || 0);
        const totalQuantity = Number(category.total_stock_quantity || 0);
        const codeLabel = String(category.code || '').trim();

        html += `
            <article class="inventory-category-admin-card">
                <div class="inventory-category-admin-card-head">
                    <div>
                        <h4>${inventoryEscapeHtml(category.name || '-')}</h4>
                        <div class="inventory-category-admin-card-meta">
                            ${codeLabel ? `<span class="inventory-category-admin-code">${inventoryEscapeHtml(codeLabel)}</span>` : '<span class="inventory-category-admin-code is-empty">No code</span>'}
                            <span class="inventory-category-admin-status ${activeClass}">${active}</span>
                        </div>
                    </div>
                    <div class="inventory-category-admin-sort">Sort #${Number(category.sort_order || 0)}</div>
                </div>
                <div class="inventory-category-admin-stats">
                    <div class="inventory-category-admin-stat">
                        <span class="inventory-category-admin-stat-label">Items</span>
                        <strong>${totalItems}</strong>
                    </div>
                    <div class="inventory-category-admin-stat">
                        <span class="inventory-category-admin-stat-label">Stock Qty</span>
                        <strong>${totalQuantity}</strong>
                    </div>
                </div>
                <div class="inventory-category-admin-actions">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="startEditCategory(${Number(category.id)})">Edit</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteCategory(${Number(category.id)})">Delete</button>
                </div>
            </article>
        `;
    });

    html += '</div>';
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
    const response = await fetch(window.SFMS_PUBLIC_URL('/api/inventory-categories'), { credentials: 'same-origin' });
    const payload = await response.json();

    if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'Failed to load categories');
    }

    inventoryCategoriesCache = (Array.isArray(payload.data?.categories) ? payload.data.categories : [])
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
        await loadInventoryEntryCategoryOptions();
        renderCategoriesList();
        applySelectedCategoryRules();
    } catch (error) {
        console.error('Category load error:', error);
            setAddItemsMessage('Could not load inventory categories.', 'warning');
        setManageCategoriesMessage(error.message || 'Could not load categories.', 'danger');
    }
}

async function fetchInventoryDepartments() {
    const response = await fetch(DEPARTMENTS_API_BASE, { credentials: 'same-origin' });
    const payload = await response.json();
    const departments = Array.isArray(payload?.data?.departments)
        ? payload.data.departments
        : Array.isArray(payload?.departments)
            ? payload.departments
            : [];

    if (!response.ok || payload?.success !== true) {
        throw new Error(payload?.message || 'Failed to load departments');
    }

    inventoryDepartmentsCache = departments;
}

async function fetchInventoryEntryRooms() {
    const response = await fetch(`${ROOMS_API_BASE}`, { credentials: 'same-origin' });
    const payload = await response.json();

    if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'Failed to load laboratory / room options');
    }

    inventoryEntryRoomsCache = Array.isArray(payload.data?.rooms) ? payload.data.rooms : [];
}

async function loadInventoryEntryCategoryOptions() {
    const formSelect = document.getElementById('inventoryEntryCategory');
    const filterSelect = document.getElementById('inventory-entry-filter-category');
    const activeCategories = inventoryCategoriesCache.filter((category) => Number(category.is_active || 0) === 1);

    if (formSelect) {
        formSelect.innerHTML = '<option value="">Select category</option>';
        activeCategories.forEach((category) => {
            const option = document.createElement('option');
            option.value = String(category.id);
            option.textContent = category.name || 'Unnamed Category';
            formSelect.appendChild(option);
        });
    }

    if (filterSelect) {
        filterSelect.innerHTML = '<option value="">All Categories</option>';
        activeCategories.forEach((category) => {
            const option = document.createElement('option');
            option.value = String(category.id);
            option.textContent = category.name || 'Unnamed Category';
            filterSelect.appendChild(option);
        });
    }
}

async function loadInventoryEntryDepartmentOptions() {
    const formSelect = document.getElementById('inventoryEntryDepartment');
    const filterSelect = document.getElementById('inventory-entry-filter-department');

    if (formSelect) {
        formSelect.innerHTML = '<option value="">Select department</option>';
        inventoryDepartmentsCache.forEach((department) => {
            const option = document.createElement('option');
            option.value = String(department.department_id);
            option.textContent = department.name || 'Unnamed Department';
            formSelect.appendChild(option);
        });
    }

    if (filterSelect) {
        filterSelect.innerHTML = '<option value="">All Departments</option>';
        inventoryDepartmentsCache.forEach((department) => {
            const option = document.createElement('option');
            option.value = String(department.department_id);
            option.textContent = department.name || 'Unnamed Department';
            filterSelect.appendChild(option);
        });
    }
}

async function loadInventoryEntryRoomOptions() {
    const formSelect = document.getElementById('inventoryEntryRoom');
    const filterSelect = document.getElementById('inventory-entry-filter-room');

    if (formSelect) {
        formSelect.innerHTML = '<option value="">Select laboratory / room</option>';
        inventoryEntryRoomsCache.forEach((room) => {
            const option = document.createElement('option');
            option.value = String(room.id);
            option.textContent = room.name || 'Unnamed Room';
            formSelect.appendChild(option);
        });
    }

    if (filterSelect) {
        filterSelect.innerHTML = '<option value="">All Laboratories / Rooms</option>';
        inventoryEntryRoomsCache.forEach((room) => {
            const option = document.createElement('option');
            option.value = String(room.id);
            option.textContent = room.name || 'Unnamed Room';
            filterSelect.appendChild(option);
        });
    }
}

async function loadInventoryEntryInventoryRoomOptions() {
    const select = document.getElementById('inventoryEntryInventoryRoom');
    if (!select) return;

    select.innerHTML = '<option value="">Select warehouse inventory</option>';

    inventoryRoomsCache.forEach((room) => {
        const option = document.createElement('option');
        option.value = String(room.id);
        option.textContent = room.name || 'Unnamed Inventory Room';
        select.appendChild(option);
    });

    if (inventoryRoomsCache.length === 1) {
        select.value = String(inventoryRoomsCache[0].id);
    }
}

function formatInventoryEntryCondition(condition) {
    return String(condition || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function renderInventoryEntryHistory(entries) {
    const container = document.getElementById('inventory-entry-container');
    if (!container) return;

    if (!Array.isArray(entries) || entries.length === 0) {
        container.innerHTML = '<div class="ui-empty-state ui-fade-in"><strong>No inventory entries found.</strong><span>Inventory receipt and stock entry records will appear here after the first entry is saved.</span></div>';
        setText('inventory-entry-count', '0 entries');
        return;
    }

    let html = '<div class="inventory-entry-table-wrap"><table class="table inventory-entry-table">';
    html += '<thead><tr>';
    html += '<th>Stock Entry ID</th><th>Date Received</th><th>Item</th><th>Supplier</th><th>OR / Receipt</th><th>Category</th><th>Department</th><th>Laboratory / Room</th><th>Warehouse</th><th>Receiver</th><th>Condition</th><th>Description</th>';
    html += '</tr></thead><tbody>';

    entries.forEach((entry) => {
        const dateReceived = entry.date_received
            ? new Date(String(entry.date_received).replace(' ', 'T')).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
            : '-';
        const itemLabel = `${inventoryEscapeHtml(entry.item_name || 'Unnamed Item')} <span class="inventory-entry-qty-pill">${Number(entry.quantity || 0)} ${inventoryEscapeHtml(entry.unit_type || '')}</span>`;

        html += '<tr>';
        html += `<td><strong>${inventoryEscapeHtml(entry.stock_entry_id || '-')}</strong></td>`;
        html += `<td>${inventoryEscapeHtml(dateReceived)}</td>`;
        html += `<td>${itemLabel}</td>`;
        html += `<td>${inventoryEscapeHtml(entry.supplier_name || '-')}</td>`;
        html += `<td>${inventoryEscapeHtml(entry.or_number || '-')}</td>`;
        html += `<td>${inventoryEscapeHtml(entry.category_name || 'Uncategorized')}</td>`;
        html += `<td>${inventoryEscapeHtml(entry.department_name || '-')}</td>`;
        html += `<td>${inventoryEscapeHtml(entry.room_name || '-')}</td>`;
        html += `<td>${inventoryEscapeHtml(entry.inventory_room_name || 'Warehouse')}</td>`;
        html += `<td>${inventoryEscapeHtml(entry.receiver_name || '-')}</td>`;
        html += `<td>${inventoryEscapeHtml(formatInventoryEntryCondition(entry.item_condition || '-'))}</td>`;
        html += `<td>${inventoryEscapeHtml(entry.description || '-')}</td>`;
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
    setText('inventory-entry-count', `${entries.length} ${entries.length === 1 ? 'entry' : 'entries'}`);
}

async function loadInventoryEntryHistory() {
    const container = document.getElementById('inventory-entry-container');
    if (container) {
        container.innerHTML = '<div class="ui-empty-state ui-fade-in"><strong>Loading inventory entries...</strong><span>Please wait while we collect your stock entry history.</span></div>';
    }

    const params = new URLSearchParams();
    const search = (document.getElementById('inventory-entry-search')?.value || '').trim();
    const categoryId = document.getElementById('inventory-entry-filter-category')?.value || '';
    const departmentId = document.getElementById('inventory-entry-filter-department')?.value || '';
    const roomId = document.getElementById('inventory-entry-filter-room')?.value || '';
    const dateFrom = document.getElementById('inventory-entry-filter-date-from')?.value || '';
    const dateTo = document.getElementById('inventory-entry-filter-date-to')?.value || '';

    if (search) params.set('search', search);
    if (categoryId) params.set('category_id', categoryId);
    if (departmentId) params.set('department_id', departmentId);
    if (roomId) params.set('room_id', roomId);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);

    try {
        const response = await fetch(`${INVENTORY_STOCK_API_BASE}/entries?${params.toString()}`, {
            credentials: 'same-origin'
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to load inventory entries');
        }

        inventoryEntryHistory = Array.isArray(payload.data?.entries) ? payload.data.entries : [];
        renderInventoryEntryHistory(inventoryEntryHistory);
    } catch (error) {
        console.error('Inventory entry history load error:', error);
        if (container) {
            container.innerHTML = '<div class="alert alert-danger ui-fade-in">Could not load inventory entry history right now.</div>';
        }
        setText('inventory-entry-count', '0 entries');
    }
}

function openInventoryEntryModal() {
    const modal = document.getElementById('inventoryEntryModal');
    if (!modal) return;

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    setInventoryEntryMessage('');
    document.getElementById('inventoryEntryForm')?.reset();
    const dateInput = document.getElementById('inventoryEntryDateReceived');
    if (dateInput) {
        dateInput.value = new Date().toISOString().slice(0, 10);
    }
    loadInventoryEntryInventoryRoomOptions();
    document.getElementById('inventoryEntryIdPreview').textContent = 'Auto-generated when saved';
    document.getElementById('inventoryEntryOrNumber')?.focus();
}

function closeInventoryEntryModal() {
    const modal = document.getElementById('inventoryEntryModal');
    if (!modal) return;

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    document.getElementById('inventoryEntryForm')?.reset();
    setInventoryEntryMessage('');
    document.getElementById('inventoryEntryIdPreview').textContent = 'Auto-generated when saved';
}

async function saveInventoryEntry() {
    const payload = {
        or_number: (document.getElementById('inventoryEntryOrNumber')?.value || '').trim(),
        supplier_name: (document.getElementById('inventoryEntrySupplierName')?.value || '').trim(),
        date_received: document.getElementById('inventoryEntryDateReceived')?.value || '',
        category_id: document.getElementById('inventoryEntryCategory')?.value || '',
        item_name: (document.getElementById('inventoryEntryItemName')?.value || '').trim(),
        quantity: Number(document.getElementById('inventoryEntryQuantity')?.value || 0),
        unit_type: (document.getElementById('inventoryEntryUnitType')?.value || '').trim(),
        department_id: document.getElementById('inventoryEntryDepartment')?.value || '',
        room_id: document.getElementById('inventoryEntryRoom')?.value || '',
        inventory_room_id: document.getElementById('inventoryEntryInventoryRoom')?.value || '',
        description: (document.getElementById('inventoryEntryDescription')?.value || '').trim(),
        item_condition: document.getElementById('inventoryEntryCondition')?.value || '',
    };

    if (!payload.or_number || !payload.supplier_name || !payload.date_received || !payload.category_id || !payload.item_name || payload.quantity <= 0 || !payload.unit_type || !payload.department_id || !payload.inventory_room_id || !payload.item_condition) {
        setInventoryEntryMessage('Please complete all required inventory entry fields before saving.', 'danger');
        return;
    }

    const saveButton = document.getElementById('saveInventoryEntryButton');
    const originalLabel = saveButton?.textContent || 'Save Entry';
    if (saveButton) {
        saveButton.disabled = true;
        saveButton.textContent = 'Saving...';
    }

    setInventoryEntryMessage('Saving inventory entry and updating warehouse stock...', 'info');

    try {
        const response = await fetch(`${INVENTORY_STOCK_API_BASE}/entries`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to save inventory entry');
        }

        const stockEntryId = result.data?.entry?.stock_entry_id || 'Saved';
        document.getElementById('inventoryEntryIdPreview').textContent = stockEntryId;
        setInventoryEntryMessage(`Inventory entry ${stockEntryId} saved successfully.`, 'info');
        await loadInventory();
        await loadInventoryEntryHistory();

        window.setTimeout(() => {
            closeInventoryEntryModal();
        }, 600);
    } catch (error) {
        console.error('Save inventory entry error:', error);
        setInventoryEntryMessage(error.message || 'Failed to save inventory entry.', 'danger');
    } finally {
        if (saveButton) {
            saveButton.disabled = false;
            saveButton.textContent = originalLabel;
        }
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
    document.getElementById('addItemsModalTitle').textContent = 'Add Item';
    document.getElementById('saveAddItemsButton').textContent = 'Save Item';
    document.getElementById('addItemsForm').reset();
    document.getElementById('inventoryItemIdInput').value = '';
    document.getElementById('inventoryQuantityInput').value = '1';
    document.getElementById('inventoryQuantityInput').setAttribute('min', '1');
    document.getElementById('inventoryQuantityInput').readOnly = false;
    document.getElementById('inventoryStatusPreviewInput').value = 'IN STOCK';
    const thresholdInput = document.getElementById('inventoryThresholdOverrideInput');
    if (thresholdInput) {
        thresholdInput.value = '';
        thresholdInput.disabled = false;
    }

    Promise.all([refreshCategoriesUI(), fetchInventoryRooms()])
        .then(() => {
            loadInventoryRoomsForSelect();
            updateInventoryStatusPreview();
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
    document.getElementById('inventoryItemIdInput').value = '';
    document.getElementById('addItemsModalTitle').textContent = 'Add Item';
    document.getElementById('saveAddItemsButton').textContent = 'Save Item';
    document.getElementById('inventoryQuantityInput').value = '1';
    document.getElementById('inventoryQuantityInput').setAttribute('min', '1');
    document.getElementById('inventoryQuantityInput').readOnly = false;
    document.getElementById('inventoryStatusPreviewInput').value = 'Auto-calculated from quantity and threshold';
    const thresholdInput = document.getElementById('inventoryThresholdOverrideInput');
    if (thresholdInput) {
        thresholdInput.value = '';
        thresholdInput.disabled = false;
    }
    setAddItemsMessage('');
}

function fillInventoryItemForm(item) {
    document.getElementById('inventoryItemIdInput').value = String(item.id || '');
    document.getElementById('inventoryRoomSelect').value = String(item.inventory_room_id || '');
    document.getElementById('inventoryItemNameInput').value = item.name || '';
    document.getElementById('inventoryQuantityInput').value = String(Number(item.quantity || 0));
    document.getElementById('inventoryQuantityInput').setAttribute('min', '0');
    document.getElementById('inventoryCategorySelect').value = item.category_id === null || item.category_id === undefined ? '' : String(item.category_id);
    document.getElementById('inventoryBrandInput').value = item.brand || '';
    document.getElementById('inventoryModelInput').value = item.model || '';
    document.getElementById('inventoryUnitTypeInput').value = item.unit_type || '';
    document.getElementById('inventoryConditionSelect').value = item.item_condition || '';
    document.getElementById('inventoryThresholdOverrideInput').value = item.reorder_level === null || item.reorder_level === undefined ? '' : String(Number(item.reorder_level));
    document.getElementById('inventoryDescriptionInput').value = item.description || '';
    updateInventoryStatusPreview();
}

function startEditInventoryItem(itemId) {
    const item = inventoryAllItems.find((entry) => Number(entry.id) === Number(itemId));
    if (!item) {
        return;
    }

    openAddItemsModal();
    document.getElementById('addItemsModalTitle').textContent = 'Edit Item';
    document.getElementById('saveAddItemsButton').textContent = 'Save Changes';

    Promise.all([refreshCategoriesUI(), fetchInventoryRooms()])
        .then(() => {
            loadInventoryRoomsForSelect();
            fillInventoryItemForm(item);
            // Quantity can only be changed via Manual Stock Adjustment, so it's
            // read-only here — Edit Item only ever touches metadata fields.
            document.getElementById('inventoryQuantityInput').readOnly = true;
            document.getElementById('inventoryItemNameInput')?.focus();
        })
        .catch((error) => {
            console.error('Edit item modal bootstrap error:', error);
            setAddItemsMessage(error.message || 'Could not load item details.', 'danger');
        });
}

function openInventoryHistoryModal() {
    const modal = document.getElementById('inventoryItemHistoryModal');
    if (!modal) return;

    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
}

function closeInventoryHistoryModal() {
    const modal = document.getElementById('inventoryItemHistoryModal');
    if (!modal) return;

    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    setInventoryHistoryMessage('');
}

async function openInventoryItemHistory(itemId) {
    const item = inventoryAllItems.find((entry) => Number(entry.id) === Number(itemId));
    const container = document.getElementById('inventoryHistoryContainer');
    if (!container || !item) return;

    openInventoryHistoryModal();
    document.getElementById('inventoryHistoryModalTitle').textContent = `${item.name || 'Item'} History`;
    container.innerHTML = '<div class="ui-empty-state ui-fade-in"><strong>Loading item history...</strong><span>Please wait while we collect recent inventory activity.</span></div>';
    setInventoryHistoryMessage('');

    try {
        const response = await fetch(`${INVENTORY_STOCK_API_BASE}/${encodeURIComponent(itemId)}/transactions`, {
            credentials: 'same-origin'
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to load item history');
        }

        const transactions = Array.isArray(payload.data?.transactions) ? payload.data.transactions : [];
        if (transactions.length === 0) {
            container.innerHTML = '<div class="ui-empty-state ui-fade-in"><strong>No history found.</strong><span>This item does not have recorded transactions yet.</span></div>';
            return;
        }

        let html = '<div class="inventory-history-table-wrap"><table class="table inventory-history-table"><thead><tr><th>Date</th><th>Type</th><th>Quantity</th><th>Performed By</th><th>Note</th></tr></thead><tbody>';
        transactions.forEach((transaction) => {
            const formattedDate = transaction.created_at
                ? new Date(String(transaction.created_at).replace(' ', 'T')).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
                : 'N/A';
            const transactionType = String(transaction.transaction_type || '').replace(/_/g, ' ').toUpperCase();
            html += '<tr>';
            html += `<td>${inventoryEscapeHtml(formattedDate)}</td>`;
            html += `<td>${inventoryEscapeHtml(transactionType)}</td>`;
            html += `<td>${inventoryEscapeHtml(transaction.quantity ?? 0)}</td>`;
            html += `<td>${inventoryEscapeHtml(transaction.performed_by_name || 'System')}</td>`;
            html += `<td>${inventoryEscapeHtml(transaction.reference_note || '-')}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    } catch (error) {
        console.error('Inventory item history error:', error);
        container.innerHTML = '<div class="alert alert-danger ui-fade-in">Could not load item history right now.</div>';
    }
}

async function deleteInventoryItem(itemId) {
    const item = inventoryAllItems.find((entry) => Number(entry.id) === Number(itemId));
    if (!item) return;

    const confirmed = await Components.confirm(`Delete "${item.name || 'this item'}"? This will remove it from inventory if it has no active report allocations.`, 'Delete', 'Cancel');
    if (!confirmed) return;

    try {
        const response = await fetch(`${INVENTORY_STOCK_API_BASE}/${Number(itemId)}`, {
            method: 'DELETE',
            credentials: 'same-origin',
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to delete item');
        }

        await loadInventory();
        Components.toast(payload.message || 'Item deleted successfully.', 'success');
    } catch (error) {
        console.error('Delete item error:', error);
        Components.alert(error.message || 'Failed to delete item.', 'danger');
    }
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
        const url    = id > 0 ? `${CATEGORIES_API_BASE}/${id}` : CATEGORIES_API_BASE;
        const method = id > 0 ? 'PATCH' : 'POST';
        const response = await fetch(url, {
            method,
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

function systemCategoryDeleteConfirm(categoryName) {
    return new Promise((resolve) => {
        const box = document.getElementById('manageCategoriesMessage');
        if (!box) { resolve(false); return; }
        box.hidden = false;
        box.className = 'inventory-modal-message inventory-modal-message-warning';
        box.innerHTML = `
            <span>Are you sure you want to delete <strong>${categoryName}</strong>? This cannot be undone.</span>
            <span style="display:inline-flex;gap:8px;margin-left:12px;">
                <button id="catDelYes" class="btn btn-danger btn-sm">Yes, Delete</button>
                <button id="catDelNo" class="btn btn-secondary btn-sm">Cancel</button>
            </span>
        `;
        box.querySelector('#catDelYes').onclick = () => { setManageCategoriesMessage(''); resolve(true); };
        box.querySelector('#catDelNo').onclick = () => { setManageCategoriesMessage(''); resolve(false); };
    });
}

async function deleteCategory(id) {
    if (!id) return;

    const category = getCategoryById(id);
    const confirmed = await systemCategoryDeleteConfirm(category ? category.name : 'this category');
    if (!confirmed) return;

    try {
        const response = await fetch(`${CATEGORIES_API_BASE}/${id}`, {
            method: 'DELETE',
            credentials: 'same-origin'
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
    const itemId = Number(document.getElementById('inventoryItemIdInput')?.value || 0);
    const inventoryRoomId = Number(document.getElementById('inventoryRoomSelect')?.value || 0);
    const categoryIdRaw = document.getElementById('inventoryCategorySelect')?.value || '';
    const name = document.getElementById('inventoryItemNameInput').value.trim();
    const brand = document.getElementById('inventoryBrandInput').value.trim();
    const model = document.getElementById('inventoryModelInput').value.trim();
    const unitType = document.getElementById('inventoryUnitTypeInput').value.trim();
    const itemCondition = document.getElementById('inventoryConditionSelect').value.trim();
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

    if (!unitType) {
        setAddItemsMessage('Please enter a unit type.', 'danger');
        return;
    }

    if (!itemCondition) {
        setAddItemsMessage('Please choose an item condition.', 'danger');
        return;
    }

    if (itemId <= 0 && (!quantity || quantity <= 0)) {
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
        brand,
        model,
        unit_type: unitType,
        item_condition: itemCondition,
        description,
        category_id: categoryIdRaw === '' ? null : Number(categoryIdRaw),
        reorder_level: Number(reorderLevelRaw)
    };

    // Quantity is only ever sent when creating a new item. Editing an existing
    // item must go through Manual Stock Adjustment instead, so the field is
    // read-only in Edit Item and is deliberately left out of the update payload.
    if (itemId > 0) {
        payload.id = itemId;
    } else {
        payload.quantity = quantity;
    }

    const saveButton = document.getElementById('saveAddItemsButton');
    const originalLabel = saveButton.textContent;
    saveButton.disabled = true;
    saveButton.textContent = 'Saving...';
    setAddItemsMessage('Saving item...');

    try {
        const url    = itemId > 0 ? `${INVENTORY_STOCK_API_BASE}/${itemId}` : INVENTORY_STOCK_API_BASE;
        const method = itemId > 0 ? 'PUT' : 'POST';
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || (itemId > 0 ? 'Failed to update item' : 'Failed to add item'));
        }

        closeAddItemsModal();
        await loadInventory();
        await loadInventoryEntryHistory();
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
        const response = await fetch(`${INVENTORY_ROOMS_API_BASE}`, {
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

    let html = '<div class="inventory-items-table-wrap">';
    html += '<table class="table inventory-items-table">';
    html += '<thead><tr>';
    html += '<th>ID</th><th>Item</th><th>Category</th><th>Location</th><th>Quantity</th><th>Threshold</th><th>Condition</th><th>Status</th><th>Description</th><th>Updated</th><th>Actions</th>';
    html += '</tr></thead><tbody>';

    items.forEach((item) => {
        const normalizedStatus = normalizeInventoryStatus(item.status);
        const status = normalizedStatus === 'out_of_stock'
            ? 'OUT OF STOCK'
            : (normalizedStatus === 'low_stock' ? 'LOW STOCK' : 'IN STOCK');
        const statusClass = getStatusClass(normalizedStatus);
        const updated = item.updated_at ? new Date(String(item.updated_at).replace(' ', 'T')).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
        const threshold = resolveItemThreshold(item);
        const quantityValue = Number(item.quantity ?? 0);
        const conditionLabel = String(item.item_condition || '').replace(/_/g, ' ');
        const lowStockMarkup = Number(item.low_stock_warning || 0) === 1
            ? '<span class="inventory-inline-chip is-low">Low stock warning</span>'
            : '';

        html += '<tr>';
        html += `<td class="inventory-item-id">#${item.id ?? '-'}</td>`;
        html += `<td class="inventory-item-cell"><div class="inventory-item-name">${inventoryEscapeHtml(item.name || 'Unnamed Item')}</div>${item.brand ? `<div class="inventory-item-meta">Brand: ${inventoryEscapeHtml(item.brand)}</div>` : ''}${item.model ? `<div class="inventory-item-meta">Model: ${inventoryEscapeHtml(item.model)}</div>` : ''}${item.unit_type ? `<div class="inventory-item-meta">Unit: ${inventoryEscapeHtml(item.unit_type)}</div>` : ''}${lowStockMarkup}</td>`;
        html += `<td class="inventory-table-cell-muted">${item.category_name || 'Uncategorized'}</td>`;
        html += `<td class="inventory-table-cell-muted">${item.inventory_room_name || 'Inventory Room'}</td>`;
        html += `<td class="inventory-table-cell-numeric">${quantityValue}</td>`;
        html += `<td class="inventory-table-cell-numeric">${formatThreshold(threshold)}</td>`;
        html += `<td class="inventory-table-cell-muted">${inventoryEscapeHtml(conditionLabel || '-')}</td>`;
        html += `<td><span class="badge inventory-status-badge ${statusClass}">${status}</span></td>`;
        html += `<td class="inventory-table-cell-muted">${inventoryEscapeHtml(item.description || '-')}</td>`;
        html += `<td class="inventory-table-cell-muted">${updated}</td>`;
        html += `<td class="inventory-actions-cell">
            <div class="inventory-table-actions">
                <button type="button" class="btn btn-secondary btn-sm inventory-action-btn is-history" data-action="history" data-item-id="${Number(item.id)}">History</button>
                ${CAN_ADD_ITEMS ? `<button type="button" class="btn btn-secondary btn-sm inventory-action-btn is-edit" data-action="edit" data-item-id="${Number(item.id)}">Edit</button>` : ''}
                ${CAN_ADD_ITEMS ? `<button type="button" class="btn btn-danger btn-sm inventory-action-btn is-delete" data-action="delete" data-item-id="${Number(item.id)}">Delete</button>` : ''}
            </div>
        </td>`;
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
    setText('inventory-count', `${items.length} items`);

    container.querySelectorAll('[data-action="history"]').forEach((button) => {
        button.addEventListener('click', () => openInventoryItemHistory(Number(button.getAttribute('data-item-id') || 0)));
    });
    if (CAN_ADD_ITEMS) {
        container.querySelectorAll('[data-action="edit"]').forEach((button) => {
            button.addEventListener('click', () => startEditInventoryItem(Number(button.getAttribute('data-item-id') || 0)));
        });
        container.querySelectorAll('[data-action="delete"]').forEach((button) => {
            button.addEventListener('click', () => deleteInventoryItem(Number(button.getAttribute('data-item-id') || 0)));
        });
    }
}

async function loadInventory() {
    const container = document.getElementById('inventory-container');
    if (container) {
        container.innerHTML = '<div class="ui-empty-state ui-fade-in"><strong>Loading inventory...</strong><div class="ui-skeleton-list inventory-loading-skeleton"><div class="ui-skeleton-row w-90"></div><div class="ui-skeleton-row w-75"></div><div class="ui-skeleton-row w-55"></div></div></div>';
    }

    try {
        const response = await fetch(INVENTORY_STOCK_API_BASE, {
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
        await loadInventoryEntryInventoryRoomOptions();
    } catch (error) {
        console.error('Inventory bootstrap error (categories / warehouse rooms):', error);
    }

    try {
        await fetchInventoryDepartments();
        await loadInventoryEntryDepartmentOptions();
    } catch (error) {
        console.error('Inventory bootstrap error (departments):', error);
    }

    try {
        await fetchInventoryEntryRooms();
        await loadInventoryEntryRoomOptions();
    } catch (error) {
        console.error('Inventory bootstrap error (laboratory / room):', error);
    }

    await loadInventory();
    await loadInventoryEntryHistory();

    const addButton = document.getElementById('addItemsButton');
    const inventoryEntryButton = document.getElementById('openInventoryEntryButton');
    const addInventoryRoomButton = document.getElementById('addInventoryRoomButton');
    const closeButton = document.getElementById('closeAddItemsModalButton');
    const cancelButton = document.getElementById('cancelAddItemsButton');
    const saveButton = document.getElementById('saveAddItemsButton');
    const inventoryEntryModal = document.getElementById('inventoryEntryModal');
    const closeInventoryEntryModalButton = document.getElementById('closeInventoryEntryModalButton');
    const cancelInventoryEntryButton = document.getElementById('cancelInventoryEntryButton');
    const saveInventoryEntryButton = document.getElementById('saveInventoryEntryButton');
    const openAdjustStockButton = document.getElementById('openAdjustStockButton');
    const closeAdjustStockModalButton = document.getElementById('closeAdjustStockModalButton');
    const cancelAdjustStockButton = document.getElementById('cancelAdjustStockButton');
    const saveAdjustStockButton = document.getElementById('saveAdjustStockButton');
    const adjustStockItemSelect = document.getElementById('adjustStockItemSelect');
    const adjustStockDirectionSelect = document.getElementById('adjustStockDirection');
    const adjustStockQuantityInput = document.getElementById('adjustStockQuantity');
    const addItemsModal = document.getElementById('addItemsModal');
    const inventoryHistoryModal = document.getElementById('inventoryItemHistoryModal');
    const closeInventoryHistoryModalButton = document.getElementById('closeInventoryHistoryModalButton');
    const closeInventoryHistoryFooterButton = document.getElementById('closeInventoryHistoryFooterButton');
    const addInventoryRoomModal = document.getElementById('addInventoryRoomModal');
    const closeAddInventoryRoomButton = document.getElementById('closeAddInventoryRoomModalButton');
    const cancelAddInventoryRoomButton = document.getElementById('cancelAddInventoryRoomButton');
    const saveInventoryRoomButton = document.getElementById('saveInventoryRoomButton');
    const categorySelect = document.getElementById('inventoryCategorySelect');
    const inventorySearchInput = document.getElementById('inventory-category-search');
    const inventoryEntrySearchInput = document.getElementById('inventory-entry-search');
    const inventoryEntryFilterCategory = document.getElementById('inventory-entry-filter-category');
    const inventoryEntryFilterDepartment = document.getElementById('inventory-entry-filter-department');
    const inventoryEntryFilterRoom = document.getElementById('inventory-entry-filter-room');
    const inventoryEntryFilterDateFrom = document.getElementById('inventory-entry-filter-date-from');
    const inventoryEntryFilterDateTo = document.getElementById('inventory-entry-filter-date-to');
    const inventoryEntryClearFiltersButton = document.getElementById('inventory-entry-clear-filters');
    const inventoryItemsStatusFilterInput = document.getElementById('inventory-items-status-filter');
    const inventoryItemsConditionFilterInput = document.getElementById('inventory-items-condition-filter');
    const inventoryItemsClearFiltersButton = document.getElementById('inventory-items-clear-filters');
    const inventoryQuantityInput = document.getElementById('inventoryQuantityInput');
    const inventoryThresholdInput = document.getElementById('inventoryThresholdOverrideInput');
    const categorySearchInput = document.getElementById('categorySearchInput');
    const categoryStatusFilter = document.getElementById('categoryStatusFilter');

    if (addButton) addButton.addEventListener('click', openAddItemsModal);
    // 'Inventory Entry' modal POSTs to a deprecated backend endpoint
    // (InventoryStockController::createEntry() returns 410 and is not even
    // routed for POST — see routes/web.php). Stock intake is now handled
    // exclusively via Purchase Receipts, so the button navigates there
    // instead of opening the non-functional modal.
    if (inventoryEntryButton) inventoryEntryButton.addEventListener('click', () => {
        window.location.href = window.SFMS_PUBLIC_URL('/purchase-receipts');
    });
    if (addInventoryRoomButton) addInventoryRoomButton.addEventListener('click', openAddInventoryRoomModal);
    if (closeButton) closeButton.addEventListener('click', closeAddItemsModal);
    if (cancelButton) cancelButton.addEventListener('click', closeAddItemsModal);
    if (saveButton) saveButton.addEventListener('click', saveAddItems);
    if (closeInventoryHistoryModalButton) closeInventoryHistoryModalButton.addEventListener('click', closeInventoryHistoryModal);
    if (closeInventoryHistoryFooterButton) closeInventoryHistoryFooterButton.addEventListener('click', closeInventoryHistoryModal);
    if (closeInventoryEntryModalButton) closeInventoryEntryModalButton.addEventListener('click', closeInventoryEntryModal);
    if (cancelInventoryEntryButton) cancelInventoryEntryButton.addEventListener('click', closeInventoryEntryModal);
    if (saveInventoryEntryButton) saveInventoryEntryButton.addEventListener('click', saveInventoryEntry);
    if (openAdjustStockButton) openAdjustStockButton.addEventListener('click', openAdjustStockModal);
    if (closeAdjustStockModalButton) closeAdjustStockModalButton.addEventListener('click', closeAdjustStockModal);
    if (cancelAdjustStockButton) cancelAdjustStockButton.addEventListener('click', closeAdjustStockModal);
    if (saveAdjustStockButton) saveAdjustStockButton.addEventListener('click', saveAdjustStock);
    if (adjustStockItemSelect) adjustStockItemSelect.addEventListener('change', updateAdjustStockReview);
    if (adjustStockDirectionSelect) adjustStockDirectionSelect.addEventListener('change', updateAdjustStockReview);
    if (adjustStockQuantityInput) adjustStockQuantityInput.addEventListener('input', updateAdjustStockReview);
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
    if (inventoryItemsStatusFilterInput) {
        inventoryItemsStatusFilterInput.addEventListener('change', () => {
            inventoryItemsStatusFilter = String(inventoryItemsStatusFilterInput.value || '').toLowerCase();
            renderInventoryOverview();
        });
    }
    if (inventoryItemsConditionFilterInput) {
        inventoryItemsConditionFilterInput.addEventListener('change', () => {
            inventoryItemsConditionFilter = String(inventoryItemsConditionFilterInput.value || '').toLowerCase();
            renderInventoryOverview();
        });
    }
    if (inventoryItemsClearFiltersButton) {
        inventoryItemsClearFiltersButton.addEventListener('click', () => {
            inventoryItemsStatusFilter = '';
            inventoryItemsConditionFilter = '';
            if (inventoryItemsStatusFilterInput) inventoryItemsStatusFilterInput.value = '';
            if (inventoryItemsConditionFilterInput) inventoryItemsConditionFilterInput.value = '';
            renderInventoryOverview();
        });
    }
    if (inventoryEntrySearchInput) inventoryEntrySearchInput.addEventListener('input', loadInventoryEntryHistory);
    if (inventoryEntryFilterCategory) inventoryEntryFilterCategory.addEventListener('change', loadInventoryEntryHistory);
    if (inventoryEntryFilterDepartment) inventoryEntryFilterDepartment.addEventListener('change', loadInventoryEntryHistory);
    if (inventoryEntryFilterRoom) inventoryEntryFilterRoom.addEventListener('change', loadInventoryEntryHistory);
    if (inventoryEntryFilterDateFrom) inventoryEntryFilterDateFrom.addEventListener('change', loadInventoryEntryHistory);
    if (inventoryEntryFilterDateTo) inventoryEntryFilterDateTo.addEventListener('change', loadInventoryEntryHistory);
    if (inventoryEntryClearFiltersButton) {
        inventoryEntryClearFiltersButton.addEventListener('click', () => {
            if (inventoryEntrySearchInput) inventoryEntrySearchInput.value = '';
            if (inventoryEntryFilterCategory) inventoryEntryFilterCategory.value = '';
            if (inventoryEntryFilterDepartment) inventoryEntryFilterDepartment.value = '';
            if (inventoryEntryFilterRoom) inventoryEntryFilterRoom.value = '';
            if (inventoryEntryFilterDateFrom) inventoryEntryFilterDateFrom.value = '';
            if (inventoryEntryFilterDateTo) inventoryEntryFilterDateTo.value = '';
            loadInventoryEntryHistory();
        });
    }
    if (categorySearchInput) {
        categorySearchInput.addEventListener('input', () => {
            categoryAdminSearchTerm = categorySearchInput.value.trim().toLowerCase();
            renderCategoriesList();
        });
    }
    if (categoryStatusFilter) {
        categoryStatusFilter.addEventListener('change', () => {
            categoryAdminStatusFilter = String(categoryStatusFilter.value || 'all').toLowerCase();
            renderCategoriesList();
        });
    }

    if (addItemsModal) {
        addItemsModal.addEventListener('click', (event) => {
            if (event.target === addItemsModal) {
                closeAddItemsModal();
            }
        });
    }

    if (inventoryHistoryModal) {
        inventoryHistoryModal.addEventListener('click', (event) => {
            if (event.target === inventoryHistoryModal) {
                closeInventoryHistoryModal();
            }
        });
    }

    if (inventoryEntryModal) {
        inventoryEntryModal.addEventListener('click', (event) => {
            if (event.target === inventoryEntryModal) {
                closeInventoryEntryModal();
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

    if (inventoryQuantityInput) {
        inventoryQuantityInput.addEventListener('input', updateInventoryStatusPreview);
    }
    if (inventoryThresholdInput) {
        inventoryThresholdInput.addEventListener('input', updateInventoryStatusPreview);
    }
});
</script>

</body>
</html>
