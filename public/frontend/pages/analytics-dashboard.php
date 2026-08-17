<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

if (!isset($_SESSION['user']) && !isset($_SESSION['auth_user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$_anUser = $_SESSION['user'] ?? $_SESSION['auth_user'] ?? [];
$_anRole = strtolower(trim((string)($_anUser['role'] ?? '')));
if ($_anRole !== 'super_admin' && $_anRole !== 'maintenance_admin' && $_anRole !== 'maintenance_staff') {
    $_anRole = 'maintenance_staff';
}

$pageTitle = 'Analytics Dashboard - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<style media="print">
    aside, nav, .sidebar, [class*="sidebar"], header { display: none !important; }
    .an-tabs, .an-filter-row, #an-print-btn          { display: none !important; }
    .an-tab-content                                  { display: block !important; page-break-inside: avoid; margin-bottom: 24px; }
    main, .container                                 { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .card                                            { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
</style>

<main class="container" style="margin-top:16px;">
    <div class="card" id="an-analytics-card" style="border-left:3px solid var(--primary-color);margin-top:0;">
        <div class="card-header d-flex justify-between align-center" style="border-bottom:0.5px solid var(--border);padding:20px 24px 16px;">
            <div>
                <h2 style="font-size:18px;font-weight:500;margin:0 0 2px;">Analytics Dashboard</h2>
                <p class="text-muted mb-0" style="font-size:13px;">Inventory health, damage trends, semester comparisons, and dispatch reports.</p>
            </div>
        </div>
        <div class="card-body" style="padding:18px 24px 26px;">

            <!-- Tab buttons -->
            <div class="an-tabs" style="display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:20px;">
                <button type="button" class="an-tab-btn" data-tab="an-tab1"
                        style="background:transparent;border:none;border-bottom:2px solid transparent;padding:10px 18px;cursor:pointer;font-size:14px;font-weight:400;color:var(--muted-text);">
                    Overview
                </button>
                <button type="button" class="an-tab-btn" data-tab="an-tab2"
                        style="background:transparent;border:none;border-bottom:2px solid transparent;padding:10px 18px;cursor:pointer;font-size:14px;font-weight:400;color:var(--muted-text);">
                    Damage Analytics
                </button>
                <button type="button" class="an-tab-btn" data-tab="an-tab3"
                        style="background:transparent;border:none;border-bottom:2px solid transparent;padding:10px 18px;cursor:pointer;font-size:14px;font-weight:400;color:var(--muted-text);">
                    Semester Comparison
                </button>
                <button type="button" class="an-tab-btn" data-tab="an-tab4"
                        style="background:transparent;border:none;border-bottom:2px solid transparent;padding:10px 18px;cursor:pointer;font-size:14px;font-weight:400;color:var(--muted-text);">
                    Dispatch &amp; Repair
                </button>
            </div>

            <!-- ─── TAB 1: Overview ──────────────────────────────────────── -->
            <div id="an-tab1" class="an-tab-content">

                <p style="font-size:14px;font-weight:500;margin:0 0 10px;">Inventory Health</p>
                <div class="an-metric-grid">

                    <!-- Total Items — blue -->
                    <div class="an-metric-card" style="border:1px solid rgba(37,99,235,0.2);background:linear-gradient(135deg,rgba(37,99,235,0.06) 0%,transparent 60%);">
                        <div style="width:48px;height:48px;border-radius:12px;background:rgba(37,99,235,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--muted-text);text-transform:uppercase;letter-spacing:.6px;font-weight:500;margin-bottom:4px;">Total Items</div>
                            <div id="an-h-total" style="font-size:32px;font-weight:700;color:#1d4ed8;line-height:1;">—</div>
                            <div style="font-size:11px;color:var(--muted-text);margin-top:4px;">In inventory</div>
                        </div>
                    </div>

                    <!-- Low Stock — orange -->
                    <div class="an-metric-card" style="border:1px solid rgba(245,158,11,0.25);background:linear-gradient(135deg,rgba(245,158,11,0.06) 0%,transparent 60%);">
                        <div style="width:48px;height:48px;border-radius:12px;background:rgba(245,158,11,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--muted-text);text-transform:uppercase;letter-spacing:.6px;font-weight:500;margin-bottom:4px;">Low Stock</div>
                            <div id="an-h-lowstock" style="font-size:32px;font-weight:700;color:#d97706;line-height:1;">—</div>
                            <div style="font-size:11px;color:var(--muted-text);margin-top:4px;">Need restocking</div>
                        </div>
                    </div>

                    <!-- Out of Stock — red -->
                    <div class="an-metric-card" style="border:1px solid rgba(239,68,68,0.22);background:linear-gradient(135deg,rgba(239,68,68,0.06) 0%,transparent 60%);">
                        <div style="width:48px;height:48px;border-radius:12px;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--muted-text);text-transform:uppercase;letter-spacing:.6px;font-weight:500;margin-bottom:4px;">Out of Stock</div>
                            <div id="an-h-outofstock" style="font-size:32px;font-weight:700;color:#dc2626;line-height:1;">—</div>
                            <div style="font-size:11px;color:var(--muted-text);margin-top:4px;">Depleted items</div>
                        </div>
                    </div>

                    <!-- Low Stock % — purple -->
                    <div class="an-metric-card" style="border:1px solid rgba(139,92,246,0.22);background:linear-gradient(135deg,rgba(139,92,246,0.06) 0%,transparent 60%);">
                        <div style="width:48px;height:48px;border-radius:12px;background:rgba(139,92,246,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="19" y1="5" x2="5" y2="19"/>
                                <circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:11px;color:var(--muted-text);text-transform:uppercase;letter-spacing:.6px;font-weight:500;margin-bottom:4px;">Low Stock %</div>
                            <div id="an-h-pct" style="font-size:32px;font-weight:700;color:#7c3aed;line-height:1;">—</div>
                            <div style="font-size:11px;color:var(--muted-text);margin-top:4px;">Stock health</div>
                        </div>
                    </div>

                </div>

                <p style="font-size:14px;font-weight:500;margin:0 0 10px;">Items Needing Attention</p>
                <div id="an-lowstock-container" class="table-responsive">
                    <div class="ui-empty-state"><strong>Loading...</strong></div>
                </div>

            </div>

            <!-- ─── TAB 2: Damage Analytics ──────────────────────────────── -->
            <div id="an-tab2" class="an-tab-content" style="display:none;">

                <div class="an-filter-row"
                     style="display:grid;grid-template-columns:1fr 160px 160px auto;gap:10px;margin-bottom:16px;align-items:end;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:500;margin-bottom:4px;">Department</label>
                        <select id="an-dept-filter" class="form-control">
                            <option value="">All Departments</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:500;margin-bottom:4px;">Date From</label>
                        <input type="date" id="an-dmg-from" class="form-control">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:500;margin-bottom:4px;">Date To</label>
                        <input type="date" id="an-dmg-to" class="form-control">
                    </div>
                    <div>
                        <button type="button" id="an-dmg-apply" class="btn btn-primary" style="width:100%;">Apply</button>
                    </div>
                </div>

                <p style="font-size:14px;font-weight:500;margin:0 0 10px;">Most Damaged Items</p>
                <div style="height:280px;margin-bottom:24px;position:relative;">
                    <canvas id="chart-damaged" style="width:100%;height:100%;"></canvas>
                </div>
                <div id="an-damaged-container" class="table-responsive" style="margin-bottom:24px;">
                    <div class="ui-empty-state"><strong>Loading...</strong></div>
                </div>

                <p style="font-size:14px;font-weight:500;margin:0 0 10px;">Inventory Movement by Department</p>
                <div id="an-deptusage-container" class="table-responsive">
                    <div class="ui-empty-state"><strong>Loading...</strong></div>
                </div>

            </div>

            <!-- ─── TAB 3: Semester Comparison ───────────────────────────── -->
            <div id="an-tab3" class="an-tab-content" style="display:none;">

                <!-- Year selector -->
                <div class="an-sem-toolbar">
                    <label style="font-size:12px;color:var(--muted-text);margin:0;white-space:nowrap;">Academic Year</label>
                    <select id="an-sem-year" class="form-control an-sem-year-select">
                        <!-- Populated by JS -->
                    </select>
                    <button type="button" id="an-sem-apply" class="btn an-sem-apply-btn">Apply</button>
                    <span id="an-sem-year-label" class="an-sem-year-label"></span>
                </div>

                <!-- Comparison Period — built entirely from the semester-detail
                     response already fetched below (school year + both semester
                     date ranges); no additional API calls. -->
                <div id="an-sem-period" class="an-sem-period-card" style="display:none;">
                    <!-- Populated by JS -->
                </div>

                <!-- Summary cards: 4 metrics, each showing S1 vs S2 -->
                <p class="an-sem-section-title">Semester Summary</p>
                <div id="an-sem-cards" class="an-sem-cards-grid">
                    <div class="ui-empty-state" style="grid-column:span 4;"><strong>Loading…</strong></div>
                </div>

                <!-- Semester grouped bar chart -->
                <div class="an-sem-panel">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;">
                        <div>
                            <p style="font-size:14px;font-weight:500;color:var(--text-light);margin:0 0 3px;">Semester Overview Chart</p>
                            <p id="an-sem-chart-subtitle" style="font-size:12px;color:var(--muted-text);margin:0;">Loading semester schedule…</p>
                        </div>
                        <div style="display:flex;gap:14px;align-items:center;flex-shrink:0;padding-top:2px;">
                            <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--muted-text);">
                                <span style="width:8px;height:8px;border-radius:2px;background:#185FA5;display:inline-block;flex-shrink:0;"></span>
                                1st Semester
                            </span>
                            <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--muted-text);">
                                <span style="width:8px;height:8px;border-radius:2px;background:#1D9E75;display:inline-block;flex-shrink:0;"></span>
                                2nd Semester
                            </span>
                        </div>
                    </div>
                    <div style="height:280px;position:relative;">
                        <canvas id="chart-semester" style="width:100%;height:100%;"></canvas>
                    </div>
                </div>

                <!-- Metric comparison table -->
                <div class="an-sem-panel">
                    <p style="font-size:14px;font-weight:500;margin:0 0 2px;">Metric Comparison</p>
                    <p id="an-sem-table-subtitle" style="font-size:12px;color:var(--muted-text);margin:0 0 14px;">Loading semester schedule…</p>
                    <div id="an-sem-table" class="table-responsive">
                        <div class="ui-empty-state"><strong>Loading…</strong></div>
                    </div>
                </div>

                <!-- Department breakdown -->
                <div class="an-sem-panel">
                    <p style="font-size:14px;font-weight:500;margin:0 0 2px;">Department Activity</p>
                    <p style="font-size:12px;color:var(--muted-text);margin:0 0 14px;">Damage reports and dispatches per department, split by semester. Top 20 by total activity.</p>
                    <div id="an-sem-dept" class="table-responsive">
                        <div class="ui-empty-state"><strong>Loading…</strong></div>
                    </div>
                </div>

            </div>

            <!-- ─── TAB 4: Dispatch & Repair Reports ─────────────────────── -->
            <div id="an-tab4" class="an-tab-content" style="display:none;">

                <div class="an-filter-row"
                     style="display:grid;grid-template-columns:160px 160px auto 1fr;gap:10px;margin-bottom:16px;align-items:end;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:500;margin-bottom:4px;">Date From</label>
                        <input type="date" id="an-rpt-from" class="form-control">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:500;margin-bottom:4px;">Date To</label>
                        <input type="date" id="an-rpt-to" class="form-control">
                    </div>
                    <div>
                        <button type="button" id="an-rpt-apply" class="btn btn-primary">Apply</button>
                    </div>
                    <div></div>
                </div>

                <p style="font-size:14px;font-weight:500;margin:0 0 10px;">Inventory Activity — Last 12 Months</p>
                <div style="height:280px;margin-bottom:24px;position:relative;">
                    <canvas id="chart-monthly" style="width:100%;height:100%;"></canvas>
                </div>

                <p style="font-size:14px;font-weight:500;margin:0 0 10px;">Dispatch Report</p>
                <div id="an-dispatch-container" class="table-responsive" style="margin-bottom:24px;">
                    <div class="ui-empty-state"><strong>Loading...</strong></div>
                </div>

                <p style="font-size:14px;font-weight:500;margin:0 0 10px;">Repair Report</p>
                <div id="an-repair-container" class="table-responsive">
                    <div class="ui-empty-state"><strong>Loading...</strong></div>
                </div>

            </div>

        </div><!-- /.card-body -->
    </div><!-- /.card -->
</main>

<style>
/* ============================================================
   Analytics Dashboard — UI polish (scoped to #an-analytics-card)
   Spacing / alignment / consistency only. No layout structure,
   routing, permissions, or data/logic changes.
   ============================================================ */

/* Overview tab: Inventory Health metric cards (shared layout;
   per-card colors stay inline). Grid rows stretch by default, so
   all four cards already render at equal height. */
#an-tab1 .an-metric-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 28px;
}
#an-tab1 .an-metric-card {
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
}

/* Filter rows (Damage Analytics + Dispatch & Repair tabs): outside of
   .form-group, .form-control has no height/padding of its own, so the
   date inputs / department select previously rendered at browser-default
   height while the Apply button used the design system's button height.
   Giving every field the same explicit height fixes the misalignment. */
#an-analytics-card .an-filter-row .form-control,
#an-analytics-card .an-filter-row .btn {
    height: 40px;
    box-sizing: border-box;
}
#an-analytics-card .an-filter-row .form-control {
    width: 100%;
    padding: 0 12px;
    border-radius: 10px;
}

/* ── Tab row hover ───────────────────────────────────────────────────── */
.an-tab-content .table tbody tr { transition: background 0.12s; }
.an-tab-content .table tbody tr:hover { background: var(--muted-card); }
/* ── No border on last table row ─────────────────────────────────────── */
#an-sem-table .table tbody tr:last-child td,
#an-sem-dept  .table tbody tr:last-child td  { border-bottom: none; }

/* ── Responsive: keep filter rows and metric cards from overflowing on
   tablet/mobile (the grids above use fixed inline column tracks). ──── */
@media (max-width: 900px) {
    #an-tab1 .an-metric-grid { grid-template-columns: repeat(2, 1fr); }
    #an-analytics-card .an-filter-row { grid-template-columns: 1fr 1fr !important; }
}
@media (max-width: 560px) {
    #an-tab1 .an-metric-grid { grid-template-columns: 1fr; }
    #an-analytics-card .an-filter-row { grid-template-columns: 1fr !important; }
}

/* ============================================================
   Semester Comparison (Tab 3) — visual polish only, scoped to
   #an-tab3. No selector below is referenced by JS for behavior;
   anLoadSemesterDetail() only reads/writes element IDs (unchanged:
   an-sem-year, an-sem-apply, an-sem-year-label, an-sem-period,
   an-sem-cards, chart-semester, an-sem-chart-subtitle,
   an-sem-table-subtitle, an-sem-table, an-sem-dept) and now also
   assigns the class names introduced here to build each card/pill,
   which is purely presentational.
   ============================================================ */
#an-tab3 .an-sem-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
#an-tab3 .an-sem-year-select {
    width: 150px;
    font-size: 13px;
    border-radius: 8px;
    border: 1px solid var(--border);
    padding: 6px 10px;
}
#an-tab3 .an-sem-apply-btn {
    background: var(--primary-color);
    color: #fff;
    border: none;
    font-size: 13px;
    font-weight: 500;
    border-radius: 8px;
    padding: 7px 16px;
    cursor: pointer;
    transition: background 0.15s ease;
}
#an-tab3 .an-sem-apply-btn:hover { background: var(--primary-dark); }
#an-tab3 .an-sem-year-label { font-size: 13px; color: var(--muted-text); }

/* Comparison Period — real config data (school year + both semester
   ranges), assembled client-side from the same payload the cards/
   chart/table below already use. No new data or endpoints. */
#an-tab3 .an-sem-period-card {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 32px;
    background: var(--muted-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 20px;
}
#an-tab3 .an-sem-period-item { display: flex; flex-direction: column; gap: 2px; }
#an-tab3 .an-sem-period-label {
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--muted-text);
}
#an-tab3 .an-sem-period-value { font-size: 13px; font-weight: 500; color: var(--text-light); }

#an-tab3 .an-sem-section-title { font-size: 14px; font-weight: 500; margin: 0 0 10px; }

#an-tab3 .an-sem-cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}

/* Per-metric color identity (matches Metric Comparison table + chart
   legend semantics): Maintenance Reports = purple, Dispatches = blue,
   Damage Reports = orange, Repair Requests = green. Accent border/icon
   only — the S1/S2 values, arrow, and Year Total are unchanged. */
#an-tab3 .an-sem-card {
    border-radius: 12px;
    padding: 1.25rem 1.1rem;
    border: 1px solid var(--border);
    border-top: 3px solid var(--border);
    background: var(--card-color);
    transition: box-shadow 0.15s ease, transform 0.15s ease;
}
#an-tab3 .an-sem-card:hover { box-shadow: 0 10px 24px rgba(15, 23, 42, 0.10); transform: translateY(-1px); }
#an-tab3 .an-sem-card--maintenance { border-top-color: #8b5cf6; }
#an-tab3 .an-sem-card--dispatches  { border-top-color: #185FA5; }
#an-tab3 .an-sem-card--damage      { border-top-color: #d97706; }
#an-tab3 .an-sem-card--repairs     { border-top-color: #1D9E75; }

#an-tab3 .an-sem-card-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
}
#an-tab3 .an-sem-card--maintenance .an-sem-card-icon { background: rgba(139, 92, 246, 0.14); }
#an-tab3 .an-sem-card--dispatches  .an-sem-card-icon { background: rgba(24, 95, 165, 0.14); }
#an-tab3 .an-sem-card--damage      .an-sem-card-icon { background: rgba(217, 119, 6, 0.14); }
#an-tab3 .an-sem-card--repairs     .an-sem-card-icon { background: rgba(29, 158, 117, 0.14); }

#an-tab3 .an-sem-card-label {
    font-size: 11px;
    color: var(--muted-text);
    text-transform: uppercase;
    letter-spacing: .6px;
    font-weight: 500;
    margin-bottom: 12px;
}
#an-tab3 .an-sem-card-body {
    display: flex;
    justify-content: space-around;
    align-items: center;
    margin-bottom: 10px;
}
#an-tab3 .an-sem-card-foot {
    text-align: center;
    font-size: 12px;
    color: var(--muted-text);
    border-top: 1px solid var(--border);
    padding-top: 8px;
}

#an-tab3 .an-sem-panel {
    background: var(--card-color);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 20px;
}
#an-tab3 .an-sem-panel:last-child { margin-bottom: 0; }

/* Department Activity numeric pills — token/rgba-based so they render
   correctly in dark theme too (previously hardcoded light-only hexes
   #E6F1FB/#0C447C, which read as a near-invisible pale wash on the
   dark navy card background). */
#an-tab3 .an-num-pill {
    background: rgba(24, 95, 165, 0.14);
    color: #185FA5;
    border-radius: 99px;
    padding: 2px 9px;
    font-size: 11px;
    font-weight: 500;
}
:root[data-theme-resolved='dark'] #an-tab3 .an-num-pill {
    background: rgba(96, 165, 250, 0.18);
    color: #93c5fd;
}

@media (max-width: 900px) {
    #an-tab3 .an-sem-cards-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 560px) {
    #an-tab3 .an-sem-cards-grid { grid-template-columns: 1fr 1fr; }
    #an-tab3 .an-sem-period-card { flex-direction: column; align-items: flex-start; gap: 10px; }
    #an-tab3 .an-sem-toolbar { gap: 8px; }
}
</style>

<script>
const AN_API = window.SFMS_PUBLIC_URL
    ? window.SFMS_PUBLIC_URL('/api/analytics')
    : '/api/analytics';

const anLoadedTabs = new Set();

// Chart instances — destroyed and recreated on each data reload
let anChartDamaged  = null;
let anChartSemester = null;
let anChartMonthly  = null;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function anEsc(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function anFetch(url, options) {
    const fetcher = window.Components && typeof Components.fetchJson === 'function'
        ? Components.fetchJson
        : async (u, o) => { const r = await fetch(u, o); return { response: r, data: await r.json() }; };
    return fetcher(url, options);
}

function anStockBadge(qty, threshold) {
    qty = Number(qty); threshold = Number(threshold);
    if (qty <= 0)
        return '<span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:500;">Out of Stock</span>';
    if (qty <= threshold)
        return '<span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:500;">Low Stock</span>';
    return '<span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:10px;font-size:12px;font-weight:500;">Available</span>';
}

function anRepairBadge(status) {
    const map = {
        pending:     'background:#fef3c7;color:#92400e',
        assigned:    'background:#dbeafe;color:#1e40af',
        in_progress: 'background:#ffedd5;color:#9a3412',
        completed:   'background:#d1fae5;color:#065f46',
        archived:    'background:#f3f4f6;color:#374151',
    };
    const style = map[status] || 'background:#f3f4f6;color:#374151';
    const label = String(status || '').replace(/_/g, ' ').replace(/\b\w/g, (s) => s.toUpperCase());
    return `<span style="${style};padding:2px 8px;border-radius:10px;font-size:12px;font-weight:500;">${anEsc(label)}</span>`;
}

// ---------------------------------------------------------------------------
// Tab switching (lazy-loads each tab on first activation)
// ---------------------------------------------------------------------------

function anSwitchTab(tabId) {
    document.querySelectorAll('.an-tab-content').forEach((el) => { el.style.display = 'none'; });
    document.getElementById(tabId).style.display = 'block';

    document.querySelectorAll('.an-tab-btn').forEach((btn) => {
        const active = btn.dataset.tab === tabId;
        btn.style.borderBottom = active ? '2px solid #185FA5' : '2px solid transparent';
        btn.style.color        = active ? '#185FA5' : 'var(--muted-text)';
        btn.style.fontWeight   = active ? '500'     : '400';
    });

    if (!anLoadedTabs.has(tabId)) {
        anLoadedTabs.add(tabId);
        switch (tabId) {
            case 'an-tab1': anLoadHealth(); anLoadLowStock(); break;
            case 'an-tab2': anLoadTab2Data(); break;
            case 'an-tab3': anLoadSemesterDetail(); break;
            case 'an-tab4': anLoadTab4Data(); break;
        }
    }
}

// ---------------------------------------------------------------------------
// TAB 1: Overview
// ---------------------------------------------------------------------------

async function anLoadHealth() {
    try {
        const { response, data: payload } = await anFetch(
            `${AN_API}/inventory-health`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const d = payload.data;
        document.getElementById('an-h-total').textContent      = d.total_items        ?? '—';
        document.getElementById('an-h-lowstock').textContent   = d.low_stock_count    ?? '—';
        document.getElementById('an-h-outofstock').textContent = d.out_of_stock_count ?? '—';
        document.getElementById('an-h-pct').textContent =
            (d.low_stock_percent != null) ? d.low_stock_percent + '%' : '—';
    } catch (_) {
        // Cards stay as '—' — non-fatal; low-stock table will show its own error
    }
}

async function anLoadLowStock() {
    const container = document.getElementById('an-lowstock-container');
    try {
        const { response, data: payload } = await anFetch(
            `${AN_API}/low-stock`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const items = Array.isArray(payload.data?.items) ? payload.data.items : [];

        if (items.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No low-stock items.</strong><span>All items are at healthy stock levels.</span></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>Item Name</th><th>Quantity</th><th>Threshold</th><th>Status</th>'
            + '</tr></thead><tbody>';
        items.forEach((item) => {
            html += '<tr>';
            html += `<td><strong>${anEsc(item.name)}</strong></td>`;
            html += `<td>${anEsc(item.quantity)}</td>`;
            html += `<td>${anEsc(item.threshold)}</td>`;
            html += `<td>${anStockBadge(item.quantity, item.threshold)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load low-stock data.</strong></div>';
    }
}

// ---------------------------------------------------------------------------
// Options — populates Tab 2 department dropdown (called once at init)
// ---------------------------------------------------------------------------

async function anLoadOptions() {
    try {
        const { response, data: payload } = await anFetch(
            `${AN_API}/options`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) return;
        const departments = Array.isArray(payload.data?.departments) ? payload.data.departments : [];
        const sel = document.getElementById('an-dept-filter');
        departments.forEach((dept) => {
            const opt = document.createElement('option');
            opt.value       = dept.department_id;
            opt.textContent = dept.name;
            sel.appendChild(opt);
        });
    } catch (_) {
        // Fail silently — dropdown stays at "All Departments"
    }
}

// ---------------------------------------------------------------------------
// TAB 2: Damage Analytics
// ---------------------------------------------------------------------------

function anLoadTab2Data() {
    anLoadDamagedItems();
    anLoadDeptUsage();
}

async function anLoadDamagedItems() {
    const container = document.getElementById('an-damaged-container');
    container.innerHTML = '<div class="ui-empty-state"><strong>Loading...</strong></div>';
    try {
        const params = new URLSearchParams();
        const deptId = document.getElementById('an-dept-filter').value;
        const from   = document.getElementById('an-dmg-from').value;
        const to     = document.getElementById('an-dmg-to').value;
        if (deptId) params.set('department_id', deptId);
        if (from)   params.set('date_from', from);
        if (to)     params.set('date_to', to);

        const { response, data: payload } = await anFetch(
            `${AN_API}/damaged-items?${params.toString()}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const rows = Array.isArray(payload.data?.most_damaged) ? payload.data.most_damaged : [];

        // ── Horizontal bar chart — top 10, reversed so highest bar is at top ──
        if (anChartDamaged) { anChartDamaged.destroy(); anChartDamaged = null; }
        const top10 = rows.slice(0, 10).slice().reverse();
        const cvsDmg = document.getElementById('chart-damaged');
        if (cvsDmg && top10.length > 0) {
            anChartDamaged = new Chart(cvsDmg, {
                type: 'bar',
                data: {
                    labels:   top10.map((r) => r.name),
                    datasets: [{
                        data:            top10.map((r) => Number(r.damage_count)),
                        backgroundColor: 'rgba(239,68,68,0.72)',
                        borderRadius:    6,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    scales: {
                        y: { ticks: { color: '#b8aacc' } },
                        x: { grid:  { color: 'rgba(168,139,250,0.1)' }, ticks: { color: '#b8aacc' } },
                    },
                },
            });
        }

        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No damage records found.</strong><span>Try adjusting the filters.</span></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>Item Name</th><th>Damage Count</th>'
            + '</tr></thead><tbody>';
        rows.forEach((row) => {
            html += '<tr>';
            html += `<td><strong>${anEsc(row.name)}</strong></td>`;
            html += `<td>${anEsc(row.damage_count)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load damage data.</strong></div>';
    }
}

async function anLoadDeptUsage() {
    const container = document.getElementById('an-deptusage-container');
    container.innerHTML = '<div class="ui-empty-state"><strong>Loading...</strong></div>';
    try {
        const params = new URLSearchParams();
        const from = document.getElementById('an-dmg-from').value;
        const to   = document.getElementById('an-dmg-to').value;
        if (from) params.set('date_from', from);
        if (to)   params.set('date_to', to);

        const { response, data: payload } = await anFetch(
            `${AN_API}/department-usage?${params.toString()}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const rows = Array.isArray(payload.data?.department_usage) ? payload.data.department_usage : [];

        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No department usage data found.</strong></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>Department</th><th>Total Inventory Moved</th>'
            + '</tr></thead><tbody>';
        rows.forEach((row) => {
            html += '<tr>';
            html += `<td><strong>${anEsc(row.department_name || '—')}</strong></td>`;
            html += `<td>${anEsc(row.total_moved)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load department usage data.</strong></div>';
    }
}

// ---------------------------------------------------------------------------
// TAB 3: Semester Detail
// ---------------------------------------------------------------------------

// The academic period is configured once (School Year + 4 semester dates)
// in School Settings — the SAME source the Dashboard's "Current Academic
// Session" card reads. There is no per-year history, so there is nothing
// to "select" here; anSemSchoolYear is populated from the API response
// purely for display in the (unchanged) selector control.
let anSemSchoolYear = null;

// Mirrors dashboard.php's date formatting so the two pages read identically.
function anFormatSemDateShort(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr + 'T00:00:00');
    if (Number.isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}
function anFormatSemDateRange(startStr, endStr) {
    if (!startStr || !endStr) return '—';
    return anFormatSemDateShort(startStr) + ' – ' + anFormatSemDateShort(endStr);
}
function anFormatSemSchoolYear(schoolYear) {
    if (!schoolYear) return null;
    return /^\d{4}-\d{4}$/.test(schoolYear) ? schoolYear.replace('-', '–') : schoolYear;
}

async function anLoadSemesterDetail() {
    const errHtml = (msg) => `<div class="ui-empty-state"><strong>${anEsc(msg)}</strong></div>`;

    document.getElementById('an-sem-cards').innerHTML =
        '<div class="ui-empty-state" style="grid-column:span 4;"><strong>Loading…</strong></div>';
    document.getElementById('an-sem-table').innerHTML = errHtml('Loading…');
    document.getElementById('an-sem-dept').innerHTML  = errHtml('Loading…');

    try {
        const { response, data: payload } = await anFetch(
            `${AN_API}/semester-detail`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');

        const d = payload.data;

        // Update the school-year label/selector from the SAME configured
        // source the Dashboard's "Current Academic Session" card uses.
        anSemSchoolYear = d.school_year || null;
        const lbl = document.getElementById('an-sem-year-label');
        const _yearSelEl = document.getElementById('an-sem-year');
        if (_yearSelEl) {
            _yearSelEl.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = anSemSchoolYear || '';
            opt.textContent = anFormatSemSchoolYear(anSemSchoolYear) || 'Not configured';
            opt.selected = true;
            _yearSelEl.appendChild(opt);
        }

        if (d.configured === false) {
            if (lbl) lbl.textContent = 'Semester schedule not configured';
            const notConfiguredMsg = 'Semester schedule has not been configured yet. Set the School Year and semester dates in School Settings to see this comparison.';
            const emptyHtml = `<div class="ui-empty-state"><strong>${anEsc(notConfiguredMsg)}</strong></div>`;
            const periodCardEmpty = document.getElementById('an-sem-period');
            if (periodCardEmpty) { periodCardEmpty.style.display = 'none'; periodCardEmpty.innerHTML = ''; }
            document.getElementById('an-sem-cards').innerHTML =
                `<div class="ui-empty-state" style="grid-column:span 4;"><strong>${anEsc(notConfiguredMsg)}</strong></div>`;
            document.getElementById('an-sem-table').innerHTML = emptyHtml;
            document.getElementById('an-sem-dept').innerHTML  = emptyHtml;
            const chartSub = document.getElementById('an-sem-chart-subtitle');
            const tableSub = document.getElementById('an-sem-table-subtitle');
            if (chartSub) chartSub.textContent = 'Not configured';
            if (tableSub) tableSub.textContent = 'Not configured';
            if (anChartSemester) { anChartSemester.destroy(); anChartSemester = null; }
            return;
        }

        const s1 = d.semesters?.s1 || {};
        const s2 = d.semesters?.s2 || {};
        const s1Label = s1.label || '1st Semester';
        const s2Label = s2.label || '2nd Semester';
        const s1Range = anFormatSemDateRange(s1.start, s1.end);
        const s2Range = anFormatSemDateRange(s2.start, s2.end);

        if (lbl) lbl.textContent = anFormatSemSchoolYear(anSemSchoolYear) ? `School Year ${anFormatSemSchoolYear(anSemSchoolYear)}` : '';

        const chartSub = document.getElementById('an-sem-chart-subtitle');
        if (chartSub) chartSub.textContent = `${s1Label} (${s1Range}) vs ${s2Label} (${s2Range})`;
        const tableSub = document.getElementById('an-sem-table-subtitle');
        if (tableSub) tableSub.textContent = `Compares ${s1Label} (${s1Range}) vs ${s2Label} (${s2Range}). Trend shows change from S1 → S2.`;

        // ── Comparison Period card — assembled from the same s1/s2 data
        // above; no additional fetch. ────────────────────────────────────
        const periodCard = document.getElementById('an-sem-period');
        if (periodCard) {
            periodCard.innerHTML = `
                <div class="an-sem-period-item">
                    <span class="an-sem-period-label">School Year</span>
                    <span class="an-sem-period-value">${anEsc(anFormatSemSchoolYear(anSemSchoolYear) || '—')}</span>
                </div>
                <div class="an-sem-period-item">
                    <span class="an-sem-period-label">${anEsc(s1Label)}</span>
                    <span class="an-sem-period-value">${anEsc(s1Range)}</span>
                </div>
                <div class="an-sem-period-item">
                    <span class="an-sem-period-label">${anEsc(s2Label)}</span>
                    <span class="an-sem-period-value">${anEsc(s2Range)}</span>
                </div>`;
            periodCard.style.display = 'flex';
        }

        // Per-metric color identity (purple/blue/orange/green) + matching
        // outline icons, reused by both the summary cards below and the
        // Metric Comparison table further down.
        const _metricIconPaths = {
            maintenance_reports: '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
            dispatches:          '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
            damage_reports:      '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
            repairs:             '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
        };
        const _metricAccent = {
            maintenance_reports: '#8b5cf6',
            dispatches:          '#185FA5',
            damage_reports:      '#d97706',
            repairs:             '#1D9E75',
        };
        const anSemIcon = (key, size) =>
            `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="${_metricAccent[key]}" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;">${_metricIconPaths[key]}</svg>`;

        const metrics = [
            { key: 'maintenance_reports', label: 'Maintenance Reports', slug: 'maintenance' },
            { key: 'dispatches',          label: 'Dispatches',          slug: 'dispatches'  },
            { key: 'damage_reports',      label: 'Damage Reports',      slug: 'damage'      },
            { key: 'repairs',             label: 'Repair Requests',     slug: 'repairs'     },
        ];

        // ── Summary cards ──────────────────────────────────────────────────
        let cardsHtml = '';
        metrics.forEach((m) => {
            const v1         = s1[m.key] ?? 0;
            const v2         = s2[m.key] ?? 0;
            const total      = v1 + v2;
            const arrowColor = v2 > v1 ? '#1D9E75' : v2 < v1 ? '#A32D2D' : 'var(--muted-text)';
            const arrow      = v2 > v1 ? '↑' : v2 < v1 ? '↓' : '—';
            cardsHtml += `
                <div class="an-sem-card an-sem-card--${m.slug}">
                    <div class="an-sem-card-icon">${anSemIcon(m.key, 18)}</div>
                    <div class="an-sem-card-label">${anEsc(m.label)}</div>
                    <div class="an-sem-card-body">
                        <div style="text-align:center;">
                            <div style="font-size:28px;font-weight:500;color:#185FA5;line-height:1;">${v1}</div>
                            <div style="font-size:11px;color:var(--muted-text);margin-top:4px;">1st Sem</div>
                        </div>
                        <div style="font-size:20px;color:${arrowColor};font-weight:500;line-height:1;">${arrow}</div>
                        <div style="text-align:center;">
                            <div style="font-size:22px;font-weight:500;color:var(--muted-text);line-height:1;">${v2}</div>
                            <div style="font-size:11px;color:var(--muted-text);margin-top:4px;">2nd Sem</div>
                        </div>
                    </div>
                    <div class="an-sem-card-foot">
                        Year Total: <strong style="color:var(--text-light);">${total}</strong>
                    </div>
                </div>`;
        });
        document.getElementById('an-sem-cards').innerHTML = cardsHtml;

        // ── Alternating grouped bar chart (S1 blue, S2 green) ─────────────
        if (anChartSemester) { anChartSemester.destroy(); anChartSemester = null; }
        const cvsSem = document.getElementById('chart-semester');
        if (cvsSem) {
            const C_S1 = '#185FA5';
            const C_S2 = '#1D9E75';
            // Read the page's own theme tokens so gridlines/ticks stay legible
            // in both light and dark mode (was hardcoded to a fixed gray).
            const _rootStyle  = getComputedStyle(document.documentElement);
            const _tickColor  = (_rootStyle.getPropertyValue('--muted-text') || '').trim() || '#9ca3af';
            const _gridColor  = (_rootStyle.getPropertyValue('--border') || '').trim() || 'rgba(24,95,165,0.08)';
            anChartSemester = new Chart(cvsSem, {
                type: 'bar',
                data: {
                    labels: ['Maint S1', 'Maint S2', 'Dispatch S1', 'Dispatch S2', 'Damage S1', 'Damage S2', 'Repair S1', 'Repair S2'],
                    datasets: [{
                        data: [
                            s1.maintenance_reports ?? 0, s2.maintenance_reports ?? 0,
                            s1.dispatches          ?? 0, s2.dispatches          ?? 0,
                            s1.damage_reports      ?? 0, s2.damage_reports      ?? 0,
                            s1.repairs             ?? 0, s2.repairs             ?? 0,
                        ],
                        backgroundColor: [C_S1, C_S2, C_S1, C_S2, C_S1, C_S2, C_S1, C_S2],
                        borderRadius: 4,
                    }],
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { grid: { color: _gridColor }, ticks: { color: _tickColor } },
                        x: { grid: { display: false },    ticks: { color: _tickColor } },
                    },
                },
            });
        }

        // ── Metric comparison table (reuses the color-coded icons above) ──
        const _thS = 'font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted-text);padding-bottom:8px;border-bottom:0.5px solid var(--border);';
        let tableHtml = '<table class="table" style="width:100%;"><thead><tr>'
            + `<th style="${_thS}">Metric</th>`
            + `<th style="${_thS}text-align:center;">1st Sem<br><span style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0;color:var(--muted-text);">${anEsc(s1Range)}</span></th>`
            + `<th style="${_thS}text-align:center;">2nd Sem<br><span style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0;color:var(--muted-text);">${anEsc(s2Range)}</span></th>`
            + `<th style="${_thS}text-align:center;">Change</th>`
            + `<th style="${_thS}text-align:center;">Trend</th>`
            + '</tr></thead><tbody>';

        metrics.forEach((m) => {
            const v1        = s1[m.key] ?? 0;
            const v2        = s2[m.key] ?? 0;
            const diff      = v2 - v1;
            const diffStr   = diff === 0
                ? '<span style="color:var(--muted-text);">—</span>'
                : diff > 0
                    ? `<span style="color:#1D9E75;font-weight:500;">+${diff}</span>`
                    : `<span style="color:#A32D2D;font-weight:500;">${diff}</span>`;
            const trendIcon = diff > 0
                ? '<span style="color:#1D9E75;display:inline-flex;align-items:center;" title="Increase in 2nd semester"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg></span>'
                : diff < 0
                    ? '<span style="color:#A32D2D;display:inline-flex;align-items:center;" title="Decrease in 2nd semester"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg></span>'
                    : '<span style="color:var(--muted-text);" title="No change">—</span>';

            tableHtml += `<tr>
                <td>${anSemIcon(m.key, 14)}&nbsp;<strong style="font-weight:500;">${anEsc(m.label)}</strong></td>
                <td style="text-align:center;">${v1}</td>
                <td style="text-align:center;">${v2}</td>
                <td style="text-align:center;">${diffStr}</td>
                <td style="text-align:center;">${trendIcon}</td>
            </tr>`;
        });
        tableHtml += '</tbody></table>';
        document.getElementById('an-sem-table').innerHTML = tableHtml;

        // ── Department breakdown ───────────────────────────────────────────
        const depts = Array.isArray(d.department_breakdown) ? d.department_breakdown : [];
        if (depts.length === 0) {
            document.getElementById('an-sem-dept').innerHTML =
                '<div class="ui-empty-state"><strong>No department data for the configured semesters.</strong></div>';
        } else {
            const _thD = 'font-size:11px;font-weight:500;text-transform:uppercase;letter-spacing:0.06em;color:var(--muted-text);padding-bottom:8px;border-bottom:0.5px solid var(--border);';
            let deptHtml = '<table class="table" style="width:100%;"><thead><tr>'
                + `<th style="${_thD}">Department</th>`
                + `<th style="${_thD}text-align:center;">S1 Damage</th>`
                + `<th style="${_thD}text-align:center;">S2 Damage</th>`
                + `<th style="${_thD}text-align:center;">S1 Dispatches</th>`
                + `<th style="${_thD}text-align:center;">S2 Dispatches</th>`
                + `<th style="${_thD}text-align:center;">Total Activity</th>`
                + '</tr></thead><tbody>';

            const _numPill = (n) => `<span class="an-num-pill">${n}</span>`;
            depts.forEach((dept) => {
                const total   = Number(dept.s1_damage || 0) + Number(dept.s2_damage || 0)
                              + Number(dept.s1_dispatches || 0) + Number(dept.s2_dispatches || 0);
                const _dName  = dept.department_name;
                const _isNone = !_dName || String(_dName).toLowerCase() === 'no department';
                const _dCell  = _isNone
                    ? `<td><em style="color:var(--muted-text);">${anEsc(_dName || 'No department')}</em></td>`
                    : `<td><strong style="font-weight:500;">${anEsc(_dName)}</strong></td>`;
                deptHtml += `<tr>
                    ${_dCell}
                    <td style="text-align:center;">${_numPill(dept.s1_damage     || 0)}</td>
                    <td style="text-align:center;">${_numPill(dept.s2_damage     || 0)}</td>
                    <td style="text-align:center;">${_numPill(dept.s1_dispatches || 0)}</td>
                    <td style="text-align:center;">${_numPill(dept.s2_dispatches || 0)}</td>
                    <td style="text-align:center;">${_numPill(total)}</td>
                </tr>`;
            });
            deptHtml += '</tbody></table>';
            document.getElementById('an-sem-dept').innerHTML = deptHtml;
        }

    } catch (err) {
        const html = `<div class="ui-empty-state"><strong>Failed to load semester data.</strong></div>`;
        const periodCardErr = document.getElementById('an-sem-period');
        if (periodCardErr) { periodCardErr.style.display = 'none'; periodCardErr.innerHTML = ''; }
        document.getElementById('an-sem-cards').innerHTML =
            `<div class="ui-empty-state" style="grid-column:span 4;"><strong>${anEsc(err.message || 'Error loading data')}</strong></div>`;
        document.getElementById('an-sem-table').innerHTML = html;
        document.getElementById('an-sem-dept').innerHTML  = html;
    }
}

// ---------------------------------------------------------------------------
// TAB 4: Dispatch & Repair Reports
// ---------------------------------------------------------------------------

async function anLoadMonthlyChart() {
    if (anChartMonthly) { anChartMonthly.destroy(); anChartMonthly = null; }
    try {
        const { response, data: payload } = await anFetch(
            `${AN_API}/monthly-comparison`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) return;

        const months = Array.isArray(payload.data?.last_12_months) ? payload.data.last_12_months : [];
        if (!months.length) return;

        const MONTH_ABB = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const labels = months.map((m) => {
            const parts = String(m.ym || '').split('-');
            const abbr  = MONTH_ABB[parseInt(parts[1], 10) - 1] || parts[1];
            return `${abbr} '${String(parts[0] || '').slice(2)}`;
        });
        const values = months.map((m) => Number(m.val) || 0);

        const cvsMon = document.getElementById('chart-monthly');
        if (!cvsMon) return;

        anChartMonthly = new Chart(cvsMon, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    data:            values,
                    backgroundColor: 'rgba(139,92,246,0.72)',
                    borderRadius:    6,
                }],
            },
            options: {
                scales: {
                    y: { grid:  { color: 'rgba(168,139,250,0.1)' }, ticks: { color: '#b8aacc' } },
                    x: { ticks: { color: '#b8aacc' } },
                },
            },
        });
    } catch (_) {
        // fail silently — chart is decorative
    }
}

function anLoadTab4Data() {
    anLoadMonthlyChart();
    anLoadDispatchReport();
    anLoadRepairReport();
}

async function anLoadDispatchReport() {
    const container = document.getElementById('an-dispatch-container');
    container.innerHTML = '<div class="ui-empty-state"><strong>Loading...</strong></div>';
    try {
        const params = new URLSearchParams();
        const from = document.getElementById('an-rpt-from').value;
        const to   = document.getElementById('an-rpt-to').value;
        if (from) params.set('date_from', from);
        if (to)   params.set('date_to', to);

        const { response, data: payload } = await anFetch(
            `${AN_API}/dispatch-report?${params.toString()}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const rows = Array.isArray(payload.data?.dispatches) ? payload.data.dispatches : [];

        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No dispatch records found.</strong></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>Dispatch Code</th><th>Items Count</th><th>Total Quantity</th>'
            + '</tr></thead><tbody>';
        rows.forEach((row) => {
            html += '<tr>';
            html += `<td><strong>${anEsc(row.dispatch_code)}</strong></td>`;
            html += `<td>${anEsc(row.items_count)}</td>`;
            html += `<td>${anEsc(row.total_quantity)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load dispatch report.</strong></div>';
    }
}

async function anLoadRepairReport() {
    const container = document.getElementById('an-repair-container');
    container.innerHTML = '<div class="ui-empty-state"><strong>Loading...</strong></div>';
    try {
        const params = new URLSearchParams();
        const from = document.getElementById('an-rpt-from').value;
        const to   = document.getElementById('an-rpt-to').value;
        if (from) params.set('date_from', from);
        if (to)   params.set('date_to', to);

        const { response, data: payload } = await anFetch(
            `${AN_API}/repair-report?${params.toString()}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const rows = Array.isArray(payload.data?.repairs) ? payload.data.repairs : [];

        if (rows.length === 0) {
            container.innerHTML = '<div class="ui-empty-state"><strong>No repair records found.</strong></div>';
            return;
        }

        let html = '<table class="table"><thead><tr>'
            + '<th>Repair Code</th><th>Status</th><th>Count</th>'
            + '</tr></thead><tbody>';
        rows.forEach((row) => {
            html += '<tr>';
            html += `<td><strong>${anEsc(row.repair_code)}</strong></td>`;
            html += `<td>${anRepairBadge(row.repair_status)}</td>`;
            html += `<td>${anEsc(row.count)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<div class="ui-empty-state"><strong>Failed to load repair report.</strong></div>';
    }
}

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
    // Default dates: start of current year → today
    const today     = new Date();
    const yearStart = today.getFullYear() + '-01-01';
    const todayStr  = today.toISOString().slice(0, 10);
    document.getElementById('an-dmg-from').value = yearStart;
    document.getElementById('an-dmg-to').value   = todayStr;
    document.getElementById('an-rpt-from').value = yearStart;
    document.getElementById('an-rpt-to').value   = todayStr;

    // Tab buttons
    document.querySelectorAll('.an-tab-btn').forEach((btn) => {
        btn.addEventListener('click', () => anSwitchTab(btn.dataset.tab));
    });

    // The Academic Year selector shows the single configured school_year
    // (populated once anLoadSemesterDetail() reads it from School Settings —
    // the same source as the Dashboard's "Current Academic Session" card).
    // There is no per-year history to pick between, so a placeholder is
    // shown until that data arrives.
    const _yearSel = document.getElementById('an-sem-year');
    if (_yearSel) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'Loading…';
        _yearSel.appendChild(opt);
    }

    // Apply buttons
    document.getElementById('an-dmg-apply').addEventListener('click', anLoadTab2Data);
    document.getElementById('an-rpt-apply').addEventListener('click', anLoadTab4Data);
    document.getElementById('an-sem-apply').addEventListener('click', () => {
        anLoadSemesterDetail();
    });

    // Print button
    const anPrintBtn = document.getElementById('an-print-btn');
    if (anPrintBtn) {
        anPrintBtn.addEventListener('click', () => window.print());
    }

    // Preload department dropdown (non-blocking; Tab 2 may not be visited)
    anLoadOptions();

    // Activate first tab
    anSwitchTab('an-tab1');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
