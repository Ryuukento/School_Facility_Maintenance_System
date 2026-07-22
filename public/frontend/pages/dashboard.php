<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

$pageTitle = 'Dashboard - SFMS';
include __DIR__ . '/../includes/header.php';

$user = $_SESSION['user'];
?>

<main class="container maintenance-admin-dashboard-page">
    <div class="card">
        <div class="card-header d-flex justify-between align-center">
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
                <div class="summary-card summary-card-buildings summary-card-action" onclick="navigateToBuildingsOverview()">
                    <div class="summary-card-content">
                        <h3 class="summary-card-title">Buildings overview</h3>
                        <div class="summary-card-value summary-card-value-action">Open</div>
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
                        </div>
                        <p class="text-muted mb-0">Current distribution of report statuses. &nbsp;<span id="status-chart-month" style="font-size:11px;font-weight:600;background:rgba(139,92,246,0.15);color:#a78bfa;padding:2px 8px;border-radius:20px;white-space:nowrap;"></span></p>
                    </div>
                    <div class="card-body status-chart-body">
                        <div style="position:relative;height:280px;">
                            <canvas id="reportsStatusChart" class="chart-canvas" style="display:block;width:100%;height:280px;"></canvas>
                        </div>
                    </div>
                </div>

                <div class="card chart-card priority-chart-card">
                    <div class="card-header">
                        <h2>Reports by Priority</h2>
                        <p class="text-muted mb-0">Priority levels across all reports. &nbsp;<span id="priority-chart-month" style="font-size:11px;font-weight:600;background:rgba(139,92,246,0.15);color:#a78bfa;padding:2px 8px;border-radius:20px;white-space:nowrap;"></span></p>
                    </div>
                    <div class="card-body">
                        <div style="position:relative;height:280px;">
                            <canvas id="reportsPriorityChart" class="chart-canvas" style="display:block;width:100%;height:280px;"></canvas>
                        </div>
                    </div>
                </div>

                <div class="card recent-reports-card">
                    <div class="card-header recent-reports-header">
                        <h2>Recent Reports</h2>
                        <a href="/School_Facility_Maintenance_System/frontend/pages/reports.php" class="recent-reports-link">View all &rarr;</a>
                    </div>
                    <div class="card-body recent-reports-body">
                        <div class="recent-reports-table-wrap" id="recentActivity">
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

<!-- ── Buildings print-options modal ────────────────────────────────────────── -->
<div id="bldg-print-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="bldgPmTitle">
    <div class="bldg-pm-backdrop" onclick="closeBldgPrintModal()"></div>
    <div class="bldg-pm-panel">

        <!-- Header -->
        <div class="bldg-pm-hd">
            <span id="bldgPmTitle" class="bldg-pm-title">Print options</span>
            <button type="button" class="bldg-pm-x" onclick="closeBldgPrintModal()" aria-label="Close">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <!-- Body -->
        <div class="bldg-pm-body">

            <!-- Section 1: Select buildings -->
            <div class="bldg-pm-sec">
                <div class="bldg-pm-sec-hd">
                    <span class="bldg-pm-sec-lbl">Select buildings</span>
                    <span class="bldg-pm-sec-acts">
                        <button type="button" class="bldg-pm-lnk" onclick="bldgPmToggleAllBuildings(true)">Select all</button>
                        <span class="bldg-pm-dot">·</span>
                        <button type="button" class="bldg-pm-lnk" onclick="bldgPmToggleAllBuildings(false)">Deselect all</button>
                    </span>
                </div>
                <div id="bldg-pm-blist" class="bldg-pm-checklist"></div>
            </div>

            <!-- Section 2: Select floors per building -->
            <div class="bldg-pm-sec">
                <div class="bldg-pm-sec-hd">
                    <span class="bldg-pm-sec-lbl">Select floors</span>
                </div>
                <div id="bldg-pm-flist" class="bldg-pm-checklist"></div>
            </div>

            <!-- Section 3: Print options -->
            <div class="bldg-pm-sec">
                <span class="bldg-pm-sec-lbl">Print options</span>
                <div class="bldg-pm-opts">
                    <label class="bldg-pm-opt">
                        <input type="checkbox" id="bldg-opt-floor-detail" checked>
                        <span>Include floor details table</span>
                    </label>
                    <label class="bldg-pm-opt">
                        <input type="checkbox" id="bldg-opt-summary" checked>
                        <span>Include building summary (floors &amp; rooms)</span>
                    </label>
                    <label class="bldg-pm-opt">
                        <input type="checkbox" id="bldg-opt-date" checked>
                        <span>Show date generated</span>
                    </label>
                    <label class="bldg-pm-opt">
                        <input type="checkbox" id="bldg-opt-breaks" checked>
                        <span>Page break between buildings</span>
                    </label>
                </div>
            </div>

        </div><!-- /.bldg-pm-body -->

        <!-- Footer -->
        <div class="bldg-pm-ft">
            <p class="bldg-pm-ft-note">Only selected buildings and floors will appear in the printed report.</p>
            <div class="bldg-pm-ft-btns">
                <button type="button" class="bldg-pm-cancel" onclick="closeBldgPrintModal()">Cancel</button>
                <button type="button" class="bldg-pm-print" id="bldg-pm-print-btn" onclick="executeBldgPrint()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="6 9 6 2 18 2 18 9"/>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                        <rect x="6" y="14" width="12" height="8"/>
                    </svg>
                    Print
                </button>
            </div>
        </div>

    </div><!-- /.bldg-pm-panel -->
</div><!-- /#bldg-print-modal -->

<!-- ── Buildings print area (hidden at screen, shown only during @media print) ── -->
<div id="dash-buildings-print-area" aria-hidden="true"></div>

<style>
/* ═══════════════════════════════════════════════════════════════════════════
   Buildings Print Modal
   ═══════════════════════════════════════════════════════════════════════════ */
#bldg-print-modal {
    position: fixed;
    inset: 0;
    z-index: 9100;
    display: flex;
    align-items: center;
    justify-content: center;
}
#bldg-print-modal[hidden] { display: none; }

.bldg-pm-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.68);
}

.bldg-pm-panel {
    position: relative;
    width: 560px;
    max-width: calc(100vw - 32px);
    max-height: 88vh;
    display: flex;
    flex-direction: column;
    background: #0f172a;
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 14px;
    box-shadow: 0 28px 56px rgba(0, 0, 0, 0.55);
    overflow: hidden;
}

.bldg-pm-hd {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 20px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.12);
    flex-shrink: 0;
}
.bldg-pm-title {
    font-size: 15px;
    font-weight: 600;
    color: #f1f5f9;
}
.bldg-pm-x {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 3px;
    display: flex;
    align-items: center;
    border-radius: 5px;
    line-height: 1;
    transition: color 0.15s, background 0.15s;
}
.bldg-pm-x:hover { color: #f1f5f9; background: rgba(148,163,184,0.1); }

.bldg-pm-body {
    flex: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(148,163,184,0.2) transparent;
}
.bldg-pm-body::-webkit-scrollbar { width: 5px; }
.bldg-pm-body::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.25); border-radius: 3px; }

.bldg-pm-sec {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(148, 163, 184, 0.08);
}
.bldg-pm-sec:last-child { border-bottom: none; }

.bldg-pm-sec-hd {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.bldg-pm-sec-lbl {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
    display: block;
    margin-bottom: 10px;
}
.bldg-pm-sec-hd .bldg-pm-sec-lbl { margin-bottom: 0; }

.bldg-pm-sec-acts { display: flex; align-items: center; gap: 4px; }
.bldg-pm-dot { font-size: 11px; color: #475569; }
.bldg-pm-lnk {
    background: none;
    border: none;
    font-size: 11px;
    color: #60a5fa;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
    text-underline-offset: 2px;
    line-height: 1;
}
.bldg-pm-lnk:hover { color: #93c5fd; }

.bldg-pm-checklist { display: flex; flex-direction: column; gap: 2px; }

/* Building rows */
.bldg-pm-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 7px 10px;
    border-radius: 7px;
    cursor: pointer;
    transition: background 0.12s;
}
.bldg-pm-row:hover { background: rgba(148, 163, 184, 0.07); }
.bldg-pm-row input[type="checkbox"] {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
    accent-color: #3b82f6;
    cursor: pointer;
    margin: 0;
}
.bldg-pm-row-name {
    font-size: 13px;
    color: #e2e8f0;
    flex: 1;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.bldg-pm-row-badge {
    font-size: 11px;
    color: #64748b;
    background: rgba(148, 163, 184, 0.1);
    border-radius: 10px;
    padding: 2px 8px;
    white-space: nowrap;
    flex-shrink: 0;
}

/* Floor groups */
.bldg-pm-group {
    margin-bottom: 2px;
    border: 1px solid rgba(148, 163, 184, 0.1);
    border-radius: 8px;
    overflow: hidden;
}
.bldg-pm-group-hd {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 10px 7px 12px;
    background: rgba(148, 163, 184, 0.05);
    cursor: pointer;
    user-select: none;
    gap: 8px;
}
.bldg-pm-group-hd:hover { background: rgba(148, 163, 184, 0.09); }
.bldg-pm-group-chevron {
    flex-shrink: 0;
    color: #64748b;
    transition: transform 0.18s;
}
.bldg-pm-group.is-collapsed .bldg-pm-group-chevron { transform: rotate(-90deg); }
.bldg-pm-group-info {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 0;
}
.bldg-pm-group-name {
    font-size: 12px;
    font-weight: 600;
    color: #94a3b8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.bldg-pm-group-acts { flex-shrink: 0; }
.bldg-pm-group-rows {
    padding: 4px 0;
}
.bldg-pm-group.is-collapsed .bldg-pm-group-rows { display: none; }
.bldg-pm-floor-row { padding-left: 28px; }

/* Loading / empty states */
.bldg-pm-loading {
    font-size: 12px;
    color: #64748b;
    padding: 8px 10px;
    font-style: italic;
}
.bldg-pm-no-floors {
    font-size: 12px;
    color: #475569;
    padding: 6px 28px;
    font-style: italic;
}

/* Options section */
.bldg-pm-opts { display: flex; flex-direction: column; gap: 8px; }
.bldg-pm-opt {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 4px 0;
}
.bldg-pm-opt input[type="checkbox"] {
    width: 15px;
    height: 15px;
    flex-shrink: 0;
    accent-color: #3b82f6;
    cursor: pointer;
    margin: 0;
}
.bldg-pm-opt span { font-size: 13px; color: #e2e8f0; }

/* Footer */
.bldg-pm-ft {
    padding: 14px 20px;
    border-top: 1px solid rgba(148, 163, 184, 0.12);
    flex-shrink: 0;
}
.bldg-pm-ft-note {
    font-size: 11px;
    color: #64748b;
    margin: 0 0 10px;
}
.bldg-pm-ft-btns {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
}
.bldg-pm-cancel {
    background: transparent;
    border: 1px solid rgba(148, 163, 184, 0.28);
    border-radius: 7px;
    color: #94a3b8;
    font-size: 13px;
    padding: 7px 16px;
    cursor: pointer;
    transition: border-color 0.15s, color 0.15s;
}
.bldg-pm-cancel:hover { border-color: rgba(148,163,184,0.5); color: #e2e8f0; }
.bldg-pm-print {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #185FA5;
    border: none;
    border-radius: 7px;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    padding: 7px 18px;
    cursor: pointer;
    transition: background 0.15s;
}
.bldg-pm-print:hover { background: #1a6bbc; }
.bldg-pm-print:disabled { background: #1c3a5e; color: #64748b; cursor: not-allowed; }

/* ═══════════════════════════════════════════════════════════════════════════
   Buildings print area — hidden at screen, shown only @media print
   ═══════════════════════════════════════════════════════════════════════════ */
#dash-buildings-print-area { display: none; }

@media print {
    /* Hide every direct body child except the print area */
    body.dash-buildings-print-mode > *:not(#dash-buildings-print-area) { display: none !important; }
    body.dash-buildings-print-mode,
    body.dash-buildings-print-mode #dash-buildings-print-area {
        display: block !important;
        background: #fff !important;
        color: #000 !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    @page { margin: 2cm; }

    /* Document wrapper */
    .bldg-print-wrap { font-family: Georgia, "Times New Roman", serif; color: #000; background: #fff; width: 100%; }

    /* Doc header */
    .bldg-print-header { text-align: center; margin-bottom: 6pt; }
    .bldg-print-school { font-size: 11pt; font-weight: normal; color: #444; margin-bottom: 4pt; }
    .bldg-print-title  { font-size: 17pt; font-weight: bold; color: #000; margin-bottom: 4pt; }
    .bldg-print-date   { font-size: 9pt; color: #666; }
    .bldg-print-divider { border: none; border-top: 1.5pt solid #333; margin: 10pt 0; }

    /* Per-building section */
    .bldg-ps { margin-bottom: 18pt; }
    .bldg-ps-break { page-break-after: always; }
    .bldg-ps-name {
        font-size: 14pt;
        font-weight: bold;
        color: #000;
        margin: 0 0 4pt;
        padding-bottom: 4pt;
        border-bottom: 1pt solid #ccc;
    }
    .bldg-ps-summary {
        font-size: 9pt;
        color: #555;
        margin: 4pt 0 8pt;
    }
    .bldg-ps-sep { margin: 0 5pt; color: #999; }

    /* Floor table (inside each section) */
    .bldg-print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
        table-layout: fixed;
        margin-bottom: 6pt;
    }
    .bldg-print-table thead tr {
        background: #f0f0f0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .bldg-print-table th {
        border: 1pt solid #bbb;
        padding: 4pt 7pt;
        text-align: left;
        font-size: 9pt;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #333;
        background: #f0f0f0 !important;
    }
    .bldg-print-table th:first-child  { width: 55%; }
    .bldg-print-table th:nth-child(2) { width: 22%; text-align: center; }
    .bldg-print-table th:nth-child(3) { width: 23%; text-align: center; }
    .bldg-print-table td {
        border: 1pt solid #ddd;
        padding: 4pt 7pt;
        color: #000;
        vertical-align: middle;
    }
    .bldg-print-table td:nth-child(2),
    .bldg-print-table td:nth-child(3) { text-align: center; }
    .bldg-print-table .row-odd  { background: #fff !important; }
    .bldg-print-table .row-even { background: #fafafa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .bldg-print-table tr { page-break-inside: avoid; }

    /* Footer */
    .bldg-print-footer { margin-top: 14pt; font-size: 8pt; color: #888; text-align: center; }
}
</style>

<link rel="stylesheet" href="/School_Facility_Maintenance_System/frontend/assets/css/dashboard.inline.css?v=20260419-4">

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
let _lastDashboardStats = null;
const LARAVEL_API_BASE = '/School_Facility_Maintenance_System/api';
const DASHBOARD_MONTH_STORAGE_KEY = 'sfms:dashboardMonthSelection';
let selectedMonth = (new Date().getMonth() + 1);
let selectedYear = new Date().getFullYear();

function getMonthDateRange(year, month) {
    const pad = (n) => String(n).padStart(2, '0');
    const lastDay = new Date(year, month, 0).getDate(); // local last day of month
    return {
        dateFrom: `${year}-${pad(month)}-01`,
        dateTo:   `${year}-${pad(month)}-${pad(lastDay)}`,
    };
}

function normalizeMonthSelection(year, month) {
    const parsedYear = Number(year);
    const parsedMonth = Number(month);

    if (!Number.isInteger(parsedYear) || !Number.isInteger(parsedMonth)) {
        return null;
    }

    if (parsedMonth < 1 || parsedMonth > 12) {
        return null;
    }

    return {
        year: parsedYear,
        month: parsedMonth
    };
}

function loadDashboardMonthSelection() {
    try {
        const storedValue = localStorage.getItem(DASHBOARD_MONTH_STORAGE_KEY);
        if (!storedValue) {
            return;
        }

        const parsedValue = JSON.parse(storedValue);
        const normalized = normalizeMonthSelection(parsedValue.year, parsedValue.month);
        if (normalized) {
            selectedYear = normalized.year;
            selectedMonth = normalized.month;
        }
    } catch (error) {
        console.warn('Unable to load dashboard month selection', error);
    }
}

function saveDashboardMonthSelection(year, month) {
    const normalized = normalizeMonthSelection(year, month);
    if (!normalized) {
        return;
    }

    selectedYear = normalized.year;
    selectedMonth = normalized.month;

    try {
        localStorage.setItem(DASHBOARD_MONTH_STORAGE_KEY, JSON.stringify(normalized));
    } catch (error) {
        console.warn('Unable to persist dashboard month selection', error);
    }
}

window.addEventListener('sfms:monthSelected', (event) => {
    const detail = event && event.detail ? event.detail : {};
    const normalized = normalizeMonthSelection(detail.year, detail.month);

    if (!normalized) {
        return;
    }

    saveDashboardMonthSelection(normalized.year, normalized.month);
    initDashboard();
});

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

function getVisibleStatusTotal(chart) {
    const dataset = chart && chart.data && chart.data.datasets && chart.data.datasets[0] ? chart.data.datasets[0] : null;
    if (!dataset || !Array.isArray(dataset.data)) return 0;

    return dataset.data.reduce((sum, value, index) => {
        const isVisible = typeof chart.getDataVisibility === 'function' ? chart.getDataVisibility(index) : true;
        return isVisible ? sum + Number(value || 0) : sum;
    }, 0);
}

// ── Chart cursor helpers ─────────────────────────────────────────────────────
// chart-lite.js has no native onHover option, so cursor changes are driven by
// reading the chart instance's _hitRegions on mousemove.
// The _sfms*HoverAdded guard prevents duplicate listeners across theme re-inits.

function _addDoughnutHoverCursor(canvas, getChart) {
    if (canvas._sfmsDoughnutHoverAdded) return;
    canvas._sfmsDoughnutHoverAdded = true;
    canvas.addEventListener('mousemove', function (e) {
        const chart = getChart();
        const regions = chart && chart._hitRegions;
        if (!regions || !regions.length) { canvas.style.cursor = 'default'; return; }
        const first = regions[0];
        if (!first || first.type !== 'arc') { canvas.style.cursor = 'default'; return; }
        const rect = canvas.getBoundingClientRect();
        const dx = e.clientX - rect.left - first.cx;
        const dy = e.clientY - rect.top  - first.cy;
        const dist = Math.sqrt(dx * dx + dy * dy);
        canvas.style.cursor = (dist >= first.innerRadius && dist <= first.outerRadius) ? 'pointer' : 'default';
    });
    canvas.addEventListener('mouseleave', function () { canvas.style.cursor = 'default'; });
}

function _addBarHoverCursor(canvas, getChart) {
    if (canvas._sfmsBarHoverAdded) return;
    canvas._sfmsBarHoverAdded = true;
    canvas.addEventListener('mousemove', function (e) {
        const chart = getChart();
        const regions = chart && chart._hitRegions;
        if (!regions || !regions.length) { canvas.style.cursor = 'default'; return; }
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        const hit = regions.some(function (r) {
            return r.type === 'rect' && x >= r.x && x <= r.x + r.width && y >= r.y && y <= r.y + r.height;
        });
        canvas.style.cursor = hit ? 'pointer' : 'default';
    });
    canvas.addEventListener('mouseleave', function () { canvas.style.cursor = 'default'; });
}

function refreshDashboardChartsForTheme() {
    if (!_lastDashboardStats) { initDashboard(); return; }
    const stats = _lastDashboardStats;
    const isLightMode = document.documentElement.getAttribute('data-theme-resolved') === 'light';
    const chartMutedText = isLightMode ? '#374151' : '#94a3b8';
    const chartGridY = isLightMode ? 'rgba(17, 24, 39, 0.12)' : 'rgba(148, 163, 184, 0.35)';
    const chartGridX = isLightMode ? 'rgba(17, 24, 39, 0.08)' : 'rgba(148, 163, 184, 0.25)';
    const doughnutBorder = isLightMode ? '#ffffff' : '#0f172a';
    const submitted  = (stats.submitted || 0) + (stats.assigned || 0);
    const inProgress = stats.in_progress || 0;
    const completed  = stats.completed   || 0;
    const cancelled  = stats.cancelled   || 0;
    const priorityLow      = stats.by_priority?.low      || 0;
    const priorityMedium   = stats.by_priority?.medium   || 0;
    const priorityHigh     = stats.by_priority?.high     || 0;
    const priorityCritical = stats.by_priority?.critical || 0;

    if (reportsStatusChart)  { reportsStatusChart.destroy();  reportsStatusChart  = null; }
    if (reportsPriorityChart){ reportsPriorityChart.destroy(); reportsPriorityChart = null; }

    const statusCanvas = document.getElementById('reportsStatusChart');
    if (statusCanvas && typeof Chart !== 'undefined') {
        const statusCtx = statusCanvas.getContext('2d');
        const centerPlugin = {
            id: 'statusCenterTextPlugin',
            afterDatasetsDraw(chart) {
                const meta = chart.getDatasetMeta(0);
                if (!meta || !meta.data || !meta.data.length) return;
                const pt = meta.data[0];
                const ctx = chart.ctx;
                const light = document.documentElement.getAttribute('data-theme-resolved') === 'light';
                const totalValue = String(getVisibleStatusTotal(chart));
                const numberFontSize = totalValue.length >= 3 ? 24 : 28;
                const labelFontSize = 13;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = `700 ${numberFontSize}px "Segoe UI", sans-serif`;
                ctx.fillStyle = light ? '#111827' : '#ffffff';
                ctx.fillText(totalValue, pt.x, pt.y - 7);
                ctx.font = `500 ${labelFontSize}px "Segoe UI", sans-serif`;
                ctx.fillStyle = light ? '#374151' : '#cbd5e1';
                ctx.fillText('total', pt.x, pt.y + 15);
                ctx.restore();
            }
        };
        const sPlugins = [centerPlugin];
        if (typeof ChartDataLabels !== 'undefined') sPlugins.push(ChartDataLabels);
        reportsStatusChart = new Chart(statusCtx, {
            type: 'doughnut', plugins: sPlugins,
            data: { labels: ['Submitted', 'In Progress', 'Completed'], datasets: [{ data: [submitted, inProgress, completed], backgroundColor: ['#3b82f6', '#8b5cf6', '#10b981'], borderColor: doughnutBorder, borderWidth: 2, hoverOffset: 3, spacing: 2 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', radiusScale: 1.15,
                onClick: (e, items) => {
                    if (items.length > 0) {
                        e.stopPropagation();
                        const idx = items[0].index;
                        const statuses = ['submitted', 'in_progress', 'completed'];
                        window.location.href = window.SFMS_PUBLIC_URL('/frontend/pages/reports.php?status=' + statuses[idx]);
                    }
                },
                plugins: {
                datalabels: { display: (c) => Number(c.dataset.data[c.dataIndex] || 0) > 0, formatter: (v) => String(v), color: '#ffffff', font: { weight: '700', size: 13 }, anchor: 'center', align: 'center' },
                legend: {
                    position: 'right',
                    align: 'center',
                    labels: {
                        color: chartMutedText,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 10,
                        boxHeight: 10,
                        padding: 16,
                        font: {
                            size: 14,
                            weight: '600'
                        }
                    }
                },
                tooltip: { callbacks: { label: c => ` ${c.formattedValue} report${Number(c.formattedValue) !== 1 ? 's' : ''}` } }
            }}
        });
        _addDoughnutHoverCursor(statusCanvas, () => reportsStatusChart);
    }

    const priorityCanvas = document.getElementById('reportsPriorityChart');
    if (priorityCanvas && typeof Chart !== 'undefined') {
        const priorityCtx = priorityCanvas.getContext('2d');
        const pPlugins = [];
        if (typeof ChartDataLabels !== 'undefined') pPlugins.push(ChartDataLabels);
        reportsPriorityChart = new Chart(priorityCtx, {
            type: 'bar', plugins: pPlugins,
            data: { labels: ['Low', 'Medium', 'High', 'Critical'], datasets: [{ label: 'Reports', data: [priorityLow, priorityMedium, priorityHigh, priorityCritical], backgroundColor: ['#94a3b8', '#3b82f6', '#f59e0b', '#991b1b'], borderRadius: 6, clip: false, barThickness: 64, maxBarThickness: 72 }] },
            options: { responsive: true, maintainAspectRatio: false, layout: { padding: { top: 28 } },
                onClick: (e, items) => {
                    if (items.length > 0) {
                        e.stopPropagation();
                        const idx = items[0].index;
                        const priorities = ['low', 'medium', 'high', 'critical'];
                        window.location.href = window.SFMS_PUBLIC_URL('/frontend/pages/reports.php?priority=' + priorities[idx]);
                    }
                },
                scales: {
                    y: { beginAtZero: true, grace: '18%', ticks: { precision: 0, stepSize: 2, color: chartMutedText }, grid: { color: chartGridY, drawBorder: false } },
                    x: { ticks: { color: chartMutedText }, grid: { color: chartGridX, drawBorder: false } }
                },
                plugins: {
                    datalabels: { display: (c) => Number(c.dataset.data[c.dataIndex] || 0) > 0, formatter: (v) => String(v), color: () => document.documentElement.getAttribute('data-theme-resolved') === 'light' ? '#111827' : '#f87171', font: { weight: '700', size: 16 }, anchor: 'end', align: 'end', offset: 4, clamp: true },
                    legend: { display: false },
                    tooltip: { callbacks: { label: c => ` ${c.parsed.y} report${c.parsed.y !== 1 ? 's' : ''}` } }
                }
            }
        });
        _addBarHoverCursor(priorityCanvas, () => reportsPriorityChart);
    }
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
    fetch(window.SFMS_PUBLIC_URL(`/api/buildings/${buildingId}/floors`))
        .then(res => res.json())
        .then(data => {
            if (data.success && (data.data?.floors || data.floors)) {
                (data.data?.floors || data.floors).forEach(f => {
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

function navigateToReportsCard(cardKey) {
    if (cardKey === 'low_stock') {
        window.location.href = '/School_Facility_Maintenance_System/frontend/pages/inventory.php?status_filter=low_stock';
        return;
    }

    const targetUrl = new URL('/School_Facility_Maintenance_System/frontend/pages/reports.php', window.location.origin);

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
    fetch(window.SFMS_PUBLIC_URL('/api/buildings'))
        .then(res => res.json())
        .then(data => {
            const buildings = data.data?.buildings || data.buildings || [];
            if (data.success && buildings.length > 0) {
                buildings.forEach(building => {
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
        notice.innerHTML = '⚠️ No buildings found. <a href="/School_Facility_Maintenance_System/frontend/pages/buildings-overview.php" style="color: #ffb3b3; text-decoration: underline;">Add a building</a> first.';
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
        const response = await fetch(window.SFMS_PUBLIC_URL('/api/buildings'), {
            method: 'POST',
            credentials: 'include',
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
        const response = await fetch(window.SFMS_PUBLIC_URL('/api/rooms'), {
            method: 'POST',
            credentials: 'include',
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

function buildChartStatsFromPayload(payload) {
    const statusStats = {
        submitted: 0,
        in_progress: 0,
        completed: 0
    };
    const priorityStats = {
        low: 0,
        medium: 0,
        high: 0,
        critical: 0
    };

    const statusChart = payload && payload.statusChart ? payload.statusChart : null;
    if (statusChart && Array.isArray(statusChart.labels) && Array.isArray(statusChart.data)) {
        statusChart.labels.forEach((label, index) => {
            const key = String(label || '').toLowerCase().replace(/\s+/g, '_');
            if (Object.prototype.hasOwnProperty.call(statusStats, key)) {
                statusStats[key] = Number(statusChart.data[index] || 0);
            }
        });
    }

    const priorityChart = payload && payload.priorityChart ? payload.priorityChart : null;
    if (priorityChart && Array.isArray(priorityChart.labels) && Array.isArray(priorityChart.data)) {
        priorityChart.labels.forEach((label, index) => {
            const key = String(label || '').toLowerCase();
            if (Object.prototype.hasOwnProperty.call(priorityStats, key)) {
                priorityStats[key] = Number(priorityChart.data[index] || 0);
            }
        });
    }

    return {
        submitted: statusStats.submitted,
        in_progress: statusStats.in_progress,
        completed: statusStats.completed,
        total: statusStats.submitted + statusStats.in_progress + statusStats.completed,
        by_priority: priorityStats
    };
}

async function fetchDashboardStats() {
    const zero = {
        total: 0, reports_today: 0, submitted: 0, assigned: 0,
        in_progress: 0, completed: 0, closed: 0, cancelled: 0,
        low_stock: 0,
        by_priority: { low: 0, medium: 0, high: 0, critical: 0 },
    };

    // Attempt 1: fast totals from the dedicated stats endpoint.
    let apiStats = null;
    try {
        const statsResp = await fetch(window.SFMS_PUBLIC_URL('/api/dashboard/stats'), {
            credentials: 'include', cache: 'no-store',
        });
        const statsJson = await statsResp.json();
        console.log('[Dashboard] stats API status:', statsResp.status);
        console.log('[Dashboard] stats API response:', JSON.stringify(statsJson));
        if (statsJson.success && statsJson.data) {
            apiStats = statsJson.data;
        }
    } catch (e) { console.error('[Dashboard] stats API error:', e); }

    // Attempt 2: detailed breakdown from the report list.
    // Needed for assigned / closed / cancelled / by_priority / reports_today.
    let derived = null;
    try {
        const reportsResp = await fetch(window.SFMS_PUBLIC_URL('/api/reports?per_page=200'), {
            credentials: 'include', cache: 'no-store',
        });
        const reportsJson = await reportsResp.json();
        console.log('[Dashboard] reports API status:', reportsResp.status);
        console.log('[Dashboard] reports API response:', JSON.stringify(reportsJson));
        if (reportsJson.success) {
            derived = buildStatsFromReports(extractReportsFromResponse(reportsJson));
        }
    } catch (e) { console.error('[Dashboard] reports API error:', e); }

    if (!apiStats && !derived) {
        console.warn('[Dashboard] both API calls failed — returning zeros');
        return zero;
    }

    // Merge: API stats for totals/low_stock; derived for fine-grained status split.
    const merged = {
        total:         apiStats ? Number(apiStats.total_reports || 0)  : (derived?.total        || 0),
        reports_today: derived  ? (derived.reports_today        || 0)  : Number(apiStats?.reports_today || 0),
        submitted:     derived  ? (derived.submitted            || 0)  : Number(apiStats?.pending       || 0),
        assigned:      derived  ? (derived.assigned             || 0)  : 0,
        in_progress:   apiStats ? Number(apiStats.in_progress   || 0)  : (derived?.in_progress  || 0),
        completed:     apiStats ? Number(apiStats.completed     || 0)  : (derived?.completed    || 0),
        closed:        derived  ? (derived.closed               || 0)  : 0,
        cancelled:     derived  ? (derived.cancelled            || 0)  : 0,
        low_stock:     apiStats ? Number(apiStats.low_stock     || 0)  : 0,
        by_priority:   derived?.by_priority || { low: 0, medium: 0, high: 0, critical: 0 },
    };
    console.log('[Dashboard] final merged stats:', JSON.stringify(merged));
    return merged;
}

async function fetchMonthlyChartStats(year = selectedYear, month = selectedMonth) {
    const normalized = normalizeMonthSelection(year, month) || {
        year: selectedYear,
        month: selectedMonth
    };

    const zero = {
        submitted: 0, assigned: 0, in_progress: 0, completed: 0, cancelled: 0, total: 0,
        by_priority: { low: 0, medium: 0, high: 0, critical: 0 }
    };

    try {
        const { dateFrom, dateTo } = getMonthDateRange(normalized.year, normalized.month);
        const response = await fetch(
            window.SFMS_PUBLIC_URL(`/api/reports?per_page=200&date_from=${dateFrom}&date_to=${dateTo}`),
            { credentials: 'include', cache: 'no-store' }
        );
        const payload = await response.json();

        if (!payload.success) {
            return zero;
        }

        const stats = buildStatsFromReports(extractReportsFromResponse(payload));
        return {
            submitted:   stats.submitted   || 0,
            assigned:    stats.assigned    || 0,
            in_progress: stats.in_progress || 0,
            completed:   stats.completed   || 0,
            cancelled:   stats.cancelled   || 0,
            total: (stats.submitted || 0) + (stats.assigned || 0) + (stats.in_progress || 0) + (stats.completed || 0),
            by_priority: stats.by_priority || { low: 0, medium: 0, high: 0, critical: 0 }
        };
    } catch (error) {
        console.error('Error loading monthly chart stats:', error);
        return zero;
    }
}

function initializeClickableCards() {
    document.querySelectorAll('.stat-card-clickable, .chart-card-clickable').forEach((card) => {
        const navigate = () => {
            const target = card.getAttribute('data-href');
            if (target) {
                window.location.href = target;
            }
        };

        card.addEventListener('click', navigate);
        card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                navigate();
            }
        });
    });
}

// ── Buildings Overview ──────────────────────────────────────────────────────
let _dashBuildingsData = [];   // cache for print

async function loadDashboardBuildings() {
    const grid    = document.getElementById('dash-buildings-grid');
    const viewAll = document.getElementById('dash-buildings-viewall');
    if (!grid) return;

    const overviewUrl = window.SFMS_PUBLIC_URL ? window.SFMS_PUBLIC_URL('/buildings-overview') : '/buildings-overview';
    if (viewAll) viewAll.href = overviewUrl;

    try {
        const res  = await fetch(window.SFMS_PUBLIC_URL('/api/buildings?per_page=12'), { credentials: 'same-origin' });
        const data = await res.json();

        if (!data.success) {
            grid.innerHTML = '<span style="font-size:13px;color:#64748b;">No buildings found.</span>';
            return;
        }

        const buildings = (data.data && Array.isArray(data.data.buildings))
            ? data.data.buildings
            : (Array.isArray(data.buildings) ? data.buildings : []);

        if (buildings.length === 0) {
            grid.innerHTML = '<span style="font-size:13px;color:#64748b;">No buildings yet.</span>';
            return;
        }

        _dashBuildingsData = buildings; // cache for print

        grid.innerHTML = buildings.map((b) => {
            const name     = String(b.name || 'Unnamed').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            const floors   = Number(b.floor_count  || 0);
            const rooms    = Number(b.room_count   || 0);
            const floorTxt = floors === 1 ? '1 floor'  : `${floors} floors`;
            const roomTxt  = rooms  === 1 ? '1 room'   : `${rooms} rooms`;
            return `<div
                style="background:rgba(56,189,248,0.07);border:1px solid rgba(56,189,248,0.18);border-radius:10px;padding:10px 12px;cursor:pointer;transition:background 0.15s;"
                onmouseenter="this.style.background='rgba(56,189,248,0.13)'"
                onmouseleave="this.style.background='rgba(56,189,248,0.07)'"
                onclick="window.location.href='${overviewUrl}'">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                        <rect x="2" y="7" width="20" height="15" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/>
                    </svg>
                    <span style="font-size:13px;font-weight:600;color:#f8fafc;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${name}</span>
                </div>
                <div style="font-size:11px;color:#94a3b8;">${floorTxt} · ${roomTxt}</div>
            </div>`;
        }).join('');
    } catch (err) {
        console.warn('Buildings overview load failed:', err);
        grid.innerHTML = '<span style="font-size:13px;color:#64748b;">Could not load buildings.</span>';
    }
}

// ── Buildings Overview Print Modal ──────────────────────────────────────────

const _bldgPm = {
    selectedBuildings: new Set(),
    selectedFloors:    {},   // { [buildingId]: Set<floorId> }
    floorCache:        {},   // { [buildingId]: floor[] }
    loadingSet:        new Set(),
};

function _bldgPmEsc(v) {
    return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function openBldgPrintModal() {
    if (!_dashBuildingsData.length) return;

    // Reset state — all buildings selected by default
    _bldgPm.selectedBuildings = new Set(_dashBuildingsData.map((b) => b.id));
    _bldgPm.selectedFloors    = {};
    _bldgPm.loadingSet        = new Set();

    _bldgPmRenderBuildings();
    _bldgPmRenderFloors();

    // Lazily fetch floors for each building
    _dashBuildingsData.forEach((b) => _bldgPmFetchFloors(b.id));

    const modal = document.getElementById('bldg-print-modal');
    modal.hidden = false;
    modal.removeAttribute('aria-hidden');
    document.body.style.overflow = 'hidden';
}

function closeBldgPrintModal() {
    const modal = document.getElementById('bldg-print-modal');
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

async function _bldgPmFetchFloors(buildingId) {
    if (_bldgPm.floorCache[buildingId] !== undefined || _bldgPm.loadingSet.has(buildingId)) return;
    _bldgPm.loadingSet.add(buildingId);
    _bldgPmRenderFloors();

    try {
        const res     = await fetch(window.SFMS_PUBLIC_URL(`/api/buildings/${buildingId}/floors`), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
        });
        const payload = await res.json();
        const floors  = Array.isArray(payload?.data?.floors) ? payload.data.floors : [];
        _bldgPm.floorCache[buildingId]    = floors;
        _bldgPm.selectedFloors[buildingId] = new Set(floors.map((f) => f.id));
    } catch (_) {
        _bldgPm.floorCache[buildingId] = [];
    }

    _bldgPm.loadingSet.delete(buildingId);
    _bldgPmRenderFloors();
}

function _bldgPmRenderBuildings() {
    const container = document.getElementById('bldg-pm-blist');
    if (!container) return;

    if (!_dashBuildingsData.length) {
        container.innerHTML = '<p style="font-size:13px;color:var(--muted-text);margin:0;">No buildings available.</p>';
        return;
    }

    container.innerHTML = _dashBuildingsData.map((b) => {
        const checked = _bldgPm.selectedBuildings.has(b.id) ? 'checked' : '';
        return `<label class="bldg-pm-check-row">
            <input type="checkbox" ${checked} onchange="bldgPmToggleBuilding(${b.id}, this.checked)">
            <span class="bldg-pm-check-label">${_bldgPmEsc(b.name)}</span>
            <span class="bldg-pm-check-meta">${Number(b.floor_count||0)} floors · ${Number(b.room_count||0)} rooms</span>
        </label>`;
    }).join('');
}

function _bldgPmRenderFloors() {
    const container = document.getElementById('bldg-pm-flist');
    if (!container) return;

    const selectedBldgs = _dashBuildingsData.filter((b) => _bldgPm.selectedBuildings.has(b.id));

    if (selectedBldgs.length === 0) {
        container.innerHTML = '<p style="font-size:13px;color:var(--muted-text);margin:0;font-style:italic;">Select at least one building above to choose floors.</p>';
        return;
    }

    const multiBuilding = selectedBldgs.length > 1;
    let html = '';

    selectedBldgs.forEach((b) => {
        const isLoading = _bldgPm.loadingSet.has(b.id);
        const floors    = _bldgPm.floorCache[b.id];

        if (multiBuilding) {
            const allSelected = floors && floors.length > 0 &&
                floors.every((f) => _bldgPm.selectedFloors[b.id]?.has(f.id));
            const groupChecked = (floors && floors.length > 0 && allSelected) ? 'checked' : '';

            html += `<div class="bldg-pm-group" id="bldg-pm-group-${b.id}">
                <div class="bldg-pm-group-hd" onclick="bldgPmToggleGroup(${b.id})">
                    <svg class="bldg-pm-group-chevron" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    <label class="bldg-pm-check-row bldg-pm-group-lbl" onclick="event.stopPropagation()">
                        <input type="checkbox" ${groupChecked} onchange="bldgPmGroupToggleFloors(${b.id}, this.checked)">
                        <span class="bldg-pm-check-label" style="font-weight:600;">${_bldgPmEsc(b.name)}</span>
                    </label>
                </div>
                <div class="bldg-pm-group-rows">`;
        }

        if (isLoading) {
            html += `<p class="${multiBuilding ? 'bldg-pm-floor-row' : ''}" style="font-size:13px;color:var(--muted-text);margin:4px 0;font-style:italic;">Loading floors…</p>`;
        } else if (!floors || floors.length === 0) {
            html += `<p class="${multiBuilding ? 'bldg-pm-floor-row' : ''}" style="font-size:13px;color:var(--muted-text);margin:4px 0;font-style:italic;">No floors found.</p>`;
        } else {
            floors.forEach((f) => {
                const checked = _bldgPm.selectedFloors[b.id]?.has(f.id) ? 'checked' : '';
                html += `<label class="bldg-pm-check-row ${multiBuilding ? 'bldg-pm-floor-row' : ''}">
                    <input type="checkbox" ${checked} onchange="bldgPmToggleFloor(${b.id}, ${f.id}, this.checked)">
                    <span class="bldg-pm-check-label">${_bldgPmEsc(f.name)}</span>
                    <span class="bldg-pm-check-meta">${Number(f.room_count||0)} rooms</span>
                </label>`;
            });
        }

        if (multiBuilding) {
            html += `</div></div>`;
        }
    });

    container.innerHTML = html;
}

function bldgPmToggleBuilding(buildingId, checked) {
    if (checked) {
        _bldgPm.selectedBuildings.add(buildingId);
        _bldgPmFetchFloors(buildingId);
    } else {
        _bldgPm.selectedBuildings.delete(buildingId);
    }
    _bldgPmRenderFloors();
}

function bldgPmToggleFloor(buildingId, floorId, checked) {
    if (!_bldgPm.selectedFloors[buildingId]) {
        _bldgPm.selectedFloors[buildingId] = new Set();
    }
    if (checked) {
        _bldgPm.selectedFloors[buildingId].add(floorId);
    } else {
        _bldgPm.selectedFloors[buildingId].delete(floorId);
    }
}

function bldgPmToggleAllBuildings(checked) {
    if (checked) {
        _dashBuildingsData.forEach((b) => {
            _bldgPm.selectedBuildings.add(b.id);
            _bldgPmFetchFloors(b.id);
        });
    } else {
        _bldgPm.selectedBuildings.clear();
    }
    _bldgPmRenderBuildings();
    _bldgPmRenderFloors();
}

function bldgPmGroupToggleFloors(buildingId, checked) {
    const floors = _bldgPm.floorCache[buildingId] || [];
    if (!_bldgPm.selectedFloors[buildingId]) {
        _bldgPm.selectedFloors[buildingId] = new Set();
    }
    if (checked) {
        floors.forEach((f) => _bldgPm.selectedFloors[buildingId].add(f.id));
    } else {
        _bldgPm.selectedFloors[buildingId].clear();
    }
    _bldgPmRenderFloors();
}

function bldgPmToggleGroup(buildingId) {
    const groupEl = document.getElementById(`bldg-pm-group-${buildingId}`);
    if (groupEl) groupEl.classList.toggle('is-collapsed');
}

function executeBldgPrint() {
    const printArea = document.getElementById('dash-buildings-print-area');
    if (!printArea) return;

    const includeFloorDetail = document.getElementById('bldg-opt-floor-detail')?.checked ?? true;
    const includeSummary     = document.getElementById('bldg-opt-summary')?.checked ?? true;
    const showDate           = document.getElementById('bldg-opt-date')?.checked ?? true;
    const pageBreaks         = document.getElementById('bldg-opt-breaks')?.checked ?? true;

    const dateStr = new Date().toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' });
    const esc     = _bldgPmEsc;

    const selectedBldgs = _dashBuildingsData.filter((b) => _bldgPm.selectedBuildings.has(b.id));

    if (selectedBldgs.length === 0) {
        alert('Please select at least one building to print.');
        return;
    }

    let sectionsHtml = '';

    selectedBldgs.forEach((b, idx) => {
        const isLast   = idx === selectedBldgs.length - 1;
        const addBreak = pageBreaks && !isLast;
        const floors   = (_bldgPm.floorCache[b.id] || []).filter((f) => _bldgPm.selectedFloors[b.id]?.has(f.id));

        let sec = `<div class="bldg-ps${addBreak ? ' bldg-ps-break' : ''}">`;
        sec += `<div class="bldg-ps-name">${esc(b.name)}</div>`;

        if (includeSummary) {
            const totalFloorCount = b.floor_count || 0;
            const floorLabel = floors.length !== totalFloorCount
                ? `${floors.length} of ${totalFloorCount}` : String(totalFloorCount);
            const totalRooms = floors.reduce((s, f) => s + Number(f.room_count||0), 0);
            sec += `<div class="bldg-ps-summary">${floorLabel} floor${Number(totalFloorCount) !== 1 ? 's' : ''} · ${totalRooms} room${totalRooms !== 1 ? 's' : ''}</div>`;
        }

        if (includeFloorDetail) {
            if (floors.length === 0) {
                sec += `<p class="bldg-ps-no-floors">No floors selected for this building.</p>`;
            } else {
                sec += `<table class="bldg-print-table"><thead><tr><th>Floor</th><th>Rooms</th><th>Items</th></tr></thead><tbody>`;
                floors.forEach((f, fi) => {
                    sec += `<tr class="${fi % 2 === 0 ? 'row-even' : 'row-odd'}">
                        <td>${esc(f.name)}</td>
                        <td style="text-align:center;">${Number(f.room_count||0)}</td>
                        <td style="text-align:center;">${Number(f.item_count||0)}</td>
                    </tr>`;
                });
                sec += `</tbody></table>`;
            }
        }

        sec += `</div>`;
        sectionsHtml += sec;
    });

    const grandFloors = selectedBldgs.reduce((s, b) => {
        return s + (_bldgPm.floorCache[b.id] || []).filter((f) => _bldgPm.selectedFloors[b.id]?.has(f.id)).length;
    }, 0);
    const grandRooms = selectedBldgs.reduce((s, b) => {
        const fl = (_bldgPm.floorCache[b.id] || []).filter((f) => _bldgPm.selectedFloors[b.id]?.has(f.id));
        return s + fl.reduce((fs, f) => fs + Number(f.room_count||0), 0);
    }, 0);

    printArea.innerHTML = `
        <div class="bldg-print-wrap">
            <div class="bldg-print-header">
                <div class="bldg-print-school">PHILCST Centralized School Facility Maintenance Reporting System</div>
                <div class="bldg-print-title">Buildings Overview Report</div>
                ${showDate ? `<div class="bldg-print-date">Generated: ${esc(dateStr)}</div>` : ''}
            </div>
            <hr class="bldg-print-divider">
            <div class="bldg-print-summary">
                Buildings: <strong>${selectedBldgs.length}</strong>
                &nbsp;&nbsp;·&nbsp;&nbsp;
                Floors: <strong>${grandFloors}</strong>
                &nbsp;&nbsp;·&nbsp;&nbsp;
                Rooms: <strong>${grandRooms}</strong>
            </div>
            ${sectionsHtml}
            <div class="bldg-print-footer">SFMS · Buildings Overview · ${esc(dateStr)}</div>
        </div>`;

    closeBldgPrintModal();

    document.body.classList.add('dash-buildings-print-mode');
    const cleanup = () => {
        document.body.classList.remove('dash-buildings-print-mode');
        window.removeEventListener('afterprint', cleanup);
    };
    window.addEventListener('afterprint', cleanup);

    setTimeout(() => window.print(), 50);
}

// Populate stats and chart
async function initDashboard() {
    try {
        loadDashboardMonthSelection();
        const stats      = await fetchDashboardStats();
        const chartStats = await fetchMonthlyChartStats(selectedYear, selectedMonth);

        // Charts always show the selected month only — no all-time fallback.
        // When the month has no reports the chart renders an empty ring,
        // which is correct ("no reports this month").
        // Stat cards are populated from `stats` (all-time) below and are unaffected.
        const effectiveChartStats = chartStats;

        // Update chart month badge labels
        const monthLabel = new Date(selectedYear, selectedMonth - 1, 1)
                .toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        const statusMonthEl   = document.getElementById('status-chart-month');
        const priorityMonthEl = document.getElementById('priority-chart-month');
        if (statusMonthEl)   statusMonthEl.textContent   = monthLabel;
        if (priorityMonthEl) priorityMonthEl.textContent = monthLabel;

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
        _lastDashboardStats = effectiveChartStats;

        // Render chart separately so card values do not fail if chart has issues.
        console.log('[Dashboard] Chart data:', {
            submitted:   effectiveChartStats.submitted,
            assigned:    effectiveChartStats.assigned,
            in_progress: effectiveChartStats.in_progress,
            completed:   effectiveChartStats.completed,
            total:       effectiveChartStats.total,
            by_priority: effectiveChartStats.by_priority,
        });
        try {
            if (typeof Chart !== 'undefined') {
                const statusCanvas = document.getElementById('reportsStatusChart');
                if (statusCanvas) {
                    const statusCtx = statusCanvas.getContext('2d');
                    if (reportsStatusChart) reportsStatusChart.destroy();

                    const statusCenterTextPlugin = {
                        id: 'statusCenterTextPlugin',
                        afterDatasetsDraw(chart) {
                            const meta = chart.getDatasetMeta(0);
                            if (!meta || !meta.data || !meta.data.length) {
                                return;
                            }
                            const point = meta.data[0];
                            const x = point.x;
                            const y = point.y;
                            const ctx = chart.ctx;
                            // Dynamic color for center text
                            const isLightMode = document.documentElement.getAttribute('data-theme-resolved') === 'light';
                            const centerTextColor = isLightMode ? '#111827' : '#fff';
                            const centerMutedColor = isLightMode ? '#374151' : '#cbd5e1';
                            ctx.save();
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.font = '700 28px "Segoe UI", sans-serif';
                            ctx.fillStyle = centerTextColor;
                            ctx.fillText(String(getVisibleStatusTotal(chart)), x, y - 7);
                            ctx.font = '500 13px "Segoe UI", sans-serif';
                            ctx.fillStyle = centerMutedColor;
                            ctx.fillText('total', x, y + 15);
                            ctx.restore();
                        }
                    };

                    const statusChartPlugins = [statusCenterTextPlugin];
                    if (typeof ChartDataLabels !== 'undefined') {
                        statusChartPlugins.push(ChartDataLabels);
                    }

                    reportsStatusChart = new Chart(statusCtx, {
                        type: 'doughnut',
                        plugins: statusChartPlugins,
                        data: {
                            labels: ['Submitted', 'In Progress', 'Completed'],
                            datasets: [{
                                data: [(effectiveChartStats.submitted || 0) + (effectiveChartStats.assigned || 0), effectiveChartStats.in_progress || 0, effectiveChartStats.completed || 0],
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
                            radiusScale: 1.15,
                            onClick: (e, items) => {
                                if (items.length > 0) {
                                    e.stopPropagation();
                                    const idx = items[0].index;
                                    const statuses = ['submitted', 'in_progress', 'completed'];
                                    window.location.href = window.SFMS_PUBLIC_URL('/frontend/pages/reports.php?status=' + statuses[idx]);
                                }
                            },
                            plugins: {
                                datalabels: {
                                    display: (context) => Number(context.dataset.data[context.dataIndex] || 0) > 0,
                                    formatter: (value) => String(value),
                                    color: '#ffffff',
                                    font: {
                                        weight: '700',
                                        size: 13
                                    },
                                    anchor: 'center',
                                    align: 'center'
                                },
                                legend: {
                                    position: 'right',
                                    align: 'center',
                                    labels: {
                                        color: chartMutedText,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        boxWidth: 10,
                                        boxHeight: 10,
                                        padding: 16,
                                        font: {
                                            size: 14,
                                            weight: '600'
                                        }
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
                    _addDoughnutHoverCursor(statusCanvas, () => reportsStatusChart);
                }

                const priorityCanvas = document.getElementById('reportsPriorityChart');
                if (priorityCanvas) {
                    const priorityCtx = priorityCanvas.getContext('2d');
                    if (reportsPriorityChart) reportsPriorityChart.destroy();
                    const priorityChartPlugins = [];
                    if (typeof ChartDataLabels !== 'undefined') {
                        priorityChartPlugins.push(ChartDataLabels);
                    }

                    reportsPriorityChart = new Chart(priorityCtx, {
                        type: 'bar',
                        plugins: priorityChartPlugins,
                        data: {
                            labels: ['Low', 'Medium', 'High', 'Critical'],
                            datasets: [{
                                label: 'Reports',
                                data: [
                                    effectiveChartStats.by_priority?.low      || 0,
                                    effectiveChartStats.by_priority?.medium   || 0,
                                    effectiveChartStats.by_priority?.high     || 0,
                                    effectiveChartStats.by_priority?.critical || 0
                                ],
                                backgroundColor: ['#94a3b8', '#3b82f6', '#f59e0b', '#991b1b'],
                                borderRadius: 6,
                                clip: false,
                                barThickness: 64,
                                maxBarThickness: 72
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    top: 28
                                }
                            },
                            onClick: (e, items) => {
                                if (items.length > 0) {
                                    e.stopPropagation();
                                    const idx = items[0].index;
                                    const priorities = ['low', 'medium', 'high', 'critical'];
                                    window.location.href = window.SFMS_PUBLIC_URL('/frontend/pages/reports.php?priority=' + priorities[idx]);
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grace: '18%',
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
                                datalabels: {
                                    display: (context) => Number(context.dataset.data[context.dataIndex] || 0) > 0,
                                    formatter: (value) => String(value),
                                    color: () => {
                                        const isLightMode = document.documentElement.getAttribute('data-theme-resolved') === 'light';
                                        return isLightMode ? '#111827' : '#f87171';
                                    },
                                    font: {
                                        weight: '700',
                                        size: 16
                                    },
                                    anchor: 'end',
                                    align: 'end',
                                    offset: 4,
                                    clamp: true
                                },
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: c => ` ${c.parsed.y} report${c.parsed.y !== 1 ? 's' : ''}`
                                    }
                                }
                            }
                        }
                    });
                    _addBarHoverCursor(priorityCanvas, () => reportsPriorityChart);
                }
            }
            console.log('[Dashboard] Status chart canvas:', document.getElementById('reportsStatusChart'));
            console.log('[Dashboard] Priority chart canvas:', document.getElementById('reportsPriorityChart'));
        } catch (chartErr) {
            console.error('Chart render error:', chartErr);
        }

    } catch (err) {
        console.error('Error initializing dashboard:', err);
        document.getElementById('stat-total').textContent = '0';
        const todayEl = document.getElementById('stat-today');
        if (todayEl) todayEl.textContent = '0';
    }

    console.log('[initDashboard] completed — effectiveChartStats:', _lastDashboardStats);

    await Promise.all([
        renderRecentActivity(),
    ]);
}

async function renderRecentActivity() {
    const list = document.getElementById('recentActivity');
    try {
        const response = await fetch(window.SFMS_PUBLIC_URL('/api/reports/recent?limit=20'), {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (!data.success) {
            list.innerHTML = '<p class="text-muted">No recent reports.</p>';
            return;
        }
        
        const reports = extractReportsFromResponse(data);
        
        if (reports.length === 0) {
            list.innerHTML = '<div class="recent-reports-empty">No reports today.</div>';
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
        
        const formatLabel = (value) => String(value || '')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, (char) => char.toUpperCase());

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');

        let rows = '';
        reports.forEach(report => {
            const date = new Date(report.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const status = (report.status || 'submitted').toLowerCase();
            const priority = (report.priority || 'medium').toLowerCase();
            const statusClass = statusClassMap[status] || 'status-submitted';
            const priorityClass = priorityClassMap[priority] || 'priority-medium';
            const reportTitle = report.title || 'Untitled report';
            const statusLabel = formatLabel(status);
            const priorityLabel = formatLabel(priority);
            const reportLocation = report.location || 'No location provided';
            const reportComment = (report.description || '').trim() || 'No comment provided';
            rows += `
                <div class="recent-report-table-row">
                    <div class="recent-report-cell recent-report-main">
                        <div class="recent-report-title">${escapeHtml(reportTitle)}</div>
                        <div class="recent-report-meta">${escapeHtml(reportLocation)}</div>
                    </div>
                    <div class="recent-report-cell recent-report-date">${escapeHtml(date)}</div>
                    <div class="recent-report-cell recent-report-comment" title="${escapeHtml(reportComment)}">${escapeHtml(reportComment)}</div>
                    <div class="recent-report-cell recent-report-pill-cell">
                        <span class="status-badge ${priorityClass}">${escapeHtml(priorityLabel)}</span>
                    </div>
                    <div class="recent-report-cell recent-report-pill-cell">
                        <span class="status-badge ${statusClass}">${escapeHtml(statusLabel)}</span>
                    </div>
                </div>
            `;
        });
        list.innerHTML = `
            <div class="recent-reports-table">
                <div class="recent-reports-table-head">
                    <div>Report</div>
                    <div>Date</div>
                    <div>Comment</div>
                    <div>Priority</div>
                    <div>Status</div>
                </div>
                <div class="recent-reports-table-body">
                    ${rows}
                </div>
            </div>
        `;
    } catch (err) {
        console.error('Failed to load recent reports:', err);
        list.innerHTML = '<div class="recent-reports-empty">Could not load recent reports.</div>';
    }
}


// Load buildings for room dropdown
function loadBuildingsForDropdown() {
    const buildingSelect = document.getElementById('buildingSelect');
    if (!buildingSelect) return;
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
    // Wait for Chart.js to be available before initializing dashboard
    function waitForChartAndInit() {
        if (typeof Chart !== 'undefined') {
            initDashboard();
        } else {
            setTimeout(waitForChartAndInit, 50);
        }
    }
    waitForChartAndInit();

    const root = document.documentElement;
    const themeObserver = new MutationObserver((mutations) => {
        const changedTheme = mutations.some((mutation) => mutation.type === 'attributes' && mutation.attributeName === 'data-theme-resolved');
        if (changedTheme) {
            refreshDashboardChartsForTheme();
        }
    });
    themeObserver.observe(root, { attributes: true, attributeFilter: ['data-theme-resolved'] });

    initializeClickableCards();

    // Dismiss buildings print modal on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('bldg-print-modal');
            if (modal && !modal.hidden) closeBldgPrintModal();
        }
    });
});
</script>


