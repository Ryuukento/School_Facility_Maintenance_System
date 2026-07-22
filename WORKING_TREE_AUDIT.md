# WORKING_TREE_AUDIT.md

## PhilCST Centralized School Facility Maintenance Reporting System — Pre-Phase-1 Working Tree Audit

**Document status:** Point-in-time, read-only audit captured **2026-07-21**, immediately following `PROJECT_BASELINE.md`. No code was modified, staged, committed, reverted, or deleted to produce this document — every finding below was obtained via `git status`, `git diff`, and direct file inspection only. This document supersedes the working-tree characterization in `PROJECT_BASELINE.md`'s "Repository Status" section with a full, file-level accounting — the prior report significantly *understated* the scope of pending changes (it sampled; this audit enumerates).

**Headline finding, stated up front:** the last commit (`cf4370f`) is far behind the application's actual current implementation. The entire `app/Observers/` directory, the entire `app/Services/` directory, 17 of the repository's 22 API controllers, 11 of its Eloquent models, 10 of its 24 migrations, and the Observer-registration wiring in `AppServiceProvider` are **all untracked** — meaning `ARCHITECTURE.md`'s description of "current implemented behavior" is only true of the working tree, not of git history. If the working tree were discarded, the ledger-based inventory architecture would not exist in the codebase at all. This materially changes how "clean baseline" should be interpreted for Phase 1 — see Final Recommendation.

---

## Git Status Summary

Raw counts from `git status --porcelain=v1` (247 entries total):

| Status | Count | Meaning |
|---|---|---|
| `M` (modified, tracked) | 90 | Tracked files with uncommitted edits. |
| `D` (deleted, tracked) | 37 | Tracked files removed from the working tree but not yet committed as deleted. |
| `??` (untracked) | 120 | Files/directories git has never tracked — includes entire new source directories, new documentation, generated artifacts, and scratch scripts. |
| Renamed (`R`) | 0 | No renames were detected by git; visually-similar old/new legacy files (e.g. deleted `public/backend/api/*.php` vs. new `app/Http/Controllers/Api/*Controller.php`) are unrelated files from git's perspective, not tracked renames. |

**Modified (`M`) — 90 files**, spanning: 6 core `app/` files, `bootstrap/app.php`, 1 migration, `database/seeders/DatabaseSeeder.php`, root `index.php`, 6 legacy `public/backend/*` files, 53 `public/frontend/*` files (CSS/JS/page templates), `routes/web.php`, `tests/Feature/ExampleTest.php`.

**Deleted (`D`) — 37 files**, spanning: `CONVERSION_NOTES.md`, `app/Http/Controllers/Api/LegacyBridgeController.php`, `laravel_app.zip` + the entire `laravel_app/` directory (7 files), all 23 `public/backend/api/*.php` legacy endpoint files, 1 avatar upload, and 3 `public/frontend/pages/*.php` files (`analytics.php`, `color-guide.php`, `color-scheme-complete-example.php`, `set-session.php`).

**Untracked (`??`) — 120 entries**, spanning: this session's own documentation (`AI_INSTRUCTIONS.md`, `ARCHITECTURE.md`, `BUSINESS_RULES.md`, `CHANGELOG.md`, `PROJECT_BASELINE.md`, `ROADMAP.md`), 17 new API controllers, `app/Http/Middleware/SyncLegacyPhpSession.php`, 11 new models, the entire `app/Observers/` and `app/Services/` directories, 10 new migrations, 6 new PHPUnit smoke/feature test files, 7 root-level scratch PHP/PowerShell scripts, 6 SQL database backup dumps, a stray `docs/` directory, a stray `query` file, and a large volume of frontend `.bak`/redesign CSS/JS files and uploaded avatar images.

---

## Categorize Every Change

| Category | Files |
|---|---|
| **Documentation** | `AI_INSTRUCTIONS.md`, `ARCHITECTURE.md`, `BUSINESS_RULES.md`, `CHANGELOG.md`, `PROJECT_BASELINE.md`, `ROADMAP.md` (all untracked); `CONVERSION_NOTES.md` (deleted, tracked); `docs/FINAL_ARCHITECTURE_SUMMARY.md` (untracked, stale). |
| **Architecture** | `bootstrap/app.php`; `app/Providers/AppServiceProvider.php`; `app/Http/Middleware/EnsureRole.php`; `app/Http/Middleware/SyncLegacyPhpSession.php` (new); `app/Http/Controllers/Api/LegacyBridgeController.php` (deleted); `index.php`; `routes/web.php`. |
| **Inventory** | `app/Observers/InventoryTransactionObserver.php`, `app/Observers/InventoryStockEntryObserver.php` (both new); `app/Models/Item.php`, `app/Models/InventoryTransaction.php` (new), `app/Models/InventoryStockEntry.php` (new); `app/Http/Controllers/Api/ItemController.php`, `InventoryStockController.php` (new), `InventoryCategoryController.php` (new), `InventoryRoomController.php` (new), `StockController.php` (new); migrations `2026_05_15_000100_create_inventory_stock_entries_table.php`, `2026_05_15_000200_add_item_management_fields_to_items_table.php`, `2026_04_23_000800/000900` (already tracked, not part of this diff); `public/backend/models/Item.php`, `public/backend/models/InventoryCategory.php` (legacy, modified). |
| **Purchase Receipt** | `app/Services/PurchaseReceiptPostingService.php` (new); `app/Http/Controllers/Api/PurchaseReceiptController.php` (new); `app/Models/Supplier.php`, `SupplierHistory.php` (new); migrations `2026_05_15_000300_create_suppliers_and_histories_and_add_supplier_id.php`, `2026_05_20_000100_create_purchase_receipts_table.php`, `2026_05_20_000200_create_purchase_receipt_items_table.php` (new); `tests/Feature/PurchaseReceiptPostingTest.php` (new). |
| **Dispatch** | `app/Services/DispatchService.php` (new); `app/Http/Controllers/Api/DispatchController.php` (new); `app/Models/Dispatch.php`, `DispatchItem.php` (new); migration `2026_05_15_000400_create_dispatches_and_items.php`, `2026_05_22_000100_add_purchase_receipt_id_to_dispatches.php` (new); `scripts/smoke_dispatch.php` (new). |
| **Deployment** | `app/Http/Controllers/Api/DeploymentTrackingController.php`, `ReplacementTrackingController.php` (new); `public/frontend/pages/deployment-tracking.php`, `replacement-request.php`, `replacement-tracking.php` (modified/new). |
| **Reports** | `app/Http/Controllers/Api/ReportController.php` (modified), `DamageReportController.php`, `RepairController.php` (new); `app/Services/DamageReportService.php`, `RepairService.php` (new); `app/Models/MaintenanceReport.php` (modified), `DamageReport.php`, `DamageReportHistory.php`, `RepairRequest.php`, `RepairHistory.php` (new); migrations `2026_04_07_000700_add_need_change_fields...` (modified — see Risk Assessment), `2026_05_15_000500_create_damage_reports_tables.php`, `2026_05_18_000600_create_repair_requests_tables.php` (new); `scripts/smoke_damage_reporting.php`, `smoke_repair_replacement.php`, `smoke_reporting.php` (new); `public/backend/controllers/ReportController.php`, `public/backend/services/ReportService.php` (legacy, modified). |
| **Authentication** | `app/Http/Controllers/Api/AuthController.php` (modified); `app/Http/Middleware/EnsureRole.php` (modified — role-alias block); `database/seeders/DatabaseSeeder.php` (modified); migration `2026_06_15_000000_normalize_user_roles.php` (new); `public/backend/middleware/SessionMiddleware.php` (legacy, modified). |
| **Legacy** | All 23 deleted `public/backend/api/*.php` files; `laravel_app.zip` and `laravel_app/` (deleted, 7 files); modified `public/backend/{config,controllers,middleware,models,services}/*` (6 files); all modified/deleted `public/frontend/*` CSS/JS/page files (~60 files); all new `public/frontend/*` redesign/`.bak` files and uploaded avatars (~35 files). |
| **Testing** | `tests/Feature/ExampleTest.php` (modified); `tests/Feature/AnalyticsIntegrationTest.php`, `tests/Feature/PurchaseReceiptPostingTest.php`, `tests/Support/AnalyticsMocks.php` (new); `scripts/smoke_*.php` (6 new files); `phpunit-results.xml` (new, generated artifact). |
| **Other** | `.claude/` (session tooling, not application code); `check_plugin_tables.ps1`, `repair_aria.ps1`, `repair_mysql_tables.ps1`, `repair_plugin_tables.ps1`, `fix_staff_dashboard.php`, `patch_assign_search.php`, `patch_dept_table.php`, `patch_email.php`, `patch_profile_username.php` (root-level scratch scripts); `db_backups/*.sql` (6 files); `query` (stray text file); `app/Http/Controllers/Api/{ActivityLogController,AnalyticsReportController,BuildingController,DepartmentController,NotificationController,RoomController,SupplierController}.php` (new, not yet mapped to a single roadmap category); `app/Models/Room.php` (new); `app/Services/AnalyticsService.php`, `ActivityLogService.php` (new). |

---

## For Every Changed File

Given the volume (247 entries), files with materially identical circumstances are grouped into one row; individually significant files are broken out. "Phase 1?" answers whether the file is in-scope for `ROADMAP.md` Phase 1 (Inventory Core Stabilization: the Observer `deleted()` locking fix and its tests).

### Documentation

| File(s) | Why it appears changed | Intentional? | Complete? | Phase 1? | Commit before Phase 1? |
|---|---|---|---|---|---|
| `AI_INSTRUCTIONS.md`, `ARCHITECTURE.md`, `BUSINESS_RULES.md`, `CHANGELOG.md`, `ROADMAP.md` | Authored in this and prior sessions per explicit request. | Yes | Yes | No | **Yes** — this is the documentation foundation everything else, including Phase 1, is defined against. |
| `PROJECT_BASELINE.md` | Authored in the immediately preceding session turn. | Yes | Yes | No | **Yes** — same reasoning; also self-referentially describes a "baseline" that is misleading if left uncommitted. |
| `CONVERSION_NOTES.md` (deleted) | Presumably removed as part of legacy cleanup; no replacement content found. | Likely, but not confirmed from content (file is gone, not diffable) | Unknown | No | **Investigate** — confirm nothing in it needs preserving before committing its deletion. |
| `docs/FINAL_ARCHITECTURE_SUMMARY.md` (untracked) | An older (dated 2026-05-15) architecture snapshot that predates this session's documentation set. It still describes `LegacyBridgeController` as an active bridge component — but that controller is *deleted* in the current working tree, confirming this document is stale relative to current code and to the new `ARCHITECTURE.md`. | Unclear whether intentionally retained or forgotten | Stale/superseded | No | **Investigate** — decide whether to archive, delete, or explicitly mark as superseded; do not treat as authoritative. |

### Architecture

| File | Why it appears changed | Intentional? | Complete? | Phase 1? | Commit before Phase 1? |
|---|---|---|---|---|---|
| `bootstrap/app.php` | Adds centralized JSON exception rendering (`ValidationException`→422, `ModelNotFoundException`→404, generic 500) and registers `SyncLegacyPhpSession` on the `web` middleware group. This is exactly the behavior `ARCHITECTURE.md` §7 describes as already-current. | Yes, and functionally significant | Yes, appears complete | No | **Yes** — without this commit, the documented exception-handling and session-bridging behavior does not exist in git history at all. |
| `app/Providers/AppServiceProvider.php` | Adds `InventoryStockEntry::observe(...)` and `InventoryTransaction::observe(...)` registration. **This is the line that turns the Observer classes on.** | Yes, and critically significant | Yes | **Yes — this is a Phase 1 precondition, not a Phase 1 deliverable itself** | **Yes, before anything else** — see Risk Assessment; without this line committed, the entire single-writer ledger guarantee is inert in the last committed state. |
| `app/Http/Middleware/EnsureRole.php` | Adds a role-alias normalization map (`admin_maintenance`→`maintenance_admin`, etc.) inline in this middleware. | Yes | Yes, functions as written | No | **Yes** — but flag that this *adds to*, rather than resolves, the duplicated role-normalization debt tracked in `ROADMAP.md` Phase 3. Do not treat this addition as a Phase 3 fix. |
| `app/Http/Middleware/SyncLegacyPhpSession.php` (new) | New file implementing the legacy/Laravel session bridge described throughout `ARCHITECTURE.md`. | Yes | Appears complete (registered and referenced elsewhere) | No | **Yes** — same reasoning as `AppServiceProvider`: documented behavior with no git history until committed. |
| `app/Http/Controllers/Api/LegacyBridgeController.php` (deleted) | Removed; superseded by direct routing to the new dedicated controllers plus `SyncLegacyPhpSession`. Corroborated by `docs/FINAL_ARCHITECTURE_SUMMARY.md` describing this controller as the *old* bridging mechanism. | Yes | Yes | No | **Yes** — this is a coherent, intentional architectural simplification; commit the deletion alongside the new controllers that replace its purpose. |
| `index.php` (root) | Modified; consistent with `ARCHITECTURE.md` §1.3's description of the root dispatcher's role-based redirect + Laravel forwarding logic. | Yes | Appears complete | No | **Yes** |
| `routes/web.php` | +229/-? lines: registers routes for the 17 new controllers and the session bridge. | Yes | Appears complete (large but coherent single-purpose change) | No | **Yes** |

### Inventory

| File | Why it appears changed | Intentional? | Complete? | Phase 1? | Commit before Phase 1? |
|---|---|---|---|---|---|
| `app/Observers/InventoryTransactionObserver.php` (new) | Implements `creating()`/`created()`/`deleted()` exactly as described in `ARCHITECTURE.md` §5, including the documented `deleted()` locking gap (confirmed at line 127: `Item::find()`, not `lockForUpdate()`). | Yes | **Deliberately incomplete** — the locking gap is the known, documented target of Phase 1 itself. | **Yes — this file is the Phase 1 deliverable.** | **Yes, commit as-is first** — Phase 1 should fix this gap as its own tracked, reviewable diff against a committed baseline, not as an invisible part of a first-ever commit of the whole Observer. |
| `app/Observers/InventoryStockEntryObserver.php` (new) | Companion Observer for the simpler stock-entry adjustment path. | Yes | Appears complete | Adjacent to Phase 1 (same subsystem) | **Yes** |
| `app/Models/Item.php` | Adds `inventory_room_id`, `category_id`, `brand`, `model`, `item_type`, `unit_type`, `item_condition`, `reserved_quantity`, `reorder_level`, `low_stock_threshold_override` to `$fillable`. | Yes | Functionally complete, but see Risk Assessment — including `quantity`/`reserved_quantity` in `$fillable` is a **precondition** for the mass-assignment violation found in `ItemController`. | No | **Investigate before committing as-is** — decide deliberately whether `quantity`/`reserved_quantity` belong in `$fillable` at all, given they are supposed to be Observer-only writable fields. |
| `app/Models/InventoryTransaction.php`, `InventoryStockEntry.php` (new) | Eloquent models for the ledger tables. | Yes | Appears complete | Adjacent to Phase 1 | **Yes** |
| `app/Http/Controllers/Api/ItemController.php` | Adds `store()`, `update()`, `destroy()`, `history()`. **`store()`/`update()` both accept `quantity` in their validated payload and pass it straight to `Item::create()`/`$item->update()` — a direct, mass-assignable write to `Item.quantity` with no `InventoryTransaction` created and no Observer involvement.** | Appears intentional as a CRUD convenience, but is a direct violation of `ARCHITECTURE.md` §5.2/§8 Rule 3 and `AI_INSTRUCTIONS.md`'s Forbidden Actions ("Never introduce direct stock mutations"). | **No — this is architecturally incomplete/incorrect as written.** | **Directly relevant to Phase 1's subject matter, though not itself in Phase 1's originally scoped deliverable list.** | **No — do not commit as-is without first flagging and deciding how to handle it.** See Risk Assessment; this is the most significant single finding in this audit. |
| `app/Http/Controllers/Api/InventoryStockController.php`, `InventoryCategoryController.php`, `InventoryRoomController.php`, `StockController.php` (new) | New controllers for stock movement, categories, inventory rooms, and stock summaries. | Yes | Not individually re-audited line-by-line in this pass — flagged for the same class of risk as `ItemController` (direct-write potential) until checked. | No | **Investigate** — spot-check each for the same direct-quantity-write pattern found in `ItemController` before committing. |
| `public/backend/models/Item.php`, `InventoryCategory.php` (legacy, modified) | Legacy model files edited (46 and 11 lines respectively). | Unclear — no instruction called for extending legacy code, and `AI_INSTRUCTIONS.md`/`ARCHITECTURE.md` both say not to extend `public/backend/*`. | Unknown | No | **Investigate** — determine why legacy models were edited; if this is inadvertent legacy extension, it should not be committed as new precedent. |

### Purchase Receipt

| File | Why it appears changed | Intentional? | Complete? | Phase 1? | Commit before Phase 1? |
|---|---|---|---|---|---|
| `app/Services/PurchaseReceiptPostingService.php` (new) | Implements `postReceipt()` exactly as described in `BUSINESS_RULES.md` §4, including the documented `DB::table('activity_logs')->insert()` inconsistency (confirmed at line 79). | Yes | Deliberately imperfect in the same documented way as the Observer gap, but not a Phase 1 target (that's Phase 1's secondary deliverable per `ROADMAP.md`). | Partially — the activity-log fix is listed under Phase 1's deliverables in `ROADMAP.md`. | **Yes, commit as-is first**, for the same "fix against a committed baseline" reasoning as the Observer. |
| `app/Http/Controllers/Api/PurchaseReceiptController.php` (new) | Thin controller delegating to the Service. | Yes | Appears complete | No | **Yes** |
| `app/Models/Supplier.php`, `SupplierHistory.php` (new); 3 related migrations (new) | Supporting schema/model for supplier identity on receipts. | Yes | Appears complete | No | **Yes** |
| `tests/Feature/PurchaseReceiptPostingTest.php` (new, untracked) | New Feature test, confirmed to exercise the posting service. | Yes | Appears complete for its stated scope | No | **Yes** |

### Dispatch

| File | Why it appears changed | Intentional? | Complete? | Phase 1? | Commit before Phase 1? |
|---|---|---|---|---|---|
| `app/Services/DispatchService.php` (new) | Implements the full `create`/`approve`/`cancel`/`release` lifecycle per `BUSINESS_RULES.md` §5. Not re-verified line-by-line in this audit pass. | Yes | Not independently re-verified here — no contrary evidence found | No | **Yes** |
| `app/Http/Controllers/Api/DispatchController.php`, `app/Models/Dispatch.php`, `DispatchItem.php`, 2 migrations (new) | Supporting schema/model/controller for dispatch. | Yes | Appears complete | No | **Yes** |
| `scripts/smoke_dispatch.php` (new) | Manual smoke-test script, per the project's existing (documented) manual-verification pattern. | Yes | Appears complete for its stated scope | No | **Yes** |

### Deployment

| File(s) | Why it appears changed | Intentional? | Complete? | Phase 1? | Commit before Phase 1? |
|---|---|---|---|---|---|
| `DeploymentTrackingController.php`, `ReplacementTrackingController.php` (new); related frontend pages | Support the deployment/replacement tracking described in `BUSINESS_RULES.md` §6/§9. Not re-verified line-by-line. | Yes | Not independently re-verified here | No | **Yes** |

### Reports

| File(s) | Why it appears changed | Intentional? | Complete? | Phase 1? | Commit before Phase 1? |
|---|---|---|---|---|---|
| `app/Http/Controllers/Api/ReportController.php` | Adds pagination, `status_group` filters (`assigned_to_me`, `overdue`, `due_soon`, `recent_assignments`), joined-column selects, a new `recent()` endpoint. The previously-audited `approve_need_change` block (still present, still a no-op on inventory) is unchanged by this diff. | Yes | Appears complete for what it adds; the pre-existing Need Change gap is untouched (expected — that's Phase 2, not this diff). | No (Need Change fix is Phase 2) | **Yes** |
| `DamageReportController.php`, `RepairController.php`, corresponding Services/Models/migrations (all new) | Full new modules for damage reports and repair requests as their own first-class entities, beyond the single generic `MaintenanceReport` model. | Yes | Not independently re-verified in this pass — flagged as "In Progress" in `PROJECT_BASELINE.md` for the same reason. | No | **Yes**, but recommend a follow-up architecture review of these two modules specifically (outside this audit's scope). |
| `database/migrations/2026_04_07_000700_add_need_change_fields_to_maintenance_reports_table.php` | **Modified an already-existing migration file** to remove a `Schema::table(...)` block that added foreign keys on `need_change_item_id`/`need_change_approved_by`, replacing it with a comment (in Tagalog) stating the FK constraint is deliberately deferred as a "TEMPORARY FIX." | Intentional as a deferral decision, but the *mechanism* — editing an already-existing migration file rather than writing a new one — directly contradicts `ARCHITECTURE.md` §2 ("never edit an existing migration after it has been applied anywhere") and `AI_INSTRUCTIONS.md` Rule 12. | No — left in a "temporary," non-final state by the comment's own admission. | No | **No — investigate first.** See Risk Assessment. This is the second most significant finding in this audit. |
| `scripts/smoke_damage_reporting.php`, `smoke_repair_replacement.php`, `smoke_reporting.php` (new) | Manual smoke scripts for the above modules. | Yes | Appears complete for stated scope | No | **Yes** |
| `public/backend/controllers/ReportController.php`, `public/backend/services/ReportService.php` (legacy, modified) | Legacy files edited (+74, +78 lines respectively) — notably, `ReportService.php` is the file `BUSINESS_RULES.md` §10 identifies as containing the *dead*, unreachable `deductNeedChangeInventory()` method. | Unclear why a file already documented as dead code received a 78-line edit. | Unknown | No | **Investigate** — confirm whether this file is truly still unreachable before assuming the edit is harmless; if it is being newly wired up somewhere, that would upgrade the Need Change gap's risk profile significantly. |

### Authentication

| File | Why it appears changed | Intentional? | Complete? | Phase 1? | Commit before Phase 1? |
|---|---|---|---|---|---|
| `app/Http/Controllers/Api/AuthController.php` | +119/−? lines; consistent with session-based auth, rate limiting, and the legacy session-sync described in `ARCHITECTURE.md` §7. | Yes | Not fully line-by-line re-verified in this pass | No | **Yes** |
| `database/seeders/DatabaseSeeder.php` | One-line change: seed super-admin `full_name` from `'Super Admin'` to `'Administrator'`. Plaintext password hashing calls (`Hash::make('ryan@123')`, `Hash::make('dagsnitoy')`) are unchanged and still present. | Yes (cosmetic) | The hardcoded-credential finding remains open regardless of this edit | No | **Yes to commit the diff shown**, but this does **not** resolve the hardcoded-credential technical debt item — do not mistake committing this change for closing that item. |
| `database/migrations/2026_06_15_000000_normalize_user_roles.php` (new) | New, additive migration to normalize stored role strings — a data-migration companion to the ongoing role-alias problem, not a schema fix for the duplication itself. | Yes | Appears complete for its narrow purpose (data normalization, not code-path consolidation) | No | **Yes** |
| `public/backend/middleware/SessionMiddleware.php` (legacy, modified) | Legacy session middleware edited (+17/−?). | Unclear — same concern as other legacy edits. | Unknown | No | **Investigate** |

### Legacy

| File group | Why it appears changed | Intentional? | Complete? | Phase 1? | Commit before Phase 1? |
|---|---|---|---|---|---|
| All 23 `public/backend/api/*.php` (deleted) | Confirmed dead-code removal per `ARCHITECTURE.md`'s own audit finding — these were already documented as unreachable. | Yes | Yes — clean removal | No | **Yes** — this is exactly the kind of legacy cleanup `ROADMAP.md` Phase 4 anticipates; safe to commit as a standalone "remove dead legacy API layer" change. |
| `laravel_app.zip`, `laravel_app/` directory (7 files, deleted) | Appears to be a redundant packaged/duplicate copy of the Laravel app (an artifact from an earlier conversion/packaging step), not a functional dependency. | Likely yes | Yes | No | **Yes** — removing a redundant zip/duplicate tree is uncontroversial cleanup, but confirm nothing references it (e.g. a deployment script) before committing the deletion. |
| `public/backend/{config,controllers,middleware,models,services}/*` (6 modified legacy files) | Mixed — some (e.g. `SessionMiddleware.php`) plausibly still-live per the bridge; others (e.g. `ReportService.php`, `Item.php`, `InventoryCategory.php`) were previously documented as dead or semi-dead. | Unclear across the group | Unknown | No | **Investigate each individually** before committing — `ARCHITECTURE.md`/`ROADMAP.md` Phase 4 calls for classifying dead-vs-live legacy files before further modifying (rather than extending) them; several of these edits predate that classification work. |
| ~60 modified/deleted `public/frontend/*` CSS/JS/page files | Large-scale visual/behavioral rewrite (e.g. `dashboard.php` +1427/−?, `inventory.php` +1312/−?, `styles.css` +403/−?) consistent with an active UI overhaul of the legacy-rendered pages. | Yes, appears to be a deliberate, large UI effort | Likely functional (large but coherent per-page diffs), not independently tested in this pass | No | **Yes**, but recommend committing as its own clearly-labeled "frontend UI refresh" change, separate from backend/architecture commits, given the sheer size. |
| ~35 new frontend `.bak`/redesign files, avatar uploads (`??`) | `.bak` files (`*.css.bak`, `*.js.bak`, `*.php.bak`, `*.inventorybak`, `*.sidebarbak`, `*.headerbak`) are manual backup copies made during editing; avatar images are user-uploaded runtime content. | `.bak` files: not intentional deliverables, just editing scratch. Avatars: yes, legitimate runtime data. | N/A | No | **`.bak` files: do not commit** (they are not source, just editing artifacts — recommend deleting them from disk outside of Phase 1, not committing them). **Avatar uploads: leave for later** — user-uploaded content typically shouldn't be tracked in git at all; if `storage/`-style handling isn't already in place, that's a separate infrastructure question, not a Phase 1 concern. |

### Testing

| File(s) | Why it appears changed | Intentional? | Complete? | Phase 1? | Commit before Phase 1? |
|---|---|---|---|---|---|
| `tests/Feature/ExampleTest.php` (modified, +3/−?) | Minor scaffold edit. | Yes | Yes | No | **Yes** |
| `tests/Feature/AnalyticsIntegrationTest.php`, `tests/Support/AnalyticsMocks.php` (new) | New Feature-test coverage for `AnalyticsReportController`, using a mocked `AnalyticsService`. | Yes | Appears complete for stated scope | No | **Yes** |
| `scripts/smoke_activity_logs.php` (new) | Manual smoke script for activity logging. | Yes | Appears complete | No | **Yes** |
| `phpunit-results.xml` (new, untracked) | Generated test-run output artifact. | Not an intentional deliverable — a byproduct of running the suite. | N/A | No | **Do not commit** — this is a generated artifact; recommend adding it to `.gitignore` rather than tracking it. |

### Other

| File(s) | Why it appears changed | Intentional? | Complete? | Phase 1? | Commit before Phase 1? |
|---|---|---|---|---|---|
| `.claude/` (untracked directory) | Session/tooling configuration for the AI assistant environment, not application code. | Yes, but not an application artifact | N/A | No | **Investigate** — decide whether this belongs in version control at all (commonly `.gitignore`d) rather than defaulting to committing it. |
| `check_plugin_tables.ps1`, `repair_aria.ps1`, `repair_mysql_tables.ps1`, `repair_plugin_tables.ps1` (untracked, root) | Ad hoc PowerShell scripts, dated June 24, apparently used to diagnose/repair a database issue at some point. | Likely a one-off diagnostic aid, not a maintained tool | N/A | No | **Leave for later / investigate** — determine if these represent a recurring operational need (in which case they deserve a proper home, e.g. `scripts/`) or one-off scratch work (in which case they shouldn't be committed to the main tree). |
| `fix_staff_dashboard.php`, `patch_assign_search.php`, `patch_dept_table.php`, `patch_email.php`, `patch_profile_username.php` (untracked, root) | Root-level one-off PHP patch/fix scripts (dated April–June), named after specific past incidents. | One-off historical fixes, not reusable tooling | N/A | No | **Investigate** — if these ran once against a specific past data/schema state, they should not be committed as if they were reusable maintenance scripts; if still needed, they belong under `scripts/` with a clear name, not the project root. |
| `check_schema.php`, `delete_user.php`, `list_users.php`, `migrate_need_change.php` (untracked, root — note: these sit alongside a *second*, different `index.php` at the same path segment reported by git, meaning they are plain root-level PHP files reachable by any web server configured to serve the project root) | Ad hoc admin/debug scripts. **`delete_user.php` and `list_users.php` in particular are the kind of script that, if left reachable under a web-served document root, could allow unauthenticated user enumeration or deletion.** | Likely one-off dev aids | N/A | No | **Investigate as a security concern, not just cleanup** — confirm these are not web-reachable in the deployed configuration; regardless, do not commit them into the tracked tree as permanent fixtures. |
| `db_backups/*.sql` (6 files, untracked, 1.5–3.2 MB each) | Raw SQL dumps of the application database at various points in time. **Confirmed to contain `INSERT INTO users` statements** — i.e. real user rows, including password hashes. | Reasonable as a local operational backup | N/A | No | **Do not commit.** Committing these would place password hashes and PII into permanent, hard-to-purge git history. Recommend excluding via `.gitignore` and storing backups outside the repository entirely. |
| `query` (untracked, root) | A 1-line text file containing only the word `mysql`. Appears to be an accidental artifact (e.g. a mistyped shell redirect). | No — has no discernible purpose | N/A | No | **Investigate/delete outside of Phase 1** — do not commit. |
| `app/Http/Controllers/Api/{ActivityLogController,AnalyticsReportController,BuildingController,DepartmentController,NotificationController,RoomController,SupplierController}.php`, `app/Models/Room.php`, `app/Services/{AnalyticsService,ActivityLogService}.php` (all new) | Additional first-class modules/controllers not yet individually re-audited in this pass, filling out the module list beyond what `ARCHITECTURE.md` §3's illustrative table names. | Yes | Not independently re-verified here | No | **Yes**, but recommend each receive its own architecture-consistency pass before being treated as "Stable" (currently "In Progress" per `PROJECT_BASELINE.md`). |

---

## Risk Assessment

### High-risk pending changes

1. **`app/Http/Controllers/Api/ItemController.php` — direct, unmediated writes to `Item.quantity`/`reserved_quantity` via `store()`/`update()`.** This is a live, verified violation of the single-writer ledger principle that both `ARCHITECTURE.md` and `AI_INSTRUCTIONS.md` treat as inviolable. It is new, uncommitted code — meaning it is being introduced *right now*, not inherited debt. If committed as-is, it becomes a second, undocumented path to mutate stock alongside the Observer, silently defeating the audit trail for any item edited through this endpoint.
2. **`database/migrations/2026_04_07_000700_...php` — an already-existing migration file was edited** to remove a foreign-key-adding block, rather than expressing the change as a new migration. This directly contradicts the "never edit an existing migration after it has been applied" rule. Whether this is merely inconvenient or actually dangerous depends on whether this migration has already run against any environment's database — that has not been established in this audit.
3. **`app/Providers/AppServiceProvider.php` (Observer registration) is uncommitted.** Until this is committed, the last known-good state of the repository (`cf4370f`) has the Observer *classes* but no Observer *registration* — meaning `Item.quantity` mutations from that state would not be governed by the ledger at all. This is high risk specifically because it inverts the assumption Phase 1 is built on: Phase 1 assumes the Observer is live and merely has a locking gap, but that liveness itself is currently unversioned.
4. **`db_backups/*.sql` containing real user rows/password hashes sitting untracked in the repository directory.** Not yet a git-history problem, but one `git add -A` or `git add db_backups` away from becoming a permanent, hard-to-purge credential leak in history.
5. **Root-level `delete_user.php` / `list_users.php` / similar scratch admin scripts**, if reachable via any web server configuration pointed at the project root, represent a live security exposure independent of git status entirely.

### Medium-risk pending changes

- `bootstrap/app.php` and `app/Http/Middleware/SyncLegacyPhpSession.php` being uncommitted (same "documented behavior with no git history" issue as above, but lower severity than the Observer registration since it's less safety-critical than stock accuracy).
- Legacy `public/backend/*` files that were modified rather than left alone, without a prior dead/live classification (`ROADMAP.md` Phase 4 work-not-yet-done).
- The large, uncommitted `routes/web.php` rewrite and the ~60-file `public/frontend/*` UI rewrite — not incorrect as far as verified, but large enough that committing them ad hoc (rather than as clearly labeled, separately reviewable changes) would make future bisection/rollback difficult.
- New inventory-adjacent controllers (`InventoryStockController`, `InventoryCategoryController`, `InventoryRoomController`, `StockController`) not yet individually checked for the same direct-write pattern found in `ItemController`.
- `public/backend/services/ReportService.php` (the file containing the dead `deductNeedChangeInventory()` method) receiving a 78-line edit — low probability but unverified possibility that it is being re-wired into a live path.
- `docs/FINAL_ARCHITECTURE_SUMMARY.md` sitting alongside the new documentation set without being marked superseded, creating a risk of a future reader treating it as current.

### Low-risk pending changes

- All 23 deleted `public/backend/api/*.php` files (previously confirmed dead code).
- Deleted `laravel_app.zip`/`laravel_app/` redundant packaging artifacts.
- New Feature/smoke test files (additive, non-behavior-changing).
- `.bak` files, `phpunit-results.xml`, `query` — inert clutter, not executed by the application.
- Cosmetic `DatabaseSeeder.php` display-name change.
- Avatar upload additions/removals (ordinary runtime user content).

---

## Recommended Action

| Pending change | Recommended action |
|---|---|
| Documentation set (`AI_INSTRUCTIONS.md`, `ARCHITECTURE.md`, `BUSINESS_RULES.md`, `CHANGELOG.md`, `ROADMAP.md`, `PROJECT_BASELINE.md`) | **Commit before Phase 1** |
| `app/Providers/AppServiceProvider.php` (Observer registration) | **Commit before Phase 1** (as a matter of priority — this is a precondition, not optional) |
| `app/Observers/*`, `app/Services/*`, new migrations, new models, new controllers (the bulk of the "real" application) | **Commit before Phase 1**, ideally as its own clearly-labeled commit distinct from documentation and from the frontend rewrite |
| `bootstrap/app.php`, `app/Http/Middleware/SyncLegacyPhpSession.php`, `index.php`, `routes/web.php` | **Commit before Phase 1** |
| `app/Http/Controllers/Api/ItemController.php` (direct quantity mass-assignment) | **Investigate further** — do not commit the `quantity`/`reserved_quantity` mass-assignment behavior as-is without a deliberate decision; this should likely be resolved (routed through `InventoryTransaction`) before or as part of Phase 1, given it directly undermines Phase 1's subject matter. |
| `database/migrations/2026_04_07_000700_...php` (edited existing migration) | **Investigate further** — determine whether this migration has already been applied anywhere; if so, the FK-removal must be expressed as a new migration instead, not as an edit to this file. |
| `InventoryStockController.php`, `InventoryCategoryController.php`, `InventoryRoomController.php`, `StockController.php` | **Investigate further** for the same direct-write risk as `ItemController` before committing. |
| Legacy `public/backend/*` modified files (6 files) | **Investigate further** — classify dead vs. live (this is `ROADMAP.md` Phase 4's job) before deciding whether to commit these edits or treat them as unwanted legacy extension. |
| `public/backend/services/ReportService.php` specifically | **Investigate further** — confirm it remains unreachable dead code despite the 78-line edit. |
| Deleted `public/backend/api/*.php` (23 files), deleted `laravel_app.zip`/`laravel_app/` | **Commit before Phase 1** (clean, previously-confirmed dead-code removal) |
| `public/frontend/*` UI rewrite (~60 modified/deleted files) | **Commit before Phase 1**, but as its own separate, clearly-labeled commit — not mixed into the same commit as backend/architecture changes |
| `docs/FINAL_ARCHITECTURE_SUMMARY.md` | **Investigate further** — mark superseded, archive, or remove; do not leave silently coexisting with `ARCHITECTURE.md` |
| `db_backups/*.sql` | **Leave for later** in the sense of "do not commit, ever, as-is" — recommend `.gitignore` exclusion and out-of-repo storage; this is a standing security hygiene item, not a Phase 1 task |
| Root scratch scripts (`fix_staff_dashboard.php`, `patch_*.php`, `check_schema.php`, `delete_user.php`, `list_users.php`, `migrate_need_change.php`, `*.ps1`) | **Investigate further**, with `delete_user.php`/`list_users.php` flagged specifically for a web-reachability check; do not commit as permanent fixtures without relocating/renaming with clear one-off-script intent |
| `.bak`/redesign scratch frontend files, `phpunit-results.xml`, `query`, `.claude/` | **Leave for later / do not commit** — recommend cleaning these from disk or adding to `.gitignore` outside of Phase 1's scope |
| Avatar uploads (added/removed) | **Leave for later** — ordinary runtime content; not a Phase 1 concern either way |
| New Feature/smoke tests (`AnalyticsIntegrationTest.php`, `PurchaseReceiptPostingTest.php`, `AnalyticsMocks.php`, `scripts/smoke_*.php`) | **Commit before Phase 1** |

---

## Final Recommendation

**The repository is not yet in a safe state to begin Phase 1 implementation, but the blocker is process hygiene, not the Phase 1 subsystem itself.**

Reasoning:

1. **The good news:** the specific subsystem Phase 1 targets — `InventoryTransactionObserver`, once its registration in `AppServiceProvider` is accounted for — is exactly as `ARCHITECTURE.md` and `PROJECT_BASELINE.md` described it: structurally sound, with exactly one confirmed, narrow, already-documented defect (the `deleted()` locking gap). No new defect was found in the Observer itself during this audit.
2. **The blocker:** starting Phase 1 today would mean writing a locking fix on top of a working tree that (a) has never committed the Observer's own registration, so there is no clean "before" state to diff Phase 1's fix against, and (b) contains, in the same uncommitted mass of changes, at least one *new* violation of the exact rule Phase 1 exists to reinforce (`ItemController`'s direct `quantity` writes) and one confirmed violation of a separate binding rule (editing an already-existing migration). Implementing Phase 1 now would either get tangled up with these unrelated, unresolved issues, or would require silently ignoring them — both of which violate the "small, reviewable change" and "no silent workarounds" principles `ROADMAP.md`/`AI_INSTRUCTIONS.md` establish.
3. **What "clean baseline" should mean here:** given that most of the actual application (Services, Observers, most controllers/models/migrations) has never been committed, achieving a clean baseline is not a matter of committing "a few pending tweaks" — it is committing, for the first time, the real application. That should happen deliberately and in reviewable groupings (e.g. backend/architecture as one change, the frontend UI rewrite as another, documentation as another, dead-legacy-removal as another), **after** the two High-risk investigation items above (`ItemController` direct writes; the edited migration) are explicitly resolved or consciously deferred with a documented reason — not silently carried into the first commit of record.

**Recommended sequence before Phase 1 begins:** (a) resolve or consciously defer-with-documentation the `ItemController` mass-assignment issue and the edited-migration issue; (b) exclude the identified non-source artifacts (`db_backups/*.sql`, `.bak` files, `phpunit-results.xml`, `query`, root scratch scripts) from version control; (c) commit the remaining accumulated changes in the separated groupings described above; (d) only then begin Phase 1's own Architecture Review and Planning steps against that clean, committed baseline. This document does not perform any of these steps — it only identifies them.
