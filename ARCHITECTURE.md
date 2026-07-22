# ARCHITECTURE.md

## PhilCST Centralized School Facility Maintenance Reporting System

**Document status:** Living reference. This document is the permanent architectural source of truth for the project. Every future feature, refactor, or bug fix must be consistent with what is described here. If code and this document disagree, treat that as a defect to resolve (either fix the code or update this document deliberately — never let them silently diverge).

---

## 1. Project Overview

### 1.1 Purpose

The system is a centralized platform for managing school facility maintenance operations for PhilCST. It replaces manual, paper-based reporting of facility damage and repair needs with a digital workflow that spans:

- Reporting of facility damage and repair requests by staff.
- Review, triage, and approval by maintenance administrators and super administrators.
- Tracking of physical assets (buildings, floors, rooms) and inventory (spare parts, consumables, equipment).
- Procurement (purchase receipts) and internal distribution (dispatches) of inventory.
- Deployment and replacement tracking of items issued against reports.
- Auditing of all state-changing actions via an activity log.

### 1.2 Objectives

- Provide a single, authoritative digital system of record for maintenance reports, inventory, and facility structure (replacing scattered manual/legacy processes).
- Enforce accountable, role-gated workflows for approvals (damage reports, repair requests, purchase receipts, dispatches).
- Maintain an accurate, auditable inventory ledger so that stock levels are always traceable to a specific transaction and actor.
- Provide administrators and super administrators with dashboards and reports for operational visibility (open reports, low stock, aging repairs, etc.).
- Migrate incrementally off a legacy procedural PHP codebase onto a modern Laravel MVC/Service/Observer architecture without a disruptive "big bang" rewrite.

### 1.3 Architecture Style

The system is a **hybrid legacy + Laravel application** that is mid-migration. Concretely:

- **Laravel (modern, actively developed)** implements the API layer (`app/Http/Controllers/Api/*`), the Eloquent models (`app/Models/*`), the business-logic Service layer (`app/Services/*`), Observers that own inventory-mutating side effects (`app/Observers/*`), and the routing/middleware stack (`routes/`, `app/Http/Middleware/*`).
- **Legacy procedural PHP** (`public/backend/*`, `public/frontend/*`) implements older controllers, raw-PDO services, and PHP-include-based view templates. Some of this code (e.g. `public/backend/services/ReportService.php`) is **dead code**: it is not reachable from any active route and exists only as unmigrated history.
- The two stacks are bridged by:
  - A root **`index.php`** front controller that inspects the request path: for `/` it resolves the legacy `$_SESSION['user']['role']` and redirects to a role-specific legacy dashboard page; for any other path it forwards into Laravel's `public/index.php`.
  - A **`SyncLegacyPhpSession`** middleware (registered on the `web` group in `bootstrap/app.php`) that keeps Laravel's session store and PHP's native `$_SESSION` superglobal in sync, so legacy pages and Laravel routes can share one authenticated identity.

Within the Laravel portion, the style is **MVC + Service Layer + Observer Pattern**:

- **Controllers** are kept thin: they validate input, delegate to a Service or directly to Eloquent for simple CRUD, and translate results into JSON responses.
- **Services** (`app/Services/*`) own multi-step business transactions (e.g. posting a purchase receipt, running a dispatch through its lifecycle). They wrap critical multi-row writes in `DB::transaction()` with `lockForUpdate()` row locking.
- **Observers** (`app/Observers/*`) own the actual mutation of inventory quantities. No controller or service is permitted to write `Item.quantity` directly for a stock-affecting operation — instead they create an `InventoryTransaction` (or `InventoryStockEntry`) record, and the corresponding Observer's `creating`/`created`/`deleted` hooks perform the validated math. This centralizes the single most safety-critical piece of logic (stock arithmetic) into two files.

This combination is best described as **Hybrid Legacy+Laravel, with the Laravel side following MVC + Service Layer + Observer Pattern (ledger-based inventory).**

---

## 2. Folder Structure

```
School_Facility_Maintenance_System/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/     Laravel API controllers (Auth, Dashboard, Item, Report, User, ...)
│   │   └── Middleware/          EnsureRole, SyncLegacyPhpSession, etc.
│   ├── Models/                  Eloquent models (Item, MaintenanceReport, ActivityLog, Dispatch, ...)
│   ├── Observers/               InventoryTransactionObserver, InventoryStockEntryObserver
│   ├── Services/                DispatchService, PurchaseReceiptPostingService, ActivityLogService, ...
│   └── Providers/               AppServiceProvider (observer + service registration)
├── bootstrap/
│   └── app.php                  Application bootstrap: routing, middleware pipeline, exception rendering
├── config/                      Framework and application configuration (database, session, cache, ...)
├── database/
│   ├── migrations/               Schema history (source of truth for table structure)
│   └── seeders/                  DatabaseSeeder (creates initial super_admin / maintenance_admin users)
├── public/
│   ├── index.php                 Laravel front controller
│   ├── backend/                  Legacy procedural PHP: config, controllers, middleware, services
│   │   ├── config/               Legacy DB settings, session settings
│   │   ├── controllers/          Legacy controllers (dead code, unrouted)
│   │   ├── middleware/            Legacy SessionMiddleware
│   │   └── services/              Legacy raw-PDO services (e.g. ReportService.php)
│   └── frontend/                 Legacy PHP-include view templates ("pages"), plus static assets (JS/CSS)
├── resources/                    Blade views for the Laravel-native pages
├── routes/
│   ├── web.php                   Laravel web routes (session-authenticated pages)
│   └── console.php               Artisan console routes/commands
├── storage/                      Framework-managed logs, cache, compiled views, uploaded files
├── tests/                        PHPUnit tests (Feature/Unit), currently sparse — see Testing note below
├── index.php                     Root legacy/Laravel dispatch front controller
└── ARCHITECTURE.md / BUSINESS_RULES.md / CHANGELOG.md   This documentation set
```

**Notes on each top-level folder:**

- **`app/`** — All Laravel application code. This is where new business logic should be added. Follow the layering described in Section 6.
- **`bootstrap/`** — Framework bootstrap. `app.php` is the single place where routing files, the middleware pipeline, and global exception-to-JSON translation are configured. CSRF is disabled for `api/*` and `backend/api/*` route prefixes (the latter is now stale, since the legacy `public/backend/api/*.php` endpoint files have been removed from the working tree — see Technical Debt notes carried over from the architecture audit).
- **`config/`** — Standard Laravel configuration. `database.php` also carries legacy-compatible connection settings consumed by `public/backend/config/database.php`.
- **`database/migrations/`** — The authoritative schema history. Any new table or column must be introduced via a migration, never via a manual `ALTER TABLE` or by editing an existing migration after it has been applied anywhere.
- **`public/backend/`** — Legacy procedural PHP surface. Some of it (services, controllers) is confirmed dead code not reachable from any live route; some legacy config/middleware is still loaded via `SyncLegacyPhpSession`/`index.php` and therefore still live. Treat any file under here as **legacy** and avoid extending it — new work belongs in `app/`.
- **`public/frontend/`** — Legacy PHP-include page templates and static assets. Newer pages live as Blade views under `resources/` instead.
- **`resources/`** — Blade templates for the Laravel-native UI, using `@extends`/`@yield`/`@section` layout inheritance, plus a shared theme system (CSS custom properties + `data-theme-resolved` attribute + `localStorage` persistence) and a small reusable vanilla-JS component library (`components.js`: `SearchableSelect`, toast, confirm dialog, alert, `fetchJson`).
- **`routes/`** — `web.php` defines the session-authenticated route surface (both Blade pages and, in the current structure, API endpoints under `/api` handled by `Api\*` controllers). `console.php` defines Artisan commands.
- **`storage/`** — Framework-managed writable storage: logs (`storage/logs/laravel.log`), compiled Blade views, cache files, and any user-uploaded files (e.g. attachment uploads for reports).
- **`tests/`** — PHPUnit test suite. Uses in-memory SQLite with `RefreshDatabase`. Currently only a `UserFactory` exists; most correctness verification instead happens through ad-hoc `scripts/smoke_*.php` scripts, which is a testing gap (see Coding Standards and the architecture audit's Technical Debt findings).

---

## 3. System Modules

| Module | Description | Key Models | Key Controllers/Services |
|---|---|---|---|
| **Authentication** | Session-based login/logout, password hashing, rate-limited login attempts, legacy/Laravel session sync | `User` | `AuthController` |
| **Users** | User CRUD, role assignment, status (active/inactive) | `User` | `UserController` |
| **Buildings** | Physical building records | `Building` | (CRUD via relevant controller) |
| **Floors** | Floors belonging to a building | `Floor` | (CRUD via relevant controller) |
| **Rooms** | Rooms belonging to a floor, associated with reports/inventory | `Room` | (CRUD via relevant controller) |
| **Inventory** | Items, categories, stock levels, reservations | `Item`, category model, `InventoryTransaction`, `InventoryStockEntry` | `ItemController`, `InventoryTransactionObserver`, `InventoryStockEntryObserver` |
| **Purchase Receipts** | Procurement intake: draft receipts with line items, posted to increase stock | `purchase_receipts`/`purchase_receipt_items` (DB-table-based), `Item` | `PurchaseReceiptPostingService` |
| **Dispatch** | Internal distribution of inventory to a department/room/request | `Dispatch`, `DispatchItem` | `DispatchService` |
| **Deployment Tracking** | Recording of items issued/deployed against reports or dispatches, and their return/replacement | `InventoryTransaction` (transaction_type=deploy/return) | `DispatchService`, Observers |
| **Damage Reports** | Reporting and lifecycle tracking of facility damage | `MaintenanceReport` (or dedicated damage report model) | `ReportController` |
| **Repair Requests** | Reporting and lifecycle tracking of repair work | Repair request model | `ReportController` (or dedicated controller) |
| **Notifications** | In-app notifications for status changes/assignments | Notification model | Relevant controllers |
| **Reports (analytics)** | Dashboards and aggregate reporting (open reports, low stock, etc.) | Aggregation queries across models | `DashboardController`, `ReportController` |
| **Activity Logs** | Append-only audit trail of user actions | `ActivityLog` | `ActivityLogService` |

---

## 4. Database Overview

The schema is defined exclusively through `database/migrations/*`. Key tables and relationships:

- **`users`** — `id`, `full_name`, `email` (unique), `password`, `role` (enum-like string: `super_admin`, `maintenance_admin`, staff roles), `status` (`active`/`inactive`). Referenced by nearly every other table as `performed_by`/`created_by`/`approved_by`/`released_by`/`receiver_user_id`, typically with `nullOnDelete()` or `setNull()` so historical records survive user deletion.
- **`buildings`** → **`floors`** → **`rooms`** — a strict three-level hierarchy (`floors.building_id`, `rooms.floor_id`), each FK generally `cascade` on delete of the parent (deleting a building cascades to its floors and rooms) since a room cannot exist without its physical building/floor.
- **`items`** — core inventory row: `name`, `item_type` (e.g. `inventory_stock`), `inventory_room_id` (nullable FK to `rooms`, storage location), `room_id` (nullable, for room-fixture-type items), `category_id`, `quantity`, `reserved_quantity`, `reorder_level`, `unit_type`, `status` (`available`/`low_stock`/`out_of_stock`, derived — never set arbitrarily).
- **`inventory_transactions`** — the append-only ledger. Columns: `item_id` (FK, `cascade` or `restrict` depending on migration), `report_id` (nullable FK), `room_id` (nullable FK), `transaction_type` (enum: `reserve`, `release`, `deploy`, `return`, `adjustment`, `dispose`), `quantity` (signed meaning depends on type), `reference_note`, `performed_by` (nullable FK to `users`). This table is the single source of truth for all stock movement history; `items.quantity`/`items.reserved_quantity` are a materialized cache of this ledger, maintained exclusively by `InventoryTransactionObserver`.
- **`inventory_stock_entries`** — a secondary, simpler stock-adjustment record type (observed via `InventoryStockEntryObserver`), used for direct quantity adjustments outside the transaction-type ledger.
- **`purchase_receipts`** / **`purchase_receipt_items`** — procurement records. `purchase_receipts.status` enum (`draft`/`posted`/effectively also supports cancellation per business rules); `purchase_receipt_items` line items reference an `item_id` that may be resolved/backfilled at posting time (see Inventory Architecture below).
- **`dispatches`** / **`dispatch_items`** — internal distribution records. `dispatches.status` enum (`pending`/`approved`/`released`/`cancelled`), FKs to `department_id`, `room_id`, and optionally `repair_request_id`/`damage_report_id`/`purchase_receipt_id` (linking a dispatch back to the request that justified it). `dispatch_items.item_id`/`quantity` per line.
- **`maintenance_reports`** — damage/repair report records, including `need_change_status`, `need_change_approved_by`, `need_change_approved_at`, and (intended but currently unused — see Business Rules) `need_change_deducted_at`.
- **`activity_logs`** — append-only audit table: `user_id` (nullable FK, `setNull` on delete), `action`, `module`, `entity_type`, `entity_id`, `details`, `meta` (JSON), `ip_address`, timestamps.

**Relationship summary:**

```
buildings 1─* floors 1─* rooms 1─* items (inventory_room_id)
categories 1─* items
items 1─* inventory_transactions
items 1─* inventory_stock_entries
purchase_receipts 1─* purchase_receipt_items ─(resolves to)→ items
dispatches 1─* dispatch_items ─→ items
dispatches *─1 rooms, departments, repair_requests/damage_reports (optional origin link)
maintenance_reports *─1 rooms, users (reporter), users (approver)
users 1─* activity_logs
users 1─* inventory_transactions (performed_by)
```

Foreign keys consistently use `nullOnDelete()`/`setNull()` for "who did this" references (preserving audit history after a user is removed) and `cascade`/`restrict` for structural containment references (building→floor→room, receipt→line item, dispatch→dispatch item).

---

## 5. Inventory Architecture

This is the most safety-critical subsystem in the application and the one place where architectural discipline matters most, because it directly controls a real, finite physical resource (spare parts / consumable stock).

### 5.1 The Flow

```
Purchase Receipt (draft)
        │  PurchaseReceiptPostingService::postReceipt()
        │  - locks purchase_receipts row (lockForUpdate)
        │  - locks/resolves each line's Item row
        ▼
InventoryTransaction (transaction_type = 'adjustment', +quantity)
        │  Eloquent create() fires model events
        ▼
InventoryTransactionObserver
        │  creating(): re-locks Item row, validates the operation
        │  created(): re-locks Item row, applies signed quantity math,
        │              derives status (available/low_stock/out_of_stock),
        │              saves Item, logs via ActivityLogService,
        │              flushes Cache::tags(['analytics'])
        ▼
Item.quantity / Item.reserved_quantity (materialized, always derived from the ledger)
        │
        ▼
Dispatch (pending → approved → released)
        │  DispatchService::releaseDispatch()
        │  - pre-validates available = quantity - reserved_quantity per line
        │  - inside DB::transaction(): creates one InventoryTransaction
        │    (transaction_type = 'deploy') per dispatch line
        ▼
InventoryTransactionObserver again decrements Item.quantity
        │
        ▼
Deployment (item is now out in the field, tied to the dispatch/report that justified it)
        │  return/replacement, if it occurs, is itself another
        │  InventoryTransaction (transaction_type = 'return')
        ▼
Deployment/Replacement History (queryable via inventory_transactions filtered by item_id)
```

### 5.2 Why this architecture exists

1. **Single writer principle.** Only the Observer ever assigns a new value to `Item.quantity`/`reserved_quantity`. Every other piece of code (controllers, services, legacy scripts) is only allowed to *express intent* by creating an `InventoryTransaction` row. This means there is exactly one place to audit, test, and reason about when answering "how could stock possibly become wrong?"
2. **Ledger = audit trail by construction.** Because `inventory_transactions` is append-only and typed, the full history of *why* an item's quantity is what it is can always be reconstructed by summing transactions — there is no possibility of a stock change with no attributable cause, actor, or reference note.
3. **Row locking prevents concurrent corruption.** Both the creating and created hooks re-acquire `lockForUpdate()` on the `Item` row inside the enclosing `DB::transaction()`, so two simultaneous dispatch releases (or a release racing a purchase posting) cannot read-modify-write the same row inconsistently.
4. **Validation happens before mutation, on the same locked read.** `creating()` checks type-specific constraints (e.g. `reserve`: requested qty ≤ available; `deploy`/`dispose`: qty ≤ on-hand quantity; `release`: qty ≤ currently reserved) against the just-locked row, so the constraint check and the eventual mutation cannot be invalidated by an interleaved transaction.
5. **Status is always derived, never set.** `Item.status` (`available`/`low_stock`/`out_of_stock`) is recomputed from `quantity` and `reorder_level` every time quantity changes, so status can never silently drift out of sync with the actual quantity.
6. **Separation of "expressing intent" from "doing the math."** `PurchaseReceiptPostingService` and `DispatchService` read as business narratives (post a receipt, release a dispatch) without needing to know or duplicate the arithmetic of stock mutation. This keeps the arithmetic in exactly one tested location and keeps the services focused on orchestration, locking of *their own* aggregate roots (the receipt, the dispatch), and status-transition rules.

### 5.3 Known deviation to be aware of

`InventoryTransactionObserver::deleted()` reverses the quantity math on transaction deletion but does **not** re-acquire `lockForUpdate()` (it uses a plain `Item::find()`), unlike `creating()`/`created()`. This is a real, narrow race-condition exposure isolated to the (rare) transaction-deletion path. It is a known deviation from the "always lock before mutating" rule described above, not a rule to imitate in new code — new Observer or Service code must always lock before mutating.

---

## 6. Application Layers

| Layer | Location | Responsibility |
|---|---|---|
| **Controllers** | `app/Http/Controllers/Api/*` | Thin. Validate request input, authorize the action (role check), call into a Service or a simple Eloquent operation, translate the result into a JSON response. Must not contain multi-step business logic or direct stock mutation. |
| **Services** | `app/Services/*` | Own multi-step business transactions and state-machine transition rules (e.g. `DispatchService`, `PurchaseReceiptPostingService`, `ActivityLogService`). Wrap critical writes in `DB::transaction()` with `lockForUpdate()`. Express stock-affecting intent by creating `InventoryTransaction`/`InventoryStockEntry` rows — never by writing `Item.quantity` directly. |
| **Models** | `app/Models/*` | Eloquent models: relationships, casts, scopes, accessors. Should stay free of orchestration logic (that belongs in Services) and free of cross-cutting side effects (that belongs in Observers). |
| **Observers** | `app/Observers/*` | Own all inventory-quantity mutation. `InventoryTransactionObserver` and `InventoryStockEntryObserver` are the only code permitted to assign `Item.quantity`/`reserved_quantity`. Registered centrally in `AppServiceProvider::boot()`. |
| **Middleware** | `app/Http/Middleware/*` | Cross-cutting request concerns: `EnsureRole` (RBAC gate), `SyncLegacyPhpSession` (keeps native `$_SESSION` and Laravel's session store consistent so legacy and Laravel pages share one identity). |
| **Views (Blade)** | `resources/` | Laravel-native page rendering using `@extends`/`@yield`/`@section` layout inheritance. New UI work should be built here, not in `public/frontend/`. |
| **JavaScript** | `resources/`/`public/frontend/` static assets | A shared vanilla-JS component library (`components.js`: `SearchableSelect`, toast, confirm dialog, alert, `fetchJson` wrapper) used across both legacy and Laravel-native pages for UI consistency without a frontend framework dependency. |
| **CSS** | shared theme stylesheet(s) | Theme system based on CSS custom properties, resolved into a `data-theme-resolved` HTML attribute and persisted via `localStorage`, so both legacy and Laravel-native pages render with a consistent light/dark theme. |

---

## 7. Security Architecture

- **Authentication.** Session-based (`AuthController` + Laravel's session guard). Passwords hashed with `Hash::make` (bcrypt) and verified via Laravel's `Hash`/`password_verify`. Login attempts are throttled via Laravel's `RateLimiter` to mitigate brute-force attempts.
- **Legacy/Laravel session bridging.** `SyncLegacyPhpSession` middleware keeps the native PHP `$_SESSION['user']` array and Laravel's authenticated session in agreement, so a single login is honored by both the legacy PHP pages and the Laravel routes/controllers.
- **Authorization / RBAC.** Enforced primarily via the `EnsureRole` middleware and ad-hoc role-string comparisons scattered through controllers (e.g. checks for `super_admin` before allowing `approve_need_change`). There is **no** Laravel Policy/Gate layer and **no** third-party permission package (e.g. spatie/laravel-permission) in use. Role strings have accumulated aliases over time (e.g. `admin_maintenance` vs `maintenance_admin`). As of the Phase 3 role-normalization consolidation (see `CHANGELOG.md`), alias resolution for all Laravel-side (`app/`) call sites is centralized in `App\Services\RoleNormalizerService` (`normalize()`, `normalizeWithStaffDefault()`, `rawValuesFor()`) — this is now the single canonical location and new code must call it rather than re-implementing alias matching locally. The five legacy `public/backend/*` duplicates and the one-time `2026_06_15_000000_normalize_user_roles` data migration remain outside this service by design (legacy-surface retirement is a separate, later roadmap phase; the migration is historical and must not be edited), and `ReportController::store()`'s `$recipientRoles` notification allowlist is a distinct raw-value list, not a normalization function.
- **Input validation.** Performed inline in controllers using Laravel's `Validator`/`$request->validate()` calls rather than dedicated `FormRequest` classes. There are currently no `FormRequest` classes in the codebase.
- **CSRF protection.** Enabled globally for the `web` middleware group via Laravel's default CSRF verification, with explicit exemptions configured in `bootstrap/app.php` for the `api/*` prefix (session-authenticated JSON API, exempted by design since it's consumed by same-origin fetch calls carrying the session cookie) and `backend/api/*` (a now-stale exemption, since the legacy PHP endpoint files it referred to have been removed from the working tree).
- **Session security.** Standard Laravel session configuration (`config/session.php`) applies (cookie flags, lifetime, driver). The legacy session bridge means session security posture should be evaluated for both the Laravel session cookie and the native PHP session mechanism together, not independently.
- **Credential hygiene finding.** `database/seeders/DatabaseSeeder.php` currently hardcodes plaintext administrator passwords (for `super_admin` and `maintenance_admin` seed accounts) directly in source. This is a known finding from the architecture audit and is called out here so it is not reintroduced elsewhere; see Coding Standards below.
- **Error handling.** `bootstrap/app.php` centralizes exception-to-JSON translation for API/JSON requests: `ValidationException` → 422 with field errors, `ModelNotFoundException` → 404, anything else → 500 with the raw message only when `config('app.debug')` is true (otherwise a generic "Server error." message), which avoids leaking stack traces/internals in production.

---

## 8. Coding Standards

These are the standing conventions this codebase already follows in its Laravel portion. New code must follow them.

1. **Thin controllers.** Controllers validate, authorize, and delegate. They must not contain multi-step business logic, direct stock mutation, or hand-rolled SQL beyond simple, obvious reads.
2. **Business logic lives in Services.** Any operation spanning more than one write, requiring a status-transition check, or requiring row-locking belongs in `app/Services/*`, not in a controller or a model.
3. **Observers own stock mutation exclusively.** No controller, service, or script may set `Item.quantity` or `Item.reserved_quantity` directly. Stock changes are always expressed by creating an `InventoryTransaction` (or `InventoryStockEntry`) record and letting the corresponding Observer perform and validate the mutation.
4. **Wrap critical multi-row writes in `DB::transaction()` with `lockForUpdate()`.** Any operation that reads a row's current state and then conditionally writes based on it (stock checks, status-transition checks) must lock the row(s) involved for the duration of the transaction.
5. **Never bypass the ledger.** Even "simple" adjustments (e.g. a purchase receipt posting) must go through `InventoryTransaction`, not a raw `UPDATE items SET quantity = ...` statement — raw SQL stock mutation defeats the audit trail and the locking/validation guarantees described in Section 5.
6. **No duplicated business logic.** Role-normalization, stock-derivation (`available`/`low_stock`/`out_of_stock`), and transition-validity rules should be implemented once and reused, not re-implemented per controller. (Role-alias normalization was consolidated into `App\Services\RoleNormalizerService` as part of the Phase 3 cleanup — see `CHANGELOG.md`. All Laravel-side call sites use it; do not add a new local normalization block.)
7. **No direct SQL unless truly necessary**, and never for stock-affecting writes. Where raw `DB::table()`/`DB::statement()` is used (e.g. locking a non-Eloquent-backed table like `purchase_receipts`), it should be limited to reads, locks, and status-flag updates that don't touch inventory quantities.
8. **Never hardcode credentials.** Seed/admin credentials must not be committed as plaintext in source (see the Security Architecture finding above); this pattern must not be replicated for any new seeder or fixture.
9. **Prefer Eloquent model events (Observers) over ad-hoc side-effect code** for anything that must happen consistently every time a given model transitions state, so the behavior can't be accidentally skipped by a new call site.
10. **Log state-changing actions via `ActivityLogService`**, not by inserting into `activity_logs` directly with `DB::table()` — keep the logging interface single and consistent (note: `PurchaseReceiptPostingService` currently does insert directly via `DB::table('activity_logs')->insert()`, which is an existing inconsistency, not the pattern to copy going forward).

---

## 9. Future Architecture Rules

These are binding rules for all future work on this system:

1. **Never bypass `InventoryTransactionObserver` (or `InventoryStockEntryObserver`).** All stock-affecting logic must flow through an `InventoryTransaction`/`InventoryStockEntry` creation, never through a direct `Item` quantity write.
2. **Never write inventory quantities directly.** `Item::quantity` and `Item::reserved_quantity` are derived, materialized values. Treating them as directly writable fields anywhere in new code is a bug.
3. **Always lock before validating-then-mutating.** Any new code path that checks a row's current state before conditionally writing to it must use `lockForUpdate()` inside a `DB::transaction()`, following the existing Observer/Service pattern — including fixing the known `deleted()` locking gap if that code path is ever touched.
4. **Always create Feature Tests for new business-logic paths.** Given the current sparse test coverage (only a `UserFactory` exists; most verification is done via manual `scripts/smoke_*.php` scripts), any new Service, Observer, or Controller business logic added going forward must ship with a PHPUnit Feature test using `RefreshDatabase`, not merely a manual smoke script.
5. **Always preserve the audit trail.** Every state-changing action (approvals, stock movement, report status changes) must be attributable: log it via `ActivityLogService` and/or an `InventoryTransaction`, with a real actor id and a human-readable reference note.
6. **Do not extend the legacy `public/backend/*` surface.** New functionality belongs in `app/` (Laravel). Legacy procedural PHP is being phased out, not grown.
7. **Do not add new role-string aliases without updating `App\Services\RoleNormalizerService`.** All Laravel-side role-alias resolution must go through that service; do not add another local normalization block.
8. **Migrations are the only way to change schema.** Never hand-edit the database schema outside of a new migration file.
9. **Treat `BUSINESS_RULES.md` as authoritative for workflow/state-machine behavior**, and `ARCHITECTURE.md` (this document) as authoritative for structural/layering decisions. Update both deliberately when behavior changes — do not let code drift from what is documented here without an explicit accompanying documentation update.
10. **Any deviation discovered from these rules (like the `deleted()` locking gap, or the currently non-functional Need Change deduction — see `BUSINESS_RULES.md`) must be documented as a known gap, not silently worked around.**
