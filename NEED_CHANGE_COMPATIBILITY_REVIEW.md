# Need Change Status — Compatibility Review

**Type:** Read-only inspection. No code or documentation was modified while producing this report.
**Scope:** Every location in the repository that reads, queries, filters, displays, validates, or compares `need_change_status`.
**Method:** Full-text search (`need_change`, case-insensitive) across `app/`, `public/backend/`, `public/frontend/`, `database/migrations/`, root-level scripts, and `db_backups/*.sql`, followed by full reads of every matching file. No `.blade.php` templates exist anywhere in the repo (frontend is plain `.php`), so no Blade layer needed inspection.

---

## 1. Is `"deducted"` already supported everywhere?

**Yes, in every live code path.** Every reachable location that branches on `need_change_status` (frontend badge logic, the detail-page "already processed" guards, and `ReplacementTrackingController`) already treats `'deducted'` as a first-class terminal/approved value. No live code path was found that still assumes the value can only be `pending` / `approved` / `rejected` in a way that would misclassify or break on `'deducted'`.

The only genuine risks are **adjacent** to the status value itself: a data-availability gap in the reports list endpoint, and a schema-drift risk on a hypothetical fresh database install. Both are detailed below.

---

## 2. Findings

### Finding 1 — `/api/reports` list endpoint never selects `need_change_*` columns
**Location:** [app/Http/Controllers/Api/ReportController.php](app/Http/Controllers/Api/ReportController.php) `index()` — the `$query->select([...])` column list contains no `need_change_*` field.
**Consequence:** [public/frontend/pages/reports.php:652-667](public/frontend/pages/reports.php:652) computes `hasNeedChange` from `report.need_change_item_id`, which the list endpoint never sends. The badge-rendering logic itself is fully `'deducted'`-aware and correct — but it never receives real data, so the Need Change column in the reports table always renders `None` regardless of true status.
**Note:** This is **pre-existing and symmetric** — it affects `pending`, `approved`, `rejected`, and `deducted` identically. It is not a regression introduced by adding `'deducted'`, but it does mean `'deducted'` is not currently *visible* in this specific table view.
**Severity: Medium** (functional gap in a visible UI surface, but not caused by and not specific to the `'deducted'` status; pre-dates this change).

### Finding 2 — Migration declares an `enum` lacking `'rejected'`; live DB is `varchar(50)`
**Location:** [database/migrations/2026_04_07_000700_add_need_change_fields_to_maintenance_reports_table.php](database/migrations/2026_04_07_000700_add_need_change_fields_to_maintenance_reports_table.php) declares `need_change_status` as `enum('pending','approved','deducted','failed')`, guarded by `Schema::hasColumn()`.
**Evidence the enum never actually applied:** all 6 available SQL backups in `db_backups/` (April–June 2026, most recent 2026-06-15) show the live column as `` `need_change_status` varchar(50) DEFAULT NULL ``, not an enum. This matches [migrate_need_change.php](migrate_need_change.php), a root-level one-off script that added the column as `VARCHAR(50)` before the Laravel migration existed — the migration's `hasColumn()` guard causes it to skip the column on any database where it already exists, which is every real environment so far.
**Live-DB verification limitation:** the local MySQL/XAMPP server was not running at review time, so the *current* live schema could not be queried directly; this finding relies on the static backup evidence above, which is consistent across all 6 dumps.
**Consequence:** On the databases that actually exist today, there is no DB-level enum constraint, so `'deducted'` (and any other string, including `'rejected'`) is accepted freely — no compatibility risk today. The only latent risk is a **hypothetical fresh install** via `php artisan migrate:fresh` on a database that has never had this column: it would create a real `enum('pending','approved','deducted','failed')`, which **includes `'deducted'`** (no risk to this change) but **omits `'rejected'`**, which would break `ReportController::update()`'s `reject_need_change` path ([app/Http/Controllers/Api/ReportController.php:329](app/Http/Controllers/Api/ReportController.php:329)) on that specific environment.
**Severity: Low** (no impact on `'deducted'` specifically or on any existing environment; only a latent fresh-install gap, and it affects `'rejected'`, not the status this task introduced).

### Finding 3 — Dead legacy PHP chain still defaults to `'pending'`, never sets `'deducted'`
**Locations:** [public/backend/models/MaintenanceReport.php](public/backend/models/MaintenanceReport.php) (defaults `need_change_status` to `'pending'` in `create()`), [public/backend/services/ReportService.php](public/backend/services/ReportService.php), [public/backend/controllers/ReportController.php](public/backend/controllers/ReportController.php).
**Reachability:** Traced the full include chain — these files are only reachable via `public/backend/bootstrap.php`, which itself is `require`d by zero files anywhere in the repository. Confirmed dead code, consistent with `WORKING_TREE_AUDIT.md`.
**Severity: Low** (unreachable at runtime; no live compatibility impact either way).

### Finding 4 — No validation rule constrains `need_change_status` values
**Locations checked:** repo-wide search for an `in:` validation rule or Form Request referencing `need_change_status` — zero matches.
**Consequence:** `need_change_status` is never accepted as raw validated user input in any request; it is only ever set server-side (`NeedChangeService::approve()` → `'deducted'`, or `ReportController::update()`'s `reject_need_change` branch → `'rejected'`). There is no allow-list to update.
**Severity: Low / informational** (no finding — confirms there is nothing to break).

---

## 3. Locations confirmed fully compatible with `'deducted'` (no action needed)

- **[app/Http/Controllers/Api/ReplacementTrackingController.php](app/Http/Controllers/Api/ReplacementTrackingController.php)** — `deriveTrackingStatus()` explicitly checks `$needChangeStatus === 'deducted'` (not `'approved'`) to derive `'replaced'` / `'for_disposal'`. This endpoint was effectively dormant before the Need Change deduction fix (since `'deducted'` was never actually being set), and is now correctly activated by it.
- **[public/frontend/pages/reports.php:658](public/frontend/pages/reports.php:658)** — badge logic already treats `needChangeStatus === 'deducted'` the same as `'approved'` (green "✓ Approved" badge). Blocked only by Finding 1 (missing data from the API), not by status-value logic.
- **[public/frontend/pages/maintenance-report-detail.php](public/frontend/pages/maintenance-report-detail.php)** (10 reference sites) — every comparison (`isApproved`, `isAlreadyProcessed`, display text) explicitly includes `status === 'deducted'` alongside `'approved'`/`'rejected'`. Fully compatible.
- **[app/Http/Controllers/Api/ReportController.php](app/Http/Controllers/Api/ReportController.php)** `show()` — selects all `need_change_*` columns plus a join to `items` for the item name/quantity; fully populated for the single-report detail view regardless of status value.
- **[app/Models/MaintenanceReport.php](app/Models/MaintenanceReport.php)** — `need_change_status` is a plain fillable string attribute with no `enum`-style Eloquent cast and no accessor/mutator logic that special-cases specific values. Nothing to break.
- **Dashboard / Analytics / Notifications** — [app/Http/Controllers/Api/DashboardController.php](app/Http/Controllers/Api/DashboardController.php) and [app/Http/Controllers/Api/NotificationController.php](app/Http/Controllers/Api/NotificationController.php) contain zero references to `need_change_status`; no dashboard statistic or notification rule branches on this field.
- **Search logic** — no dedicated search/filter endpoint (global or otherwise) queries or filters on `need_change_status` anywhere in the codebase.

---

## 4. Summary Table

| # | Finding | Severity | Caused by this change? |
|---|---|---|---|
| 1 | `/api/reports` index() omits `need_change_*` columns, so `reports.php` badge never receives data | Medium | No — pre-existing, symmetric across all statuses |
| 2 | Migration enum lacks `'rejected'`; live DBs are `varchar(50)` (fresh-install-only risk) | Low | No — pre-existing drift, and doesn't affect `'deducted'` |
| 3 | Dead legacy `public/backend/*` chain defaults to `'pending'` | Low | No — unreachable code |
| 4 | No validation rule restricts allowed values | Low / informational | No — confirms nothing to break |

**No Critical or High severity findings.** No location was found that actively mishandles, rejects, or breaks on `'deducted'`.

---

*This document was generated as a read-only review. No application code, migrations, or other documentation files were modified in the course of this review.*
