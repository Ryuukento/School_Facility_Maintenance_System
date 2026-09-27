<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

if (!isset($_SESSION['user']) && !isset($_SESSION['auth_user'])) {
    header('Location: /School_Facility_Maintenance_System/frontend/pages/index.php');
    exit;
}

$_irUser = $_SESSION['user'] ?? $_SESSION['auth_user'] ?? [];
$_irRole = strtolower(trim((string)($_irUser['role'] ?? '')));


$pageTitle = 'Inventory Reports - SFMS';
include __DIR__ . '/../includes/header.php';
?>

<style media="print">
    @page { margin: 14mm; }

    aside, nav, .sidebar, [class*="sidebar"], header { display: none !important; }
    #ir-menu, #ir-room-list, .ir-print-btn           { display: none !important; }
    #ir-room-search-wrap, #ir-gen-toolbar-actions    { display: none !important; }
    #ir-export-csv-btn, .ir-sem-filters              { display: none !important; }
    main, .container                                 { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .card, .ir-shell                                 { box-shadow: none !important; border: none !important; background: #ffffff !important; }
    .card-body                                       { padding: 8px !important; }
    .ir-layout                                       { display: block !important; }

    /* The `header { display:none }` rule above targets the app chrome; the report's
       own title block must survive so printouts are identifiable. */
    .ir-page-head                                    { display: block !important; margin-bottom: 12px !important; }
    .ir-crumbs                                       { display: block !important; }
    .ir-crumbs ol                                    { display: flex !important; }
    /* Keep the freshness stamp on the printout, but flatten its on-screen chrome. */
    .ir-updated { display: inline-flex !important; background: none !important; border: none !important; box-shadow: none !important; padding: 0 !important; }
    .ir-shell, .ir-content, .ir-rooms                { overflow: visible !important; }
    .ir-section-inner                                { grid-template-columns: 1fr !important; }
    .ir-detail                                       { padding: 0 !important; }
    .ir-stat-grid                                    { grid-template-columns: repeat(4, 1fr) !important; }
    .ir-stat                                         { break-inside: avoid; }

    /* Tables must print in full: the on-screen sticky-header scroll box would
       otherwise clip every row past the container's max-height. */
    .ir-table-wrap { max-height: none !important; overflow: visible !important; border: none !important; box-shadow: none !important; }
    .ir-table thead th { background: #f1f5f9 !important; color: #334155 !important; }
    .ir-table tfoot td { background: #f8fafc !important; }

    /* Dark theme paints text near-white; on paper that is invisible. */
    .ir-page, .ir-page h1, .ir-page h2, .ir-page h3,
    .ir-page .ir-stat-value, .ir-page .ir-table td,
    .ir-page .ir-section-title, .ir-page .ir-report-title { color: #111827 !important; }
    .ir-page .ir-page-sub, .ir-page .ir-stat-title, .ir-page .ir-stat-desc,
    .ir-page .ir-sub, .ir-page .ir-crumbs, .ir-page .ir-crumbs ol,
    .ir-page .ir-updated, .ir-page .ir-updated .ir-updated-label,
    .ir-page .text-muted { color: #475569 !important; }
    .ir-page .ir-stat, .ir-page .ir-report-block { border: 1px solid #cbd5e1 !important; background: #ffffff !important; box-shadow: none !important; }
    .ir-page .ir-stat-icon { background: #f1f5f9 !important; color: #334155 !important; border-color: #cbd5e1 !important; }
    .ir-page .ir-stat { background: #ffffff !important; }
    .ir-page .ir-pill { background: #f1f5f9 !important; color: #111827 !important; border-color: #cbd5e1 !important; }
    .ir-page .ir-pill::before { box-shadow: none !important; }
    .ir-page .ir-sem-filters, .ir-page .ir-empty { box-shadow: none !important; }
    .ir-page .ir-report-letter { background: #f1f5f9 !important; color: #111827 !important; border-color: #cbd5e1 !important; }
</style>

<style>
/* ═══════════════════════════════════════════════════════════════════════════
   Inventory Reports — page-scoped design layer

   Scope: every rule is prefixed with .ir-page / .ir-* / #ir-* so nothing here
   can leak into another page. Colours resolve from the shared design tokens in
   styles.css (:root and :root[data-theme-resolved='dark'|'light']) via a thin
   local token layer, so the page follows the app theme automatically instead of
   hardcoding a palette that only works in one mode.

   Layer order:
     1. Local tokens        5. Section nav
     2. Page header         6. Room list + search
     3. Shell + layout      7. Stat cards
     4. Controls (btn/form) 8. Tables / pills / states / responsive
   ═══════════════════════════════════════════════════════════════════════════ */

/* ── 1. Local tokens ─────────────────────────────────────────────────────── */
/* Surfaces run in four deliberate steps so depth reads as depth:
   page background (darkest) → shell → raised panel → recessed well.
   Previously the shell was lighter than the panels nested inside it, which made
   every stat card look like a hole punched into the card rather than a card. */
.ir-page {
    --ir-card:          #131c2e;   /* shell            */
    --ir-panel:         #182235;   /* raised: stats, tables, filter bar */
    --ir-well:          #0f172a;   /* recessed: rails, inputs */
    --ir-thead-bg:      #141d30;

    /* Two line weights: the shell edge is the only strong one, nested frames
       stay quiet so the page doesn't read as a grid of boxes. */
    --ir-border:        rgba(255, 255, 255, .07);
    --ir-border-strong: rgba(255, 255, 255, .12);
    --ir-border-soft:   rgba(255, 255, 255, .045);
    --ir-hover:         rgba(255, 255, 255, .05);
    --ir-row-hover:     rgba(255, 255, 255, .032);

    /* Top-edge light catch — the detail that separates a "surface" from a "box". */
    --ir-inset:         inset 0 1px 0 rgba(255, 255, 255, .045);
    --ir-inset-field:   inset 0 1px 2px rgba(2, 6, 23, .18);

    --ir-text:          var(--text-primary, #f8fafc);
    --ir-muted:         #94a3b8;
    --ir-dim:           #64748b;

    --ir-accent:        #8b5cf6;
    --ir-accent-hover:  #7c3aed;
    --ir-accent-soft:   rgba(139, 92, 246, .10);
    --ir-accent-text:   #a78bfa;
    --ir-warning:       #f59e0b;

    /* Soft monochrome sheen for raised cards — a top-down light falloff rather
       than a colour gradient, so cards gain depth without going decorative. */
    --ir-sheen:         linear-gradient(180deg, rgba(255, 255, 255, .035), rgba(255, 255, 255, 0) 60%);

    --ir-radius-lg:     16px;
    --ir-radius-nested: 12px;
    --ir-radius-md:     9px;
    --ir-radius-sm:     7px;

    /* Tight contact shadow + a wider ambient one. Large blurry shadows read as
       "template"; short dark ones read as physical elevation. */
    --ir-shadow-1:      0 1px 2px rgba(2, 6, 23, .40);
    --ir-shadow:        0 1px 2px rgba(2, 6, 23, .40), 0 12px 32px rgba(2, 6, 23, .38);
    --ir-shadow-hover:  0 1px 2px rgba(2, 6, 23, .45), 0 6px 18px rgba(2, 6, 23, .35);

    --ir-ease:          cubic-bezier(.2, .7, .2, 1);
    --ir-t:             150ms var(--ir-ease);

    margin-top: 24px;
    margin-bottom: 44px;
    color: var(--ir-text);
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

:root[data-theme-resolved='light'] .ir-page {
    --ir-card:          #ffffff;
    --ir-panel:         #ffffff;
    --ir-well:          #f8fafc;
    --ir-thead-bg:      #f8fafc;

    --ir-border:        rgba(15, 23, 42, .09);
    --ir-border-strong: rgba(15, 23, 42, .15);
    --ir-border-soft:   rgba(15, 23, 42, .06);
    --ir-hover:         rgba(15, 23, 42, .04);
    --ir-row-hover:     rgba(15, 23, 42, .025);
    /* No-op rather than `none`: this token is composed into comma-separated
       box-shadow lists, where the `none` keyword would invalidate the whole rule. */
    --ir-inset:         inset 0 0 0 rgba(0, 0, 0, 0);
    --ir-inset-field:   inset 0 1px 2px rgba(15, 23, 42, .05);

    --ir-muted:         #64748b;
    --ir-dim:           #94a3b8;
    --ir-accent-soft:   rgba(139, 92, 246, .08);
    --ir-accent-text:   #6d28d9;
    --ir-sheen:         linear-gradient(180deg, rgba(15, 23, 42, .015), rgba(15, 23, 42, 0) 60%);

    --ir-shadow-1:      0 1px 2px rgba(15, 23, 42, .05);
    --ir-shadow:        0 1px 2px rgba(15, 23, 42, .05), 0 12px 32px rgba(15, 23, 42, .07);
    --ir-shadow-hover:  0 1px 2px rgba(15, 23, 42, .06), 0 6px 18px rgba(15, 23, 42, .09);
}

/* Headings are Poppins app-wide (styles.css h1–h6) — a geometric, consumer
   face. Scoped back to the body grotesque here, page-locally.
   font-family only: a letter-spacing here would outrank the .ir-*-title class
   rules below (0,1,1 beats 0,1,0) and flatten their per-size tracking. */
.ir-page h1, .ir-page h2, .ir-page h3,
.ir-page h4, .ir-page h5, .ir-page h6 {
    font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
}

/* Shared icon sizing — all icons are inline SVG on currentColor. */
.ir-page .ir-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
    display: block;
}

/* ── 2. Page header ──────────────────────────────────────────────────────── */
.ir-page-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 26px;
}

.ir-page-head-main { min-width: 0; }

/* Breadcrumb — real trail, still restrained (dim, no purple badge). */
.ir-crumbs { margin-bottom: 10px; }

.ir-crumbs ol {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: 12.5px;
    line-height: 1.2;
    color: var(--ir-dim);
}

.ir-crumbs li { display: inline-flex; align-items: center; }
.ir-crumbs .ir-crumb-sep { color: var(--ir-dim); opacity: .55; }
.ir-crumbs .ir-crumb-sep .ir-icon { width: 13px; height: 13px; }
.ir-crumbs [aria-current='page'] { color: var(--ir-muted); font-weight: 500; }

/* Right-hand meta slot in the page header. */
.ir-page-head-aside { display: flex; align-items: center; gap: 10px; }

.ir-updated {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 12px;
    font-size: 12.5px;
    line-height: 1.2;
    color: var(--ir-muted);
    background: var(--ir-panel);
    border: 1px solid var(--ir-border);
    border-radius: var(--ir-radius-md);
    box-shadow: var(--ir-inset), var(--ir-shadow-1);
    font-variant-numeric: tabular-nums;
}

.ir-updated .ir-icon { width: 14px; height: 14px; color: var(--ir-dim); }
.ir-updated .ir-updated-label { color: var(--ir-dim); }

.ir-page-title {
    font-size: 28px;
    font-weight: 600;
    line-height: 1.15;
    letter-spacing: -.026em;
    color: var(--ir-text);
    margin: 0;
}

.ir-page-sub {
    font-size: 14px;
    line-height: 1.55;
    color: var(--ir-muted);
    margin: 9px 0 0;
    max-width: 62ch;
}

/* ── 3. Shell + two-panel layout ─────────────────────────────────────────── */
/* Overrides .card's hardcoded light border/radius/shadow from styles.css. */
.ir-page .ir-shell {
    margin: 0;
    background: var(--ir-card);
    border: 1px solid var(--ir-border-strong);
    border-radius: var(--ir-radius-lg);
    box-shadow: var(--ir-inset), var(--ir-shadow);
    overflow: hidden;
}

.ir-layout {
    display: flex;
    align-items: stretch;
    min-height: 560px;
}

.ir-content {
    flex: 1;
    min-width: 0;
    overflow: auto;
}

/* ── 4. Controls: buttons + form fields ──────────────────────────────────── */
/* Page-scoped so the pill-shaped gradient .btn from styles.css becomes the
   flatter enterprise button without touching any other page. */
.ir-page .btn {
    gap: 7px;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 500;
    letter-spacing: -.005em;
    border-radius: var(--ir-radius-md);
    box-shadow: none;
    transition: background var(--ir-t), border-color var(--ir-t),
                color var(--ir-t), box-shadow var(--ir-t);
}

.ir-page .btn .ir-icon { width: 15px; height: 15px; }

/* No lift on press — enterprise controls stay put and change tone instead. */
.ir-page .btn:active:not(:disabled) { transform: none; }

.ir-page .btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 1px var(--ir-card), 0 0 0 3px rgba(139, 92, 246, .55);
}

.ir-page .btn-primary {
    background: var(--ir-accent);
    border-color: rgba(0, 0, 0, .18);
    color: #ffffff;
    /* Inset top highlight + tight contact shadow: reads as a raised key, not a
       flat colour swatch, without resorting to a gradient. */
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .18), var(--ir-shadow-1);
}

.ir-page .btn-primary:hover {
    background: var(--ir-accent-hover);
    border-color: rgba(0, 0, 0, .24);
}

.ir-page .btn-primary:active:not(:disabled) {
    background: #6d28d9;
    box-shadow: inset 0 1px 2px rgba(2, 6, 23, .35);
}

/* A real surface — `transparent` reads as an unfinished ghost button. */
.ir-page .btn-secondary {
    background: var(--ir-panel);
    border-color: var(--ir-border-strong);
    color: var(--ir-text);
    box-shadow: var(--ir-inset), var(--ir-shadow-1);
}

.ir-page .btn-secondary:hover {
    background: var(--ir-well);
    border-color: rgba(148, 163, 184, .30);
}

.ir-page .btn-secondary:active:not(:disabled) {
    box-shadow: inset 0 1px 2px rgba(2, 6, 23, .25);
}

/* .form-control is painted white by color-scheme.css (--bg-white) in both
   themes; scoping it here keeps Section 3's filters readable in dark mode. */
/* Inputs are recessed wells, one step below the panel they sit on. */
.ir-page .form-control {
    width: 100%;
    padding: 8px 11px;
    font-family: inherit;
    font-size: 13px;
    color: var(--ir-text);
    background: var(--ir-well);
    border: 1px solid var(--ir-border-strong);
    border-radius: var(--ir-radius-md);
    box-shadow: var(--ir-inset-field);
    transition: border-color var(--ir-t), box-shadow var(--ir-t);
}

.ir-page .form-control:hover { border-color: rgba(148, 163, 184, .28); }

.ir-page .form-control:focus {
    outline: none;
    border-color: var(--ir-accent);
    box-shadow: 0 0 0 3px rgba(139, 92, 246, .16);
}

.ir-page select.form-control {
    appearance: none;
    -webkit-appearance: none;
    padding-right: 34px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 14px;
}

/* Sentence case at label weight. Uppercase + 700 + wide tracking is the single
   strongest "generic admin template" tell on a form. */
.ir-field-label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: -.005em;
    color: var(--ir-muted);
    margin-bottom: 6px;
}

/* ── 7. Stat cards: icon + title + large value + description ─────────────── */
.ir-stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 12px;
    margin-bottom: 28px;
}

/* Metric card: accented icon tile top-right, number dominant. Equal heights via
   the grid's default stretch. The "soft gradient" is a monochrome top-down sheen
   (--ir-sheen), not a colour wash — depth without the template look. */
.ir-stat {
    position: relative;
    display: block;
    padding: 20px;
    border: 1px solid var(--ir-border);
    border-radius: var(--ir-radius-nested);
    background: var(--ir-sheen), var(--ir-panel);
    box-shadow: var(--ir-inset), var(--ir-shadow-1);
    transition: border-color var(--ir-t), box-shadow var(--ir-t);
}

.ir-stat:hover {
    border-color: var(--ir-border-strong);
    box-shadow: var(--ir-inset), var(--ir-shadow-hover);
}

.ir-stat-icon {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--ir-radius-md);
    /* Neutral by default; the accent classes below tint per card. */
    color: var(--ir-muted);
    background: rgba(148, 163, 184, .10);
    border: 1px solid var(--ir-border-strong);
    transition: color var(--ir-t), background var(--ir-t), border-color var(--ir-t);
}

.ir-stat-icon .ir-icon { width: 18px; height: 18px; }

.ir-stat-body { min-width: 0; padding-right: 46px; }

.ir-stat-title {
    font-size: 12.5px;
    font-weight: 500;
    letter-spacing: -.005em;
    color: var(--ir-muted);
    margin-bottom: 10px;
}

.ir-stat-value {
    font-size: 32px;
    font-weight: 600;
    line-height: 1;
    letter-spacing: -.03em;
    color: var(--ir-text);
    font-variant-numeric: tabular-nums;
    word-break: break-word;
}

.ir-stat-desc {
    font-size: 12px;
    line-height: 1.45;
    color: var(--ir-dim);
    margin-top: 10px;
}

/* Per-card accent — flat tint on the icon tile only. The value stays full
   contrast so four cards don't compete; only the warning card tints its number,
   because that one is meant to pull the eye. */
.ir-stat-accent-inventory  .ir-stat-icon { color: #a78bfa; background: rgba(139,  92, 246, .12); border-color: rgba(139,  92, 246, .24); }
.ir-stat-accent-rooms   .ir-stat-icon { color: #60a5fa; background: rgba( 59, 130, 246, .12); border-color: rgba( 59, 130, 246, .24); }
.ir-stat-accent-total   .ir-stat-icon { color: #34d399; background: rgba( 16, 185, 129, .12); border-color: rgba( 16, 185, 129, .24); }
.ir-stat-accent-warning .ir-stat-icon { color: #fbbf24; background: rgba(245, 158,  11, .13); border-color: rgba(245, 158,  11, .26); }
.ir-stat-accent-warning .ir-stat-value { color: var(--ir-warning); }

/* ── 8. Tables ───────────────────────────────────────────────────────────── */
/* The wrap is the scroll container, which is what makes `position: sticky` on
   the header row actually stick; without a bounded height the header binds to a
   box that never scrolls vertically. Print CSS lifts the cap so nothing clips. */
.ir-table-wrap {
    width: 100%;
    max-height: min(58vh, 540px);
    overflow: auto;
    border: 1px solid var(--ir-border);
    border-radius: var(--ir-radius-nested);
    background: var(--ir-panel);
    box-shadow: var(--ir-inset), var(--ir-shadow-1);
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, .28) transparent;
}

/* The default chrome scrollbar is the loudest element in a dark panel. */
.ir-table-wrap::-webkit-scrollbar        { width: 10px; height: 10px; }
.ir-table-wrap::-webkit-scrollbar-track  { background: transparent; }
.ir-table-wrap::-webkit-scrollbar-thumb  {
    background: rgba(148, 163, 184, .24);
    border: 3px solid transparent;
    background-clip: content-box;
    border-radius: 999px;
}
.ir-table-wrap::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, .40); background-clip: content-box; }

/* Before the first render the wrap is empty — don't paint an empty bordered box. */
.ir-table-wrap:empty { display: none; }

/* An empty/error state fills the wrap, so only one frame may show. Preferred:
   the wrap yields and the empty state keeps its dashed border. Browsers without
   :has() fall back to the first rule — flat empty state inside the wrap's frame,
   which is what shipped before and still looks correct. */
.ir-table-wrap > .ir-empty {
    border: none;
    border-radius: 0;
    background: transparent;
}

.ir-table-wrap:has(> .ir-empty) {
    border: none;
    background: transparent;
    box-shadow: none;
}

.ir-table-wrap:has(> .ir-empty) > .ir-empty {
    border: 1px dashed var(--ir-border-strong);
    border-radius: var(--ir-radius-nested);
    background: var(--ir-well);
}

.ir-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 13px;
}

.ir-table th,
.ir-table td {
    padding: 11px 16px;
    text-align: left;
    vertical-align: middle;
    border-bottom: 1px solid var(--ir-border-soft);
}

/* Header row is quiet and small, not loud and bold — the data is the content,
   the header is only a legend. */
.ir-table thead th {
    font-size: 11.5px;
    font-weight: 500;
    letter-spacing: .01em;
    color: var(--ir-dim);
    background: var(--ir-thead-bg);
    border-bottom: 1px solid var(--ir-border);
    padding-top: 9px;
    padding-bottom: 9px;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 2;
}

.ir-table tbody td { color: var(--ir-text); }

.ir-table tbody tr { transition: background var(--ir-t); }

/* Neutral, not tinted: a purple wash on every hovered row makes the table feel
   decorated rather than functional. */
.ir-table tbody tr:hover { background: var(--ir-row-hover); }

.ir-table tbody tr:last-child td { border-bottom: none; }

/* right-align + tabular figures for every numeric column */
.ir-table .ir-num {
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.ir-table .ir-name { font-weight: 500; letter-spacing: -.005em; }

.ir-table .ir-sub {
    display: block;
    font-size: 11.5px;
    font-weight: 400;
    color: var(--ir-dim);
    margin-top: 3px;
}

/* totals row — sticks to the bottom of the scroll box so it stays in view */
.ir-table tfoot td {
    padding: 12px 16px;
    border-top: 1px solid var(--ir-border-strong);
    border-bottom: none;
    background: var(--ir-thead-bg);
    font-weight: 600;
    color: var(--ir-text);
    position: sticky;
    bottom: 0;
    z-index: 2;
}

.ir-table tfoot .ir-total-label {
    font-size: 11.5px;
    font-weight: 500;
    letter-spacing: .01em;
    color: var(--ir-dim);
}

/* ── Pill badges — theme-aware, replaces hardcoded light-theme pairs ─────── */
/* Status colour rides a small dot rather than flooding the label. Seven blocks
   of saturated fill in one column is the "colourful admin template" look; a
   neutral chip with a coloured indicator stays legible and scans faster. */
.ir-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 9px 3px 8px;
    border-radius: var(--radius-full, 9999px);
    font-size: 11.5px;
    font-weight: 500;
    letter-spacing: -.005em;
    white-space: nowrap;
    color: var(--ir-text);
    background: rgba(148, 163, 184, .10);
    border: 1px solid var(--ir-border-strong);
}

.ir-pill::before {
    content: '';
    flex-shrink: 0;
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background: var(--ir-dot, #94a3b8);
    box-shadow: 0 0 0 2px var(--ir-dot-halo, transparent);
}

.ir-pill-success { --ir-dot: #10b981; --ir-dot-halo: rgba( 16, 185, 129, .18); }
.ir-pill-warning { --ir-dot: #f59e0b; --ir-dot-halo: rgba(245, 158,  11, .18); }
.ir-pill-danger  { --ir-dot: #ef4444; --ir-dot-halo: rgba(239,  68,  68, .18); }
.ir-pill-info    { --ir-dot: #3b82f6; --ir-dot-halo: rgba( 59, 130, 246, .18); }
.ir-pill-violet  { --ir-dot: #8b5cf6; --ir-dot-halo: rgba(139,  92, 246, .18); }
.ir-pill-orange  { --ir-dot: #f97316; --ir-dot-halo: rgba(249, 115,  22, .18); }
.ir-pill-neutral { --ir-dot: #64748b; color: var(--ir-muted); }

/* ── Loading skeleton ────────────────────────────────────────────────────── */
.ir-loading {
    display: grid;
    gap: 10px;
    padding: 20px;
}

.ir-loading-row {
    height: 11px;
    border-radius: 5px;
    background: linear-gradient(90deg,
                rgba(148, 163, 184, .13),
                rgba(148, 163, 184, .05),
                rgba(148, 163, 184, .13));
    background-size: 200% 100%;
    animation: ir-shimmer 1.5s linear infinite;
}
.ir-loading-row:nth-child(1) { width: 100%; }
.ir-loading-row:nth-child(2) { width: 92%;  }
.ir-loading-row:nth-child(3) { width: 84%;  }
.ir-loading-row:nth-child(4) { width: 74%;  }

@keyframes ir-shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

@media (prefers-reduced-motion: reduce) {
    .ir-loading-row { animation: none; }
    .ir-page *      { transition: none !important; }
}

/* ── Empty / error states ────────────────────────────────────────────────── */
/* Generous, quiet, and centred. A dashed purple-badged box reads as an error;
   an empty state should read as "nothing here yet", which is neutral. */
.ir-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 60px 24px;
    border: 1px dashed var(--ir-border-strong);
    border-radius: var(--ir-radius-nested);
    background: var(--ir-well);
}

.ir-empty-icon {
    width: 52px;
    height: 52px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    margin-bottom: 18px;
    background: rgba(148, 163, 184, .09);
    border: 1px solid var(--ir-border-strong);
    color: var(--ir-dim);
}

.ir-empty-icon .ir-icon { width: 24px; height: 24px; }

.ir-empty-title {
    display: block;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: -.01em;
    color: var(--ir-text);
    margin-bottom: 6px;
}

.ir-empty-hint {
    font-size: 13px;
    line-height: 1.55;
    color: var(--ir-dim);
    max-width: 44ch;
}

.ir-empty-danger { border-color: rgba(239, 68, 68, .30); }
.ir-empty-danger .ir-empty-icon  { background: rgba(239, 68, 68, .10); border-color: rgba(239, 68, 68, .26); color: #f87171; }
.ir-empty-danger .ir-empty-title { color: #f87171; }

/* Empty state inside the narrow room rail needs a tighter footprint. */
#ir-room-list-inner .ir-empty { padding: 34px 16px; border: none; background: transparent; }
#ir-room-list-inner .ir-empty-icon { width: 40px; height: 40px; margin-bottom: 12px; }
#ir-room-list-inner .ir-empty-icon .ir-icon { width: 18px; height: 18px; }
#ir-room-list-inner .ir-empty-title { font-size: 13px; }
#ir-room-list-inner .ir-empty-hint  { font-size: 12px; }

/* ── 5. Section nav (left rail) ──────────────────────────────────────────── */
/* Rails are recessed wells, a step below the shell — that is what makes the
   content pane read as the foreground. */
.ir-nav {
    width: 226px;
    flex-shrink: 0;
    padding: 20px 12px;
    border-right: 1px solid var(--ir-border);
    background: var(--ir-well);
}

.ir-nav-label {
    margin: 0 0 12px;
    padding: 0 12px;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: var(--ir-dim);
}

.ir-menu-btn {
    position: relative;
    display: flex;
    align-items: center;
    gap: 11px;
    width: 100%;
    padding: 9px 12px;
    margin-bottom: 2px;
    text-align: left;
    font-family: inherit;
    font-size: 13.5px;
    font-weight: 500;
    line-height: 1.3;
    letter-spacing: -.005em;
    color: var(--ir-muted);
    background: transparent;
    border: none;
    border-radius: var(--ir-radius-md);
    cursor: pointer;
    transition: background var(--ir-t), color var(--ir-t);
}

.ir-menu-btn .ir-icon { width: 16px; height: 16px; color: var(--ir-dim); transition: color var(--ir-t); }

.ir-menu-btn:hover { background: var(--ir-hover); color: var(--ir-text); }
.ir-menu-btn:hover .ir-icon { color: var(--ir-muted); }

/* Selection has to be unmistakable: accent fill + rail marker + full-contrast
   text + tinted icon, all four together. */
.ir-menu-btn.ir-active {
    background: var(--ir-accent-soft);
    color: var(--ir-text);
    font-weight: 600;
    box-shadow: inset 0 0 0 1px rgba(139, 92, 246, .18);
}

/* Active indicator: a rail marker rather than a full-height border, so the
   pill's rounded corners stay intact. */
.ir-menu-btn.ir-active::before {
    content: '';
    position: absolute;
    left: -12px;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 20px;
    border-radius: 0 3px 3px 0;
    background: var(--ir-accent);
}

.ir-menu-btn.ir-active .ir-icon { color: var(--ir-accent-text); }

.ir-menu-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px rgba(139, 92, 246, .45);
}

/* ── 6. Room list + search ───────────────────────────────────────────────── */
/* 300px rather than the previous 260px: the rail now nests a per-building
   search field inside a padded panel, and at 260px that input had ~230px of
   usable width and clipped its own placeholder. */
.ir-rooms {
    width: 300px;
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--ir-border);
    background: var(--ir-well);
    min-height: 0;
}

#ir-room-search-wrap {
    position: sticky;
    top: 0;
    z-index: 2;
    padding: 14px 12px;
    border-bottom: 1px solid var(--ir-border);
    background: var(--ir-well);
}

.ir-search { position: relative; display: block; }

.ir-search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 14px;
    height: 14px;
    color: var(--ir-dim);
    pointer-events: none;
}

/* Rounded rectangle, not a capsule: capsule search boxes read as consumer UI. */
#ir-room-search {
    width: 100%;
    padding: 8px 12px 8px 32px;
    font-family: inherit;
    font-size: 13px;
    color: var(--ir-text);
    background: var(--ir-panel);
    border: 1px solid var(--ir-border-strong);
    border-radius: var(--ir-radius-md);
    box-shadow: var(--ir-inset-field);
    transition: border-color var(--ir-t), box-shadow var(--ir-t), background var(--ir-t);
    -webkit-appearance: none;
    appearance: none;
}

#ir-room-search::placeholder { color: var(--ir-dim); }
#ir-room-search::-webkit-search-cancel-button { filter: grayscale(1) opacity(.6); }

#ir-room-search:hover { border-color: rgba(148, 163, 184, .28); }

#ir-room-search:focus {
    outline: none;
    border-color: var(--ir-accent);
    box-shadow: 0 0 0 3px rgba(139, 92, 246, .16);
}

.ir-search-count {
    margin-top: 9px;
    padding: 0 2px;
    font-size: 11.5px;
    color: var(--ir-dim);
    font-variant-numeric: tabular-nums;
}

.ir-rooms-inner {
    flex: 1;
    overflow-y: auto;
    padding: 8px;
    min-height: 0;
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, .26) transparent;
}

.ir-rooms-inner::-webkit-scrollbar       { width: 10px; }
.ir-rooms-inner::-webkit-scrollbar-track { background: transparent; }
.ir-rooms-inner::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, .22);
    border: 3px solid transparent;
    background-clip: content-box;
    border-radius: 999px;
}

/* .ir-group-label was removed with the flat room list. It styled the inline
   building captions that separated groups of rooms in the old single-level
   rail; buildings are now real expandable rows (.ir-bld-btn), so nothing
   rendered it any more. */

.ir-room-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 7px 10px;
    margin-bottom: 1px;
    text-align: left;
    font-family: inherit;
    font-size: 13px;
    line-height: 1.35;
    letter-spacing: -.005em;
    color: var(--ir-muted);
    background: transparent;
    border: none;
    border-radius: var(--ir-radius-sm);
    cursor: pointer;
    transition: background var(--ir-t), color var(--ir-t);
}

.ir-room-btn:hover { background: var(--ir-hover); color: var(--ir-text); }

.ir-room-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px rgba(139, 92, 246, .45);
}

/* Selected room: raised surface + accent ring, so it reads as selected against
   both the rail and a merely-hovered row. */
.ir-room-btn.ir-active {
    background: var(--ir-panel);
    color: var(--ir-text);
    font-weight: 600;
    box-shadow: inset 0 0 0 1px rgba(139, 92, 246, .30);
}

.ir-room-btn .ir-room-name {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Bare tabular figure, no chip — a badge on every row turns the list into a
   scoreboard. */
.ir-room-btn .ir-room-count {
    flex-shrink: 0;
    min-width: 18px;
    text-align: right;
    font-size: 11.5px;
    font-weight: 500;
    font-variant-numeric: tabular-nums;
    color: var(--ir-dim);
}

.ir-room-btn .ir-room-count:empty { display: none; }
.ir-room-btn.ir-active .ir-room-count { color: var(--ir-muted); }

/* ── 6b. Building → rooms accordion ──────────────────────────────────────── */
/* The rail used to render every room (~163) as one flat list, so finding a room
   meant scrolling past all the others. It now lists BUILDINGS only; a building's
   rooms are built into its panel the first time it is expanded, so the initial
   DOM holds one row per building instead of one row per room.

   Room rows inside a panel deliberately reuse .ir-room-btn — same hover, same
   focus ring, same .ir-active selected state — so selecting a room looks and
   behaves exactly as it did before this change. */

.ir-bld {
    margin-bottom: 6px;
    background: var(--ir-panel);
    border: 1px solid var(--ir-border);
    border-radius: var(--ir-radius-md);
    box-shadow: var(--ir-shadow-1);
    overflow: hidden;
}

.ir-bld.ir-open { border-color: rgba(139, 92, 246, .30); }

.ir-bld-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 9px 10px;
    text-align: left;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 600;
    line-height: 1.35;
    letter-spacing: -.005em;
    color: var(--ir-text);
    background: transparent;
    border: none;
    cursor: pointer;
    transition: background var(--ir-t);
}

.ir-bld-btn:hover { background: var(--ir-hover); }

.ir-bld-btn:focus-visible {
    outline: none;
    box-shadow: inset 0 0 0 2px rgba(139, 92, 246, .45);
}

/* ▸ collapsed / ▼ expanded. One chevron rotated 90deg rather than two glyphs,
   so the state change reads as a single element moving. */
.ir-bld-caret {
    flex-shrink: 0;
    width: 13px;
    height: 13px;
    color: var(--ir-dim);
    transition: transform var(--ir-t), color var(--ir-t);
}

.ir-bld.ir-open .ir-bld-caret {
    transform: rotate(90deg);
    color: var(--ir-accent-text);
}

/* Long building names truncate rather than wrapping the row to two lines; the
   button carries a title attribute so the full name stays reachable. */
.ir-bld-name {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ir-bld-count {
    flex-shrink: 0;
    font-size: 11px;
    font-weight: 500;
    color: var(--ir-dim);
    font-variant-numeric: tabular-nums;
}

/* display:none, not max-height:0 — a collapsed building must cost nothing in
   height AND nothing in the tab order. */
.ir-bld-panel {
    display: none;
    padding: 8px;
    border-top: 1px solid var(--ir-border-soft);
    background: var(--ir-well);
}

.ir-bld.ir-open > .ir-bld-panel { display: block; }

.ir-bld-search { position: relative; display: block; margin-bottom: 6px; }

.ir-bld-search .ir-search-icon { width: 13px; height: 13px; left: 9px; }

.ir-bld-search-input {
    width: 100%;
    padding: 6px 10px 6px 29px;
    font-family: inherit;
    font-size: 12px;
    color: var(--ir-text);
    background: var(--ir-panel);
    border: 1px solid var(--ir-border-strong);
    border-radius: var(--ir-radius-sm);
    box-shadow: var(--ir-inset-field);
    transition: border-color var(--ir-t), box-shadow var(--ir-t);
    -webkit-appearance: none;
    appearance: none;
}

.ir-bld-search-input::placeholder { color: var(--ir-dim); }
.ir-bld-search-input::-webkit-search-cancel-button { filter: grayscale(1) opacity(.6); }
.ir-bld-search-input:hover { border-color: rgba(148, 163, 184, .28); }

.ir-bld-search-input:focus {
    outline: none;
    border-color: var(--ir-accent);
    box-shadow: 0 0 0 3px rgba(139, 92, 246, .16);
}

/* Each building's room list scrolls on its own, so opening several buildings
   at once can never push the rail to an unusable height. */
.ir-bld-rooms {
    max-height: 264px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, .26) transparent;
}

.ir-bld-rooms::-webkit-scrollbar       { width: 8px; }
.ir-bld-rooms::-webkit-scrollbar-track { background: transparent; }
.ir-bld-rooms::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, .22);
    border: 2px solid transparent;
    background-clip: content-box;
    border-radius: 999px;
}

/* Compact in-panel message — the full .ir-empty block is far too tall for a
   260px rail. */
.ir-bld-note {
    padding: 10px 8px;
    font-size: 11.5px;
    line-height: 1.45;
    color: var(--ir-dim);
    text-align: center;
}

.ir-hidden { display: none !important; }

/* ── Content panes, section headings, report blocks ──────────────────────── */
.ir-pane   { padding: 28px; }
.ir-detail { padding: 28px; min-width: 0; }

.ir-section-inner {
    display: grid;
    grid-template-columns: 300px minmax(0, 1fr);
    align-items: stretch;
    min-height: 560px;
}

.ir-toolbar {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.ir-toolbar-actions { display: flex; gap: 8px; flex-wrap: wrap; }

.ir-section-title {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    letter-spacing: -.015em;
    color: var(--ir-text);
}

.ir-section-sub {
    margin: 5px 0 0;
    font-size: 12.5px;
    line-height: 1.5;
    color: var(--ir-dim);
}

.ir-detail-title {
    margin: 0 0 6px;
    font-size: 19px;
    font-weight: 600;
    letter-spacing: -.02em;
    color: var(--ir-text);
}

.ir-detail-meta {
    margin: 0;
    font-size: 13px;
    color: var(--ir-dim);
}

.ir-detail-meta strong { color: var(--ir-muted); font-weight: 600; font-variant-numeric: tabular-nums; }

.ir-divider {
    height: 1px;
    margin: 30px 0;
    background: var(--ir-border);
    border: none;
}

/* Separates a section's header block from its data, so it sits tighter than the
   between-sections divider. */
.ir-divider-tight { margin: 18px 0; }

/* Section 3 report blocks (A/B/C/D) */
.ir-report-block { margin-bottom: 30px; }
.ir-report-block:last-child { margin-bottom: 0; }

.ir-report-head {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 12px;
}

/* Monochrome index marker — a purple chip per block turns four reports into
   four competing focal points. */
.ir-report-letter {
    flex-shrink: 0;
    width: 21px;
    height: 21px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10.5px;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    border-radius: 6px;
    color: var(--ir-dim);
    background: rgba(148, 163, 184, .10);
    border: 1px solid var(--ir-border-strong);
}

.ir-report-title {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: -.01em;
    color: var(--ir-text);
}

/* Section 3 filter bar. Stays on the raised panel level so the recessed inputs
   inside it still have a surface to sink into. */
.ir-sem-filters {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    flex-wrap: wrap;
    padding: 14px 16px;
    margin-bottom: 28px;
    border: 1px solid var(--ir-border);
    border-radius: var(--ir-radius-nested);
    background: var(--ir-panel);
    box-shadow: var(--ir-inset), var(--ir-shadow-1);
}

.ir-sem-filters .ir-field-year   { width: 130px; }
.ir-sem-filters .ir-field-period { width: 230px; }
.ir-sem-filters .ir-field-action { margin-left: auto; }

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 1024px) {
    .ir-nav   { width: 194px; }
    .ir-rooms { width: 260px; }
    .ir-section-inner { grid-template-columns: 260px minmax(0, 1fr); }
}

@media (max-width: 900px) {
    .ir-page-title { font-size: 25px; }

    /* Left rail collapses into a horizontal, scrollable tab strip. */
    .ir-layout { display: block; min-height: 0; }
    .ir-nav {
        width: auto;
        display: flex;
        gap: 6px;
        padding: 10px;
        overflow-x: auto;
        border-right: none;
        border-bottom: 1px solid var(--ir-border);
        scrollbar-width: none;
    }
    .ir-nav::-webkit-scrollbar { display: none; }
    .ir-nav-label { display: none; }
    .ir-menu-btn  { width: auto; margin-bottom: 0; white-space: nowrap; }
    .ir-menu-btn.ir-active::before { left: 12px; right: 12px; top: auto; bottom: 2px; width: auto; height: 2px; transform: none; border-radius: 2px; }

    .ir-section-inner { grid-template-columns: 1fr; min-height: 0; }
    /* Raised from 300px: the rail is now an accordion, and at 300px an expanded
       building left barely one room visible under its own search field. */
    .ir-rooms {
        width: auto;
        max-height: 440px;
        border-right: none;
        border-bottom: 1px solid var(--ir-border);
    }
    /* Shorter inner scroller here so the rail's scrollbar stays the primary one
       and the two don't fight on touch. */
    .ir-bld-rooms { max-height: 216px; }
    .ir-pane, .ir-detail { padding: 20px; }
    .ir-stat-grid  { grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }
    .ir-stat-value { font-size: 26px; }
    .ir-table-wrap { max-height: none; }
    .ir-sem-filters .ir-field-action { margin-left: 0; }
}

@media (max-width: 620px) {
    .ir-page-head  { align-items: flex-start; margin-bottom: 20px; }
    .ir-page-title { font-size: 23px; }
    .ir-page-head-aside { width: 100%; }
    .ir-updated    { width: 100%; justify-content: flex-start; }
    .ir-stat-grid  { grid-template-columns: 1fr 1fr; gap: 10px; }
    .ir-stat       { padding: 14px; }
    .ir-stat-icon  { top: 13px; right: 13px; }
    .ir-stat-icon .ir-icon { width: 15px; height: 15px; }
    .ir-stat-body  { padding-right: 22px; }
    .ir-stat-title { font-size: 12px; margin-bottom: 8px; }
    .ir-stat-value { font-size: 22px; }
    .ir-pane, .ir-detail { padding: 16px; }
    .ir-toolbar-actions .btn { flex: 1; }
    .ir-sem-filters .ir-field-year,
    .ir-sem-filters .ir-field-period,
    .ir-sem-filters .ir-field-action { width: 100%; }
    .ir-sem-filters .ir-field-action .btn { width: 100%; }
}

/* Phones: the section menu wraps into a 2-column grid so every section is
   visible at once, instead of a sideways strip whose hidden scrollbar gave no
   hint that more sections were off-screen. */
@media (max-width: 640px) {
    .ir-nav {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        overflow-x: visible;
    }
    .ir-menu-btn {
        width: 100%;
        white-space: normal;
        justify-content: flex-start;
        text-align: left;
    }
}
</style>

<main class="container ir-page">

    <header class="ir-page-head">
        <div class="ir-page-head-main">
            <nav class="ir-crumbs" aria-label="Breadcrumb">
                <ol>
                    <li>Inventory</li>
                    <li class="ir-crumb-sep" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></li>
                    <li aria-current="page">Inventory Reports</li>
                </ol>
            </nav>
            <h1 class="ir-page-title">Inventory Reports</h1>
            <p class="ir-page-sub">Per room reports, general inventory summary, and semestral/yearly reports.</p>
        </div>
        <div class="ir-page-head-aside">
            <span class="ir-updated" id="ir-last-updated">
                <svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                <span class="ir-updated-label">Last updated</span>
                <span id="ir-last-updated-time">&mdash;</span>
            </span>
        </div>
    </header>

    <div class="card ir-shell">

        <!-- Two-panel layout: left section menu + right content -->
        <div class="ir-layout">

            <!-- ── Left section menu ───────────────────────────────────── -->
            <nav id="ir-menu" class="ir-nav" aria-label="Report sections">
                <p class="ir-nav-label">Reports</p>
                <button type="button" class="ir-menu-btn" data-section="ir-s1">
                    <svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/></svg>
                    <span>Per Room Report</span>
                </button>
                <button type="button" class="ir-menu-btn" data-section="ir-s2">
                    <svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
                    <span>General Inventory</span>
                </button>
                <button type="button" class="ir-menu-btn" data-section="ir-s3">
                    <svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
                    <span>Semestral / Yearly</span>
                </button>
            </nav>

            <!-- ── Right content panels ───────────────────────────────── -->
            <div class="ir-content">

                <!-- ══════════════════════════════════════════════════════ -->
                <!-- SECTION 1 — Per Room Report                           -->
                <!-- ══════════════════════════════════════════════════════ -->
                <div id="ir-s1" class="ir-section">
                    <div class="ir-section-inner">

                        <!-- Room list (left sub-panel) -->
                        <aside id="ir-room-list" class="ir-rooms" aria-label="Buildings and rooms">
                            <div id="ir-room-search-wrap">
                                <div class="ir-search">
                                    <svg class="ir-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg>
                                    <!-- The id is kept as ir-room-search even though this now searches
                                         BUILDINGS: it is referenced by the print stylesheet wrapper and by
                                         the field styling above, and renaming it would be churn with no
                                         user-visible gain. Only the label the user actually reads changed. -->
                                    <input type="search" id="ir-room-search" placeholder="Search building…"
                                           autocomplete="off" aria-label="Search building">
                                </div>
                                <div id="ir-room-search-count" class="ir-search-count"></div>
                            </div>
                            <div id="ir-room-list-inner" class="ir-rooms-inner">
                                <div class="ir-loading">
                                    <div class="ir-loading-row"></div>
                                    <div class="ir-loading-row"></div>
                                    <div class="ir-loading-row"></div>
                                    <div class="ir-loading-row"></div>
                                </div>
                            </div>
                        </aside>

                        <!-- Room detail (right sub-panel) -->
                        <div class="ir-detail">

                            <!-- Per Room Report summary (Part 4) -->
                            <div class="ir-stat-grid" id="ir-room-summary">
                                <div class="ir-stat ir-stat-accent-rooms">
                                    <div class="ir-stat-icon" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"/><path d="M15 9h2a2 2 0 0 1 2 2v10"/><path d="M9 7h2M9 11h2M9 15h2"/></svg></div>
                                    <div class="ir-stat-body">
                                        <div class="ir-stat-title">Total Rooms</div>
                                        <div class="ir-stat-value" id="ir-rs-total">—</div>
                                        <div class="ir-stat-desc">Rooms on record</div>
                                    </div>
                                </div>
                                <div class="ir-stat ir-stat-accent-total">
                                    <div class="ir-stat-icon" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg></div>
                                    <div class="ir-stat-body">
                                        <div class="ir-stat-title">Rooms with Items</div>
                                        <div class="ir-stat-value" id="ir-rs-filled">—</div>
                                        <div class="ir-stat-desc" id="ir-rs-filled-desc">Rooms holding inventory</div>
                                    </div>
                                </div>
                                <div class="ir-stat ir-stat-accent-warning">
                                    <div class="ir-stat-icon" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"/></svg></div>
                                    <div class="ir-stat-body">
                                        <div class="ir-stat-title">Empty Rooms</div>
                                        <div class="ir-stat-value" id="ir-rs-empty">—</div>
                                        <div class="ir-stat-desc" id="ir-rs-empty-desc">No inventory assigned</div>
                                    </div>
                                </div>
                            </div>

                            <div class="ir-toolbar">
                                <div id="ir-room-detail-header">
                                    <p class="ir-detail-meta">Select a room from the list to view its inventory.</p>
                                </div>
                                <div class="ir-toolbar-actions" id="ir-room-detail-actions" style="display:none;">
                                    <button type="button" id="ir-print-room-btn" class="btn btn-secondary ir-print-btn">
                                        <svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
                                        Print This Room Report
                                    </button>
                                </div>
                            </div>
                            <hr class="ir-divider ir-divider-tight">
                            <div id="ir-room-items-container" class="ir-table-wrap"></div>
                        </div>

                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════ -->
                <!-- SECTION 2 — General Inventory Report                  -->
                <!-- ══════════════════════════════════════════════════════ -->
                <div id="ir-s2" class="ir-section ir-pane" style="display:none;">

                    <!-- Summary cards (Part 2): icon + title + large value + description -->
                    <div class="ir-stat-grid">
                        <div class="ir-stat ir-stat-accent-inventory">
                            <div class="ir-stat-icon" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg></div>
                            <div class="ir-stat-body">
                                <div class="ir-stat-title">Items in Inventory</div>
                                <div class="ir-stat-value" id="ir-gen-inventory">—</div>
                                <div class="ir-stat-desc" id="ir-gen-inventory-desc">Total units held in inventory</div>
                            </div>
                        </div>
                        <div class="ir-stat ir-stat-accent-rooms">
                            <div class="ir-stat-icon" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"/><path d="M15 9h2a2 2 0 0 1 2 2v10"/><path d="M9 7h2M9 11h2M9 15h2"/></svg></div>
                            <div class="ir-stat-body">
                                <div class="ir-stat-title">Items in Rooms</div>
                                <div class="ir-stat-value" id="ir-gen-rooms">—</div>
                                <div class="ir-stat-desc" id="ir-gen-rooms-desc">Total items deployed to rooms</div>
                            </div>
                        </div>
                        <div class="ir-stat ir-stat-accent-total">
                            <div class="ir-stat-icon" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7.5 15.5v-3M12 15.5v-7M16.5 15.5v-5"/></svg></div>
                            <div class="ir-stat-body">
                                <div class="ir-stat-title">Total Items Overall</div>
                                <div class="ir-stat-value" id="ir-gen-total">—</div>
                                <div class="ir-stat-desc" id="ir-gen-total-desc">Combined inventory quantity</div>
                            </div>
                        </div>
                        <div class="ir-stat ir-stat-accent-warning">
                            <div class="ir-stat-icon" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4M12 17h.01"/></svg></div>
                            <div class="ir-stat-body">
                                <div class="ir-stat-title">Low Stock Count</div>
                                <div class="ir-stat-value" id="ir-gen-lowstock">—</div>
                                <div class="ir-stat-desc" id="ir-gen-lowstock-desc">Items at or below threshold</div>
                            </div>
                        </div>
                    </div>

                    <!-- TASK 6B PHASE 2 — this toolbar used to head a
                         "Bodega / Stockrooms" table that broke Inventory down
                         per stockroom. Inventory is now a single centralized
                         pool, so that breakdown no longer exists; the toolbar
                         stays because it carries the Export / Print actions
                         for the whole section. Nothing was lost: the table's
                         two figures (item count and total quantity) are the
                         "Items in Inventory" card above and the "Total Items"
                         / "Total Quantity" cards below. -->
                    <div id="ir-gen-toolbar" class="ir-toolbar">
                        <div>
                            <p class="ir-section-title">General Inventory Report</p>
                            <p class="ir-section-sub">Stock on hand and room assignments for the current period.</p>
                        </div>
                        <div class="ir-toolbar-actions" id="ir-gen-toolbar-actions">
                            <button type="button" id="ir-export-csv-btn" class="btn btn-secondary">
                                <svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 12 5 5 5-5"/><path d="M5 21h14"/></svg>
                                Export to CSV
                            </button>
                            <button type="button" id="ir-print-gen-btn" class="btn btn-secondary ir-print-btn">
                                <svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
                                Print General Report
                            </button>
                        </div>
                    </div>
                    <div class="ir-toolbar">
                        <div>
                            <p class="ir-section-title">All Rooms</p>
                            <p class="ir-section-sub">Every room on record with its capacity and assigned inventory.</p>
                        </div>
                    </div>
                    <div id="ir-gen-rooms-table" class="ir-table-wrap"></div>

                    <hr class="ir-divider">

                    <!-- Bottom summary. Every figure is re-used from the single
                         /api/analytics/inventory-summary request this section
                         already makes — no extra API call. -->
                    <div class="ir-toolbar">
                        <div>
                            <p class="ir-section-title">Inventory Summary</p>
                            <p class="ir-section-sub">Roll-up across inventory and rooms for the current period.</p>
                        </div>
                    </div>
                    <div class="ir-stat-grid" id="ir-gen-summary-cards">
                        <div class="ir-stat ir-stat-accent-inventory">
                            <div class="ir-stat-icon" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21V9l9-6 9 6v12"/><path d="M3 21h18"/><path d="M9 21v-8h6v8"/></svg></div>
                            <div class="ir-stat-body">
                                <div class="ir-stat-title">Units in Inventory</div>
                                <div class="ir-stat-value" id="ir-sum-inventory-qty">&mdash;</div>
                                <div class="ir-stat-desc" id="ir-sum-inventory-qty-desc">Held centrally, not yet deployed</div>
                            </div>
                        </div>
                        <div class="ir-stat ir-stat-accent-rooms">
                            <div class="ir-stat-icon" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h18M3 12h18M3 17h18"/></svg></div>
                            <div class="ir-stat-body">
                                <div class="ir-stat-title">Total Items</div>
                                <div class="ir-stat-value" id="ir-sum-items">&mdash;</div>
                                <div class="ir-stat-desc" id="ir-sum-items-desc">Distinct item records</div>
                            </div>
                        </div>
                        <div class="ir-stat ir-stat-accent-total">
                            <div class="ir-stat-icon" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7.5 15.5v-3M12 15.5v-7M16.5 15.5v-5"/></svg></div>
                            <div class="ir-stat-body">
                                <div class="ir-stat-title">Total Quantity</div>
                                <div class="ir-stat-value" id="ir-sum-qty">&mdash;</div>
                                <div class="ir-stat-desc" id="ir-sum-qty-desc">Units across all locations</div>
                            </div>
                        </div>
                        <div class="ir-stat ir-stat-accent-warning">
                            <div class="ir-stat-icon" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4M12 17h.01"/></svg></div>
                            <div class="ir-stat-body">
                                <div class="ir-stat-title">Low Stock Items</div>
                                <div class="ir-stat-value" id="ir-sum-lowstock">&mdash;</div>
                                <div class="ir-stat-desc" id="ir-sum-lowstock-desc">At or below threshold</div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ══════════════════════════════════════════════════════ -->
                <!-- SECTION 3 — Semestral / Yearly Report                 -->
                <!-- ══════════════════════════════════════════════════════ -->
                <div id="ir-s3" class="ir-section ir-pane" style="display:none;">

                    <!-- Filters -->
                    <div class="ir-sem-filters">
                        <div class="ir-field-year">
                            <label class="ir-field-label" for="ir-sem-year">Year</label>
                            <input type="number" id="ir-sem-year" class="form-control" min="2000" max="2099">
                        </div>
                        <div class="ir-field-period">
                            <label class="ir-field-label" for="ir-sem-period">Period</label>
                            <select id="ir-sem-period" class="form-control">
                                <option value="1">1st Semester (Jan – Jun)</option>
                                <option value="2">2nd Semester (Jul – Dec)</option>
                                <option value="full">Full Year</option>
                            </select>
                        </div>
                        <div class="ir-field-action">
                            <button type="button" id="ir-sem-generate" class="btn btn-primary">
                                <svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg>
                                Generate Report
                            </button>
                        </div>
                    </div>

                    <!-- Report output -->
                    <div id="ir-sem-output" style="display:none;">

                        <div class="ir-toolbar">
                            <div>
                                <p id="ir-sem-period-label" class="ir-section-title"></p>
                                <p class="ir-section-sub">Receipts, dispatches, damage reports, and closing stock for the selected period.</p>
                            </div>
                            <div class="ir-toolbar-actions">
                                <button type="button" id="ir-print-sem-btn" class="btn btn-secondary ir-print-btn">
                                    <svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
                                    Print Semestral Report
                                </button>
                            </div>
                        </div>

                        <!-- Report A: Items Received -->
                        <section class="ir-report-block">
                            <div class="ir-report-head">
                                <span class="ir-report-letter" aria-hidden="true">A</span>
                                <h3 class="ir-report-title">Items Received</h3>
                            </div>
                            <div id="ir-sem-receipts" class="ir-table-wrap"></div>
                        </section>

                        <!-- Report B: Items Dispatched -->
                        <section class="ir-report-block">
                            <div class="ir-report-head">
                                <span class="ir-report-letter" aria-hidden="true">B</span>
                                <h3 class="ir-report-title">Items Dispatched</h3>
                            </div>
                            <div id="ir-sem-dispatches" class="ir-table-wrap"></div>
                        </section>

                        <!-- Report C: Damage Reports -->
                        <section class="ir-report-block">
                            <div class="ir-report-head">
                                <span class="ir-report-letter" aria-hidden="true">C</span>
                                <h3 class="ir-report-title">Damage Reports</h3>
                            </div>
                            <div id="ir-sem-damages" class="ir-table-wrap"></div>
                        </section>

                        <!-- Report D: Current Stock Summary -->
                        <section class="ir-report-block">
                            <div class="ir-report-head">
                                <span class="ir-report-letter" aria-hidden="true">D</span>
                                <h3 class="ir-report-title">Current Stock Summary</h3>
                            </div>
                            <!-- Reuses the same .ir-stat component as Sections 1 & 2 (Part 5). -->
                            <div id="ir-sem-stock-cards" class="ir-stat-grid">
                                <div class="ir-stat ir-stat-accent-total">
                                    <div class="ir-stat-icon" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7.5 15.5v-3M12 15.5v-7M16.5 15.5v-5"/></svg></div>
                                    <div class="ir-stat-body">
                                        <div class="ir-stat-title">Total Quantity</div>
                                        <div class="ir-stat-value" id="ir-sem-qty">—</div>
                                        <div class="ir-stat-desc">Units currently in stock</div>
                                    </div>
                                </div>
                                <div class="ir-stat ir-stat-accent-inventory">
                                    <div class="ir-stat-icon" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg></div>
                                    <div class="ir-stat-body">
                                        <div class="ir-stat-title">Distinct Items</div>
                                        <div class="ir-stat-value" id="ir-sem-distinct">—</div>
                                        <div class="ir-stat-desc">Unique item records</div>
                                    </div>
                                </div>
                                <div class="ir-stat ir-stat-accent-warning">
                                    <div class="ir-stat-icon" aria-hidden="true"><svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4M12 17h.01"/></svg></div>
                                    <div class="ir-stat-body">
                                        <div class="ir-stat-title">Low Stock Count</div>
                                        <div class="ir-stat-value" id="ir-sem-ls">—</div>
                                        <div class="ir-stat-desc">Items at or below threshold</div>
                                    </div>
                                </div>
                            </div>
                            <div id="ir-sem-stock-table" class="ir-table-wrap"></div>
                        </section>

                    </div><!-- /#ir-sem-output -->

                </div>

            </div><!-- /.ir-content -->
        </div><!-- /.ir-layout -->

    </div><!-- /.ir-shell -->
</main>

<script>
// ---------------------------------------------------------------------------
// API base URLs
// ---------------------------------------------------------------------------
// NOTE: the app is served from a SUBDIRECTORY (/School_Facility_Maintenance_System),
// so every API path MUST go through window.SFMS_PUBLIC_URL(). These two constants
// previously used bare root-absolute paths ('/api/rooms', '/api/inventory-rooms'),
// which resolved to http://localhost/api/... and returned Apache's 404 HTML page.
// irFetch() then called .json() on that HTML, threw, and the catch blocks rendered
// "Failed to load rooms." and "Failed to load rooms data." — the routes were fine
// all along; only the URLs were wrong.
const IR_ROOMS_API    = window.SFMS_PUBLIC_URL
    ? window.SFMS_PUBLIC_URL('/api/rooms')
    : '/api/rooms';

// TASK 6B PHASE 2 — IR_INV_ROOMS_API (/api/inventory-rooms) was removed along
// with the per-stockroom table it fed. The route itself is untouched and still
// live; this page simply no longer calls it.

const IR_ITEMS_API    = window.SFMS_PUBLIC_URL
    ? window.SFMS_PUBLIC_URL('/api/items')
    : '/api/items';

// IR_HEALTH_API (/api/analytics/inventory-health) was removed: the General
// Inventory cards now read every figure from /api/analytics/inventory-summary,
// which supplies the same low-stock count plus the inventory/room placement
// split in a single request.

const IR_SUMMARY_API  = window.SFMS_PUBLIC_URL
    ? window.SFMS_PUBLIC_URL('/api/analytics/inventory-summary')
    : '/api/analytics/inventory-summary';

const IR_RECEIPTS_API = window.SFMS_PUBLIC_URL('/api/purchase-receipts');

// Same subdirectory bug as IR_ROOMS_API above: Section 3's dispatch report called
// fetch('/api/dispatches?…') directly, which 404s. Verified: /api/dispatches -> 404,
// /School_Facility_Maintenance_System/api/dispatches -> 200.
const IR_DISPATCH_API = window.SFMS_PUBLIC_URL
    ? window.SFMS_PUBLIC_URL('/api/dispatches')
    : '/api/dispatches';

const IR_DMG_API      = window.SFMS_PUBLIC_URL
    ? window.SFMS_PUBLIC_URL('/api/damage-reports')
    : '/api/damage-reports';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function irEsc(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function irFetch(url, options) {
    const response = await fetch(url, options);
    return { response, data: await response.json() };
}

const IR_JSON_OPTS = { credentials: 'same-origin', headers: { 'Accept': 'application/json' } };

/**
 * Fetch JSON and unwrap the standard { success, message, data } envelope.
 * Throws on transport failure, non-2xx, or success:false so every caller can
 * use one catch block.
 */
async function irGet(url) {
    const { response, data: payload } = await irFetch(url, IR_JSON_OPTS);
    if (!response.ok || !payload || payload.success !== true) {
        throw new Error((payload && payload.message) || `Request failed (HTTP ${response.status})`);
    }
    return payload.data;
}

/**
 * Part 6 — "Avoid unnecessary API requests. Do not duplicate queries."
 * Section 1 (Per Room Report) and Section 2 (All Rooms table) both need the
 * exact same /api/rooms payload. This memoises the in-flight promise so the
 * request is issued once per page load no matter which section loads first.
 */
const IR_CACHE = new Map();

function irGetCached(key, url) {
    if (!IR_CACHE.has(key)) {
        IR_CACHE.set(key, irGet(url).catch((err) => {
            IR_CACHE.delete(key);   // don't cache failures — allow a retry
            throw err;
        }));
    }
    return IR_CACHE.get(key);
}

/** Rooms + per-room item counts. Shared by Section 1 and Section 2. */
function irGetRooms() {
    return irGetCached('rooms', `${IR_ROOMS_API}?per_page=200&with_item_counts=1`);
}

/** Inventory summary (totals + inventory/room placement split). */
function irGetSummary() {
    return irGetCached('summary', IR_SUMMARY_API);
}

// ── Render helpers (Part 3: loading indicator + empty-state design) ─────────

function irLoadingHTML(rows) {
    let html = '<div class="ir-loading">';
    for (let i = 0; i < (rows || 4); i++) html += '<div class="ir-loading-row"></div>';
    return html + '</div>';
}

function irSetLoading(elId, rows) {
    const el = document.getElementById(elId);
    if (el) el.innerHTML = irLoadingHTML(rows);
}

/**
 * Inline SVG icon set. Presentation only — replaces the emoji glyphs that
 * rendered at a different size/weight on every OS and read as consumer-grade
 * next to the rest of the interface. Icons inherit currentColor, so they follow
 * the theme automatically.
 */
function irSvg(paths) {
    return '<svg class="ir-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
        + ' stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        + paths + '</svg>';
}

const IR_ICON = {
    rooms:    irSvg('<path d="M3 21h18"/><path d="M5 21V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v16"/><path d="M15 9h2a2 2 0 0 1 2 2v10"/><path d="M9 7h2M9 11h2M9 15h2"/>'),
    box:      irSvg('<path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>'),
    search:   irSvg('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/>'),
    warehouse:irSvg('<path d="M3 21V9l9-5 9 5v12"/><path d="M9 21v-6h6v6"/>'),
    receipt:  irSvg('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/>'),
    dispatch: irSvg('<path d="M10 17h4V5H2v12h3"/><path d="M20 17h2v-4l-3-4h-4v8h2"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>'),
    damage:   irSvg('<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76Z"/>'),
    check:    irSvg('<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>'),
    warning:  irSvg('<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4M12 17h.01"/>')
};

function irEmptyHTML(icon, title, hint) {
    return '<div class="ir-empty">'
        + `<div class="ir-empty-icon" aria-hidden="true">${icon}</div>`
        + `<span class="ir-empty-title">${irEsc(title)}</span>`
        + (hint ? `<span class="ir-empty-hint">${irEsc(hint)}</span>` : '')
        + '</div>';
}

function irErrorHTML(title, detail) {
    return '<div class="ir-empty ir-empty-danger">'
        + `<div class="ir-empty-icon" aria-hidden="true">${IR_ICON.warning}</div>`
        + `<span class="ir-empty-title">${irEsc(title)}</span>`
        + `<span class="ir-empty-hint">${irEsc(detail || 'Please try again.')}</span>`
        + '</div>';
}

/** Locale-formatted integer with thousands separators; em dash when unknown. */
function irNum(value) {
    if (value === null || value === undefined || value === '') return '—';
    const n = Number(value);
    return Number.isFinite(n) ? n.toLocaleString() : irEsc(value);
}

function irPlural(n, singular, plural) {
    return `${irNum(n)} ${Number(n) === 1 ? singular : (plural || singular + 's')}`;
}

// Badges use the .ir-pill-* classes defined in this page's <style> block. The
// previous inline pairs (e.g. background:#d1fae5;color:#065f46) were light-theme
// only and washed out against the dark surface.
function irPill(variant, label) {
    return `<span class="ir-pill ir-pill-${variant}">${irEsc(label)}</span>`;
}

/**
 * Header freshness stamp. This is the time the BROWSER last successfully pulled
 * data, not a server-side data-modified timestamp — no endpoint exposes one, and
 * inventing one would mean touching the API. Labelled "Last updated" and shown
 * as a local wall-clock time so it can't be read as a server guarantee.
 */
function irMarkUpdated() {
    const el = document.getElementById('ir-last-updated-time');
    if (!el) return;
    el.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function irTitleCase(value) {
    return String(value || '').replace(/_/g, ' ').replace(/\b\w/g, (s) => s.toUpperCase());
}

function irStatusBadge(status) {
    const map = {
        available:    'success',
        low_stock:    'warning',
        out_of_stock: 'danger',
        damaged:      'violet',
        for_repair:   'orange',
    };
    return irPill(map[status] || 'neutral', irTitleCase(status) || '—');
}

function irSeverityBadge(severity) {
    const map = {
        low:      'success',
        medium:   'warning',
        high:     'orange',
        critical: 'danger',
    };
    return irPill(map[severity] || 'neutral', String(severity || '—').toUpperCase());
}

function irDmgStatusBadge(status) {
    const map = {
        pending:      'warning',
        under_review: 'orange',
        repairing:    'info',
        repaired:     'success',
        replaced:     'violet',
        closed:       'neutral',
    };
    return irPill(map[status] || 'neutral', irTitleCase(status) || '—');
}

// ---------------------------------------------------------------------------
// Section switching (lazy-loads each section on first activation)
// ---------------------------------------------------------------------------

const irLoadedSections = new Set();

function irSwitchSection(sectionId) {
    document.querySelectorAll('.ir-section').forEach((el) => { el.style.display = 'none'; });
    document.getElementById(sectionId).style.display = 'block';

    // Active state is a class so it inherits theme variables instead of the old
    // hardcoded #2563eb / #374151 inline colours.
    document.querySelectorAll('.ir-menu-btn').forEach((btn) => {
        btn.classList.toggle('ir-active', btn.dataset.section === sectionId);
    });

    if (!irLoadedSections.has(sectionId)) {
        irLoadedSections.add(sectionId);
        switch (sectionId) {
            case 'ir-s1': irLoadRoomList(); break;
            case 'ir-s2': irLoadGeneralReport(); break;
            case 'ir-s3': /* Section 3 loads on Generate click */ break;
        }
    }
}

// ===========================================================================
// SECTION 1 — Per Room Report
// ===========================================================================

let irActiveRoomId   = null;
let irActiveRoomName = null;

let irAllRooms  = [];   // full room list, as returned by /api/rooms
let irBuildings = [];   // [{ key, name, rooms: [...] }] derived from irAllRooms

/**
 * Loads the room list and the Per Room summary cards from ONE shared request
 * (irGetRooms(), memoised — Section 2's All Rooms table reuses the same promise).
 */
async function irLoadRoomList() {
    const inner = document.getElementById('ir-room-list-inner');
    try {
        const data  = await irGetRooms();
        irAllRooms  = Array.isArray(data?.rooms) ? data.rooms : [];

        irRenderRoomSummary(irAllRooms);
        irRenderBuildingList(irAllRooms);
        irMarkUpdated();

        // No room is auto-selected any more. Every building must start collapsed,
        // and auto-selecting a room would have forced one of them open just to
        // have somewhere to paint the highlight. The detail pane keeps its
        // "Select a room from the list…" placeholder until the user picks one.

    } catch (err) {
        inner.innerHTML = irErrorHTML('Failed to load rooms.', err.message);
        ['ir-rs-total', 'ir-rs-filled', 'ir-rs-empty'].forEach((id) => {
            document.getElementById(id).textContent = '—';
        });
    }
}

/** Part 4 — Total Rooms / Rooms with Items / Empty Rooms. */
function irRenderRoomSummary(rooms) {
    const total  = rooms.length;
    const filled = rooms.filter((r) => Number(r.item_count) > 0).length;
    const empty  = total - filled;
    const units  = rooms.reduce((sum, r) => sum + Number(r.total_quantity || 0), 0);

    document.getElementById('ir-rs-total').textContent  = irNum(total);
    document.getElementById('ir-rs-filled').textContent = irNum(filled);
    document.getElementById('ir-rs-empty').textContent  = irNum(empty);

    document.getElementById('ir-rs-filled-desc').textContent = total
        ? `${irPlural(units, 'unit')} across ${irPlural(filled, 'room')}`
        : 'Rooms holding inventory';
    document.getElementById('ir-rs-empty-desc').textContent = total
        ? `${Math.round((empty / total) * 100)}% of all rooms`
        : 'No inventory assigned';
}

/**
 * Groups the flat /api/rooms payload into buildings.
 *
 * ONE SOURCE OF TRUTH: buildings and their room counts are derived from the
 * same room payload the summary cards and Section 2 already use, so a building
 * name or count here can never drift from the room data it describes. No extra
 * request is issued.
 *
 * Grouped on building_id, not building_name — two buildings may legitimately
 * share a display name, and collapsing them into one row would silently hide
 * rooms. Rooms with no building fall into a single "Unassigned" group rather
 * than being dropped.
 */
function irGroupRoomsByBuilding(rooms) {
    const collator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });
    const map = new Map();

    rooms.forEach((r) => {
        const hasBuilding = r.building_id !== null && r.building_id !== undefined && r.building_id !== '';
        const key = hasBuilding ? `b${r.building_id}` : 'unassigned';
        if (!map.has(key)) {
            map.set(key, { key, name: r.building_name || 'Unassigned', rooms: [] });
        }
        map.get(key).rooms.push(r);
    });

    const groups = Array.from(map.values());
    // numeric:true so "Room 2" sorts before "Room 10", not after it.
    groups.forEach((g) => g.rooms.sort((a, b) => collator.compare(a.name || '', b.name || '')));
    groups.sort((a, b) => collator.compare(a.name, b.name));
    return groups;
}

/**
 * Renders the BUILDING list. Rooms are not rendered here — each building's rows
 * are built on first expand (irBuildBuildingPanel), which is what keeps ~163
 * room elements out of the initial DOM.
 */
function irRenderBuildingList(rooms) {
    const inner   = document.getElementById('ir-room-list-inner');
    const counter = document.getElementById('ir-room-search-count');

    irBuildings = irGroupRoomsByBuilding(rooms);

    if (irBuildings.length === 0) {
        counter.textContent = '';
        inner.innerHTML = irEmptyHTML(IR_ICON.rooms, 'No buildings found.',
            'Add a building and its rooms under Buildings to start tracking inventory.');
        return;
    }

    const caret = '<svg class="ir-bld-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
        + ' stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        + '<path d="m9 18 6-6-6-6"/></svg>';

    let html = '';
    irBuildings.forEach((b) => {
        const btnId   = `ir-bld-btn-${irEsc(b.key)}`;
        const panelId = `ir-bld-panel-${irEsc(b.key)}`;
        html += `<div class="ir-bld" data-bld-key="${irEsc(b.key)}" data-bld-name="${irEsc(b.name)}">
                    <button type="button" class="ir-bld-btn" id="${btnId}"
                            aria-expanded="false" aria-controls="${panelId}"
                            title="${irEsc(b.name)}">
                        ${caret}
                        <span class="ir-bld-name">${irEsc(b.name)}</span>
                        <span class="ir-bld-count">${irPlural(b.rooms.length, 'room')}</span>
                    </button>
                    <div class="ir-bld-panel" id="${panelId}" role="region" aria-labelledby="${btnId}"></div>
                 </div>`;
    });
    // Container for the "no buildings match" state; kept as a sibling so the
    // building rows themselves are only hidden, never destroyed (which would
    // throw away expanded state and any in-progress room search).
    html += '<div id="ir-bld-no-match" class="ir-hidden"></div>';

    inner.innerHTML = html;
    counter.textContent = irPlural(irBuildings.length, 'building');

    inner.querySelectorAll('.ir-bld-btn').forEach((btn) => {
        btn.addEventListener('click', () => irToggleBuilding(btn.closest('.ir-bld')));
    });
}

/** Expand/collapse one building. Rooms are built lazily, once. */
function irToggleBuilding(card) {
    if (!card) return;

    const btn   = card.querySelector('.ir-bld-btn');
    const panel = card.querySelector('.ir-bld-panel');
    const open  = card.classList.toggle('ir-open');

    btn.setAttribute('aria-expanded', open ? 'true' : 'false');

    if (open && !panel.dataset.built) {
        irBuildBuildingPanel(card, panel);
        panel.dataset.built = '1';
    }
}

/** Builds one building's room rows + its scoped room search. */
function irBuildBuildingPanel(card, panel) {
    const group = irBuildings.find((b) => b.key === card.dataset.bldKey);
    const rooms = group ? group.rooms : [];

    if (rooms.length === 0) {
        panel.innerHTML = '<p class="ir-bld-note">No rooms are currently registered in this building.</p>';
        return;
    }

    let html = `<label class="ir-bld-search">
                    <svg class="ir-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg>
                    <input type="search" class="ir-bld-search-input" autocomplete="off"
                           placeholder="Search rooms in this building…"
                           aria-label="Search rooms in ${irEsc(group.name)}">
                </label>
                <div class="ir-bld-rooms">`;

    rooms.forEach((r) => {
        const count = Number(r.item_count || 0);
        // Same markup/classes as before this change, so a selected room looks
        // and behaves identically — only its container moved.
        html += `<button type="button" class="ir-room-btn"
                         data-room-id="${irEsc(r.id)}"
                         data-room-name="${irEsc(r.name)}"
                         data-room-search="${irEsc(String(r.name || '').toLowerCase())}"
                         title="${irEsc(r.name)}">
                    <span class="ir-room-name">${irEsc(r.name)}</span>
                    <span class="ir-room-count">${count > 0 ? irNum(count) : ''}</span>
                 </button>`;
    });

    html += '</div><p class="ir-bld-note ir-hidden" data-room-no-match>No rooms match your search.</p>';
    panel.innerHTML = html;

    panel.querySelectorAll('.ir-room-btn').forEach((btn) => {
        btn.addEventListener('click', () => irSelectRoom(btn));
    });

    // Scoped room search: only ever touches rows inside THIS panel, so typing
    // here never searches the other buildings' rooms.
    const input = panel.querySelector('.ir-bld-search-input');
    let timer = null;
    const run = () => irFilterRoomsInPanel(panel, input.value);
    input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(run, 100); });
    input.addEventListener('search', run);

    // If the selected room lives in this building, restore its highlight.
    if (irActiveRoomId !== null) {
        panel.querySelectorAll('.ir-room-btn').forEach((btn) => {
            if (String(btn.dataset.roomId) === String(irActiveRoomId)) btn.classList.add('ir-active');
        });
    }
}

/**
 * Selects a room. Unchanged behaviour: same active class, same call into
 * irLoadRoomItems(), which still renders the existing Per Room Inventory Report.
 * The only difference is that the previous selection may now live in a
 * different building's panel, so the clear is scoped to the whole rail.
 */
function irSelectRoom(btn) {
    irActiveRoomId   = btn.dataset.roomId;
    irActiveRoomName = btn.dataset.roomName;

    document.querySelectorAll('#ir-room-list-inner .ir-room-btn.ir-active')
        .forEach((b) => b.classList.remove('ir-active'));
    btn.classList.add('ir-active');

    irLoadRoomItems(irActiveRoomId, irActiveRoomName);
}

/** Filters rooms WITHIN one expanded building. */
function irFilterRoomsInPanel(panel, term) {
    const needle = String(term || '').trim().toLowerCase();
    const note   = panel.querySelector('[data-room-no-match]');
    let shown = 0;

    panel.querySelectorAll('.ir-room-btn').forEach((btn) => {
        const hit = needle === '' || (btn.dataset.roomSearch || '').includes(needle);
        btn.classList.toggle('ir-hidden', !hit);
        if (hit) shown++;
    });

    if (note) note.classList.toggle('ir-hidden', shown > 0);
}

/**
 * Filters the BUILDING list by name. Rows are hidden rather than re-rendered so
 * that an expanded building stays expanded, its room search keeps its text, and
 * the focused input keeps focus while the user types.
 */
function irFilterBuildings(term) {
    const inner   = document.getElementById('ir-room-list-inner');
    const counter = document.getElementById('ir-room-search-count');
    const noMatch = document.getElementById('ir-bld-no-match');

    // Absent when the list rendered an error or the "no buildings" state.
    if (!inner || !noMatch) return;

    const needle = String(term || '').trim().toLowerCase();
    const cards  = inner.querySelectorAll('.ir-bld');
    let shown = 0;

    cards.forEach((card) => {
        const hit = needle === '' || (card.dataset.bldName || '').toLowerCase().includes(needle);
        card.classList.toggle('ir-hidden', !hit);
        if (hit) shown++;
    });

    counter.textContent = needle === ''
        ? irPlural(cards.length, 'building')
        : `${irNum(shown)} of ${irNum(cards.length)} buildings match`;

    if (shown === 0) {
        noMatch.innerHTML = irEmptyHTML(IR_ICON.search, 'No buildings match your search.',
            `Nothing matches “${term}”. Try a different search.`);
        noMatch.classList.remove('ir-hidden');
    } else {
        noMatch.classList.add('ir-hidden');
    }
}

async function irLoadRoomItems(roomId, roomName) {
    const header    = document.getElementById('ir-room-detail-header');
    const actions   = document.getElementById('ir-room-detail-actions');
    const container = document.getElementById('ir-room-items-container');

    const heading = (sub) =>
        `<h3 class="ir-detail-title">${irEsc(roomName)}</h3>
         <p class="ir-detail-meta">${sub}</p>`;

    header.innerHTML      = heading('Per Room Inventory Report');
    actions.style.display = 'flex';
    container.className   = 'ir-table-wrap';
    container.innerHTML   = irLoadingHTML(4);

    try {
        const d = await irGet(`${IR_ITEMS_API}?room_id=${encodeURIComponent(roomId)}&per_page=200`);

        // Defensive shape handling (same pattern as dispatch-create.php)
        let items = [];
        if (Array.isArray(d?.data))             { items = d.data; }
        else if (Array.isArray(d?.items?.data)) { items = d.items.data; }
        else if (Array.isArray(d?.items))       { items = d.items; }
        else if (Array.isArray(d))              { items = d; }

        const totalQty = items.reduce((sum, it) => sum + Number(it.quantity || 0), 0);

        header.innerHTML = heading(
            `<strong>${irNum(items.length)}</strong> item${items.length !== 1 ? 's' : ''}`
            + ` &middot; <strong>${irNum(totalQty)}</strong> total unit${totalQty !== 1 ? 's' : ''}`
        );

        if (items.length === 0) {
            container.innerHTML = irEmptyHTML(IR_ICON.box, 'No items in this room.',
                'Items dispatched to this room will appear here.');
            return;
        }

        let html = '<table class="ir-table"><thead><tr>'
            + '<th>Item Name</th><th>Category</th><th class="ir-num">Quantity</th>'
            + '<th>Status</th><th>Condition</th>'
            + '</tr></thead><tbody>';
        items.forEach((item) => {
            const catName = item.category?.name ?? item.category_name ?? '—';
            const meta    = [item.brand, item.model].filter(Boolean).join(' ');
            html += '<tr>';
            html += `<td class="ir-name">${irEsc(item.name)}`
                 +  (meta ? `<span class="ir-sub">${irEsc(meta)}</span>` : '')
                 +  '</td>';
            html += `<td>${irEsc(catName)}</td>`;
            html += `<td class="ir-num">${irNum(item.quantity)}</td>`;
            html += `<td>${irStatusBadge(item.status)}</td>`;
            html += `<td>${irEsc(item.item_condition || item.condition || '—')}</td>`;
            html += '</tr>';
        });
        html += '</tbody><tfoot><tr>'
            + `<td class="ir-total-label" colspan="2">Total — ${irPlural(items.length, 'item')}</td>`
            + `<td class="ir-num">${irNum(totalQty)}</td>`
            + '<td colspan="2"></td>'
            + '</tr></tfoot></table>';
        container.innerHTML = html;

    } catch (err) {
        header.innerHTML   = heading('Per Room Inventory Report');
        container.innerHTML = irErrorHTML('Failed to load items for this room.', err.message);
    }
}

// ===========================================================================
// SECTION 2 — General Inventory Report
// ===========================================================================

async function irLoadGeneralReport() {
    irLoadGeneralCards();
    irLoadRoomsTable();
}

/**
 * Part 1 bug + Part 6 performance.
 *
 * Previously this made THREE requests: /api/analytics/inventory-health plus two
 * throwaway /api/items?per_page=1 calls whose only purpose was to read the
 * pagination `total`. Worse, the placement split was computed client-side from
 * two paginated totals that did not actually distinguish placement at all, so
 * "Items in Rooms" was structurally pinned to 0 and the first card reported the
 * grand total of all items.
 *
 * It is now ONE request to /api/analytics/inventory-summary, which returns the
 * real placement split (inventory = item_type 'inventory_stock', rooms =
 * item_type 'room_asset') computed server-side from a single query. All figures
 * are live DB values; nothing is hardcoded.
 */
async function irLoadGeneralCards() {
    const ids = ['ir-gen-inventory', 'ir-gen-rooms', 'ir-gen-total', 'ir-gen-lowstock'];
    try {
        const d = await irGetSummary();

        document.getElementById('ir-gen-inventory').textContent = irNum(d.inventory_quantity);
        document.getElementById('ir-gen-rooms').textContent    = irNum(d.room_quantity);
        document.getElementById('ir-gen-total').textContent    = irNum(d.total_quantity);
        document.getElementById('ir-gen-lowstock').textContent = irNum(d.low_stock_count);

        // Descriptions carry the distinct-item counts so both figures are visible
        // without inventing a second row of cards.
        document.getElementById('ir-gen-inventory-desc').textContent =
            `${irPlural(d.inventory_items, 'item')} held in inventory`;
        document.getElementById('ir-gen-rooms-desc').textContent =
            Number(d.room_items) > 0
                ? `${irPlural(d.room_items, 'item')} in ${irPlural(d.room_count_with_items, 'room')}`
                : 'No items assigned to rooms yet';
        document.getElementById('ir-gen-total-desc').textContent =
            `${irPlural(d.distinct_items, 'distinct item')}`
            + (Number(d.unplaced_quantity) > 0
                ? ` · ${irNum(d.unplaced_quantity)} unassigned`
                : '');
        document.getElementById('ir-gen-lowstock-desc').textContent =
            Number(d.distinct_items) > 0
                ? `of ${irPlural(d.distinct_items, 'item')} at or below threshold`
                : 'Items at or below threshold';

        // Bottom summary — same payload, no second request.
        document.getElementById('ir-sum-inventory-qty').textContent = irNum(d.inventory_quantity);
        document.getElementById('ir-sum-items').textContent    = irNum(d.distinct_items);
        document.getElementById('ir-sum-qty').textContent      = irNum(d.total_quantity);
        document.getElementById('ir-sum-lowstock').textContent = irNum(d.low_stock_count);
        document.getElementById('ir-sum-inventory-qty-desc').textContent =
            `${irPlural(d.inventory_items, 'item')} not yet deployed`;
        document.getElementById('ir-sum-qty-desc').textContent =
            `${irNum(d.inventory_quantity)} in inventory · ${irNum(d.room_quantity)} in rooms`;
        document.getElementById('ir-sum-lowstock-desc').textContent =
            Number(d.low_stock_count) > 0 ? 'Needs restocking' : 'All items above threshold';

        irMarkUpdated();
    } catch (err) {
        // Surface the failure instead of silently leaving four em dashes behind.
        ids.concat(['ir-sum-inventory-qty', 'ir-sum-items', 'ir-sum-qty', 'ir-sum-lowstock'])
           .forEach((id) => { document.getElementById(id).textContent = '—'; });
        // Descriptions revert to their neutral static captions for the same
        // reason: a stale "N in inventory · N in rooms" under an em dash is
        // worse than no number at all.
        document.getElementById('ir-sum-inventory-qty-desc').textContent = 'Held centrally, not yet deployed';
        document.getElementById('ir-sum-qty-desc').textContent      = 'Units across all locations';
        document.getElementById('ir-sum-lowstock-desc').textContent = 'At or below threshold';
        const desc = document.getElementById('ir-gen-total-desc');
        if (desc) desc.textContent = 'Could not load summary — ' + err.message;
    }
}

// TASK 6B PHASE 2 — irLoadBodegaTable() was removed. It rendered one row per
// inventory_rooms record ("Bodega Name / Code / Item Count / Total Qty /
// Status") and drove the bottom-summary "Stockrooms" card. Inventory is now a
// single centralized pool, so a per-stockroom breakdown is exactly the concept
// being retired. Its two aggregate figures are still on screen — total item
// count and total quantity are the "Items in Inventory" and "Total Quantity"
// cards — and the /api/inventory-rooms route it called is untouched.

async function irLoadRoomsTable() {
    const container = document.getElementById('ir-gen-rooms-table');
    container.innerHTML = irLoadingHTML(4);
    try {
        // Same memoised promise Section 1 uses — no duplicate request.
        const data  = await irGetRooms();
        const rooms = Array.isArray(data?.rooms) ? data.rooms : [];

        if (rooms.length === 0) {
            container.innerHTML = irEmptyHTML(IR_ICON.rooms, 'No rooms found.',
                'Add buildings, floors, and rooms to populate this report.');
            return;
        }

        const totalItems = rooms.reduce((s, r) => s + Number(r.item_count || 0), 0);
        const totalQty   = rooms.reduce((s, r) => s + Number(r.total_quantity || 0), 0);
        const totalCap   = rooms.reduce((s, r) => s + Number(r.capacity || 0), 0);

        let html = '<table class="ir-table"><thead><tr>'
            + '<th>Room Name</th><th>Building</th><th>Floor</th>'
            + '<th class="ir-num">Capacity</th><th class="ir-num">Items</th><th class="ir-num">Total Qty</th>'
            + '</tr></thead><tbody>';
        rooms.forEach((r) => {
            const count = Number(r.item_count || 0);
            html += '<tr>';
            html += `<td class="ir-name">${irEsc(r.name)}</td>`;
            html += `<td>${irEsc(r.building_name || '—')}</td>`;
            html += `<td>${irEsc(r.floor_name || '—')}</td>`;
            html += `<td class="ir-num">${irNum(r.capacity)}</td>`;
            // data-csv keeps the export numeric: the on-screen cell shows an
            // "Empty" pill for zero-item rooms, which must not leak into the
            // CSV's numeric "Items" column.
            html += `<td class="ir-num" data-csv="${count}">${count > 0 ? irNum(count) : irPill('neutral', 'Empty')}</td>`;
            html += `<td class="ir-num">${irNum(r.total_quantity)}</td>`;
            html += '</tr>';
        });
        html += '</tbody><tfoot><tr>'
            + `<td class="ir-total-label" colspan="3">Total — ${irPlural(rooms.length, 'room')}</td>`
            + `<td class="ir-num">${irNum(totalCap)}</td>`
            + `<td class="ir-num">${irNum(totalItems)}</td>`
            + `<td class="ir-num">${irNum(totalQty)}</td>`
            + '</tr></tfoot></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = irErrorHTML('Failed to load rooms data.', err.message);
    }
}

// ===========================================================================
// SECTION 3 — Semestral / Yearly Report
// ===========================================================================

function irGetDateRange() {
    const year   = parseInt(document.getElementById('ir-sem-year').value, 10) || new Date().getFullYear();
    const period = document.getElementById('ir-sem-period').value;
    let dateFrom, dateTo, label;
    if (period === '1') {
        dateFrom = `${year}-01-01`;
        dateTo   = `${year}-06-30`;
        label    = `1st Semester ${year} (Jan – Jun)`;
    } else if (period === '2') {
        dateFrom = `${year}-07-01`;
        dateTo   = `${year}-12-31`;
        label    = `2nd Semester ${year} (Jul – Dec)`;
    } else {
        dateFrom = `${year}-01-01`;
        dateTo   = `${year}-12-31`;
        label    = `Full Year ${year}`;
    }
    return { dateFrom, dateTo, label };
}

async function irGenerateSemReport() {
    const { dateFrom, dateTo, label } = irGetDateRange();

    document.getElementById('ir-sem-output').style.display         = 'block';
    document.getElementById('ir-sem-period-label').textContent      = `Report Period: ${label}`;
    irSetLoading('ir-sem-receipts', 3);
    irSetLoading('ir-sem-dispatches', 3);
    irSetLoading('ir-sem-damages', 3);
    document.getElementById('ir-sem-stock-table').innerHTML         = '';
    document.getElementById('ir-sem-qty').textContent      = '—';
    document.getElementById('ir-sem-distinct').textContent = '—';
    document.getElementById('ir-sem-ls').textContent       = '—';

    // All 4 report sections fire in parallel
    irLoadSemReceipts(dateFrom, dateTo);
    irLoadSemDispatches(dateFrom, dateTo);
    irLoadSemDamages(dateFrom, dateTo);
    irLoadSemStockSummary();
}

async function irLoadSemReceipts(dateFrom, dateTo) {
    const container = document.getElementById('ir-sem-receipts');
    try {
        const params = new URLSearchParams({ date_from: dateFrom, date_to: dateTo });
        const { response, data: payload } = await irFetch(
            `${IR_RECEIPTS_API}?${params.toString()}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const rows = Array.isArray(payload.data?.receipts) ? payload.data.receipts : [];

        if (rows.length === 0) {
            container.innerHTML = irEmptyHTML(IR_ICON.receipt, "No receipts in this period.", "Try a different year or period.");
            return;
        }
        let html = '<table class="ir-table"><thead><tr>'
            + '<th>OR Number</th><th>Date</th><th>Supplier</th><th>Items</th><th>Received By</th>'
            + '</tr></thead><tbody>';
        rows.forEach((r) => {
            const d = r.receipt_date ? new Date(r.receipt_date).toLocaleDateString() : '—';
            html += '<tr>';
            html += `<td><strong>${irEsc(r.or_number)}</strong></td>`;
            html += `<td>${irEsc(d)}</td>`;
            html += `<td>${irEsc(r.supplier_name || '—')}</td>`;
            html += `<td>${irEsc(r.item_count ?? '—')}</td>`;
            html += `<td>${irEsc(r.received_by_name || '—')}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = irErrorHTML("Failed to load receipts.", err.message);
    }
}

async function irLoadSemDispatches(dateFrom, dateTo) {
    const container = document.getElementById('ir-sem-dispatches');
    try {
        const params = new URLSearchParams({ per_page: '200', date_from: dateFrom, date_to: dateTo });
        const data   = await irGet(`${IR_DISPATCH_API}?${params.toString()}`);
        const rows   = Array.isArray(data?.data) ? data.data : [];

        if (rows.length === 0) {
            container.innerHTML = irEmptyHTML(IR_ICON.dispatch, "No dispatches in this period.", "Try a different year or period.");
            return;
        }
        let html = '<table class="ir-table"><thead><tr>'
            + '<th>Dispatch Code</th><th>Department</th><th>Room</th><th>Items</th><th>Date</th>'
            + '</tr></thead><tbody>';
        rows.forEach((r) => {
            const d = r.created_at ? new Date(r.created_at).toLocaleDateString() : '—';
            html += '<tr>';
            html += `<td><strong>${irEsc(r.dispatch_code)}</strong></td>`;
            html += `<td>${irEsc(r.department_name || '—')}</td>`;
            html += `<td>${irEsc(r.room_name || '—')}</td>`;
            html += `<td>${irEsc(r.item_count ?? '—')}</td>`;
            html += `<td>${irEsc(d)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = irErrorHTML("Failed to load dispatches.", err.message);
    }
}

async function irLoadSemDamages(dateFrom, dateTo) {
    const container = document.getElementById('ir-sem-damages');
    try {
        const params = new URLSearchParams({ per_page: '200', date_from: dateFrom, date_to: dateTo });
        const { response, data: payload } = await irFetch(
            `${IR_DMG_API}?${params.toString()}`,
            { credentials: 'same-origin', headers: { 'Accept': 'application/json' } }
        );
        if (!response.ok || !payload.success) throw new Error(payload.message || 'Failed');
        const paginator = payload.data?.reports;
        const rows      = Array.isArray(paginator?.data) ? paginator.data : [];

        if (rows.length === 0) {
            container.innerHTML = irEmptyHTML(IR_ICON.damage, "No damage reports in this period.", "Try a different year or period.");
            return;
        }
        let html = '<table class="ir-table"><thead><tr>'
            + '<th>Report Code</th><th>Item</th><th>Room</th><th>Severity</th><th>Status</th><th>Date</th>'
            + '</tr></thead><tbody>';
        rows.forEach((r) => {
            const d = r.created_at ? new Date(r.created_at).toLocaleDateString() : '—';
            html += '<tr>';
            html += `<td><strong>${irEsc(r.damage_report_code)}</strong></td>`;
            html += `<td>${irEsc(r.item?.name || '—')}</td>`;
            html += `<td>${irEsc(r.room?.name || '—')}</td>`;
            html += `<td>${irSeverityBadge(r.severity_level)}</td>`;
            html += `<td>${irDmgStatusBadge(r.status)}</td>`;
            html += `<td>${irEsc(d)}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = irErrorHTML("Failed to load damage reports.", err.message);
    }
}

async function irLoadSemStockSummary() {
    const tbl = document.getElementById('ir-sem-stock-table');
    tbl.innerHTML = irLoadingHTML(3);
    try {
        // Reuses the memoised summary promise — if Section 2 already loaded it,
        // this costs no extra request (Part 6).
        const d = await irGetSummary();

        document.getElementById('ir-sem-qty').textContent      = irNum(d.total_quantity);
        document.getElementById('ir-sem-distinct').textContent = irNum(d.distinct_items);
        document.getElementById('ir-sem-ls').textContent       = irNum(d.low_stock_count);

        const lowItems = Array.isArray(d.low_stock_items) ? d.low_stock_items : [];
        if (lowItems.length === 0) {
            tbl.innerHTML = irEmptyHTML(IR_ICON.check, "No low-stock items.", "Every item is above its reorder threshold.");
            return;
        }

        const totalQty = lowItems.reduce((s, it) => s + Number(it.quantity || 0), 0);

        let html = '<table class="ir-table"><thead><tr>'
            + '<th>Item Name</th><th class="ir-num">Quantity</th><th class="ir-num">Threshold</th><th>Status</th>'
            + '</tr></thead><tbody>';
        lowItems.forEach((item) => {
            const qty = Number(item.quantity || 0);
            html += '<tr>';
            html += `<td class="ir-name">${irEsc(item.name)}</td>`;
            html += `<td class="ir-num">${irNum(item.quantity)}</td>`;
            html += `<td class="ir-num">${irNum(item.threshold)}</td>`;
            html += `<td>${qty === 0 ? irPill('danger', 'Out of Stock') : irPill('warning', 'Low Stock')}</td>`;
            html += '</tr>';
        });
        html += '</tbody><tfoot><tr>'
            + `<td class="ir-total-label">Total — ${irPlural(lowItems.length, 'item')}</td>`
            + `<td class="ir-num">${irNum(totalQty)}</td>`
            + '<td colspan="2"></td>'
            + '</tr></tfoot></table>';
        tbl.innerHTML = html;
    } catch (err) {
        ['ir-sem-qty', 'ir-sem-distinct', 'ir-sem-ls'].forEach((id) => {
            document.getElementById(id).textContent = '—';
        });
        tbl.innerHTML = irErrorHTML('Failed to load stock summary.', err.message);
    }
}

// ---------------------------------------------------------------------------
// Export to CSV — Section 2 General Inventory
// ---------------------------------------------------------------------------

/**
 * Extracts one table cell's value for CSV.
 *
 * Cells may carry an explicit data-csv override (used where the rendered cell
 * is a pill rather than the raw value). Otherwise, cells that pair a primary
 * label with an .ir-sub secondary line are joined with an em dash — without
 * this, textContent concatenates them into one unreadable run
 * ("Room 12Main Building · Floor 2").
 */
function irCellCsv(td) {
    if (td.hasAttribute('data-csv')) {
        return td.getAttribute('data-csv');
    }

    const sub = td.querySelector('.ir-sub');
    if (sub) {
        const clone = td.cloneNode(true);
        clone.querySelectorAll('.ir-sub').forEach((n) => n.remove());
        const main    = clone.textContent.replace(/\s+/g, ' ').trim();
        const subText = sub.textContent.replace(/\s+/g, ' ').trim();
        return subText ? `${main} — ${subText}` : main;
    }

    return td.textContent.replace(/\s+/g, ' ').trim();
}

function exportInventoryCSV() {
    const rows = [];

    // TASK 6B PHASE 2 — Section A used to be the per-stockroom "Bodega /
    // Stockrooms" table. That table is gone, so the export now leads with the
    // Inventory Summary cards instead: the same aggregate figures, read
    // straight off the rendered cards so the CSV still matches what is on
    // screen.
    rows.push(['Inventory Summary']);
    rows.push(['Metric', 'Value']);
    [
        ['Items in Inventory (units)', 'ir-gen-inventory'],
        ['Items in Rooms (units)',     'ir-gen-rooms'],
        ['Total Items Overall (units)', 'ir-gen-total'],
        ['Distinct Item Records',      'ir-sum-items'],
        ['Low Stock Count',            'ir-gen-lowstock'],
    ].forEach(([label, id]) => {
        const el = document.getElementById(id);
        rows.push([label, el ? el.textContent.trim() : '—']);
    });

    // Section B — All Rooms, so the CSV matches what is on screen.
    const roomRows = document.querySelectorAll('#ir-gen-rooms-table tbody tr');
    if (roomRows.length > 0) {
        rows.push([]);
        rows.push(['All Rooms']);
        rows.push(['Room Name', 'Building', 'Floor', 'Capacity', 'Items', 'Total Quantity']);
        roomRows.forEach((row) => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 0) {
                rows.push([...cells].map(irCellCsv));
            }
        });
    }

    const csv  = rows.map((r) =>
        r.map((c) => '"' + String(c).replace(/"/g, '""') + '"').join(',')
    ).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'inventory-report-' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
    URL.revokeObjectURL(url);
}

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {
    // Section menu
    document.querySelectorAll('.ir-menu-btn').forEach((btn) => {
        btn.addEventListener('click', () => irSwitchSection(btn.dataset.section));
    });

    // Section 1 print button
    document.getElementById('ir-print-room-btn').addEventListener('click', () => window.print());

    // Section 1 BUILDING search — filters the already-loaded list client-side,
    // so typing costs zero extra API requests (Part 6). Rooms have their own
    // search inside each expanded building.
    const searchInput = document.getElementById('ir-room-search');
    let searchTimer = null;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => irFilterBuildings(searchInput.value), 120);
    });
    searchInput.addEventListener('search', () => irFilterBuildings(searchInput.value));

    // Section 2 export + print buttons
    document.getElementById('ir-export-csv-btn').addEventListener('click', exportInventoryCSV);
    document.getElementById('ir-print-gen-btn').addEventListener('click', () => window.print());

    // Section 3: year default + generate button
    document.getElementById('ir-sem-year').value = new Date().getFullYear();
    document.getElementById('ir-sem-generate').addEventListener('click', irGenerateSemReport);
    document.getElementById('ir-print-sem-btn').addEventListener('click', () => window.print());

    // Activate Section 1 by default
    irSwitchSection('ir-s1');
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
