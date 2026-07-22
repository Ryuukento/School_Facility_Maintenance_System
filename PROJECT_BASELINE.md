# PROJECT_BASELINE.md

## PhilCST Centralized School Facility Maintenance Reporting System — Baseline Engineering Report

**Document status:** Point-in-time snapshot, captured **2026-07-21**, immediately prior to the start of `ROADMAP.md` Phase 1 (Inventory Core Stabilization). This report is read-only: it was produced by direct inspection of the repository's current working tree, git history, and source files — not by re-stating prior documentation from memory. Every claim below was verified against the actual file contents at the time of writing. This document is a snapshot, not a living reference: it should not be edited to track ongoing progress — instead, a future baseline should be captured (e.g. `PROJECT_BASELINE_<phase>.md` or an update to `CHANGELOG.md`) when a meaningful comparison point is needed again.

---

## Repository Status

- **Branch:** `main` (single local branch at time of capture).
- **Commit history:** 6 commits (`d3cb6b2` Initial commit → `79caa4a` → `71f5bf0` → `fbfd3a7` → `cf4370f`), i.e. shallow, coarse-grained history with generic messages ("hey", "first commit") rather than a fine-grained per-feature commit log.
- **Working tree state: NOT clean.** A substantial, uncommitted change set is currently present, including:
  - Deletion (in the working tree, not yet committed) of `CONVERSION_NOTES.md`, `laravel_app.zip`, the entire `laravel_app/` directory, and all `public/backend/api/*.php` legacy endpoint files (23 files).
  - Deletion of `app/Http/Controllers/Api/LegacyBridgeController.php`.
  - Modifications to core Laravel files: `AuthController`, `DashboardController`, `ItemController`, `ReportController`, `UserController`, `EnsureRole`, `ActivityLog`, `Item`, `MaintenanceReport` models, `AppServiceProvider`, `bootstrap/app.php`, one migration, `DatabaseSeeder`, root `index.php`.
  - Modifications and some deletions across `public/backend/*` (config, controllers, middleware, models, services) and `public/frontend/*` (most CSS/JS assets and page templates modified; `analytics.php`, `color-guide.php`, `color-scheme-complete-example.php` deleted).
  - New, currently **untracked** files: `tests/Feature/AnalyticsIntegrationTest.php`, `tests/Feature/PurchaseReceiptPostingTest.php`, `tests/Support/AnalyticsMocks.php` (and a modified `tests/Feature/ExampleTest.php`).
- **Practical implication:** the repository is mid-change. The legacy-removal and test-expansion work described elsewhere in this report exists in the working tree but has **not been committed**. Any baseline comparison must account for the fact that the last committed state (`cf4370f`) does not match the current working tree.

---

## Architecture Status

- The hybrid Laravel + legacy-PHP architecture described in `ARCHITECTURE.md` is confirmed present and structurally intact: Controllers under `app/Http/Controllers/Api/*` (22 controllers), Services under `app/Services/*` (6 services), and exactly 2 Observers under `app/Observers/*` (`InventoryTransactionObserver`, `InventoryStockEntryObserver`), matching the documented single-writer inventory model.
- The actual Controller surface is **broader** than the illustrative table in `ARCHITECTURE.md` §3 — in addition to the controllers named there, the codebase now includes dedicated controllers for damage reports, repair requests, suppliers, analytics, departments, inventory rooms/categories/stock, dispatches, deployment tracking, replacement tracking, purchase receipts, buildings, and rooms. This is consistent with the documented module list in principle, but `ARCHITECTURE.md` §3's table is illustrative rather than exhaustive as currently written.
- The ledger-based inventory model (`InventoryTransaction` → `InventoryTransactionObserver` → `Item.quantity`/`reserved_quantity`) is confirmed implemented as described: `creating()` and `created()` both call `Item::lockForUpdate()->find(...)` (verified at `app/Observers/InventoryTransactionObserver.php:19` and `:58`).
- Migration history is linear and additive: 24 migration files spanning 2026-03-27 through 2026-06-15, with no evidence of destructive schema edits to already-applied migrations.
- **Conclusion:** the architecture is mature enough to build on. The layering is real and consistently applied, not aspirational.

---

## Completed Documentation

The following documentation exists in the repository root and is treated as authoritative per `AI_INSTRUCTIONS.md`'s Mandatory Reading Order:

1. `ARCHITECTURE.md` — structural/layering source of truth.
2. `BUSINESS_RULES.md` — workflow/state-machine source of truth.
3. `CHANGELOG.md` — historical record of milestones and findings.
4. `ROADMAP.md` — forward engineering plan (Phases 0–9).
5. `AI_INSTRUCTIONS.md` — permanent behavioral contract for AI contributors.
6. `PROJECT_BASELINE.md` — this document.

All five prior documents were authored/reviewed on 2026-07-21 and are internally consistent with the verified codebase state described in this report as of the same date.

---

## Current Modules

| Module | Status | Basis |
|---|---|---|
| **Inventory ledger core** (`InventoryTransaction`, Observers) | **Stable** | Single-writer pattern confirmed implemented and locked correctly in `creating()`/`created()`; one known, documented gap remains (see Known Functional Gaps). |
| **Purchase Receipts** (`PurchaseReceiptPostingService`) | **Stable, with a documented inconsistency** | Posting logic confirmed correct and lock-protected; activity logging still bypasses `ActivityLogService` (direct `DB::table()` insert), a documented deviation, not a correctness defect. |
| **Dispatch** (`DispatchService`) | **Stable** | Full lifecycle (`create`/`approve`/`cancel`/`release`) present with transition guards and pre-release availability validation, matching `BUSINESS_RULES.md` §5. |
| **Damage Reports / Repair Requests** (`DamageReportService`, `RepairService`, dedicated controllers) | **In Progress** | Present as first-class Services/Controllers — broader than the single generic `ReportController` implied in `ARCHITECTURE.md` §3's table. Their state machines were not re-audited line-by-line in this pass; treat as in progress until independently verified against `BUSINESS_RULES.md` §7–§9. |
| **Need Change Workflow** | **In Progress / Known-Incomplete** | Status-flag transition confirmed working; inventory deduction confirmed absent (see Known Functional Gaps). This is the subject of `ROADMAP.md` Phase 2. |
| **Buildings / Floors / Rooms** | **Stable** | Structural hierarchy and cascade-delete relationships match `BUSINESS_RULES.md` §2; no changes observed since the architecture audit. |
| **Users / Auth / RBAC** (`AuthController`, `UserController`, `EnsureRole`) | **Stable, with duplicated logic** | Session-based auth and rate limiting confirmed present; role-alias normalization confirmed duplicated across **12 files** (broader than the "~9 locations" figure previously documented) — see Current Technical Debt. |
| **Analytics / Dashboards** (`AnalyticsService`, `AnalyticsReportController`, `DashboardController`) | **In Progress** | Newly test-covered (see Testing Status) but not yet described in detail in `ARCHITECTURE.md`'s module table; cache-tag invalidation behavior matches the documented design. |
| **Activity Logs** (`ActivityLogService`) | **Stable, with one known bypass** | Append-only logging confirmed as the standard path; `PurchaseReceiptPostingService` remains a documented exception. |
| **Legacy `public/backend/*`** | **Legacy — actively being removed (uncommitted)** | All `public/backend/api/*.php` endpoint files are marked deleted in the current working tree (uncommitted). Legacy controllers, models, services, and middleware under `public/backend/{controllers,models,services,middleware}` still exist on disk and have not all been removed. |
| **Legacy `public/frontend/*`** | **Partially Migrated** | Most legacy page templates and static assets still exist and were modified in the current working tree; a small number of pages (`analytics.php`, `color-guide.php`, `color-scheme-complete-example.php`) have been deleted. No evidence yet of Blade equivalents replacing these pages under `resources/`. |
| **Legacy/Laravel bridge** (`SyncLegacyPhpSession`, root `index.php`) | **Stable (load-bearing)** | Still present and referenced; no changes observed. Remains a single point of fragility per `ROADMAP.md` Known Risks. |

---

## Current Technical Debt

Verified as still present in the codebase at time of capture (cross-referenced against `ROADMAP.md`'s Technical Debt Queue):

- **`InventoryTransactionObserver::deleted()` still uses `Item::find()` instead of `Item::lockForUpdate()->find()`** (confirmed at `app/Observers/InventoryTransactionObserver.php:127`), unlike `creating()`/`created()`. Unchanged from the original audit finding.
- **`PurchaseReceiptPostingService` still logs activity via direct `DB::table('activity_logs')->insert()`** (confirmed at `app/Services/PurchaseReceiptPostingService.php:79`), rather than `ActivityLogService`. Unchanged.
- **Role-alias normalization is duplicated across at least 12 files** (confirmed via direct search for `admin_maintenance`/`maintenance_admin` literals): `PurchaseReceiptController`, `AuthController`, `ReportController`, `UserController`, `DashboardController`, `DeploymentTrackingController`, `InventoryStockController`, `ReplacementTrackingController`, `EnsureRole`, `SyncLegacyPhpSession`, `RepairService`, `DamageReportService`. This is a wider footprint than the "~9 locations" cited in earlier documentation — the debt has not shrunk and may have grown as new controllers were added.
- **`database/seeders/DatabaseSeeder.php` still hardcodes plaintext administrator passwords** (confirmed at lines 23 and 32). Unchanged.
- **`bootstrap/app.php` still exempts `backend/api/*` from CSRF protection** (confirmed at line 21), even though the legacy endpoint files that prefix referred to are now deleted in the working tree (pending commit). This exemption is now provably stale and safe to remove once the legacy deletion is committed.
- **No `FormRequest` classes exist**; validation remains inline in controllers. Unchanged, low-severity.
- **No Policy/Gate authorization layer exists**; RBAC remains ad-hoc role-string comparison via `EnsureRole` and inline checks. Unchanged, low-severity.

No new, previously undocumented technical debt was identified during this verification pass.

---

## Known Functional Gaps

*(Verified only — no speculative gaps are listed.)*

- **Need Change approval has no inventory effect.** Confirmed directly in `app/Http/Controllers/Api/ReportController.php`: the `approve_need_change` branch (around lines 306–313) sets `need_change_status`, `need_change_approved_by`, and `need_change_approved_at` only. No `InventoryTransaction` is created anywhere in this code path, and `need_change_deducted_at` is read/selected (lines 274, in the query projection) but is never written. This exactly matches the gap already documented in `BUSINESS_RULES.md` §10 — it has not been fixed, and no partial fix was found.
- **`InventoryTransactionObserver::deleted()` locking gap** (see Current Technical Debt above) is a confirmed, still-present functional gap in the strict sense that it deviates from the "always lock before mutating" rule stated in `ARCHITECTURE.md` §5.2.

No other functional gaps beyond these two previously-documented ones were newly discovered during this verification pass. This report does not speculate about undocumented behavior in modules (e.g. Damage Reports, Repair Requests, Analytics) that were not exhaustively re-audited in this pass — see the "In Progress" markings in Current Modules above for what remains unverified rather than confirmed-correct.

---

## Testing Status

- **Test infrastructure:** `tests/TestCase.php` exists as the base test case; `database/factories/UserFactory.php` is the only model factory in the repository.
- **New, currently uncommitted test coverage exists** beyond what prior documentation described:
  - `tests/Feature/PurchaseReceiptPostingTest.php` — Feature-level coverage of purchase receipt posting behavior (e.g. confirms an existing item is updated exactly once on posting).
  - `tests/Feature/AnalyticsIntegrationTest.php` with supporting `tests/Support/AnalyticsMocks.php` — Feature-level coverage of `AnalyticsReportController` endpoints, using a mocked `AnalyticsService` to avoid SQLite incompatibilities.
  - `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php` remain present as scaffold/example tests.
- **Coverage gaps that remain, verified:**
  - **No Feature tests exist yet for either Observer** (`InventoryTransactionObserver`, `InventoryStockEntryObserver`), including no test of the `deleted()` locking gap identified above.
  - **No Feature tests exist yet for `DispatchService`**'s lifecycle (`create`/`approve`/`cancel`/`release`).
  - **No tests exist for the Need Change workflow**, which is unsurprising given the workflow's functional gap, but means the gap itself is not currently regression-guarded.
- **Manual verification scripts** (`scripts/smoke_dispatch.php`, `scripts/smoke_inventory_supplier_components.php`, `scripts/smoke_damage_reporting.php`, `scripts/smoke_repair_replacement.php`, `scripts/smoke_activity_logs.php`, `scripts/smoke_reporting.php`) still exist and remain the primary verification method for the modules not yet covered by PHPUnit Feature tests.
- **Net assessment:** testing coverage has begun to improve since the original architecture audit (new Feature tests for Purchase Receipts and Analytics now exist), but this improvement is **uncommitted**, and the two most safety-critical gaps — Observer behavior and the `deleted()` locking gap specifically — remain untested. `ROADMAP.md` Phase 6 (Testing) and Phase 1 (which requires tests for the locking fix) are both still fully applicable and not yet satisfied.

---

## Security Status

- **Authentication:** Session-based login with bcrypt hashing and `RateLimiter` throttling confirmed present in `AuthController`; no changes observed.
- **Legacy/Laravel session bridge:** `SyncLegacyPhpSession` confirmed still present and unchanged; still a load-bearing, untested piece of infrastructure per `ROADMAP.md` Known Risks.
- **CSRF:** The `backend/api/*` exemption in `bootstrap/app.php` is confirmed still present and is now demonstrably stale, since every file under `public/backend/api/*.php` is deleted in the current (uncommitted) working tree. This exemption should be removed once that deletion is committed — tracked under `ROADMAP.md` Phase 3.
- **Credential hygiene:** `database/seeders/DatabaseSeeder.php` is confirmed to still contain two hardcoded plaintext passwords. This remains an open finding, not yet remediated.
- **Authorization:** RBAC remains ad-hoc role-string comparison; no Policy/Gate layer or permission package has been introduced. Role-alias duplication (12 files, confirmed) remains the primary authorization-consistency risk — every new call site that checks a role string is a place a normalization mistake could silently under- or over-grant access.
- **Net assessment:** no new security regressions were identified. The three known findings from the original audit (stale CSRF exemption, hardcoded credentials, role-alias duplication) are all confirmed still open and are unchanged in severity, though the CSRF exemption's staleness is now more clearly demonstrable given the legacy file deletions already staged in the working tree.

---

## Migration Status

- **Legacy → Laravel migration is in active, uncommitted progress.** The working tree shows a decisive step forward: all `public/backend/api/*.php` legacy endpoint files (23 files, previously "dead code, unrouted" per `ARCHITECTURE.md`) are now deleted in the working tree, along with the `LegacyBridgeController.php`, `laravel_app.zip`, and the entire redundant `laravel_app/` directory copy.
- **Not yet removed:** legacy controllers, models, services, and middleware under `public/backend/{controllers,models,services,middleware,utils,tasks}` still exist on disk (e.g. `ReportController.php`, `ReportService.php`, `AuthenticationService.php`, `FacilityService.php`, and associated model classes). Whether each of these is dead code or still reachable via the root `index.php`/`SyncLegacyPhpSession` bridge has **not been re-verified in this pass** and should not be assumed — this is exactly the classification work scoped as `ROADMAP.md` Phase 4, which has not yet started.
- **`public/frontend/*` legacy page templates are still substantially present** and were modified (not removed) in the current working tree; only three pages were deleted (`analytics.php`, `color-guide.php`, `color-scheme-complete-example.php`). No Blade-based replacements for these were found under `resources/` in this pass.
- **Database schema migration is fully additive and on-track:** 24 migrations, none showing evidence of retroactive editing of an already-applied migration.
- **Net assessment:** meaningful legacy-removal progress exists in the working tree but is **uncommitted** and **partial** — the dead API endpoint layer has been cleared, but the legacy controller/service/model layer and the legacy frontend page layer have not. This is earlier-stage progress on `ROADMAP.md` Phase 4/5 than a full phase completion, and should not be characterized as "Phase 4 complete."

---

## Ready For Phase 1?

**Yes, conditionally.** The project is ready to *begin* `ROADMAP.md` Phase 1 (Inventory Core Stabilization), for the following verified reasons:

- The subsystem Phase 1 targets — the inventory ledger and its Observers — is structurally stable, correctly layered, and has exactly one confirmed, narrowly-scoped defect (the `deleted()` locking gap), which is precisely what Phase 1 is scoped to fix. No new, additional defects were discovered in this subsystem during verification.
- The documentation foundation Phase 1 depends on (`ARCHITECTURE.md` §5, `BUSINESS_RULES.md` §3, `ROADMAP.md` Phase 1 definition) is complete, internally consistent, and matches the verified code.
- Feature-testing precedent now exists in the repository (`PurchaseReceiptPostingTest.php`, `AnalyticsIntegrationTest.php`), which Phase 1's own test-coverage requirement can follow as a pattern rather than establishing test infrastructure from scratch.

**One condition should be resolved first, as a matter of process discipline rather than a technical blocker:** the repository's working tree currently holds a large, uncommitted change set (legacy file deletions, core controller/model modifications, new test files) that predates and is unrelated to Phase 1's scope. Starting Phase 1 work on top of an uncommitted, mixed-purpose diff makes it harder to produce the "small, reviewable change" that `ROADMAP.md`'s Engineering Philosophy requires, and harder to cleanly roll back Phase 1 independently of the pending legacy-removal work if needed. It is recommended that the current working-tree changes be reviewed and committed (or deliberately reverted, if unintended) as their own change, separate from Phase 1, before Phase 1 implementation begins.

---

## Baseline Checklist

- [x] `ARCHITECTURE.md` exists and reflects verified current structure.
- [x] `BUSINESS_RULES.md` exists and reflects verified current behavior, including the Need Change gap.
- [x] `CHANGELOG.md` exists and records the audit and documentation milestones.
- [x] `ROADMAP.md` exists with Phase 0 marked complete and Phase 1 scoped.
- [x] `AI_INSTRUCTIONS.md` exists and is binding for future AI contributors.
- [x] Inventory ledger single-writer pattern verified intact in code (Observers are the only mutators).
- [x] The one known Observer locking gap (`deleted()`) reconfirmed present and unresolved.
- [x] The Need Change functional gap reconfirmed present and unresolved.
- [x] Known technical debt items (role-alias duplication, hardcoded seeder credentials, stale CSRF exemption, activity-log logging inconsistency) reconfirmed present, with no new items discovered.
- [x] Current test coverage inventoried, including newly-added but uncommitted Feature tests.
- [ ] **Uncommitted working-tree changes reviewed and committed (or reverted) as a change independent of Phase 1.** *(Open — recommended before Phase 1 implementation begins; see "Ready For Phase 1?" above.)*
- [ ] Phase 1 Architecture Review and Planning steps (per the Implementation Workflow in `AI_INSTRUCTIONS.md`) — **not yet started; intentionally out of scope for this document.**
