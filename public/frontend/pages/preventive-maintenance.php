<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

if (!isset($_SESSION['user']) && !isset($_SESSION['auth_user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$_pmUser = $_SESSION['user'] ?? $_SESSION['auth_user'] ?? [];
$_pmRole = strtolower(trim((string)($_pmUser['role'] ?? '')));
$_pmUserId = (int)($_pmUser['user_id'] ?? 0);

// PM RBAC — confirmed plan: create/archive/activate are Administrator /
// Head Maintenance only. Edit/Complete are allowed for all three roles at
// the route/middleware layer, but the backend's canManageTask() additionally
// restricts Maintenance Staff to only their own assigned tasks — the same
// restriction is mirrored client-side below (pmCanManageTask()) purely to
// decide which buttons to render; the API re-checks regardless, so this is
// presentation only, never the actual authorization boundary.
$_pmCanCreate = in_array($_pmRole, ['super_admin', 'maintenance_admin'], true);
$_pmCanArchive = in_array($_pmRole, ['super_admin', 'maintenance_admin'], true);

$pageTitle = 'Preventive Maintenance - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<main class="container pm-page" style="margin-top:16px;">
    <section class="pm-page__header">
        <div class="pm-page__title-group">
            <p class="pm-page__eyebrow">Facility Maintenance</p>
            <h1 class="pm-page__title">Preventive Maintenance</h1>
            <p class="pm-page__description">Digital Preventive Maintenance Management Plan — annual schedule and monthly checklist for critical equipment and facilities.</p>
        </div>
        <div class="pm-page__actions">
            <button type="button" class="btn pm-page__secondary-action" id="pm-print-btn">Print Schedule</button>
            <?php if ($_pmCanCreate): ?>
            <button type="button" class="btn pm-page__primary-action" id="pm-new-task-btn">+ New PM Task</button>
            <?php endif; ?>
        </div>
    </section>

    <section class="pm-summary-cards" id="pm-summary-cards">
        <div class="pm-summary-card pm-summary-card--loading">
            <div class="ui-skeleton-row w-55"></div>
        </div>
    </section>

    <section class="pm-panel">
        <div class="pm-tabs" role="tablist">
            <button type="button" class="pm-tab pm-tab--active" id="pm-tab-overview" role="tab" aria-selected="true">Overview</button>
            <button type="button" class="pm-tab" id="pm-tab-schedule" role="tab" aria-selected="false">Annual Schedule</button>
            <button type="button" class="pm-tab" id="pm-tab-checklist" role="tab" aria-selected="false">Monthly Checklist</button>
        </div>

        <div class="pm-toolbar" id="pm-toolbar" style="display:none;">
            <div class="pm-toolbar__search">
                <label class="pm-toolbar__label" for="pm-filter-search">Search</label>
                <input type="search" id="pm-filter-search" class="form-control pm-toolbar__control" placeholder="Search by activity or equipment...">
            </div>
            <div class="pm-toolbar__filter">
                <label class="pm-toolbar__label" for="pm-filter-category">Equipment</label>
                <select id="pm-filter-category" class="form-control pm-toolbar__control">
                    <option value="">All Equipment</option>
                </select>
            </div>
            <div class="pm-toolbar__filter">
                <label class="pm-toolbar__label" for="pm-filter-location">Location</label>
                <input type="text" id="pm-filter-location" class="form-control pm-toolbar__control" placeholder="e.g. Gymnasium">
            </div>
            <div class="pm-toolbar__filter">
                <label class="pm-toolbar__label" for="pm-filter-frequency">Frequency</label>
                <select id="pm-filter-frequency" class="form-control pm-toolbar__control">
                    <option value="">All Frequencies</option>
                </select>
            </div>
            <div class="pm-toolbar__filter">
                <label class="pm-toolbar__label" for="pm-filter-assignee-search">Assigned Personnel</label>
                <input type="text" id="pm-filter-assignee-search" class="form-control pm-toolbar__control" placeholder="Search personnel...">
                <input type="hidden" id="pm-filter-assignee-id">
            </div>
            <div class="pm-toolbar__filter">
                <label class="pm-toolbar__label" for="pm-filter-status">Status</label>
                <select id="pm-filter-status" class="form-control pm-toolbar__control"></select>
            </div>
            <div class="pm-toolbar__filter">
                <label class="pm-toolbar__label" for="pm-filter-month">Month</label>
                <select id="pm-filter-month" class="form-control pm-toolbar__control">
                    <option value="">All Months</option>
                </select>
            </div>
            <div class="pm-toolbar__filter" id="pm-filter-year-wrap" style="display:none;">
                <label class="pm-toolbar__label" for="pm-filter-year">Year</label>
                <div class="pm-year-nav">
                    <button type="button" class="btn pm-page__ghost-action" id="pm-year-prev">&lsaquo;</button>
                    <input type="number" id="pm-filter-year" class="form-control pm-toolbar__control" style="width:90px;">
                    <button type="button" class="btn pm-page__ghost-action" id="pm-year-next">&rsaquo;</button>
                </div>
            </div>
            <div class="pm-toolbar__filter" id="pm-filter-active-wrap">
                <label class="pm-toolbar__label" for="pm-filter-active">Show</label>
                <select id="pm-filter-active" class="form-control pm-toolbar__control">
                    <option value="1">Active</option>
                    <option value="0">Archived</option>
                    <option value="all">All</option>
                </select>
            </div>
            <div class="pm-toolbar__action">
                <button type="button" id="pm-filter-apply" class="btn pm-page__primary-action">Apply</button>
                <button type="button" id="pm-filter-clear" class="btn pm-page__ghost-action">Clear</button>
            </div>
        </div>

        <!-- Overview panel: calendar + Due This Week + Maintenance Plans list -->
        <div class="pm-table-panel" id="pm-overview-panel">
            <div class="pm-overview-grid">
                <div class="pm-overview-card pm-overview-calendar">
                    <div class="pm-overview-card__header">
                        <div>
                            <h2 class="pm-table-panel__title">Monthly Preventive Maintenance Calendar</h2>
                            <p class="pm-table-panel__description">The confirmed Jan&ndash;Dec schedule from the Preventive Maintenance Management Plan &mdash; nothing here is invented.</p>
                        </div>
                        <div class="pm-overview-calendar__nav">
                            <button type="button" class="btn pm-page__ghost-action" id="pm-overview-prev" aria-label="Previous month">&lsaquo;</button>
                            <span id="pm-overview-period-label" class="pm-overview-period-label"></span>
                            <button type="button" class="btn pm-page__ghost-action" id="pm-overview-next" aria-label="Next month">&rsaquo;</button>
                        </div>
                    </div>
                    <div class="pm-calendar-legend">
                        <span class="pm-calendar-legend__item"><span class="pm-cal-dot pm-cal-dot--overdue"></span>Overdue</span>
                        <span class="pm-calendar-legend__item"><span class="pm-cal-dot pm-cal-dot--due"></span>Due</span>
                        <span class="pm-calendar-legend__item"><span class="pm-cal-dot pm-cal-dot--due-soon"></span>Due Soon</span>
                        <span class="pm-calendar-legend__item"><span class="pm-cal-dot pm-cal-dot--upcoming"></span>Scheduled</span>
                    </div>
                    <div id="pm-overview-calendar-grid" class="pm-calendar-grid"></div>
                    <div class="pm-overview-card__subheader">
                        <h3>Scheduled This Month</h3>
                    </div>
                    <div id="pm-overview-month-list" class="pm-cal-activity-list"></div>
                </div>
                <div class="pm-overview-card pm-overview-dueweek">
                    <div class="pm-overview-card__header">
                        <div>
                            <h2 class="pm-table-panel__title">Due This Week</h2>
                            <p class="pm-table-panel__description">Overdue and upcoming activities from the existing schedule and status logic.</p>
                        </div>
                    </div>
                    <div id="pm-overview-dueweek-list" class="pm-dueweek-list">
                        <div class="ui-skeleton-list">
                            <div class="ui-skeleton-row w-90"></div>
                            <div class="ui-skeleton-row w-75"></div>
                            <div class="ui-skeleton-row w-55"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pm-overview-card pm-overview-plans">
                <div class="pm-overview-card__header">
                    <div>
                        <h2 class="pm-table-panel__title">Maintenance Plans</h2>
                        <p class="pm-table-panel__description">Every existing PM plan &mdash; equipment, location, frequency, and current status.</p>
                    </div>
                </div>
                <div id="pm-overview-plans-container" class="pm-table-shell pm-table-shell--scroll">
                    <div class="ui-empty-state ui-fade-in" aria-live="polite">
                        <strong>Loading maintenance plans...</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Annual Schedule panel -->
        <div class="pm-table-panel" id="pm-schedule-panel" style="display:none;">
            <div class="pm-table-panel__header">
                <div>
                    <h2 class="pm-table-panel__title">Annual Preventive Maintenance Schedule</h2>
                    <p class="pm-table-panel__description" id="pm-due-soon-note">A digital reproduction of the Preventive Maintenance Management Plan's Jan&ndash;Dec schedule grid.</p>
                </div>
            </div>
            <div id="pm-schedule-container" class="pm-table-shell pm-table-shell--scroll">
                <div class="ui-empty-state ui-fade-in" aria-live="polite">
                    <strong>Loading annual schedule...</strong>
                    <div class="ui-skeleton-list" style="margin-top: 12px;">
                        <div class="ui-skeleton-row w-90"></div>
                        <div class="ui-skeleton-row w-75"></div>
                        <div class="ui-skeleton-row w-55"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Checklist panel -->
        <div class="pm-table-panel" id="pm-checklist-panel" style="display:none;">
            <div class="pm-table-panel__header">
                <div>
                    <h2 class="pm-table-panel__title">Monthly Checklist</h2>
                    <p class="pm-table-panel__description" id="pm-checklist-period-note">Completion checklist for the selected month.</p>
                </div>
            </div>
            <div id="pm-checklist-container" class="pm-table-shell">
                <div class="ui-empty-state ui-fade-in" aria-live="polite">
                    <strong>Loading monthly checklist...</strong>
                    <div class="ui-skeleton-list" style="margin-top: 12px;">
                        <div class="ui-skeleton-row w-90"></div>
                        <div class="ui-skeleton-row w-75"></div>
                        <div class="ui-skeleton-row w-55"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Create / Edit modal -->
    <div id="pm-form-modal" class="modal" style="display:none;" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="pm-form-modal-title">
        <div class="modal-content pm-form-modal">
            <div class="modal-header">
                <h3 class="modal-title" id="pm-form-modal-title">New Preventive Maintenance Task</h3>
                <button type="button" class="modal-close" id="pm-form-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="pm-task-form">
                    <input type="hidden" id="pm-form-task-id" value="">

                    <div class="pm-form-grid">
                        <div class="form-group">
                            <label for="pm-form-category">Equipment Nomenclature *</label>
                            <select id="pm-form-category" class="form-control" required>
                                <option value="">Select category</option>
                            </select>
                        </div>
                        <div class="form-group" id="pm-form-category-other-wrap" style="display:none;">
                            <label for="pm-form-category-other">Other Category *</label>
                            <input type="text" id="pm-form-category-other" class="form-control" maxlength="100" placeholder="Specify category">
                        </div>
                        <div class="form-group">
                            <label for="pm-form-title">Activity / Title *</label>
                            <input type="text" id="pm-form-title" class="form-control" maxlength="255" placeholder="e.g. ACU Filter Cleaning" required>
                        </div>
                        <div class="form-group">
                            <label for="pm-form-frequency">Frequency *</label>
                            <select id="pm-form-frequency" class="form-control" required>
                                <option value="">Select frequency</option>
                            </select>
                        </div>
                    </div>

                    <p class="pm-form-section-label">Location</p>
                    <div class="pm-form-grid">
                        <div class="form-group">
                            <label for="pm-form-location-name">Location Name (free text)</label>
                            <input type="text" id="pm-form-location-name" class="form-control" maxlength="255" placeholder="e.g. Gymnasium, Boiler Room">
                            <small class="text-muted">For manual-plan locations not tied to a specific room. Used when no Building/Room below is selected.</small>
                        </div>
                    </div>
                    <div class="pm-form-grid">
                        <div class="form-group">
                            <label for="pm-form-building">Building</label>
                            <select id="pm-form-building" class="form-control">
                                <option value="">Select building</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="pm-form-floor">Floor</label>
                            <select id="pm-form-floor" class="form-control" disabled>
                                <option value="">Select building first</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="pm-form-room">Room / Area</label>
                            <select id="pm-form-room" class="form-control" disabled>
                                <option value="">Select floor first</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="pm-form-item">Equipment / Asset</label>
                            <select id="pm-form-item" class="form-control">
                                <option value="">None / general category</option>
                            </select>
                        </div>
                    </div>

                    <p class="pm-form-section-label">Schedule</p>
                    <div class="pm-form-grid">
                        <div class="form-group">
                            <label for="pm-form-last-completed">Last Completed Date</label>
                            <input type="date" id="pm-form-last-completed" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="pm-form-next-due">Next Due Date</label>
                            <input type="date" id="pm-form-next-due" class="form-control">
                            <small class="text-muted">Leave blank to auto-calculate from the scheduled months, or from Last Completed + Frequency.</small>
                        </div>
                        <div class="form-group">
                            <label for="pm-form-department">Responsible Department</label>
                            <select id="pm-form-department" class="form-control">
                                <option value="">Unassigned</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="pm-form-assignee-search">Assigned Personnel</label>
                            <input type="text" id="pm-form-assignee-search" class="form-control" placeholder="Search maintenance personnel...">
                            <input type="hidden" id="pm-form-assignee-id">
                        </div>
                    </div>

                    <p class="pm-form-section-label">Scheduled Months (X-mark grid)</p>
                    <div class="pm-month-picker" id="pm-form-months">
                        <label class="pm-month-picker__item"><input type="checkbox" value="1"><span>Jan</span></label>
                        <label class="pm-month-picker__item"><input type="checkbox" value="2"><span>Feb</span></label>
                        <label class="pm-month-picker__item"><input type="checkbox" value="3"><span>Mar</span></label>
                        <label class="pm-month-picker__item"><input type="checkbox" value="4"><span>Apr</span></label>
                        <label class="pm-month-picker__item"><input type="checkbox" value="5"><span>May</span></label>
                        <label class="pm-month-picker__item"><input type="checkbox" value="6"><span>Jun</span></label>
                        <label class="pm-month-picker__item"><input type="checkbox" value="7"><span>Jul</span></label>
                        <label class="pm-month-picker__item"><input type="checkbox" value="8"><span>Aug</span></label>
                        <label class="pm-month-picker__item"><input type="checkbox" value="9"><span>Sep</span></label>
                        <label class="pm-month-picker__item"><input type="checkbox" value="10"><span>Oct</span></label>
                        <label class="pm-month-picker__item"><input type="checkbox" value="11"><span>Nov</span></label>
                        <label class="pm-month-picker__item"><input type="checkbox" value="12"><span>Dec</span></label>
                    </div>
                    <small class="text-muted">Check the months this item is scheduled for per the Preventive Maintenance Management Plan. Leave all unchecked to fall back to Last Completed + Frequency for the next due date.</small>

                    <div class="form-group" style="margin-top:16px;">
                        <label for="pm-form-notes">Notes</label>
                        <textarea id="pm-form-notes" class="form-control" rows="3" placeholder="Optional notes about this PM plan..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="pm-form-cancel">Cancel</button>
                <button type="button" class="btn btn-primary" id="pm-form-save">Save Task</button>
            </div>
        </div>
    </div>

    <!-- Mark Completed modal -->
    <div id="pm-complete-modal" class="modal" style="display:none;" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="pm-complete-modal-title">
        <div class="modal-content pm-complete-modal">
            <div class="modal-header">
                <h3 class="modal-title" id="pm-complete-modal-title">Mark as Completed</h3>
                <button type="button" class="modal-close" id="pm-complete-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="pm-complete-form">
                    <input type="hidden" id="pm-complete-task-id" value="">
                    <div class="form-group">
                        <label for="pm-complete-date">Completed Date *</label>
                        <input type="date" id="pm-complete-date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="pm-complete-performed-search">Performed By</label>
                        <input type="text" id="pm-complete-performed-search" class="form-control" placeholder="Search maintenance personnel...">
                        <input type="hidden" id="pm-complete-performed-id">
                    </div>
                    <div class="form-group">
                        <label for="pm-complete-notes">Remarks</label>
                        <textarea id="pm-complete-notes" class="form-control" rows="2" placeholder="Optional remarks..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="pm-complete-findings">Findings</label>
                        <textarea id="pm-complete-findings" class="form-control" rows="2" placeholder="Optional findings / observations..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="pm-complete-action-taken">Action Taken</label>
                        <textarea id="pm-complete-action-taken" class="form-control" rows="2" placeholder="What was done to address the findings..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="pm-complete-proof">Completion Proof (photo)</label>
                        <input type="file" id="pm-complete-proof" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                        <small class="text-muted">Allowed: JPG, PNG, WEBP, GIF (max 5MB)</small>
                        <div id="pm-complete-proof-preview" style="margin-top:10px;"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="pm-complete-cancel">Cancel</button>
                <button type="button" class="btn btn-primary" id="pm-complete-save">Save Completion</button>
            </div>
        </div>
    </div>

    <!-- View Details + History modal -->
    <div id="pm-history-modal" class="modal" style="display:none;" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="pm-history-modal-title">
        <div class="modal-content pm-history-modal">
            <div class="modal-header">
                <h3 class="modal-title" id="pm-history-modal-title">Task Details</h3>
                <button type="button" class="modal-close" id="pm-history-modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="pm-history-task-summary" class="pm-history-task-summary"></div>
                <h4 class="pm-history-section-title">Completion History</h4>
                <div id="pm-history-list-container">
                    <div class="ui-skeleton-list">
                        <div class="ui-skeleton-row w-90"></div>
                        <div class="ui-skeleton-row w-75"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="pm-history-modal-dismiss">Close</button>
            </div>
        </div>
    </div>

    <div id="pm-print-container" class="pm-print-only"></div>
</main>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/preventive-maintenance.inline.css?v=20260923-1">

<script>
const PM_CURRENT_USER = {
    userId: <?php echo (int) $_pmUserId; ?>,
    role: '<?php echo htmlspecialchars($_pmRole, ENT_QUOTES); ?>',
};
const PM_CAN_CREATE = <?php echo $_pmCanCreate ? 'true' : 'false'; ?>;
const PM_CAN_ARCHIVE = <?php echo $_pmCanArchive ? 'true' : 'false'; ?>;

const PM_API_BASE = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/preventive-maintenance') : '/api/preventive-maintenance';

const PM_MONTH_NAMES = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

const PM_SCHEDULE_STATUS_OPTIONS = [
    ['', 'All Statuses'],
    ['overdue', 'Overdue'],
    ['due', 'Due'],
    ['due_soon', 'Due Soon'],
    ['upcoming', 'Upcoming'],
    ['unscheduled', 'Unscheduled'],
];

const PM_CHECKLIST_STATUS_OPTIONS = [
    ['', 'All Statuses'],
    ['overdue', 'Overdue'],
    ['due', 'Due'],
    ['due_soon', 'Due Soon'],
    ['pending', 'Pending'],
    ['completed', 'Completed'],
];

const PM_STATUS_LABELS = {
    unscheduled: 'Unscheduled', upcoming: 'Upcoming', due_soon: 'Due Soon',
    due: 'Due', overdue: 'Overdue', completed: 'Completed', pending: 'Pending',
};

let pmActiveTab = 'overview';
let pmOptions = { categories: [], frequencies: {}, due_soon_days: 14 };
let pmScheduleRows = [];
let pmScheduleRowsById = {};
let pmChecklistItems = [];
let pmChecklistItemsById = {};
let pmChecklistYear = new Date().getFullYear();
let pmChecklistMonth = new Date().getMonth() + 1;

function pmEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function pmNotify(message, type = 'danger') {
    if (window.Components && typeof Components.alert === 'function') {
        Components.alert(message, type);
        return;
    }
    const _div = document.createElement('div');
    _div.style.cssText = 'position:fixed;top:80px;right:20px;z-index:9999;background:#dc2626;color:white;padding:12px 20px;border-radius:8px;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,0.3);max-width:350px;';
    _div.textContent = message;
    document.body.appendChild(_div);
    setTimeout(() => _div.remove(), 4000);
}

function pmMutedDash() {
    return '<span class="pm-muted-cell">&mdash;</span>';
}

function pmFormatDate(value) {
    if (!value) return null;
    try {
        const parsed = new Date(value + (String(value).length <= 10 ? 'T00:00:00' : ''));
        if (isNaN(parsed.getTime())) return String(value);
        return parsed.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    } catch (error) {
        return String(value);
    }
}

function pmDaysHint(days) {
    if (days === null || days === undefined) return '';
    const n = Number(days);
    if (n < 0) return `${Math.abs(n)} day${Math.abs(n) === 1 ? '' : 's'} overdue`;
    if (n === 0) return 'Due today';
    return `in ${n} day${n === 1 ? '' : 's'}`;
}

// Same parsing rule as pmFormatDate() above — kept as a separate function
// (rather than having pmFormatDate return the Date) so every new call site
// that needs the actual Date object (Overview calendar/Due This Week) stays
// byte-for-byte consistent with how dates are already displayed elsewhere,
// instead of introducing a second, possibly-diverging parser.
function pmParseDate(value) {
    if (!value) return null;
    const parsed = new Date(String(value) + (String(value).length <= 10 ? 'T00:00:00' : ''));
    return isNaN(parsed.getTime()) ? null : parsed;
}

// Whole calendar days between today and a parsed Date — pure date arithmetic
// on the existing next_due_date, not a new status system. The authoritative
// status/status_label always still come from the server (row.status).
function pmDaysUntilFromDate(date) {
    if (!date) return null;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    return Math.round((d - today) / 86400000);
}

// Mirrors PreventiveMaintenanceService::canManageTask() for presentation
// only — the API is the real authorization boundary and re-checks this on
// every update()/complete() call.
function pmCanManageTask(row) {
    if (PM_CURRENT_USER.role === 'super_admin' || PM_CURRENT_USER.role === 'maintenance_admin') return true;
    if (PM_CURRENT_USER.role === 'maintenance_staff') {
        return Number(row.assigned_user_id) === Number(PM_CURRENT_USER.userId);
    }
    return false;
}

function pmStatusBadge(status, label) {
    const key = String(status || 'unscheduled').replace(/_/g, '-');
    const text = label || PM_STATUS_LABELS[status] || status || 'Unknown';
    return `<span class="badge pm-badge-${pmEscapeHtml(key)} pm-status-badge">${pmEscapeHtml(text)}</span>`;
}

function pmGetLoadingMarkup(label) {
    return `
        <div class="ui-empty-state ui-fade-in" aria-live="polite">
            <strong>${pmEscapeHtml(label || 'Loading...')}</strong>
            <div class="ui-skeleton-list" style="margin-top: 12px;">
                <div class="ui-skeleton-row w-90"></div>
                <div class="ui-skeleton-row w-75"></div>
                <div class="ui-skeleton-row w-55"></div>
            </div>
        </div>
    `;
}

// ---------------------------------------------------------------------
// Options / summary
// ---------------------------------------------------------------------

function pmPopulateStatusFilter() {
    const select = document.getElementById('pm-filter-status');
    const opts = pmActiveTab === 'checklist' ? PM_CHECKLIST_STATUS_OPTIONS : PM_SCHEDULE_STATUS_OPTIONS;
    const current = select.value;
    select.innerHTML = opts.map(([value, label]) => `<option value="${value}">${pmEscapeHtml(label)}</option>`).join('');
    if (opts.some(([value]) => value === current)) select.value = current;
}

async function pmLoadOptions() {
    try {
        const { response, data: payload } = await Components.fetchJson(`${PM_API_BASE}/support/options`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed to load options');

        pmOptions = payload.data || pmOptions;

        const categoryOptionsHtml = (pmOptions.categories || []).map((c) => `<option value="${pmEscapeHtml(c)}">${pmEscapeHtml(c)}</option>`).join('');
        document.getElementById('pm-form-category').innerHTML = '<option value="">Select category</option>' + categoryOptionsHtml;
        document.getElementById('pm-filter-category').innerHTML = '<option value="">All Equipment</option>' + categoryOptionsHtml;

        const freqEntries = Object.entries(pmOptions.frequencies || {});
        const freqOptionsHtml = freqEntries.map(([key, label]) => `<option value="${pmEscapeHtml(key)}">${pmEscapeHtml(label)}</option>`).join('');
        document.getElementById('pm-form-frequency').innerHTML = '<option value="">Select frequency</option>' + freqOptionsHtml;
        document.getElementById('pm-filter-frequency').innerHTML = '<option value="">All Frequencies</option>' + freqOptionsHtml;

        const monthOptionsHtml = PM_MONTH_NAMES.map((name, idx) => `<option value="${idx + 1}">${name}</option>`).join('');
        document.getElementById('pm-filter-month').innerHTML = '<option value="">All Months</option>' + monthOptionsHtml;

        const dueSoonDays = Number(pmOptions.due_soon_days || 14);
        const note = document.getElementById('pm-due-soon-note');
        if (note) note.textContent = `A digital reproduction of the Preventive Maintenance Management Plan's Jan–Dec schedule grid. "Due Soon" means within ${dueSoonDays} day${dueSoonDays === 1 ? '' : 's'}.`;
    } catch (error) {
        pmNotify(error.message || 'Unable to load PM options.');
    }
}

async function pmLoadDepartmentsFilter() {
    try {
        const { response, data: payload } = await Components.fetchJson(`${window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/departments') : '/api/departments'}?per_page=200`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok || !payload.success) return;
        const departments = payload.data?.departments || [];
        const formSelect = document.getElementById('pm-form-department');
        formSelect.innerHTML = '<option value="">Unassigned</option>' + departments.map((d) => `<option value="${d.department_id}">${pmEscapeHtml(d.name)}</option>`).join('');
    } catch (error) {
        // Non-fatal.
    }
}

async function pmLoadBuildingsForm() {
    try {
        const { response, data: payload } = await Components.fetchJson(`${window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/buildings') : '/api/buildings'}?per_page=200`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok || !payload.success) return;
        const buildings = payload.data?.buildings || [];
        const formSelect = document.getElementById('pm-form-building');
        formSelect.innerHTML = '<option value="">Select building</option>' + buildings.map((b) => `<option value="${b.id}">${pmEscapeHtml(b.name)}</option>`).join('');
    } catch (error) {
        // Non-fatal.
    }
}

async function pmLoadSummary() {
    const container = document.getElementById('pm-summary-cards');
    try {
        const { response, data: payload } = await Components.fetchJson(`${PM_API_BASE}/summary`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed to load summary');

        const s = payload.data || {};
        const cards = [
            { label: 'Active Plans', value: s.active_plans, tone: 'active' },
            { label: 'Due Soon', value: s.due_soon, tone: 'due-soon' },
            { label: 'Due', value: s.due, tone: 'due' },
            { label: 'Overdue', value: s.overdue, tone: 'overdue' },
            { label: 'Completed This Month', value: s.completed_this_month, tone: 'completed' },
        ];

        container.innerHTML = cards.map((c) => `
            <div class="pm-summary-card pm-summary-card--${c.tone}">
                <div class="pm-summary-card__value">${pmEscapeHtml(c.value ?? 0)}</div>
                <div class="pm-summary-card__label">${pmEscapeHtml(c.label)}</div>
            </div>
        `).join('');
    } catch (error) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load summary.</strong></div>';
    }
}

// ---------------------------------------------------------------------
// Shared filter collection
// ---------------------------------------------------------------------

function pmCollectFilters() {
    return {
        search: document.getElementById('pm-filter-search').value.trim(),
        category: document.getElementById('pm-filter-category').value,
        location: document.getElementById('pm-filter-location').value.trim(),
        frequency: document.getElementById('pm-filter-frequency').value,
        assignedUserId: document.getElementById('pm-filter-assignee-id').value,
        status: document.getElementById('pm-filter-status').value,
        month: document.getElementById('pm-filter-month').value,
        activeFilter: document.getElementById('pm-filter-active').value,
    };
}

function pmReloadActiveTab() {
    if (pmActiveTab === 'checklist') pmLoadChecklist();
    else pmLoadScheduleGrid();
}

// Resets every toolbar control to its default (unfiltered) value. Shared by
// the existing "Clear" button and by switching into the Overview tab, whose
// calendar/Due This Week/Maintenance Plans list are meant to always reflect
// the full active dataset rather than silently inheriting a filter left set
// on the Annual Schedule tab.
function pmResetToolbarFilters() {
    document.getElementById('pm-filter-search').value = '';
    document.getElementById('pm-filter-category').value = '';
    document.getElementById('pm-filter-location').value = '';
    document.getElementById('pm-filter-frequency').value = '';
    document.getElementById('pm-filter-assignee-search').value = '';
    document.getElementById('pm-filter-assignee-id').value = '';
    document.getElementById('pm-filter-status').value = '';
    document.getElementById('pm-filter-month').value = '';
    document.getElementById('pm-filter-active').value = '1';
}

// ---------------------------------------------------------------------
// Overview — calendar + Due This Week + Maintenance Plans list
//
// Deliberately reuses the exact same pmScheduleRows fetched by
// pmLoadScheduleGrid() (the /schedule-grid endpoint) — no second network
// call, no new endpoint, no new scheduling logic. row.months[m] is the
// server's own scopeByMonth()-driven X-mark data (scheduled_months only,
// the same field the Annual Schedule table renders as X marks), and
// row.status/row.status_label/row.next_due_date are the server's own
// computed values. Nothing here invents a schedule or a status.
// ---------------------------------------------------------------------

function pmOverviewTasksForMonth(month) {
    return pmScheduleRows.filter((r) => !!(r.months && r.months[month]));
}

function pmRenderCalendar() {
    const grid = document.getElementById('pm-overview-calendar-grid');
    const label = document.getElementById('pm-overview-period-label');
    const listEl = document.getElementById('pm-overview-month-list');
    if (!grid || !label) return;

    const year = pmChecklistYear;
    const month = pmChecklistMonth; // 1-12
    label.textContent = `${PM_MONTH_NAMES[month - 1]} ${year}`;

    const firstOfMonth = new Date(year, month - 1, 1);
    const daysInMonth = new Date(year, month, 0).getDate();
    const startWeekday = firstOfMonth.getDay(); // 0 = Sun

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Day-level dots only for tasks whose actual next_due_date falls in the
    // displayed month/year — that is the only day-of-month figure the data
    // actually contains. The manual's month-only X-marks (SA/Q/Monthly) are
    // shown as the "Scheduled This Month" list below instead of being
    // assigned to an invented day.
    const dayMap = {};
    pmScheduleRows.forEach((row) => {
        const d = pmParseDate(row.next_due_date);
        if (d && d.getFullYear() === year && (d.getMonth() + 1) === month) {
            const day = d.getDate();
            (dayMap[day] = dayMap[day] || []).push(row);
        }
    });

    const weekdayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
        .map((w) => `<div class="pm-cal-weekday">${w}</div>`).join('');

    let cells = '';
    for (let i = 0; i < startWeekday; i++) {
        cells += '<div class="pm-cal-cell pm-cal-cell--empty"></div>';
    }
    for (let day = 1; day <= daysInMonth; day++) {
        const cellDate = new Date(year, month - 1, day);
        const isToday = cellDate.getTime() === today.getTime();
        const rowsForDay = dayMap[day] || [];
        const dots = rowsForDay.slice(0, 4).map((r) => {
            const tone = pmEscapeHtml(String(r.status || 'upcoming').replace(/_/g, '-'));
            return `<span class="pm-cal-dot pm-cal-dot--${tone}" title="${pmEscapeHtml(r.category)} &mdash; ${pmEscapeHtml(r.status_label || r.status || '')}"></span>`;
        }).join('');
        const more = rowsForDay.length > 4 ? `<span class="pm-cal-more">+${rowsForDay.length - 4}</span>` : '';
        cells += `<div class="pm-cal-cell${isToday ? ' pm-cal-cell--today' : ''}${rowsForDay.length ? ' pm-cal-cell--has-tasks' : ''}">
            <span class="pm-cal-daynum">${day}</span>
            <div class="pm-cal-dots">${dots}${more}</div>
        </div>`;
    }

    grid.innerHTML = weekdayHeaders + cells;

    if (listEl) {
        const monthRows = pmOverviewTasksForMonth(month);
        if (!monthRows.length) {
            listEl.innerHTML = '<div class="ui-empty-state ui-fade-in"><strong>No PM activities scheduled for this month.</strong></div>';
        } else {
            listEl.innerHTML = monthRows.map((r) => {
                const tone = pmEscapeHtml(String(r.status || 'upcoming').replace(/_/g, '-'));
                return `
                <div class="pm-cal-activity">
                    <span class="pm-cal-dot pm-cal-dot--${tone}"></span>
                    <span class="pm-cal-activity__name">${pmEscapeHtml(r.category)}</span>
                    <span class="pm-cal-activity__meta">${r.location_label ? pmEscapeHtml(r.location_label) : 'All Locations'} &middot; ${pmEscapeHtml(r.frequency_label)}</span>
                </div>`;
            }).join('');
        }
    }
}

function pmRenderDueWeek() {
    const container = document.getElementById('pm-overview-dueweek-list');
    if (!container) return;

    const items = pmScheduleRows
        .filter((r) => r.is_active && r.next_due_date && ['overdue', 'due', 'due_soon'].includes(r.status))
        .map((r) => ({ row: r, days: pmDaysUntilFromDate(pmParseDate(r.next_due_date)) }))
        .filter((x) => x.days !== null && x.days <= 7)
        .sort((a, b) => a.days - b.days);

    if (!items.length) {
        container.innerHTML = '<div class="ui-empty-state ui-fade-in"><strong>Nothing due this week.</strong></div>';
        return;
    }

    container.innerHTML = items.map(({ row, days }) => `
        <div class="pm-dueweek-item">
            <div class="pm-dueweek-item__top">
                <span class="pm-dueweek-item__name">${pmEscapeHtml(row.category)}</span>
                ${pmStatusBadge(row.status, row.status_label)}
            </div>
            <div class="pm-dueweek-item__title">${pmEscapeHtml(row.title)}</div>
            <div class="pm-dueweek-item__meta">
                <span>${row.location_label ? pmEscapeHtml(row.location_label) : 'All Locations'}</span>
                ${row.assigned_user_name ? `<span>&middot; ${pmEscapeHtml(row.assigned_user_name)}</span>` : ''}
            </div>
            <div class="pm-dueweek-item__due">${pmEscapeHtml(row.next_due_label || pmFormatDate(row.next_due_date))} <span class="pm-dueweek-item__hint">(${pmDaysHint(days)})</span></div>
        </div>
    `).join('');
}

function pmRenderPlansList() {
    const container = document.getElementById('pm-overview-plans-container');
    if (!container) return;

    const rows = pmScheduleRows;
    if (!rows.length) {
        container.innerHTML = '<div class="ui-empty-state ui-fade-in"><strong>No preventive maintenance tasks found.</strong></div>';
        return;
    }

    let html = '<table class="table pm-table pm-plans-table ui-fade-in"><thead><tr>'
        + '<th>Plan / Equipment</th><th>Location</th><th>Frequency</th><th>Last Done</th><th>Next Due</th><th>Assigned</th><th>Status</th><th class="pm-table__action-head">Actions</th>'
        + '</tr></thead><tbody>';

    rows.forEach((row) => {
        html += '<tr>';
        html += `<td class="pm-table__title-cell">
            <div class="pm-cell-primary">${pmEscapeHtml(row.category)}</div>
            <div class="pm-cell-secondary">${pmEscapeHtml(row.title)}</div>
        </td>`;
        html += `<td>${row.location_label ? pmEscapeHtml(row.location_label) : pmMutedDash()}</td>`;
        html += `<td>${pmEscapeHtml(row.frequency_label)}</td>`;
        html += `<td>${row.last_completed_date ? pmFormatDate(row.last_completed_date) : pmMutedDash()}</td>`;
        html += `<td>${row.next_due_label ? pmEscapeHtml(row.next_due_label) : pmMutedDash()}</td>`;
        html += `<td>${row.assigned_user_name ? pmEscapeHtml(row.assigned_user_name) : pmMutedDash()}</td>`;
        html += `<td>${pmStatusBadge(row.status, row.status_label)}</td>`;
        html += `<td class="pm-table__action-cell">${pmScheduleActionsCell(row)}</td>`;
        html += '</tr>';
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

function pmRenderOverview() {
    pmRenderCalendar();
    pmRenderDueWeek();
    pmRenderPlansList();
}

// ---------------------------------------------------------------------
// Annual Schedule grid
// ---------------------------------------------------------------------

function pmScheduleActionsCell(row) {
    const canManage = pmCanManageTask(row);
    let html = `<button type="button" class="pm-action-btn pm-action-btn--view" data-action="view" data-id="${row.id}">View</button>`;

    if (canManage) {
        html += `<button type="button" class="pm-action-btn pm-action-btn--edit" data-action="edit" data-id="${row.id}">Edit</button>`;
        if (row.is_active) {
            html += `<button type="button" class="pm-action-btn pm-action-btn--complete" data-action="complete" data-id="${row.id}">Complete</button>`;
        }
    }

    if (PM_CAN_ARCHIVE) {
        if (row.is_active) {
            html += `<button type="button" class="pm-action-btn pm-action-btn--archive" data-action="archive" data-id="${row.id}">Archive</button>`;
        } else {
            html += `<button type="button" class="pm-action-btn pm-action-btn--activate" data-action="activate" data-id="${row.id}">Reactivate</button>`;
        }
    }

    return `<div class="pm-actions-cell">${html}</div>`;
}

async function pmLoadScheduleGrid() {
    const f = pmCollectFilters();
    const container = document.getElementById('pm-schedule-container');
    container.innerHTML = pmGetLoadingMarkup('Loading annual schedule...');

    const params = new URLSearchParams({ is_active: f.activeFilter });
    if (f.search) params.set('search', f.search);
    if (f.category) params.set('category', f.category);
    if (f.location) params.set('location', f.location);
    if (f.frequency) params.set('frequency', f.frequency);

    try {
        const { response, data: payload } = await Components.fetchJson(`${PM_API_BASE}/schedule-grid?${params.toString()}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed to load the annual schedule');

        let rows = Array.isArray(payload.data?.rows) ? payload.data.rows : [];
        if (f.assignedUserId) rows = rows.filter((r) => String(r.assigned_user_id) === String(f.assignedUserId));
        if (f.status) rows = rows.filter((r) => r.status === f.status);
        if (f.month) rows = rows.filter((r) => !!(r.months && r.months[f.month]));

        pmScheduleRows = rows;
        pmScheduleRowsById = {};
        rows.forEach((r) => { pmScheduleRowsById[r.id] = r; });

        if (pmActiveTab === 'overview') {
            pmRenderOverview();
            return;
        }

        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state ui-fade-in"><strong>No preventive maintenance tasks found.</strong><span>Try adjusting your filters' + (PM_CAN_CREATE ? ' or create a new PM task.' : '.') + '</span></div>';
            return;
        }

        const monthHeaders = PM_MONTH_NAMES.map((name) => `<th class="pm-month-col">${name}</th>`).join('');
        let html = '<table class="table pm-table pm-schedule-table ui-fade-in"><thead><tr>'
            + '<th class="pm-sticky-col pm-sticky-col--freq">Frequency</th>'
            + '<th class="pm-sticky-col pm-sticky-col--equip">Equipment / Activity</th>'
            + '<th class="pm-sticky-col pm-sticky-col--loc">Location</th>'
            + monthHeaders
            + '<th>Status</th><th class="pm-table__action-head">Actions</th>'
            + '</tr></thead><tbody>';

        rows.forEach((row) => {
            html += '<tr>';
            html += `<td class="pm-sticky-col pm-sticky-col--freq">${pmEscapeHtml(row.frequency_label)}</td>`;
            html += `<td class="pm-sticky-col pm-sticky-col--equip pm-table__title-cell">
                <div class="pm-cell-primary">${pmEscapeHtml(row.category)}</div>
                <div class="pm-cell-secondary">${pmEscapeHtml(row.title)}</div>
            </td>`;
            html += `<td class="pm-sticky-col pm-sticky-col--loc">${row.location_label ? pmEscapeHtml(row.location_label) : pmMutedDash()}</td>`;
            for (let m = 1; m <= 12; m++) {
                const marked = !!(row.months && row.months[m]);
                html += `<td class="pm-month-col${marked ? ' pm-month-col--marked' : ''}">${marked ? '<span class="pm-x-mark">X</span>' : ''}</td>`;
            }
            html += `<td>${pmStatusBadge(row.status, row.status_label)}</td>`;
            html += `<td class="pm-table__action-cell">${pmScheduleActionsCell(row)}</td>`;
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (error) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load the annual schedule.</strong></div>';
        pmNotify(error.message || 'Unable to load the annual schedule.');
    }
}

// ---------------------------------------------------------------------
// Monthly Checklist
// ---------------------------------------------------------------------

function pmChecklistActionsCell(item) {
    const canManage = pmCanManageTask(item);
    let html = `<button type="button" class="pm-action-btn pm-action-btn--view" data-action="view" data-id="${item.id}">View</button>`;
    if (canManage && item.item_status !== 'completed') {
        html += `<button type="button" class="pm-action-btn pm-action-btn--complete" data-action="complete" data-id="${item.id}">Mark Complete</button>`;
    }
    return `<div class="pm-actions-cell">${html}</div>`;
}

async function pmLoadChecklist() {
    const f = pmCollectFilters();
    const container = document.getElementById('pm-checklist-container');
    container.innerHTML = pmGetLoadingMarkup('Loading monthly checklist...');

    const params = new URLSearchParams({ year: String(pmChecklistYear), month: String(pmChecklistMonth) });
    if (f.search) params.set('search', f.search);
    if (f.category) params.set('category', f.category);
    if (f.location) params.set('location', f.location);
    if (f.frequency) params.set('frequency', f.frequency);
    if (f.assignedUserId) params.set('assigned_user_id', f.assignedUserId);
    if (f.status) params.set('status', f.status);

    const periodNote = document.getElementById('pm-checklist-period-note');
    if (periodNote) periodNote.textContent = `Completion checklist for ${PM_MONTH_NAMES[pmChecklistMonth - 1]} ${pmChecklistYear}.`;

    try {
        const { response, data: payload } = await Components.fetchJson(`${PM_API_BASE}/checklist?${params.toString()}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed to load the monthly checklist');

        const items = Array.isArray(payload.data?.items) ? payload.data.items : [];
        pmChecklistItems = items;
        pmChecklistItemsById = {};
        items.forEach((it) => { pmChecklistItemsById[it.id] = it; });

        if (items.length === 0) {
            container.innerHTML = '<div class="ui-empty-state ui-fade-in"><strong>No checklist items found for this period.</strong><span>Try adjusting your filters.</span></div>';
            return;
        }

        const periodLabel = `${PM_MONTH_NAMES[pmChecklistMonth - 1]} ${pmChecklistYear}`;

        let html = '<div class="table-responsive"><table class="table pm-table ui-fade-in"><thead><tr>'
            + '<th>Equipment</th><th>Location</th><th>Frequency</th><th>Scheduled</th><th>Assigned Personnel</th><th>Status</th><th>Completed</th><th class="pm-table__action-head">Actions</th>'
            + '</tr></thead><tbody>';

        items.forEach((item) => {
            const isCompleted = item.item_status === 'completed';
            const completedCell = isCompleted
                ? `${pmFormatDate(item.completed_date) || pmMutedDash()}${item.performed_by_name ? `<div class="pm-cell-secondary">by ${pmEscapeHtml(item.performed_by_name)}</div>` : ''}`
                : '<span class="text-muted">Not yet completed</span>';

            html += '<tr>';
            html += `<td class="pm-table__title-cell">
                <div class="pm-cell-primary">${pmEscapeHtml(item.category)}</div>
                <div class="pm-cell-secondary">${pmEscapeHtml(item.title)}</div>
            </td>`;
            html += `<td>${item.location_label ? pmEscapeHtml(item.location_label) : pmMutedDash()}</td>`;
            html += `<td>${pmEscapeHtml(item.frequency_label)}</td>`;
            html += `<td>${pmEscapeHtml(periodLabel)}</td>`;
            html += `<td>${item.assigned_user_name ? pmEscapeHtml(item.assigned_user_name) : '<span class="text-muted">Not assigned</span>'}</td>`;
            html += `<td>${pmStatusBadge(item.item_status, PM_STATUS_LABELS[item.item_status])}</td>`;
            html += `<td>${completedCell}</td>`;
            html += `<td class="pm-table__action-cell">${pmChecklistActionsCell(item)}</td>`;
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        container.innerHTML = html;
    } catch (error) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load the monthly checklist.</strong></div>';
        pmNotify(error.message || 'Unable to load the monthly checklist.');
    }
}

// ---------------------------------------------------------------------
// Personnel selects + cascading location selects (Create/Edit modal)
// ---------------------------------------------------------------------

let pmAssigneeSelect = null;
let pmCompletePerformedSelect = null;
let pmFilterAssigneeSelect = null;

function pmInitPersonnelSelects() {
    // Personnel comes from Preventive Maintenance's own neutral support
    // endpoint, which reads the shared PersonnelDirectoryService — not the
    // Repair Request module (see PreventiveMaintenanceRepairDecouplingTest).
    const personnelEndpoint = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/preventive-maintenance/support/personnel') : '/api/preventive-maintenance/support/personnel';

    if (!pmAssigneeSelect) {
        pmAssigneeSelect = new Components.SearchableSelect({
            inputId: 'pm-form-assignee-search',
            hiddenId: 'pm-form-assignee-id',
            endpoint: personnelEndpoint,
            displayKey: 'full_name',
            // SearchableSelect's default hidden-value fallback chain checks
            // it.id then it.department_id before it.user_id — a personnel
            // row has no `id` but DOES have `department_id`, so without this
            // override the hidden field would end up holding a department id
            // instead of the selected user's id.
            onSelect: (it) => { document.getElementById('pm-form-assignee-id').value = it.user_id || ''; },
        });
    }

    if (!pmCompletePerformedSelect) {
        pmCompletePerformedSelect = new Components.SearchableSelect({
            inputId: 'pm-complete-performed-search',
            hiddenId: 'pm-complete-performed-id',
            endpoint: personnelEndpoint,
            displayKey: 'full_name',
            onSelect: (it) => { document.getElementById('pm-complete-performed-id').value = it.user_id || ''; },
        });
    }

    if (!pmFilterAssigneeSelect) {
        pmFilterAssigneeSelect = new Components.SearchableSelect({
            inputId: 'pm-filter-assignee-search',
            hiddenId: 'pm-filter-assignee-id',
            endpoint: personnelEndpoint,
            displayKey: 'full_name',
            onSelect: (it) => {
                document.getElementById('pm-filter-assignee-id').value = it.user_id || '';
                pmReloadActiveTab();
            },
        });
    }
}

async function pmPopulateFloors(buildingId, selectedFloorId) {
    const floorSelect = document.getElementById('pm-form-floor');
    const roomSelect = document.getElementById('pm-form-room');

    if (!buildingId) {
        floorSelect.innerHTML = '<option value="">Select building first</option>';
        floorSelect.disabled = true;
        roomSelect.innerHTML = '<option value="">Select floor first</option>';
        roomSelect.disabled = true;
        return;
    }

    floorSelect.innerHTML = '<option value="">Loading floors...</option>';
    floorSelect.disabled = true;

    try {
        const base = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL(`/api/buildings/${buildingId}/floors`) : `/api/buildings/${buildingId}/floors`;
        const { response, data: payload } = await Components.fetchJson(base, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok || !payload.success) throw new Error('Failed to load floors');

        const floors = payload.data?.floors || [];
        floorSelect.innerHTML = '<option value="">Select floor</option>' + floors.map((f) => `<option value="${f.id}"${String(f.id) === String(selectedFloorId) ? ' selected' : ''}>${pmEscapeHtml(f.name)}</option>`).join('');
        floorSelect.disabled = false;

        if (selectedFloorId) {
            await pmPopulateRooms(buildingId, selectedFloorId, null);
        } else {
            roomSelect.innerHTML = '<option value="">Select floor first</option>';
            roomSelect.disabled = true;
        }
    } catch (error) {
        floorSelect.innerHTML = '<option value="">Failed to load floors</option>';
    }
}

async function pmPopulateRooms(buildingId, floorId, selectedRoomId) {
    const roomSelect = document.getElementById('pm-form-room');

    if (!floorId) {
        roomSelect.innerHTML = '<option value="">Select floor first</option>';
        roomSelect.disabled = true;
        return;
    }

    roomSelect.innerHTML = '<option value="">Loading rooms...</option>';
    roomSelect.disabled = true;

    try {
        const base = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/rooms') : '/api/rooms';
        const { response, data: payload } = await Components.fetchJson(`${base}?building_id=${encodeURIComponent(buildingId)}&floor_id=${encodeURIComponent(floorId)}&per_page=200`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok || !payload.success) throw new Error('Failed to load rooms');

        const rooms = payload.data?.rooms || [];
        roomSelect.innerHTML = '<option value="">Select room / area</option>' + rooms.map((r) => `<option value="${r.id}"${String(r.id) === String(selectedRoomId) ? ' selected' : ''}>${pmEscapeHtml(r.name)}</option>`).join('');
        roomSelect.disabled = false;
    } catch (error) {
        roomSelect.innerHTML = '<option value="">Failed to load rooms</option>';
    }
}

async function pmPopulateItems(roomId, selectedItemId) {
    const itemSelect = document.getElementById('pm-form-item');
    itemSelect.innerHTML = '<option value="">Loading equipment...</option>';

    try {
        const base = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/api/items') : '/api/items';
        const params = new URLSearchParams({ item_type: 'room_asset', per_page: '200' });
        if (roomId) params.set('room_id', roomId);
        const { response, data: payload } = await Components.fetchJson(`${base}?${params.toString()}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok || !payload.success) throw new Error('Failed to load equipment');

        const items = payload.data?.data || [];
        itemSelect.innerHTML = '<option value="">None / general category</option>' + items.map((it) => `<option value="${it.id}"${String(it.id) === String(selectedItemId) ? ' selected' : ''}>${pmEscapeHtml(it.name)}${it.asset_code ? ' — ' + pmEscapeHtml(it.asset_code) : ''}</option>`).join('');
    } catch (error) {
        itemSelect.innerHTML = '<option value="">Failed to load equipment</option>';
    }
}

document.getElementById('pm-form-building').addEventListener('change', (e) => {
    document.getElementById('pm-form-room').innerHTML = '<option value="">Select floor first</option>';
    document.getElementById('pm-form-room').disabled = true;
    pmPopulateFloors(e.target.value, null);
});

document.getElementById('pm-form-floor').addEventListener('change', (e) => {
    pmPopulateRooms(document.getElementById('pm-form-building').value, e.target.value, null);
});

document.getElementById('pm-form-category').addEventListener('change', (e) => {
    const otherWrap = document.getElementById('pm-form-category-other-wrap');
    otherWrap.style.display = e.target.value === 'Other' ? 'block' : 'none';
});

function pmGetScheduledMonths() {
    const checked = Array.from(document.querySelectorAll('#pm-form-months input[type="checkbox"]:checked'));
    return checked.length ? checked.map((c) => Number(c.value)) : null;
}

function pmSetScheduledMonths(months) {
    const set = new Set((months || []).map((m) => Number(m)));
    document.querySelectorAll('#pm-form-months input[type="checkbox"]').forEach((cb) => {
        cb.checked = set.has(Number(cb.value));
    });
}

function pmResetForm() {
    document.getElementById('pm-task-form').reset();
    document.getElementById('pm-form-task-id').value = '';
    document.getElementById('pm-form-assignee-id').value = '';
    document.getElementById('pm-form-category-other-wrap').style.display = 'none';
    document.getElementById('pm-form-location-name').value = '';
    document.getElementById('pm-form-floor').innerHTML = '<option value="">Select building first</option>';
    document.getElementById('pm-form-floor').disabled = true;
    document.getElementById('pm-form-room').innerHTML = '<option value="">Select floor first</option>';
    document.getElementById('pm-form-room').disabled = true;
    pmSetScheduledMonths(null);
    pmPopulateItems(null, null);
}

function pmOpenFormModal(mode, row) {
    pmInitPersonnelSelects();
    pmResetForm();

    const modal = document.getElementById('pm-form-modal');
    const title = document.getElementById('pm-form-modal-title');

    if (mode === 'edit' && row) {
        title.textContent = 'Edit Preventive Maintenance Task';
        document.getElementById('pm-form-task-id').value = row.id;

        const categorySelect = document.getElementById('pm-form-category');
        const knownCategory = Array.from(categorySelect.options).some((o) => o.value === row.category);
        if (knownCategory) {
            categorySelect.value = row.category;
        } else {
            categorySelect.value = 'Other';
            document.getElementById('pm-form-category-other-wrap').style.display = 'block';
            document.getElementById('pm-form-category-other').value = row.category || '';
        }

        document.getElementById('pm-form-title').value = row.title || '';
        document.getElementById('pm-form-frequency').value = row.frequency || '';
        document.getElementById('pm-form-last-completed').value = row.last_completed_date ? String(row.last_completed_date).slice(0, 10) : '';
        document.getElementById('pm-form-next-due').value = row.next_due_date ? String(row.next_due_date).slice(0, 10) : '';
        document.getElementById('pm-form-department').value = row.department_id || '';
        document.getElementById('pm-form-notes').value = row.notes || '';
        document.getElementById('pm-form-location-name').value = row.location_name || '';
        pmSetScheduledMonths(row.scheduled_months);

        if (row.assigned_user_id) {
            document.getElementById('pm-form-assignee-id').value = row.assigned_user_id;
            document.getElementById('pm-form-assignee-search').value = row.assigned_user_name || '';
        }

        if (row.building_id) {
            document.getElementById('pm-form-building').value = row.building_id;
            pmPopulateFloors(row.building_id, row.floor_id).then(() => {
                if (row.room_id) pmPopulateRooms(row.building_id, row.floor_id, row.room_id);
            });
        }

        pmPopulateItems(row.room_id || null, row.item_id || null);
    } else {
        title.textContent = 'New Preventive Maintenance Task';
    }

    modal.style.display = 'flex';
    modal.removeAttribute('aria-hidden');
}

function pmCloseFormModal() {
    const modal = document.getElementById('pm-form-modal');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
}

async function pmSubmitForm() {
    const taskId = document.getElementById('pm-form-task-id').value;
    const isEdit = !!taskId;

    let category = document.getElementById('pm-form-category').value;
    if (category === 'Other') {
        category = document.getElementById('pm-form-category-other').value.trim();
    }
    const title = document.getElementById('pm-form-title').value.trim();
    const frequency = document.getElementById('pm-form-frequency').value;

    const missing = [];
    if (!category) missing.push('Equipment Nomenclature');
    if (!title) missing.push('Activity / Title');
    if (!frequency) missing.push('Frequency');
    if (missing.length > 0) {
        pmNotify('Please fill in: ' + missing.join(', '), 'warning');
        return;
    }

    const payload = {
        category,
        title,
        frequency,
        item_id: document.getElementById('pm-form-item').value || null,
        building_id: document.getElementById('pm-form-building').value || null,
        floor_id: document.getElementById('pm-form-floor').value || null,
        room_id: document.getElementById('pm-form-room').value || null,
        location_name: document.getElementById('pm-form-location-name').value.trim() || null,
        department_id: document.getElementById('pm-form-department').value || null,
        assigned_user_id: document.getElementById('pm-form-assignee-id').value || null,
        last_completed_date: document.getElementById('pm-form-last-completed').value || null,
        next_due_date: document.getElementById('pm-form-next-due').value || null,
        scheduled_months: pmGetScheduledMonths(),
        notes: document.getElementById('pm-form-notes').value.trim() || null,
    };

    const saveBtn = document.getElementById('pm-form-save');
    try {
        Components.setLoading(saveBtn, true);

        const url = isEdit ? `${PM_API_BASE}/${taskId}` : PM_API_BASE;
        const { response, data: resPayload } = await Components.fetchJson(url, {
            method: isEdit ? 'PATCH' : 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(payload),
        });

        if (!response.ok || !resPayload.success) {
            throw new Error(resPayload.message || 'Failed to save preventive maintenance task');
        }

        Components.toast(resPayload.message || 'Preventive maintenance task saved.', 'success');
        pmCloseFormModal();
        pmReloadActiveTab();
        pmLoadSummary();
    } catch (error) {
        pmNotify(error.message || 'Unable to save preventive maintenance task.');
    } finally {
        Components.setLoading(saveBtn, false);
    }
}

// ---------------------------------------------------------------------
// Mark Completed modal
// ---------------------------------------------------------------------

function pmOpenCompleteModal(row) {
    pmInitPersonnelSelects();
    document.getElementById('pm-complete-form').reset();
    document.getElementById('pm-complete-task-id').value = row.id;
    document.getElementById('pm-complete-performed-id').value = '';
    document.getElementById('pm-complete-proof-preview').innerHTML = '';
    document.getElementById('pm-complete-date').value = new Date().toISOString().slice(0, 10);
    document.getElementById('pm-complete-modal-title').textContent = `Mark as Completed — ${row.title || ''}`;

    const modal = document.getElementById('pm-complete-modal');
    modal.style.display = 'flex';
    modal.removeAttribute('aria-hidden');
}

function pmCloseCompleteModal() {
    const modal = document.getElementById('pm-complete-modal');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
}

document.getElementById('pm-complete-proof').addEventListener('change', (e) => {
    const preview = document.getElementById('pm-complete-proof-preview');
    preview.innerHTML = '';
    const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
    if (!file) return;

    const maxBytes = 5 * 1024 * 1024;
    if (file.size > maxBytes) {
        pmNotify('Image must be 5MB or smaller.', 'warning');
        e.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = () => {
        preview.innerHTML = `<img src="${reader.result}" alt="Completion proof preview" style="max-width:280px;border:1px solid rgba(148,163,184,.3);border-radius:8px;">`;
    };
    reader.readAsDataURL(file);
});

async function pmSubmitComplete() {
    const taskId = document.getElementById('pm-complete-task-id').value;
    const completedDate = document.getElementById('pm-complete-date').value;

    if (!completedDate) {
        pmNotify('Please enter the completed date.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('completed_date', completedDate);
    const performedBy = document.getElementById('pm-complete-performed-id').value;
    if (performedBy) formData.append('performed_by', performedBy);
    const notes = document.getElementById('pm-complete-notes').value.trim();
    if (notes) formData.append('notes', notes);
    const findings = document.getElementById('pm-complete-findings').value.trim();
    if (findings) formData.append('findings', findings);
    const actionTaken = document.getElementById('pm-complete-action-taken').value.trim();
    if (actionTaken) formData.append('action_taken', actionTaken);
    const proofFile = document.getElementById('pm-complete-proof').files?.[0] || null;
    if (proofFile) formData.append('completion_proof', proofFile);

    const saveBtn = document.getElementById('pm-complete-save');
    try {
        Components.setLoading(saveBtn, true);

        const { response, data: payload } = await Components.fetchJson(`${PM_API_BASE}/${taskId}/complete`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            body: formData,
        });

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to record completion');
        }

        Components.toast(payload.message || 'Preventive maintenance task marked completed.', 'success');
        pmCloseCompleteModal();
        pmReloadActiveTab();
        pmLoadSummary();
    } catch (error) {
        pmNotify(error.message || 'Unable to record completion.');
    } finally {
        Components.setLoading(saveBtn, false);
    }
}

// ---------------------------------------------------------------------
// View Details + History modal
// ---------------------------------------------------------------------

function pmHistoryRowMarkup(h) {
    const proofUrl = h.completion_proof_path
        ? (window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/' + String(h.completion_proof_path).replace(/^\/+/, '')) : '/' + String(h.completion_proof_path).replace(/^\/+/, ''))
        : null;

    return `
        <div class="pm-history-row">
            <div class="pm-history-row__main">
                <div class="pm-cell-primary">${pmEscapeHtml(pmFormatDate(h.completed_date) || h.completed_date)}</div>
                <div class="pm-cell-secondary">Performed by: ${h.performed_by_name ? pmEscapeHtml(h.performed_by_name) : 'Not recorded'}</div>
                <div class="pm-cell-secondary">Recorded by: ${h.recorded_by_name ? pmEscapeHtml(h.recorded_by_name) : 'Unknown'}</div>
                ${h.notes ? `<div class="pm-history-row__notes"><strong>Remarks:</strong> ${pmEscapeHtml(h.notes)}</div>` : ''}
                ${h.findings ? `<div class="pm-history-row__notes"><strong>Findings:</strong> ${pmEscapeHtml(h.findings)}</div>` : ''}
                ${h.action_taken ? `<div class="pm-history-row__notes"><strong>Action Taken:</strong> ${pmEscapeHtml(h.action_taken)}</div>` : ''}
                ${h.next_due_date_snapshot ? `<div class="pm-cell-secondary">Next due set to: ${pmEscapeHtml(pmFormatDate(h.next_due_date_snapshot))}</div>` : ''}
            </div>
            ${proofUrl ? `<a href="${pmEscapeHtml(proofUrl)}" target="_blank" rel="noopener" class="pm-history-row__proof"><img src="${pmEscapeHtml(proofUrl)}" alt="Completion proof"></a>` : ''}
        </div>
    `;
}

function pmScheduledMonthsLabel(row) {
    if (row.scheduled_months_label) return row.scheduled_months_label;
    if (Array.isArray(row.scheduled_months) && row.scheduled_months.length) {
        return row.scheduled_months.map((m) => PM_MONTH_NAMES[Number(m) - 1]).filter(Boolean).join(', ');
    }
    return null;
}

async function pmOpenHistoryModal(row) {
    const modal = document.getElementById('pm-history-modal');
    const summary = document.getElementById('pm-history-task-summary');
    const listContainer = document.getElementById('pm-history-list-container');

    const statusKey = row.status || row.item_status || 'unscheduled';
    const statusLabel = row.status_label || PM_STATUS_LABELS[statusKey];
    const monthsLabel = pmScheduledMonthsLabel(row);

    document.getElementById('pm-history-modal-title').textContent = row.title || 'Task Details';
    summary.innerHTML = `
        <div class="pm-history-task-summary__grid">
            <div><span class="pm-cell-secondary">Equipment Nomenclature</span><div class="pm-cell-primary">${pmEscapeHtml(row.category)}</div></div>
            <div><span class="pm-cell-secondary">Frequency</span><div class="pm-cell-primary">${pmEscapeHtml(row.frequency_label)}</div></div>
            <div><span class="pm-cell-secondary">Location</span><div class="pm-cell-primary">${row.location_label ? pmEscapeHtml(row.location_label) : 'Not set'}</div></div>
            <div><span class="pm-cell-secondary">Scheduled Months</span><div class="pm-cell-primary">${monthsLabel ? pmEscapeHtml(monthsLabel) : 'Unscheduled'}</div></div>
            <div><span class="pm-cell-secondary">Responsible Department</span><div class="pm-cell-primary">${row.department_name ? pmEscapeHtml(row.department_name) : 'Unassigned'}</div></div>
            <div><span class="pm-cell-secondary">Assigned Personnel</span><div class="pm-cell-primary">${row.assigned_user_name ? pmEscapeHtml(row.assigned_user_name) : 'Unassigned'}</div></div>
            <div><span class="pm-cell-secondary">Last Completed</span><div class="pm-cell-primary">${pmFormatDate(row.last_completed_date) || 'Never'}</div></div>
            <div><span class="pm-cell-secondary">Next Due</span><div class="pm-cell-primary">${row.next_due_label ? pmEscapeHtml(row.next_due_label) : 'Unscheduled'}</div></div>
            <div><span class="pm-cell-secondary">Status</span><div class="pm-cell-primary">${pmStatusBadge(statusKey, statusLabel)}</div></div>
        </div>
        ${row.notes ? `<div class="pm-history-row__notes" style="margin-top:10px;"><strong>Notes:</strong> ${pmEscapeHtml(row.notes)}</div>` : ''}
    `;

    listContainer.innerHTML = '<div class="ui-skeleton-list"><div class="ui-skeleton-row w-90"></div><div class="ui-skeleton-row w-75"></div></div>';
    modal.style.display = 'flex';
    modal.removeAttribute('aria-hidden');

    try {
        const { response, data: payload } = await Components.fetchJson(`${PM_API_BASE}/${row.id}/history?per_page=50`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed to load history');

        const historyRows = payload.data?.history?.data || [];
        if (historyRows.length === 0) {
            listContainer.innerHTML = '<p class="pm-muted-cell">No completion history recorded yet.</p>';
        } else {
            listContainer.innerHTML = historyRows.map(pmHistoryRowMarkup).join('');
        }
    } catch (error) {
        listContainer.innerHTML = '<p class="pm-muted-cell">Failed to load completion history.</p>';
    }
}

function pmCloseHistoryModal() {
    const modal = document.getElementById('pm-history-modal');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
}

// ---------------------------------------------------------------------
// Archive / Activate
// ---------------------------------------------------------------------

async function pmToggleActive(row, activate) {
    const verb = activate ? 'reactivate' : 'archive';
    if (!window.confirm(`Are you sure you want to ${verb} "${row.title}"?`)) return;

    try {
        const { response, data: payload } = await Components.fetchJson(`${PM_API_BASE}/${row.id}/${activate ? 'activate' : 'archive'}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok || !payload.success) throw new Error(payload.message || `Failed to ${verb} task`);

        Components.toast(payload.message || `Task ${activate ? 'reactivated' : 'archived'}.`, 'success');
        pmReloadActiveTab();
        pmLoadSummary();
    } catch (error) {
        pmNotify(error.message || `Unable to ${verb} this task.`);
    }
}

// ---------------------------------------------------------------------
// Print view — manual-style Jan–Dec schedule grid
// ---------------------------------------------------------------------

function pmBuildPrintView() {
    const year = new Date().getFullYear();
    const monthHeaders = PM_MONTH_NAMES.map((name) => `<th>${name}</th>`).join('');

    let rowsHtml = '';
    pmScheduleRows.forEach((row) => {
        rowsHtml += '<tr>';
        rowsHtml += `<td>${pmEscapeHtml(row.frequency_label)}</td>`;
        rowsHtml += `<td>${pmEscapeHtml(row.category)}</td>`;
        rowsHtml += `<td>${row.location_label ? pmEscapeHtml(row.location_label) : ''}</td>`;
        for (let m = 1; m <= 12; m++) {
            rowsHtml += `<td>${row.months && row.months[m] ? 'X' : ''}</td>`;
        }
        rowsHtml += '</tr>';
    });

    return `
        <div class="pm-print-header">
            <h1>PHILIPPINE COLLEGE OF SCIENCE AND TECHNOLOGY</h1>
            <h2>PREVENTIVE MAINTENANCE SCHEDULE — ${year}</h2>
        </div>
        <table class="pm-print-table">
            <thead>
                <tr><th>Frequency</th><th>Equipment Nomenclature</th><th>Location</th>${monthHeaders}</tr>
            </thead>
            <tbody>${rowsHtml}</tbody>
        </table>
    `;
}

function pmPrintSchedule() {
    if (!pmScheduleRows.length) {
        pmNotify('There is no schedule data loaded to print yet.', 'warning');
        return;
    }
    document.getElementById('pm-print-container').innerHTML = pmBuildPrintView();
    document.body.classList.add('pm-printing');
    window.print();
}

window.addEventListener('afterprint', () => {
    document.body.classList.remove('pm-printing');
});

// ---------------------------------------------------------------------
// Tabs
// ---------------------------------------------------------------------

function pmSwitchTab(tab) {
    pmActiveTab = tab;
    document.getElementById('pm-tab-overview').classList.toggle('pm-tab--active', tab === 'overview');
    document.getElementById('pm-tab-schedule').classList.toggle('pm-tab--active', tab === 'schedule');
    document.getElementById('pm-tab-checklist').classList.toggle('pm-tab--active', tab === 'checklist');
    document.getElementById('pm-tab-overview').setAttribute('aria-selected', tab === 'overview' ? 'true' : 'false');
    document.getElementById('pm-tab-schedule').setAttribute('aria-selected', tab === 'schedule' ? 'true' : 'false');
    document.getElementById('pm-tab-checklist').setAttribute('aria-selected', tab === 'checklist' ? 'true' : 'false');

    document.getElementById('pm-overview-panel').style.display = tab === 'overview' ? '' : 'none';
    document.getElementById('pm-schedule-panel').style.display = tab === 'schedule' ? '' : 'none';
    document.getElementById('pm-checklist-panel').style.display = tab === 'checklist' ? '' : 'none';

    // The toolbar (search/filters) only applies to the Annual Schedule and
    // Monthly Checklist tabs. Overview is a fixed, always-unfiltered
    // dashboard view of the same data, so its own filters are reset every
    // time it's entered rather than silently inheriting whatever was left
    // set on another tab.
    document.getElementById('pm-toolbar').style.display = tab === 'overview' ? 'none' : '';
    if (tab === 'overview') pmResetToolbarFilters();

    document.getElementById('pm-filter-year-wrap').style.display = tab === 'checklist' ? '' : 'none';
    document.getElementById('pm-filter-active-wrap').style.display = tab === 'schedule' ? '' : 'none';

    // Month stays visible on both tabs: an optional filter on Schedule, and
    // part of the mandatory year+month period (alongside Prev/Next) on
    // Checklist — so it must reflect the current period, never blank, there.
    const monthSelect = document.getElementById('pm-filter-month');
    monthSelect.value = tab === 'checklist' ? String(pmChecklistMonth) : '';

    pmPopulateStatusFilter();
    pmReloadActiveTab();
}

// ---------------------------------------------------------------------
// Wiring
// ---------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('pm-filter-year').value = pmChecklistYear;
    document.getElementById('pm-filter-year-wrap').style.display = 'none';
    pmPopulateStatusFilter();

    pmLoadOptions();
    pmLoadBuildingsForm();
    pmLoadDepartmentsFilter();
    pmLoadSummary();
    pmLoadScheduleGrid();

    document.getElementById('pm-tab-overview').addEventListener('click', () => pmSwitchTab('overview'));
    document.getElementById('pm-tab-schedule').addEventListener('click', () => pmSwitchTab('schedule'));
    document.getElementById('pm-tab-checklist').addEventListener('click', () => pmSwitchTab('checklist'));

    document.getElementById('pm-overview-prev').addEventListener('click', () => {
        pmChecklistMonth -= 1;
        if (pmChecklistMonth < 1) { pmChecklistMonth = 12; pmChecklistYear -= 1; }
        pmRenderCalendar();
    });
    document.getElementById('pm-overview-next').addEventListener('click', () => {
        pmChecklistMonth += 1;
        if (pmChecklistMonth > 12) { pmChecklistMonth = 1; pmChecklistYear += 1; }
        pmRenderCalendar();
    });

    document.getElementById('pm-filter-search').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') pmReloadActiveTab();
    });
    ['pm-filter-category', 'pm-filter-frequency', 'pm-filter-status', 'pm-filter-month', 'pm-filter-active'].forEach((id) => {
        document.getElementById(id).addEventListener('change', () => pmReloadActiveTab());
    });
    document.getElementById('pm-filter-apply').addEventListener('click', () => pmReloadActiveTab());
    document.getElementById('pm-filter-clear').addEventListener('click', () => {
        pmResetToolbarFilters();
        pmReloadActiveTab();
    });

    document.getElementById('pm-filter-year').addEventListener('change', (e) => {
        const y = parseInt(e.target.value, 10);
        if (y && y > 1900) { pmChecklistYear = y; pmLoadChecklist(); }
    });
    document.getElementById('pm-year-prev').addEventListener('click', () => {
        pmChecklistMonth -= 1;
        if (pmChecklistMonth < 1) { pmChecklistMonth = 12; pmChecklistYear -= 1; }
        document.getElementById('pm-filter-year').value = pmChecklistYear;
        document.getElementById('pm-filter-month').value = String(pmChecklistMonth);
        pmLoadChecklist();
    });
    document.getElementById('pm-year-next').addEventListener('click', () => {
        pmChecklistMonth += 1;
        if (pmChecklistMonth > 12) { pmChecklistMonth = 1; pmChecklistYear += 1; }
        document.getElementById('pm-filter-year').value = pmChecklistYear;
        document.getElementById('pm-filter-month').value = String(pmChecklistMonth);
        pmLoadChecklist();
    });

    document.getElementById('pm-print-btn').addEventListener('click', pmPrintSchedule);

    const newTaskBtn = document.getElementById('pm-new-task-btn');
    if (newTaskBtn) newTaskBtn.addEventListener('click', () => pmOpenFormModal('create', null));

    document.getElementById('pm-form-modal-close').addEventListener('click', pmCloseFormModal);
    document.getElementById('pm-form-cancel').addEventListener('click', pmCloseFormModal);
    document.getElementById('pm-form-modal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('pm-form-modal')) pmCloseFormModal();
    });
    document.getElementById('pm-form-save').addEventListener('click', pmSubmitForm);

    document.getElementById('pm-complete-modal-close').addEventListener('click', pmCloseCompleteModal);
    document.getElementById('pm-complete-cancel').addEventListener('click', pmCloseCompleteModal);
    document.getElementById('pm-complete-modal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('pm-complete-modal')) pmCloseCompleteModal();
    });
    document.getElementById('pm-complete-save').addEventListener('click', pmSubmitComplete);

    document.getElementById('pm-history-modal-close').addEventListener('click', pmCloseHistoryModal);
    document.getElementById('pm-history-modal-dismiss').addEventListener('click', pmCloseHistoryModal);
    document.getElementById('pm-history-modal').addEventListener('click', (e) => {
        if (e.target === document.getElementById('pm-history-modal')) pmCloseHistoryModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        pmCloseFormModal();
        pmCloseCompleteModal();
        pmCloseHistoryModal();
    });

    // Event delegation: table rows are re-rendered on every load.
    document.getElementById('pm-schedule-container').addEventListener('click', (e) => {
        const btn = e.target.closest('.pm-action-btn');
        if (!btn) return;
        const row = pmScheduleRowsById[btn.dataset.id];
        if (!row) return;

        const action = btn.dataset.action;
        if (action === 'view') pmOpenHistoryModal(row);
        else if (action === 'edit') pmOpenFormModal('edit', row);
        else if (action === 'complete') pmOpenCompleteModal(row);
        else if (action === 'archive') pmToggleActive(row, false);
        else if (action === 'activate') pmToggleActive(row, true);
    });

    // Same action set, same pmScheduleRowsById lookup — the Overview tab's
    // Maintenance Plans list renders from the identical fetched rows, so its
    // row actions must behave identically rather than being a second
    // implementation of view/edit/complete/archive/activate.
    document.getElementById('pm-overview-plans-container').addEventListener('click', (e) => {
        const btn = e.target.closest('.pm-action-btn');
        if (!btn) return;
        const row = pmScheduleRowsById[btn.dataset.id];
        if (!row) return;

        const action = btn.dataset.action;
        if (action === 'view') pmOpenHistoryModal(row);
        else if (action === 'edit') pmOpenFormModal('edit', row);
        else if (action === 'complete') pmOpenCompleteModal(row);
        else if (action === 'archive') pmToggleActive(row, false);
        else if (action === 'activate') pmToggleActive(row, true);
    });

    document.getElementById('pm-checklist-container').addEventListener('click', (e) => {
        const btn = e.target.closest('.pm-action-btn');
        if (!btn) return;
        const item = pmChecklistItemsById[btn.dataset.id];
        if (!item) return;

        const action = btn.dataset.action;
        if (action === 'view') pmOpenHistoryModal(item);
        else if (action === 'complete') pmOpenCompleteModal(item);
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
