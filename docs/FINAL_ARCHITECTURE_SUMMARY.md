# School Facility Maintenance System — Final Architecture Summary

Date: 2026-05-15  
Phase: Verification, Stabilization, and Documentation (No major new features)

## 1) Backend Architecture Overview

The backend uses a hybrid architecture:

- **Laravel API layer** for core modern endpoints (`/api/*`) such as items, suppliers, dispatches, stock summaries/movements, and auth/session checks.
- **Legacy PHP API layer** under `public/backend/api/*.php` for backward compatibility with existing frontend pages.
- **Bridge routing** (`LegacyBridgeController`) keeps legacy routes available while newer Laravel controllers are introduced.
- **Service/model split** in legacy flow (router → controller/service → model) for facility/inventory domains.

Key backend patterns:

- Server-side pagination/search via `q`, `page`, `per_page` for large-select endpoints.
- Parameterized SQL and bounded `per_page` caps.
- Centralized stock mutation via `inventory_transactions` + observer rules.
- Activity logging for auditable changes.

## 2) Frontend Reusable Component Architecture

A lightweight global component system is centralized at:

- `public/frontend/assets/js/components.js`

Provided utilities:

- `Components.SearchableSelect`
- `Components.toast(message, type, duration)`
- `Components.confirm(message, yesLabel, noLabel)`
- `Components.alert(message, type)`
- `Components.setLoading(button, loading)`
- `Components.setFieldError(fieldId, message)` / `Components.clearFieldError(fieldId)`

Stability hardening added:

- Single global outside-click listener (`Components._clickBound` guard)
- Instance registry pruning for detached DOM nodes (`pruneDisconnectedInstances`)
- `destroy()` support to remove stale instance references

## 3) API Optimization Structure

Optimized select/list endpoints (server-side filtering + pagination):

- `public/backend/api/departments.php?action=list` → `data.departments` + `data.pagination`
- `public/backend/api/inventory-rooms-api.php?action=list` → `data.rooms` + `data.pagination`
- `public/backend/api/users-api.php?action=list` → `data.users` + `data.pagination`
- `public/backend/api/items.php?action=list` → via `FacilityService::listItems()` returns `data.items` + `data.pagination`

Compatibility in reusable selects is handled by a response normalizer in `components.js` supporting:

- `data.departments`
- `data.rooms`
- `data.users`
- `data.items`
- `data.data` (paginator shape)
- direct array fallback

## 4) Inventory Workflow

Primary inventory flow:

1. Create/update inventory stock item (legacy/Laravel API depending page)
2. Record stock-related transactions in `inventory_transactions`
3. Observer updates item `quantity` and `reserved_quantity`
4. UI reflects low-stock/out-of-stock states via API + list pages

Verified by smoke checks:

- reserve → release → deploy → return → adjustment → dispose
- observer rollback behavior on transaction delete
- insufficient-stock deploy blocked
- no negative stock after operations

## 5) Dispatch Workflow

Dispatch flow:

1. Create dispatch with one or more line items
2. Approve dispatch
3. Release dispatch (optional receiver selection)
4. On release, deploy transactions reduce stock and clear reserved stock

Verified by smoke checks:

- create dispatch
- approve dispatch
- release dispatch
- resulting quantity decrease and reserved reset validated

## 6) Stock Transaction Lifecycle

`InventoryTransactionObserver` enforces lifecycle invariants:

- **creating** validates stock constraints per transaction type
- **created** mutates stock/reserved counters safely
- **deleted** applies conservative rollback logic

Supported transaction types:

- `reserve`, `release`, `deploy`, `return`, `adjustment`, `dispose`

Protection outcomes validated:

- Prevents negative stock via pre-checks
- Prevents release beyond reserved
- Maintains reserved/quantity consistency after deploy/release

## 7) Reusable Components System Status

Standardized pages in current scope are now using reusable dialog/toast patterns instead of native blocking dialogs:

- `public/frontend/pages/inventory.php`
- `public/frontend/pages/inventory-manage.php`
- `public/frontend/pages/suppliers-manage.php`
- `public/frontend/pages/edit-report.php`
- `public/frontend/pages/reports.php`

Dispatch pages already use shared modal/toast infrastructure and reusable searchable selects:

- `public/frontend/pages/dispatch-create.php`
- `public/frontend/pages/dispatch-detail.php`

## 8) Scalability Strategy

- Prefer server-side search/pagination for all large option sets and list views.
- Keep reusable client widgets framework-agnostic to avoid heavy runtime overhead.
- Maintain API response consistency to reduce frontend branching and per-page adapters.
- Keep stock write operations centralized in transactions/observers to avoid drift.

## 9) Performance Optimization Strategy

- Avoid full-dataset dropdown loads in pages with large records.
- Debounce query-based search inputs (`SearchableSelect`).
- Use per-page caps to protect APIs.
- Keep lightweight DOM updates and avoid duplicate global listeners.
- Route and API compatibility maintained to avoid expensive broad rewrites.

## 10) Database Relationship Overview (Relevant Domain)

Core entities and relationships:

- `items` ↔ `inventory_transactions` (1:M)
- `dispatches` ↔ `dispatch_items` (1:M)
- `users` referenced by `dispatches.approved_by/released_by`, `inventory_transactions.performed_by`
- `suppliers` ↔ `supplier_histories` (1:M; cascades on supplier delete)
- `inventory_stock_entries.supplier_id` nullable FK to `suppliers`

## 11) Verification Results

Executed checks and results:

- `php scripts/smoke_dispatch.php` ✅
  - Dispatch create/approve/release flow successful
  - Stock deduction and reserved reset validated

- `php scripts/smoke_inventory_supplier_components.php` ✅
  - Inventory transaction lifecycle + rollback checks successful
  - Supplier create/update/history/delete integration successful
  - Components guard checks successful

- `php artisan route:list` ✅
  - Routes registered successfully; no registration-time conflicts/errors

- `php -l public/backend/api/users-api.php` ✅
- `php -l app/Http/Controllers/Api/SupplierController.php` ✅
- Workspace error scan (`get_errors`) ✅ no errors

- `php artisan test --filter=Inventory --stop-on-failure` ℹ️ no matching tests found in current suite

## 12) Files Created

- `scripts/smoke_inventory_supplier_components.php`
- `docs/FINAL_ARCHITECTURE_SUMMARY.md`

## 13) Files Modified in Stabilization Pass

- `public/frontend/assets/js/components.js`
- `public/frontend/pages/inventory.php`
- `public/frontend/pages/inventory-manage.php`
- `public/frontend/pages/suppliers-manage.php`
- `public/frontend/pages/edit-report.php`
- `public/frontend/pages/reports.php`
- `public/backend/api/users-api.php`
- `app/Http/Controllers/Api/SupplierController.php`

(Additional earlier phase files remain part of the delivered implementation baseline.)

## 14) Recommended Future Module Integrations

1. Extend `Components.*` adoption to remaining legacy pages still using native dialogs.
2. Add dedicated automated Laravel feature tests for:
   - dispatch approval/release stock invariants
   - supplier lifecycle/history
   - searchable list endpoint pagination contracts
3. Add API contract tests for select endpoints to enforce stable JSON shapes.
4. Add lightweight frontend smoke harness for critical pages (inventory/dispatch/reports).
5. Add observability counters for transaction anomalies and slow list endpoints.

## 15) Completion Status

Current implementation scope is **stable, verified, and production-ready** for:

- inventory stock transaction lifecycle
- dispatch approve/release flow
- supplier CRUD/history integration
- reusable component baseline in targeted standardized pages

No major feature expansion was performed in this phase.
