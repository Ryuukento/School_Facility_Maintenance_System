# CHANGELOG.md

## PhilCST Centralized School Facility Maintenance Reporting System — Project Changelog

**Document status:** Living reference. This changelog records major architectural milestones and additions to the system. Entries are grouped by milestone rather than by exact release version, since the project has not yet adopted formal semantic versioning. Historical entries are recorded to the best of available evidence from the codebase; entries without a precise date are marked accordingly rather than assigned a fabricated date. Future entries should include the date they land and should be added at the time of the change, not retroactively.

Format inspired by [Keep a Changelog](https://keepachangelog.com/), adapted for this project's milestone-based history.

---

## [Unreleased]

### Planned / Under consideration
- Add a `need_change_status` enum handling review: `reject_need_change` currently sets the status to `'rejected'`, a value not present in the `need_change_status` enum defined by `database/migrations/2026_04_07_000700_add_need_change_fields_to_maintenance_reports_table.php` (`pending`/`approved`/`deducted`/`failed`) — a pre-existing inconsistency, out of scope for the Need Change deduction fix below and left untouched.
- Expand automated test coverage: introduce Feature tests (PHPUnit + `RefreshDatabase`) for `DispatchService`, `PurchaseReceiptPostingService`, and the two inventory Observers, reducing reliance on manual `scripts/smoke_*.php` verification scripts.
- Remove the stale `backend/api/*` CSRF exemption in `bootstrap/app.php` now that the legacy `public/backend/api/*.php` endpoint files it referenced have been removed from the working tree.
- Replace hardcoded plaintext administrator credentials in `database/seeders/DatabaseSeeder.php` with an environment-variable-driven or prompted seeding approach.
- Route `PurchaseReceiptPostingService`'s activity logging through `ActivityLogService` instead of a direct `DB::table('activity_logs')->insert()` call, for consistency with `DispatchService`.

---

## 2026-07-22 — Inventory quantity-edit bypass closed (Inventory Module QA follow-up)

- **Fixed** — `InventoryStockController::update()` (`PUT /api/inventory-stock/{id}`, used by the "Edit Item" modal in `inventory.php`) accepted an optional `quantity` field and wrote it straight into `items.quantity` via raw SQL, then logged the delta with a second raw `INSERT INTO inventory_transactions`. This bypassed `InventoryAdjustmentService`, `InventoryTransactionObserver`, activity logging, and analytics cache invalidation entirely — a direct violation of the single-writer principle documented in `ARCHITECTURE.md` §5, and confirmed as a real bug during the Inventory Module End-to-End QA review. Manual Stock Adjustment (`ItemController::adjustStock` → `InventoryAdjustmentService`) is the only sanctioned path for changing on-hand quantity.
  - `quantity` was removed from `update()`'s validation rules; the method now always resolves `$quantity` from the existing DB row and never from the request body, so it is accepted but silently ignored.
  - The `reserved_quantity` guard and the raw-SQL `inventory_transactions` insert (the ledger bypass) were removed along with it, since both existed solely to support the now-removed quantity path.
  - The method's docblock was updated to state plainly that quantity is never accepted here.
  - `public/frontend/pages/inventory.php`'s shared Add/Edit Item modal made its quantity input `readOnly` whenever `startEditInventoryItem()` opens it in edit mode, and `saveAddItems()` no longer includes `quantity` in the update payload (it is still sent, and still required, when creating a new item).
- **Added** — `tests/Feature/InventoryStockUpdateQuantityBypassTest.php` (2 tests, 9 assertions): confirms a metadata-only edit still succeeds and leaves quantity untouched, and confirms a `quantity` field sent in the request body is ignored — the item's quantity is unchanged and no `inventory_transactions` row is written.
- No changes to Purchase Receipt, Manual Stock Adjustment, Need Change, Deploy, or any legacy endpoint; this fix touched only `InventoryStockController::update()` and the Edit Item path in `inventory.php`.
- `php artisan test`: 60 passed, 6 failed, 242 assertions (the same 6 pre-existing, unrelated `PurchaseReceiptPostingTest`/`ExampleTest` failures documented above are unchanged; the prior 58/233 baseline plus this change's 2/9 new tests).

---

## 2026-07-22 — Navigation audit and cleanup (Phase 4 System Flow Optimization follow-up)

- **Fixed** — `public/frontend/includes/sidebar.php`'s "Create Report" link was computed by role (`$showCreateReport = in_array(...)`) and then unconditionally overwritten to `false` one line later, hiding the shortcut from every user regardless of role. This was documented in `SYSTEM_FLOW_REVIEW.md` §8 and ranked #8 in its Top 20 Improvements. The hardcoded override was removed; the role check now also includes `super_admin` (previously only `maintenance_admin`/`maintenance_staff`), matching `reports.php`'s existing `$canCreateReport` gate and `BUSINESS_RULES.md` §1's permission table, where "Submit damage report / repair request" is ✅ for all three roles.
- **Fixed** — The "All Reports" sidebar link only highlighted as active on an exact `basename()` match against `reports.php`/`maintenance-reports-list.php`, so it lost its active state when a user drilled into `maintenance-report-detail.php` — inconsistent with the multi-page active-highlight pattern already used for Damage Reports, Repair Requests, Dispatches, and Activity Logs. The active check now matches `in_array($current_page, ['reports.php', 'maintenance-reports-list.php', 'maintenance-report-detail.php'], true)`, consistent with the other sections.
- **Fixed** — `public/frontend/pages/dashboard.php`'s "no buildings found" fallback notice (shown in the Add Room modal when `/api/buildings` returns empty) linked to `backend/setup.html`, a file that does not exist on disk — a dead link. It now points to the existing `frontend/pages/buildings-overview.php` page instead.
- **Removed** — `public/frontend/components/components.php` defined `renderNavbar()` and `renderSidebar()`, a dead, unreferenced duplicate navigation menu (confirmed via a codebase-wide grep to have zero callers, referenced only from the already-dead `public/frontend/guide1/README.md`). Its hardcoded menu array also pointed at `/frontend/pages/analytics.php`, which does not exist (the real page is `analytics-dashboard.php`) — a stale, broken, legacy route. Both functions were deleted; the file's other unrelated presentation helpers (`renderAlert`, `renderCard`, `renderFormGroup`, `renderTable`, `renderModal`, `renderBadge`) were left untouched.
- Audited the live sidebar, navbar (`includes/header.php`), and every `public_url()`/route-alias link used across `public/frontend/pages` and `public/frontend/includes` against `routes/web.php`: no other broken or missing routes were found — every remaining nav target resolves to an existing file or defined route.
- No changes to any completed Phase 4 work (Inventory Entry redirect, Manual Stock Adjustment UI, Report Completion Notification, User Management Security Fix) or to any business logic; this was a navigation-only fix. Confirmed-dead/orphaned legacy pages with no live incoming nav links (e.g. `super-admin-dashboard.php`, `profile.php`, `report-detail.php`, `edit-report.php`, `maintenance-create-report.php`, `inventory-transactions.php`) were deliberately left in place — they are already unreachable from navigation, and deleting them is a separate cleanup task (`SYSTEM_FLOW_REVIEW.md` Top 20 #14/#19), not a navigation-consistency repair.
- **Added** — `tests/Feature/NavigationConsistencyTest.php` (5 tests, 12 assertions): asserts the sidebar no longer hardcodes `$showCreateReport` to `false`, that the Create Report role check includes all three roles that can submit reports, that the All Reports link stays active from its detail page, that `components.php` no longer defines the dead duplicate `renderSidebar()`/`renderNavbar()`, and that `dashboard.php` no longer links to the nonexistent `backend/setup.html`.
- `php artisan test`: 58 passed, 6 failed, 233 assertions (the same 6 pre-existing, unrelated `PurchaseReceiptPostingTest`/`ExampleTest` failures documented above are unchanged; the prior 53/221 baseline plus this change's 5/12 new tests).

---

## 2026-07-22 — User management authorization hardened (Phase 4 System Flow Optimization follow-up)

- **Fixed** — `GET /api/users` had no role guard at all (only the group-level `EnsureApiAuthenticated` login check): any authenticated user, regardless of role, could call it directly and read the full name/email/role/status roster. This was the clearest concrete security gap identified in `SYSTEM_FLOW_REVIEW.md` §6 (Users) and ranked #4 in its Top 20 Improvements. The route now reuses the existing `EnsureRole::class . ':super_admin'` middleware — the same guard already applied to every other user-management route (`store`, `deactivate`, `activate`, `approve`, `reject`, `resetPassword`) — matching `BUSINESS_RULES.md` §1's permission table ("Manage users" = super_admin only). No new authorization system, Policy, or Gate was introduced; `EnsureRole` and `RoleNormalizerService` were reused as-is.
- **Fixed** — The Approve User modal (`public/frontend/pages/users.php`) offered "Admin" (`super_admin`) as an assignable role, but `UserController::approve()`'s validator only ever accepted `maintenance_admin`/`maintenance_staff` (`'role' => ['required', 'string', 'in:maintenance_admin,maintenance_staff']`) — selecting "Admin" always failed server-side with no graceful handling. This was ranked #5 in the same Top 20 list. The "Admin" option was removed from the modal so the frontend role list exactly matches the backend validator; no backend validation was changed or loosened.
- `PATCH /api/users/profile` (self-service profile update) was intentionally left without an `EnsureRole` guard — it is scoped to the authenticated user's own account via the session, not a privileged user-management action, and adding a role restriction there would break self-service profile editing for every role.
- **Added** — `tests/Feature/UserManagementAuthorizationTest.php` (10 tests, 17 assertions): a `super_admin` can list users and approve a pending user; `maintenance_admin`/`maintenance_staff` cannot list users, approve users, delete (reject) a pending user, or edit (deactivate) a privileged (`super_admin`) account, all receiving `403 Forbidden`; an unauthenticated request receives `401 Unauthorized`; the `approve` endpoint rejects a `super_admin` role assignment with `422`; and the Approve User modal's markup is asserted to only ever offer `maintenance_staff`/`maintenance_admin`, never `super_admin`, locking the frontend/backend contract in place.
- No changes to `store`, `deactivate`, `activate`, `reject`, `resetPassword`, or `updateProfile` beyond verifying their existing `EnsureRole::class . ':super_admin'` guards were already correct — all six were already properly restricted prior to this change. No Policy/Gate layer was added, consistent with `ARCHITECTURE.md` §7 documenting RBAC as `EnsureRole`-middleware-based by design. No changes to inventory modules, Need Change, or the Report Completion Notification work above.
- `php artisan test`: 53 passed, 6 failed, 221 assertions (the same 6 pre-existing, unrelated `PurchaseReceiptPostingTest`/`ExampleTest` failures documented above are unchanged; the prior 43/204 baseline plus this change's 10/17 new tests).

---

## 2026-07-22 — Report Completion Notification added (Phase 4 System Flow Optimization follow-up)

- **Added** — `ReportController::update()` now notifies the report owner (`maintenance_reports.created_by`) exactly once whenever a report transitions into `status = 'completed'` or `status = 'closed'`. This closes the "communication loop" gap identified in `SYSTEM_FLOW_REVIEW.md` §7 (Notifications) and ranked #3 in its Top 20 Improvements: previously, the original requester had no way to learn their issue was resolved except by manually checking the reports list.
- No new notification system was introduced. The fix reuses the exact raw `DB::table('notifications')->insert()` pattern already established in `ReportController::store()` for the new-report-submission notification, including the same schema-flexible `Schema::hasColumn('notifications', 'report_id')` guard (the `notifications` migration itself does not declare a `report_id` column, but the guard already existed for environments where one has been added). No new table, model, or service was created.
- The notification's `message` includes the required fields: report number, facility/room (`location`), assigned staff (`$report->assignee->full_name`, via the existing `MaintenanceReport::assignee()` Eloquent relationship, or "Unassigned"), completion date (`completed_date`, defaulting to today if not supplied), and final status ("Completed"/"Closed").
- **Duplicate prevention**: the report's status is captured (`$previousStatus`) before `$report->update($changes)` is applied; the notification only fires when the new status differs from the previous one (i.e. on the actual transition into a terminal state), not on every save. A repeated/idempotent `PATCH` re-submitting the same terminal status (e.g. `completed` → `completed`) does not create a second notification. This mirrors the transition-based idempotency approach already used by `NeedChangeService::approve()`, without adding a new "notified_at" column, since the existing `status` field already carries enough information to detect the transition.
- Verified the pre-existing, unrelated new-report-submission notification flow in `ReportController::store()` is untouched and still fires correctly; verified that non-terminal status transitions (e.g. `submitted → assigned`) do not trigger this notification, since no live assignment-notification code path exists today (`SYSTEM_FLOW_REVIEW.md` §7 confirms the only assignment notification lives in unreachable legacy `ReportService.php` code, out of scope for this change).
- **Added** — `tests/Feature/ReportCompletionNotificationTest.php` (5 tests, 20 assertions): completing a report notifies the owner exactly once with all required fields present; closing a report notifies the owner exactly once; a repeated completion update does not duplicate the notification; a non-terminal status transition does not notify; and the existing new-report-submission notification flow is unaffected.
- No changes to inventory modules, Need Change, `NotificationController`, the `notifications` schema, or any other business rule. `php artisan test`: 43 passed, 6 failed, 204 assertions (the 6 pre-existing, unrelated `PurchaseReceiptPostingTest` failures documented above are unchanged; the prior 38/184 baseline plus this change's 5/20 new tests).

---

## 2026-07-22 — Manual Stock Adjustment UI added (Phase 4 System Flow Optimization follow-up)

- **Added** — A "Manual Adjustment" interface on `public/frontend/pages/inventory.php`, visible to `super_admin`/`maintenance_admin` only (matching `BUSINESS_RULES.md` §1's permission table). This closes the gap identified in `SYSTEM_FLOW_REVIEW.md` §5/Top 20 #2: two fully-built, audited backend endpoints for manual stock adjustment (`ItemController::adjustStock` → `InventoryAdjustmentService`, and a separate `InventoryStockController::adjust`) existed with **no UI caller**, leaving the "Edit Item" modal as the only de facto way to change quantity — silently, with no reason captured and no ledger entry.
- The new "Adjust Stock" button/modal calls the officially documented endpoint only: `POST /api/items/{item}/adjust-stock` → `InventoryAdjustmentService::adjust()` (per `BUSINESS_RULES.md` §3, "Stock Adjustment (manual)"). `InventoryStockController::adjust()` was deliberately **not** wired up — inspection shows it performs a raw `UPDATE items SET quantity = ...` and bypasses `InventoryTransactionObserver` entirely, in direct conflict with `ARCHITECTURE.md` §5's single-writer principle. Wiring the UI to that endpoint instead would have reused a duplicate, architecturally-noncompliant path; no backend code was added, changed, or duplicated to make this decision.
- The modal lets the user select an item (populated from the already-loaded inventory list — no new endpoint), view current on-hand/available stock, choose Increase/Decrease, enter a quantity, and enter a reason. The backend's `reason` field is, and remains, free text (`BUSINESS_RULES.md` §3); no separate fixed "reason" enum/list exists anywhere in the codebase, so none was invented for the UI — a single required reason field is the faithful reflection of the existing system, not a second one.
- UX: a live "review" summary (current → projected quantity) updates as the form is filled in, a `Components.confirm()` dialog gates submission, the Save button disables and shows "Saving..." during the request, and `Components.toast()`/`Components.alert()` report success/failure — consistent with the existing Delete/Edit item patterns already used on this page.
- All validation (empty/zero/negative quantity, missing item, missing reason, insufficient available stock on decrease) is enforced by the existing, already-tested `InventoryAdjustmentService`/`ItemController::adjustStock` — no validation logic was duplicated client-side beyond basic required-field checks before submission.
- No routes, controllers, services, database schema, or business rules were changed — `ItemController::adjustStock`, `InventoryAdjustmentService`, and the `POST /api/items/{item}/adjust-stock` route all pre-existed this change and were already covered by `tests/Feature/InventoryAdjustmentTest.php` (11 tests, unchanged). `php artisan test`: unchanged baseline (38 passed, 6 failed, 184 assertions — the 6 pre-existing `PurchaseReceiptPostingTest` failures documented above).
- Out of scope for this change (unchanged): the "Edit Item" modal's own quantity field (tracked separately in `[Unreleased] > Planned / Under consideration` above) and the Inventory Entry History panel.

---

## 2026-07-22 — Inventory Entry button repaired (Phase 4 System Flow Optimization follow-up)

- **Fixed** — The "Inventory Entry" button on `public/frontend/pages/inventory.php` (visible to `super_admin`/`maintenance_admin`) opened a modal whose "Save Entry" action `POST`ed to `/api/inventory-stock/entries`. That path has no `POST` route in `routes/web.php` (only `GET`, for `InventoryStockController::listEntries()`), so every submission failed with a 405 before reaching any controller code. Root cause traced further: the controller method matching that endpoint's own docblock, `InventoryStockController::createEntry()`, is not a working implementation — it immediately returns HTTP 410 ("This endpoint is deprecated. Use POST /api/purchase-receipts and POST /api/purchase-receipts/{id}/post to add stock."). Stock intake has already been fully consolidated onto the Purchase Receipts workflow; this modal/button were leftover surface area pointing at intake logic the backend had already superseded, identified in `SYSTEM_FLOW_REVIEW.md` §5 (Inventory) as the #1-ranked improvement.
- Rather than adding the missing route to a deliberately-deprecated stub, or resurrecting `createEntry()` (which would reintroduce a second, inconsistent stock-intake path alongside Purchase Receipts — the exact duplication `SYSTEM_FLOW_REVIEW.md` flags as a bottleneck), the button's click handler now navigates to the existing, fully-functional Purchase Receipts page (`window.SFMS_PUBLIC_URL('/purchase-receipts')`, matching the same named route/URL pattern already used by `public/frontend/includes/sidebar.php` for its own Purchase Receipts nav link) instead of opening the non-functional modal.
- This is a one-line JS change (`inventory.php`'s `openInventoryEntryButton` click handler). The modal's markup, its `saveInventoryEntry()`/`loadInventoryEntryHistory()` JS, and the separate "Inventory Entry History" panel on the same page were **not** touched or removed — out of scope for this fix, and already tracked separately in `SYSTEM_FLOW_REVIEW.md`.
- No routes, controllers, database schema, or business rules were changed. `php artisan test` before and after: unchanged baseline (38 passed, 6 failed, 184 assertions — the 6 pre-existing `PurchaseReceiptPostingTest` failures caused by the unrelated `APP_URL`-subdirectory routing collision, already documented above).

---

## 2026-07-22 — Inventory Status Calculation Consolidation

- Consolidated the 4 duplicated implementations of inventory status derivation (`quantity <= 0` → `out_of_stock`; `quantity <= reorder_level` → `low_stock`; else → `available`) into a single new service, `App\Services\InventoryStatusService`, continuing the Phase 3 architecture cleanup started by the Role Normalization Consolidation entry below.
- `InventoryStatusService` exposes one static method, `deriveStatus(int $quantity, int $reorderLevel): string`, styled after the existing `RoleNormalizerService` — a stateless static utility with no constructor, matching the precedent already established in this codebase for single-purpose derivation helpers used across mixed Controller/Service call sites.
- Replaced duplicated `private function deriveStatus()` / `deriveItemStatus()` methods (10 call sites total) in: `ItemController::store()` (2 call sites — initial-quantity-zero creation, post-ledger status re-derivation); `InventoryAdjustmentService::adjust()` (1 call site); `PurchaseReceiptPostingService::postReceipt()`/`resolveInventoryItem()` (2 call sites); `InventoryStockController::update()`/`adjust()`/`deploy()`/`createOrUpdateRoomAsset()` (5 call sites). All 4 private methods were removed entirely; every call site now calls `InventoryStatusService::deriveStatus(...)`.
- Intentionally left untouched (documented, not duplicates): `InventoryStockController::index()`'s raw-SQL `low_stock_warning` `CASE WHEN` computed column (a read-time boolean flag for listing, not a status *derivation*) and `summary()`'s aggregate `COUNT`s (which read the already-persisted `status` column, not derive it); `AnalyticsService`/`AnalyticsReportController`/`StockController`/`InventoryCategoryController`/`DashboardController`'s low-stock reporting logic (these compare against `low_stock_threshold_override`/`default_low_stock_threshold` for analytics purposes — a distinct, pre-existing business concept from the `reorder_level`-based `Item.status` write-path, and out of scope per this task's "do not modify Reports" constraint).
- This was a pure, mechanical, non-behavior-changing refactor: `php artisan test` was run before and after and produced an identical result both times (38 passed, 6 failed, 184 assertions). The 6 pre-existing failures are unchanged and unrelated to this change.
- No production behavior changed. `InventoryTransactionObserver`, `InventoryStockEntryObserver`, `NeedChangeService`, `DispatchService`, and the database schema were not modified. `BUSINESS_RULES.md` was not modified, since no business behavior changed.

---

## 2026-07-22 — Shared Test Infrastructure refactor completed

- **Changed** — Finished migrating `tests/Feature/PurchaseReceiptPostingTest.php` onto the three shared test traits introduced earlier in this refactor (`tests/Support/BuildsSharedTestSchema.php`, `ConfiguresIsolatedSqliteConnection.php`, `InteractsWithLegacySession.php`), which `ItemControllerInventoryTest.php`, `InventoryAdjustmentTest.php`, `NeedChangeApprovalTest.php`, `ReportsApiNeedChangeTest.php`, and `InventoryTransactionObserverRollbackTest.php` already used. `PurchaseReceiptPostingTest.php` previously carried its own byte-for-byte-duplicated private `useInMemoryDatabase()`, `seedUser()`, and `seedItem()` methods, plus inline `Schema::create()` Blueprint definitions for `users`/`departments`/`items`/`activity_logs`/`inventory_transactions` that the shared traits already provide; these are now replaced with calls to the shared trait methods, and its six `withSession(['auth_user' => ...])` call sites now use `InteractsWithLegacySession::actingAsSessionUser()`. The file's own `inventory_rooms`, `inventory_categories`, `purchase_receipts`, `purchase_receipt_items` tables and `seedInventoryRoom()`/`seedReceipt()`/`seedReceiptItem()` helpers remain local, since no other test file needs them.
- This was a pure, mechanical, non-behavior-changing refactor: `php artisan test` was run before and after and produced an identical result both times (38 passed, 6 failed, 184 assertions). The 6 failures are the pre-existing, previously-documented `PurchaseReceiptPostingTest` failures caused by the `APP_URL`-subdirectory routing collision (see the "Testing sprint" entry below) — fixing those was explicitly out of scope for this refactor and is left as-is, consistent with how it was previously deferred.
- No production code was changed.

---

## Role Normalization Consolidation
*2026-07-21*

- Consolidated the ~11 duplicated Laravel-side implementations of role-alias normalization (`admin_maintenance` → `maintenance_admin`; `eelab_staff`/`maintenance_personnel` → `maintenance_staff`) into a single new service, `App\Services\RoleNormalizerService`, per the debt item tracked in `ROADMAP.md` Phase 3 and called out in `ARCHITECTURE.md` §7/§8 and `AI_INSTRUCTIONS.md` Rule 8.
- `RoleNormalizerService` exposes three static methods: `normalize()` (alias lookup, empty input passes through unchanged), `normalizeWithStaffDefault()` (same, but an empty role defaults to `maintenance_staff`, matching several call sites' existing behavior), and `rawValuesFor(array $canonicalRoles)` (returns the raw/alias role strings that resolve to a given set of canonical roles, for `whereIn()`-style queries against the un-normalized stored `role` column).
- Two method variants were needed, not one, because call sites disagreed on how to treat an empty/missing role string; preserving that split preserves every call site's existing authorization behavior exactly (no authorization decision changed).
- Replaced duplicated logic in: `EnsureRole` and `SyncLegacyPhpSession` middleware; `DamageReportService::normalizeRole()`, `RepairService::normalizeRole()` (and its separate `searchTechnicians()` `$allowedRoles` array); `DashboardController::normalizeRole()`; `AuthController::normalizeRoleAlias()`; `DeploymentTrackingController`, `PurchaseReceiptController`, and `ReplacementTrackingController`'s identical `ROLE_ALIASES` const + `resolveRole()` pair; `InventoryStockController::resolveRole()`; `ReportController::normalizeRole()`. Existing method names/signatures on each class were kept as thin delegating wrappers where call sites reference them by name, minimizing the diff.
- Intentionally left untouched (documented, not fixed, as this was a pure refactor): the five legacy `public/backend/*` duplicates (deferred to `ROADMAP.md` Phase 4's legacy-surface classification, and out of scope per `AI_INSTRUCTIONS.md`'s rule against extending the legacy surface without explicit instruction); the already-applied one-time data migration `database/migrations/2026_06_15_000000_normalize_user_roles.php` (a historical DB backfill, not a runtime normalization function, and migrations that have run must not be edited); and `ReportController::store()`'s `$recipientRoles` notification allowlist (line ~170), which is a list of raw role values to notify, not a normalization function, and additionally includes `admin`/`department_admin` values outside this service's three-alias table.
- No authorization decisions, route permissions, or database schema changed. `BUSINESS_RULES.md` was not modified, since no business behavior changed.

---

## Stock Adjustment workflow
*2026-07-21*

- Added a dedicated administrator-facing endpoint, `POST /api/items/{item}/adjust-stock`, for manually increasing or decreasing an item's on-hand quantity outside of a purchase receipt, dispatch, or Need Change flow (e.g. physical recounts, damage/loss corrections). Previously there was no official way to perform this — `ItemController::update()` intentionally stopped accepting `quantity` once inventory quantity became ledger-derived, leaving no replacement path.
- New `InventoryAdjustmentService::adjust()` (`app/Services/InventoryAdjustmentService.php`) implements the workflow: locks the `Item` row (`lockForUpdate()`) inside `DB::transaction()`, pre-validates a decrease against the item's *available* quantity (`quantity - reserved_quantity`, the same definition already used by `DispatchService::releaseDispatch()`), then creates a single `adjustment`-type `InventoryTransaction`. Increases and decreases both reuse the existing `adjustment` transaction type and its existing `reference_note` `'DEDUCT:'`-prefix convention (already implemented in `InventoryTransactionObserver`) to signal direction — no new transaction type was introduced. `InventoryTransactionObserver` performs the actual `Item.quantity` mutation and audit trail write, as with every other inventory workflow. The item's `status` is re-derived after the transaction, mirroring `ItemController::store()`'s existing post-adjustment status recomputation. The action is logged via `ActivityLogService` (`ADJUST_STOCK`).
- `ItemController::adjustStock()` validates the request (`direction: increase|decrease`, `quantity: integer >= 1`, `reason: required string`) and delegates to the Service; a `ValidationException` (insufficient stock) is translated to a `422` response with the specific message, consistent with how `ReportController`/`DispatchController` handle Service-level validation failures.
- The route is restricted to `super_admin`/`maintenance_admin` via the existing `EnsureRole` middleware, matching the permission tier already applied to purchase receipt posting and dispatch approval/release.
- Added `tests/Feature/InventoryAdjustmentTest.php` (11 tests, 34 assertions) covering: increase, decrease, decrease exceeding on-hand quantity, decrease exceeding available quantity after an existing reservation, zero/negative quantity rejection, missing reason rejection, invalid direction rejection, `super_admin` allowed, `maintenance_staff` forbidden (403, no transaction created), and quantity never going negative with status correctly recomputed to `out_of_stock`.
- No changes to Purchase Receipt, Need Change, Dispatch, Deployment Tracking, Reports, or either Observer — this was purely an additive endpoint/Service reusing the existing `adjustment` transaction type.

---

## Project initialization
*(date not precisely determinable from available history)*

- Initial system built as a procedural PHP application (native sessions, raw PDO database access, PHP-include-based page templates) to digitize school facility maintenance reporting.
- Established the foundational facility hierarchy (buildings, floors, rooms) and basic maintenance report submission.

## Migration to Laravel
*(date not precisely determinable from available history)*

- Introduced a Laravel application alongside the existing legacy PHP codebase, adopting an incremental migration strategy rather than a full rewrite.
- Added `bootstrap/app.php`-based application bootstrap (routing, middleware pipeline, centralized JSON exception rendering for API/JSON requests).
- Added `SyncLegacyPhpSession` middleware to bridge Laravel's session store with the legacy native `$_SESSION` mechanism, allowing legacy and Laravel pages to share one authenticated identity during the transition period.
- Began migrating controllers, models, and views into the Laravel `app/`/`resources/` structure; legacy equivalents under `public/backend/` and `public/frontend/` were retained during the transition and are being phased out incrementally.

## Inventory architecture
*(date not precisely determinable from available history)*

- Introduced the append-only `inventory_transactions` ledger as the authoritative record of all stock movement, replacing direct quantity mutation.
- Introduced the `InventoryTransactionObserver` and `InventoryStockEntryObserver` as the sole authorized mutators of `Item.quantity`/`Item.reserved_quantity`, registered centrally in `AppServiceProvider::boot()`.
- Established the transaction-type vocabulary (`reserve`, `release`, `deploy`, `return`, `adjustment`, `dispose`) and the corresponding validation rules (availability checks against locked `Item` rows before mutation).
- Established derived-status logic for `Item.status` (`available` / `low_stock` / `out_of_stock`), computed from `quantity` and `reorder_level` rather than set directly.

## Purchase Receipt Posting Service
*(date not precisely determinable from available history)*

- Added `PurchaseReceiptPostingService::postReceipt()` to formalize the draft-to-posted purchase receipt workflow: row-locking the receipt and each resolved line item's `Item`, validating draft status and non-empty line items, resolving or creating the target `Item` per line, and creating one `adjustment`-type `InventoryTransaction` per line to apply the stock increase through the ledger.
- Added item-resolution logic to match an incoming receipt line to an existing `inventory_stock` item by room + case-insensitive name, falling back to creating a new `Item` when no match exists.

## InventoryTransactionObserver
*(date not precisely determinable from available history)*

- Implemented `creating()` hook: locks the target `Item` row and validates the requested transaction against type-specific constraints (reserve/deploy/dispose/release) before allowing creation.
- Implemented `created()` hook: re-locks the `Item` row, applies the validated quantity math, recomputes derived status, persists the `Item`, logs the action via `ActivityLogService`, and flushes the `analytics` cache tag (with an individual-key fallback).
- Implemented `deleted()` hook: reverses the quantity math for a deleted transaction. (Note: this hook does not currently re-acquire `lockForUpdate()`, unlike `creating()`/`created()` — tracked as a known gap under Unreleased above.)

## DispatchService
*(date not precisely determinable from available history)*

- Added `DispatchService` to manage the full dispatch lifecycle: `createDispatch()`, `approveDispatch()`, `cancelDispatch()`, and `releaseDispatch()`.
- Implemented status-transition guards for each lifecycle method (e.g. only `pending` dispatches can be approved, only `approved` dispatches can be released, only `pending`/`approved` dispatches can be cancelled), each raising `ValidationException` on an invalid transition.
- Implemented pre-release stock availability validation (per line: requested quantity must not exceed `quantity - reserved_quantity`) prior to creating any `deploy`-type `InventoryTransaction`, ensuring an entire release either fully succeeds or is fully rejected — no partial releases.
- Integrated `ActivityLogService` logging for dispatch creation, approval, cancellation, and release events.

## 2026-07-21 — Architecture Audit

- Conducted a full, analysis-only architecture review of the system as Lead Software Architect, covering: overall architecture and scoring, folder structure, database architecture, business workflows, strengths, technical debt (by severity), code smells, security review, performance review, maintainability review, scalability review, missing features, and a suggested improvement roadmap.
- Verified, via direct source inspection, that the Need Change approval workflow's inventory-deduction step (present in the legacy, now-unreachable `ReportService::deductNeedChangeInventory()`) was not carried over into the live Laravel `ReportController::update()` — corrected an earlier, disproven hypothesis of a "double-deduction bug" to the accurate finding of a **non-functional deduction step** (see `BUSINESS_RULES.md`, Section 10).
- Identified the `InventoryTransactionObserver::deleted()` locking gap, the duplicated role-alias-normalization pattern, the stale `backend/api/*` CSRF exemption, the hardcoded seeder credentials, and the sparse automated test coverage as the primary technical-debt findings feeding the Unreleased section above.
- No application code was modified as part of this audit; it was strictly read-only analysis.

## 2026-07-21 — Documentation Creation

- Authored `ARCHITECTURE.md`, `BUSINESS_RULES.md`, and this `CHANGELOG.md` as the permanent, single-source-of-truth technical documentation set for the project, derived from the Architecture Audit above.
- Established these three documents as binding references: all future features, refactors, and bug fixes are expected to be consistent with `ARCHITECTURE.md`'s structural/layering rules and `BUSINESS_RULES.md`'s workflow/state-machine rules, and all future milestones are expected to be recorded in this changelog.
- No application code was modified as part of this documentation effort; only these three documentation files were created.

## 2026-07-21 — Phase 1: ItemController ledger-bypass fix

- **Fixed** — `ItemController::store()` and `ItemController::update()` no longer mass-assign `quantity`, `reserved_quantity`, or `status` directly onto `Item` (a violation of the single-writer principle identified in `ARCHITECTURE_VIOLATIONS.md`, Violation 1).
- `store()` now creates the new `Item` at `quantity = 0` (`status` derived as `out_of_stock`), then — if an initial quantity was requested — creates an `adjustment`-type `InventoryTransaction` so `InventoryTransactionObserver` performs and audits the actual stock increase, matching the pattern already used by `PurchaseReceiptPostingService`.
- `update()` no longer accepts `quantity` or `status` at all; a dedicated stock-adjustment endpoint is required for changing on-hand quantity post-creation (tracked as a follow-up under Unreleased below, not implemented in this change).
- No other module (Need Change, Dispatch, Purchase Receipt, Deployment Tracking) was touched. No business rules changed.

## 2026-07-21 — Phase 1: ItemController inventory workflow tests

- **Added** — `tests/Feature/ItemControllerInventoryTest.php`, five PHPUnit Feature tests covering the ItemController ledger-bypass fix above: creating an item with an initial quantity (asserts the quantity is applied via exactly one `adjustment`-type `InventoryTransaction` and status is Observer-derived), creating an item with zero quantity (asserts no transaction is created and status is `out_of_stock`), updating an item with `quantity` in the payload (asserts it is ignored while metadata updates still apply), updating an item with `status` in the payload (asserts it is ignored), and a metadata-only update (asserts all non-ledger fields persist normally).
- Follows the existing self-contained Feature-test convention established by `PurchaseReceiptPostingTest` (an isolated in-memory SQLite connection with a hand-built minimal schema, `withSession()` for auth), rather than `RefreshDatabase` against the real migrations.
- No production code was changed to make these tests pass; the two adjustments the test setup required — forcing `app.url`/root URL, and authenticating via both `auth_user` and the flat `user_id`/`role` session keys `ItemController` reads directly — are both test-harness-only accommodations for a pre-existing environment quirk (`APP_URL` includes the XAMPP subdirectory, which collides with a legacy catch-all redirect route in `routes/web.php`) and are fully contained inside the new test file.

## 2026-07-21 — Phase 1: InventoryTransactionObserver::deleted() locking fix

- **Fixed** — `InventoryTransactionObserver::deleted()` now acquires `Item::lockForUpdate()->find()` before reversing the quantity math for a deleted `InventoryTransaction`, matching the locking strategy already used by `creating()` and `created()` in the same class (closes the gap tracked under Unreleased above and documented in `ARCHITECTURE.md`, Section on Observer locking).
- This is a single-line change (plus an updated inline comment); no other line of `deleted()` — the per-transaction-type rollback arithmetic, the `$item->save()` call, or the analytics-cache-flush fallback — was modified.
- No other `InventoryTransactionObserver` method, and no other file, was touched.

## 2026-07-21 — Phase 1: Testing sprint — Need Change / inventory ledger coverage

- **Added** — `tests/Feature/NeedChangeApprovalTest.php` (4 tests): covers `NeedChangeService::approve()` via `PATCH /api/reports/{id}` — a valid approval deducting stock through exactly one `deploy` `InventoryTransaction` and stamping all four `need_change_*` result fields; duplicate-approval protection (second approval is a no-op, no second transaction, no second deduction, timestamps unchanged); the `need_change_item_id === null` validation-error path; and the insufficient-stock validation-error path. All four assert no `InventoryTransaction` row and no report-state mutation on the failure paths.
- **Added** — `tests/Feature/InventoryTransactionObserverRollbackTest.php` (4 tests): exercises `InventoryTransactionObserver::deleted()` directly against the model (no public endpoint deletes a transaction today, matching the existing precedent in `PurchaseReceiptPostingTest::test_inventory_transaction_observer_adjustment_increases_quantity_once()`). Covers `deploy` rollback after a prior `reserve` (both quantity and reserved_quantity restored correctly), `deploy` rollback with no prior reservation (quantity restored, never negative), `adjustment` rollback, and `release` rollback.
- **Added** — `tests/Feature/ReportsApiNeedChangeTest.php` (6 tests): confirms `GET /api/reports` exposes all six `need_change_*` fields with correct values after a real approval (closing the gap fixed in the entry below); confirms `GET /api/reports/{id}` returns the same values as the list endpoint (comparing timestamps as parsed instants, since `index()` returns Eloquent-cast ISO8601 strings while `show()` returns raw `DB::table()` datetime strings — a pre-existing, harmless serialization difference between the two endpoints, not a defect); confirms `status`/`priority`/`department_id`/date-range/`assigned_to_me` filters return identical row counts and row sets regardless of Need Change field values; and confirms role-based visibility (regular users see only own/assigned reports, privileged roles see all reports including ones with `need_change_status = 'deducted'`) is unchanged by the presence of the new fields.
- No production code was changed. One test-diagnosis step (temporarily instrumenting `PurchaseReceiptPostingTest.php` with a debug `fwrite(STDERR, ...)` line to inspect a response body, then reverting it — confirmed via `git diff` to leave no residual change) confirmed the root cause of that file's 6 pre-existing failing tests: `APP_URL` in `.env` includes the XAMPP subdirectory path, which the Laravel test client's URL-preparation step folds into the request URI, causing every `postJson()` call in that file to hit the legacy subdirectory-redirect catch-all route in `routes/web.php` instead of the intended `api/*` route (`MethodNotAllowedHttpException`, rendered as a generic 500 by `bootstrap/app.php`'s exception handler). This is the same issue `ItemControllerInventoryTest.php` already works around with `Config::set('app.url', ...); app('url')->forceRootUrl(...)` in `setUp()`. All three new test files in this entry apply that same workaround. `PurchaseReceiptPostingTest.php` itself was left untouched — fixing its pre-existing failures is out of scope for this testing-sprint task, which targeted the completed inventory/Need Change workflow only.
- Full suite: 27 passed, 6 failed (150 assertions) — the 6 failures are the pre-existing `PurchaseReceiptPostingTest` failures described above, unchanged from before this change (previously 13 passed / 6 failed with no Need Change coverage).

## 2026-07-21 — Phase 1: Reports API exposes Need Change fields

- **Fixed** — `ReportController::index()` (`GET /api/reports`) now selects `need_change_item_id`, `need_change_quantity`, `need_change_status`, `need_change_approved_by`, `need_change_approved_at`, and `need_change_deducted_at` from `maintenance_reports`. Previously these columns were omitted from the list endpoint's `select()`, so every report in the list view was serialized without them.
- These fields already existed on the `maintenance_reports` table and were already fully populated by `NeedChangeService::approve()` and `ReportController::update()`'s `reject_need_change` branch; only the list-endpoint's column selection was incomplete. No new column, no schema change, no new business logic.
- Consequence: `public/frontend/pages/reports.php`'s existing Need Change badge logic (lines ~652-667), which already correctly branches on `'deducted'`/`'approved'`/`'rejected'`/pending, previously always rendered "None" in the reports table because `report.need_change_item_id` was always `undefined`. It will now render the correct badge, since the underlying data is present. No frontend code was changed.
- No filtering, scoping, or role-based visibility logic in `index()` was touched — the `status_group`/`department_id`/`status`/`priority`/date-range filters and role-based `where` scoping are unchanged.
- Identified during the read-only `NEED_CHANGE_COMPATIBILITY_REVIEW.md` review (Finding 1, Medium severity) as a pre-existing, symmetric data-availability gap affecting all Need Change statuses equally, not specific to any one status value.

## 2026-07-21 — Phase 1: Need Change inventory deduction

- **Added** — `App\Services\NeedChangeService::approve()`, closing the Need Change inventory-deduction functional gap documented in `BUSINESS_RULES.md` Section 10 (previously: approving a Need Change request only flipped a status flag with no ledger effect).
- Approving a Need Change request now, inside a single `DB::transaction()`: row-locks the report, no-ops idempotently if `need_change_deducted_at` is already set (duplicate-approval prevention), validates the report has a Need Change item and the item has sufficient stock, creates exactly one `deploy`-type `InventoryTransaction` (letting `InventoryTransactionObserver` perform the actual `Item.quantity` mutation), sets `need_change_status = 'deducted'`, stamps `need_change_approved_by`/`need_change_approved_at`/`need_change_deducted_at`, and logs an `APPROVE_NEED_CHANGE` activity log entry via `ActivityLogService`.
- **Changed** — `ReportController::update()`'s `approve_need_change` branch now delegates to `NeedChangeService::approve()` instead of setting `need_change_status`/`need_change_approved_by`/`need_change_approved_at` directly on `$changes`, keeping the controller thin. The `reject_need_change` branch and all other CASE A/B/C logic in `update()` were not touched.
- No inventory transaction type was chosen from the legacy dead-code reference (`public/backend/services/ReportService.php`'s `deductNeedChangeInventory()`, which used `'adjustment'` via raw SQL); `'deploy'` was chosen instead as the architecturally correct type — the item physically leaves inventory to serve as the replacement part, the same semantic already used by `DispatchService::releaseDispatch()`, and it reuses the Observer's existing `deploy` availability validation without requiring any Observer change.
- `BUSINESS_RULES.md` Section 10 rewritten to describe this new behavior (replacing the "Functional gap" framing with a "Historical note"); the `[Unreleased]` entry tracking this gap has been removed and replaced with a note about the still-outstanding test coverage and a pre-existing, unrelated `need_change_status` enum inconsistency on the rejection path (left untouched, out of scope).
- No automated test was added in this change (see `[Unreleased]` for the recommended Feature test coverage); verified via `php artisan test` that the existing suite's baseline (6 pre-existing failures in `ExampleTest`/`PurchaseReceiptPostingTest`, unrelated to this change) is unchanged.

---

## Future changes

*(New entries should be added above this section, in reverse-chronological order, each with a real date and a concise description of what changed and why. Suggested categories per entry: Added / Changed / Fixed / Deprecated / Removed / Security.)*

- **Added** — placeholder for new features (e.g. new modules, new report types, new dashboard widgets).
- **Changed** — placeholder for behavioral changes to existing workflows (must also be reflected in `BUSINESS_RULES.md`).
- **Fixed** — placeholder for bug fixes (e.g. eventually closing the Need Change deduction gap, the Observer locking gap).
- **Deprecated** — placeholder for legacy (`public/backend/*`, `public/frontend/*`) surfaces scheduled for removal as the Laravel migration completes.
- **Removed** — placeholder for legacy code/files removed once fully migrated.
- **Security** — placeholder for security-relevant changes (e.g. removing hardcoded seeder credentials, closing stale CSRF exemptions).
