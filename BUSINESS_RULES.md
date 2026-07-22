# BUSINESS_RULES.md

## PhilCST Centralized School Facility Maintenance Reporting System — Business Rules

**Document status:** Living reference. This document describes the CURRENT, as-implemented behavior of every business workflow in the system, verified directly against source code. Where the current implementation has a known functional gap, that gap is called out explicitly and left undocumented-as-a-fix — it is a description of present behavior, not a design proposal. Any future change to behavior must be reflected here at the same time the code changes.

---

## 1. Authentication

- A user authenticates with an email/password pair. Passwords are hashed with Laravel's `Hash` facade (bcrypt) and verified on login; plaintext passwords are never stored.
- Login attempts are rate-limited (Laravel `RateLimiter`) to reduce brute-force risk.
- A successful login establishes both a Laravel session and a synchronized native PHP `$_SESSION['user']` entry (via `SyncLegacyPhpSession`), so both the Laravel routes and the legacy PHP pages recognize the same authenticated identity.
- A user record has a `status` of `active` or `inactive`. Inactive users must not be able to authenticate (status is a login precondition, not merely a display flag).

### Roles

- `super_admin` — highest privilege. Can approve/reject Need Change requests, manage users, and perform all administrative actions available to `maintenance_admin`.
- `maintenance_admin` — manages maintenance operations: reviewing/triaging damage reports and repair requests, approving dispatches, posting purchase receipts.
- Additional staff-facing roles exist for reporting facility issues (submitting damage reports / repair requests) without administrative privileges.
- Role values have accumulated aliases over time (e.g. `admin_maintenance` as an alias of `maintenance_admin`). Role-checking code normalizes these aliases before comparison. Any code that checks a role string must account for known aliases rather than assuming a single canonical spelling exists everywhere in the database.

### Permissions (by role)

| Action | staff | maintenance_admin | super_admin |
|---|---|---|---|
| Submit damage report / repair request | ✅ | ✅ | ✅ |
| View own reports | ✅ | ✅ | ✅ |
| Triage / assign / update report status | ❌ | ✅ | ✅ |
| Approve/reject Need Change | ❌ | ❌ | ✅ |
| Manage users | ❌ | ❌ | ✅ |
| Post purchase receipts | ❌ | ✅ | ✅ |
| Approve/release dispatches | ❌ | ✅ | ✅ |
| Manually adjust stock (increase/decrease) | ❌ | ✅ | ✅ |
| View inventory/dashboards | ❌ (limited) | ✅ | ✅ |

---

## 2. Buildings / Floors / Rooms

- Facility structure is a strict three-level hierarchy: **Building → Floor → Room**. A floor must belong to exactly one building; a room must belong to exactly one floor.
- Deleting a building cascades to delete its floors, which cascades to delete its rooms (structural containment — a floor/room cannot outlive its parent).
- Rooms are the unit referenced by inventory storage locations (`items.inventory_room_id`), maintenance reports, and dispatches (`dispatches.room_id`) — i.e., "where" for both physical stock and reported issues.
- A room may simultaneously be: a location that holds inventory items, a location where a maintenance report originates, and a destination for a dispatch. These are independent relationships, not mutually exclusive.

---

## 3. Inventory

### Categories

- Items are optionally grouped under a category (`items.category_id`). Category is informational/organizational; it does not itself gate any workflow rule.

### Items

- Each item has a `item_type` (e.g. `inventory_stock` for consumable/spare-part stock items), a `quantity` (current on-hand count), a `reserved_quantity` (amount earmarked but not yet deployed), a `reorder_level` (threshold for low-stock alerting), and a `unit_type` (e.g. `pc`).
- `items.status` is **always derived**, never set directly: `out_of_stock` when `quantity <= 0`; `low_stock` when `0 < quantity <= reorder_level`; otherwise `available`. Status is recomputed every time quantity changes.
- **An item's `quantity` and `reserved_quantity` may only be changed by creating an `InventoryTransaction` (or `InventoryStockEntry`) record.** No controller, service, script, or manual database edit is permitted to update these fields directly. This is enforced architecturally by `InventoryTransactionObserver`/`InventoryStockEntryObserver` (see `ARCHITECTURE.md` Section 5) and must be treated as an inviolable rule for all new code.

### Suppliers

- Purchase receipts represent intake from a supplier/vendor context; supplier identity is recorded on the receipt (e.g. an OR/reference number tied to the procurement), used to build the audit trail (`reference_note` on the resulting `InventoryTransaction` references the receipt's OR number).

### Stock (the ledger)

- All stock movement is recorded as an `InventoryTransaction` row with a `transaction_type`:
  - `adjustment` — a manual or receipt-driven increase (or correction) to on-hand quantity.
  - `reserve` — earmarks quantity for a pending dispatch/request without removing it from on-hand quantity; increases `reserved_quantity`.
  - `release` — undoes a reservation (decreases `reserved_quantity`) without changing on-hand quantity, typically when a reservation is cancelled.
  - `deploy` — reduces on-hand quantity when an item physically leaves inventory (e.g. a dispatch is released to its recipient).
  - `return` — increases on-hand quantity when a previously deployed item is returned.
  - `dispose` — reduces on-hand quantity for items removed from service (damaged beyond repair, expired, etc.).
- Each transaction carries `performed_by` (the acting user, nullable to preserve history if the user is later deleted) and a `reference_note` describing the business reason (e.g. `"Purchase receipt OR#12345"`, `"Dispatch DSP-XXXX"`).
- **Validation before mutation:** the system validates each transaction type against the current, row-locked state of the item before applying it — `reserve` requires `quantity <= available` (i.e. `quantity - reserved_quantity`); `deploy`/`dispose` require `quantity <= item.quantity`; `release` requires `quantity <= item.reserved_quantity`. A transaction that would violate these constraints is rejected rather than applied.

### Reservations

- Reserving stock (`transaction_type = 'reserve'`) does not remove it from `quantity`; it increases `reserved_quantity`, which reduces the *available* quantity (`quantity - reserved_quantity`) visible to new reservation/deployment requests.
- A reservation must eventually be either **released** (returned to available pool, `reserved_quantity` decreases) or **deployed** (converted into an actual outbound movement, `quantity` decreases and — depending on implementation path — `reserved_quantity` is also cleared for that portion).

### Stock Adjustment (manual)

- `POST /api/items/{item}/adjust-stock` lets a `maintenance_admin` or `super_admin` manually increase or decrease an item's on-hand `quantity` outside of a purchase receipt, dispatch, or Need Change flow — e.g. correcting a physical recount, or writing off damage/loss discovered outside the normal Dispatch/Need Change paths. This is the only route through which quantity can be changed for a reason not already tied to one of those workflows.
- Request body: `direction` (`increase` or `decrease`, required), `quantity` (integer, required, must be `>= 1`), `reason` (string, required, max 500 chars — a free-text explanation, stored on the resulting transaction and the activity log).
- Implemented by `InventoryAdjustmentService::adjust()`. The item row is locked (`lockForUpdate()`) for the duration of the operation. A `decrease` is rejected if `quantity` exceeds the item's *available* quantity (`quantity - reserved_quantity`) — the same definition used for a Dispatch release — so a manual write-off cannot push the item into a state where `reserved_quantity` exceeds on-hand `quantity`.
- Reuses the existing `adjustment` transaction type (see Stock (the ledger) above) rather than introducing a new one: an `increase` creates a plain `adjustment` transaction; a `decrease` creates an `adjustment` transaction whose `reference_note` is prefixed `DEDUCT:` — the same convention `InventoryTransactionObserver` already uses to distinguish a receipt-driven increase from a manual reduction. `Item.status` is recomputed after the transaction is applied.
- Every adjustment is logged via `ActivityLogService` (`action = ADJUST_STOCK`), recording the direction, quantity, and reason.

---

## 4. Purchase Receipt

### States

`draft → posted` (a receipt may also be represented as effectively voided/cancelled prior to posting, but the concrete state transition validated in code is specifically `draft → posted`; only a `draft` receipt can be posted).

### Rules

- A purchase receipt begins in `draft` status with one or more line items (`purchase_receipt_items`), each specifying an item reference (by id, or by room + item name if the item doesn't yet have a stable id/does not yet exist), a quantity received, and optionally a category/unit.
- **Posting a receipt is the only way its line items affect on-hand inventory.** A `draft` receipt with unposted line items has no inventory effect.
- Posting rules, enforced by `PurchaseReceiptPostingService::postReceipt()`:
  1. The receipt row is locked (`lockForUpdate()`) for the duration of the operation.
  2. The receipt must currently be in `draft` status — posting an already-posted receipt is rejected with a validation error ("Receipt is already posted").
  3. The receipt must have at least one line item — posting a receipt with no items is rejected ("Cannot post a receipt with no items").
  4. For each line item, the target `Item` is resolved: by `item_id` if already set and it exists; otherwise by matching an existing `inventory_stock` item in the same room by case-insensitive name; otherwise a **new** `Item` is created (with `quantity = 0`, `status = out_of_stock` initially) to receive the stock. If resolution changes which item the line points to, the line item's `item_id` is backfilled.
  5. For each line, exactly one `InventoryTransaction` (`transaction_type = 'adjustment'`, quantity = quantity received, `reference_note = "Purchase receipt OR#<or_number>"`) is created — this is what actually increases the item's on-hand quantity, via the Observer.
  6. After the transaction is applied, the item's `status` is recomputed from its refreshed `quantity` and `reorder_level`.
  7. The receipt is flipped to `posted`, and an activity log entry is recorded summarizing the OR number and line item count.
- Once `posted`, a receipt is immutable with respect to inventory effect — it cannot be re-posted, and there is no code path that reverses a posted receipt's inventory effect other than creating a separate, explicit compensating transaction.

---

## 5. Dispatch

### States

`pending → approved → released`, with `cancelled` reachable from either `pending` or `approved`.

### Rules (enforced by `DispatchService`)

- **Creation (`pending`)**: A dispatch is created with a department/room/optional origin link (repair request, damage report, or purchase receipt) and one or more `DispatchItem` lines (item + quantity). Creation does not move any stock; it only records intent. Creating a dispatch does not require the requested items to currently be available in the amounts requested — availability is only checked at release.
- **Approval (`pending → approved`)**: Only a `pending` dispatch can be approved. Attempting to approve a dispatch in any other status is rejected ("Only pending dispatches can be approved."). Approval records `approved_by`.
- **Cancellation (`pending`/`approved` → `cancelled`)**: Only a `pending` or `approved` dispatch can be cancelled; a `released` or already-`cancelled` dispatch cannot be. Cancellation requires a reason, which is appended to the dispatch's `notes` field (prefixed `CANCELLED:`). Cancelling a dispatch never creates an inventory transaction — because no stock has been deployed yet (deployment only happens at release), there is nothing to reverse.
- **Release (`approved → released`)**: Only an `approved` dispatch can be released ("Only approved dispatches can be released."). Before release, the service pre-validates that for every dispatch line, the requested quantity does not exceed the currently available quantity (`item.quantity - item.reserved_quantity`); if any line fails this check, the entire release is rejected with a validation error naming the insufficient item — no partial release occurs. On successful validation, the release proceeds inside a single `DB::transaction()`: one `InventoryTransaction` (`transaction_type = 'deploy'`) is created per dispatch line (this is what actually decrements on-hand quantity, via the Observer), the dispatch is updated to `released` with `released_by` and (optionally) `receiver_user_id` recorded, and an activity log entry is written.

### Business restrictions

- A dispatch cannot skip from `pending` directly to `released`; it must pass through `approved`.
- A dispatch cannot be released if doing so would drive any line's on-hand quantity negative — this is enforced both as a pre-check and implicitly by the Observer's own `deploy` validation.
- A dispatch, once `released`, is terminal with respect to its own state machine — reversal of a release (e.g. an item coming back) is represented as a new `return`-type `InventoryTransaction`, not as a change to the dispatch's own status.

---

## 6. Deployment

### Tracking

- "Deployment" refers to inventory that has left on-hand stock via a `deploy`-type `InventoryTransaction`, most commonly created as a side effect of a dispatch release. Each deployment is traceable to the specific dispatch (via `reference_note`, e.g. `"Dispatch DSP-XXXX"`) and the user who released it (`performed_by`).
- Because every deployment is a ledger row, the full deployment history of any item (what left, when, to where/whom, under which dispatch) can always be reconstructed by querying `inventory_transactions` filtered by `item_id` and `transaction_type = 'deploy'`.

### Replacement

- A replacement (e.g. issuing a new unit in exchange for a failed/damaged one) is represented as its own pair of ledger entries where applicable: a `dispose`/`deploy` movement for the item being removed from service or replaced, and a separate `deploy` movement for the replacement unit, each independently validated and audited. There is no single "replace" transaction type — replacement is composed from the existing transaction-type vocabulary.

### History

- Full stock history for any item, and full deployment/replacement history for any report or dispatch, is derivable entirely from `inventory_transactions` — this table is the authoritative history; `items.quantity` is only ever a cached, current-state summary of it.

---

## 7. Damage Reports

- A staff user submits a damage report describing a facility issue tied to a specific room.
- Reports move through a review lifecycle (submitted → under review → in progress/repairing → resolved/replaced → closed), gated by maintenance_admin/super_admin action.
- A damage report may result in a "Need Change" request when the resolution requires consuming inventory (a replacement part) — see Section 10, Need Change Workflow, for the precise current behavior and its known gap.
- All status transitions and approvals on a damage report should be attributable (actor + timestamp) and are expected to be reflected in the activity log.

---

## 8. Repair Requests

- A repair request follows its own lifecycle: `pending → assigned → diagnosing → repairing → waiting_parts → completed/failed → archived`.
- `assigned` requires a maintenance_admin (or super_admin) to assign the request to a handler.
- `waiting_parts` reflects a repair that is blocked on inventory availability — this state is where a linked Need Change / purchase receipt / dispatch workflow is expected to resolve the blockage.
- `failed` and `completed` are both terminal outcomes prior to `archived`; `archived` is the final state and is expected to be reached only from a terminal outcome, not directly from an in-progress state.

---

## 9. Replacement Workflow

- A replacement is initiated when a damage report or repair request determines that a component must be swapped rather than repaired in place.
- The replacement consumes inventory through the same `InventoryTransaction` mechanism as any other deployment (a `deploy` or `adjustment`-derived movement against the replacement item), tied back to the originating report via `reference_note` and/or the transaction's `report_id` column.
- There is no separate "replacement" table; a replacement is a business interpretation of an ordinary deploy/dispose transaction pair, linked to the report that necessitated it.

---

## 10. Need Change Workflow

**As of 2026-07-21, this section documents the CURRENT, as-implemented, functional behavior. The deduction gap previously described here has been closed — see the Historical note at the end of this section for the prior (non-functional) behavior.**

### Current implementation

- A maintenance report can carry a "Need Change" request, tracked via `maintenance_reports.need_change_item_id`, `need_change_quantity` (defaults to `1`), `need_change_status`, `need_change_approved_by`, `need_change_approved_at`, and `need_change_deducted_at`.
- The live Laravel endpoint for this is `ReportController::update()` (`app/Http/Controllers/Api/ReportController.php`), which accepts `approve_need_change`/`reject_need_change` flags. Only a `super_admin` may exercise this action. The actual approval logic is delegated to `NeedChangeService::approve()` (`app/Services/NeedChangeService.php`) — the controller only authorizes the request and translates the Service's result/exception into an HTTP response, per the Controllers-are-thin standard.
- On `approve_need_change`, `NeedChangeService::approve()`, wrapped in a single `DB::transaction()`:
  1. Row-locks the `maintenance_reports` row (`lockForUpdate()`).
  2. **Idempotency guard:** if `need_change_deducted_at` is already set, returns immediately with no further effect — a repeated or concurrent approval call never creates a second deduction (see Duplicate-approval prevention below).
  3. Validates the report actually carries a Need Change item (`need_change_item_id` must be set) and that the row-locked `Item` has sufficient `quantity` to cover `need_change_quantity`; either failure raises a `ValidationException` and rolls back the transaction, leaving the report and inventory untouched.
  4. Creates exactly one `deploy`-type `InventoryTransaction` (`quantity = need_change_quantity`, `report_id` = the report's id, `reference_note = "Need Change approval for report #<id>"`) — the item physically leaves inventory to be used as the replacement part, the same semantic already used for a Dispatch release. `InventoryTransactionObserver` performs and audits the actual `Item.quantity` mutation; the Service never assigns `Item.quantity`/`reserved_quantity` directly.
  5. Sets `need_change_status = 'deducted'`, stamps `need_change_approved_by` (the acting super_admin's id) and both `need_change_approved_at`/`need_change_deducted_at` to the same timestamp.
  6. Logs an `APPROVE_NEED_CHANGE` activity log entry via `ActivityLogService`.
- On `reject_need_change`, the controller sets the corresponding rejected status without any inventory effect (which is correct, since a rejection should never consume stock) — this path was not changed by the fix above.

### Duplicate-approval prevention

Because the report row is locked (`lockForUpdate()`) for the full duration of the transaction, and the very first thing checked after acquiring the lock is whether `need_change_deducted_at` is already non-null, two concurrent approval requests for the same report cannot both pass the check: whichever transaction commits first sets `need_change_deducted_at`, and any other transaction — whether it arrived concurrently (blocked on the row lock, then sees the now-set column) or arrives later (a simple re-submitted approval) — observes it already set and returns without creating a second `InventoryTransaction`.

### Historical note (resolved gap)

Prior to 2026-07-21, approving a Need Change request only flipped `need_change_status` to `'approved'` and stamped the approver/timestamp — it created no `InventoryTransaction`, never decremented `Item.quantity`, and never set `need_change_deducted_at`, making approval indistinguishable from a no-op from the ledger's perspective. This was a **functional regression from an incomplete Laravel migration**: a legacy, non-Laravel, raw-PDO implementation of this same behavior existed in `public/backend/services/ReportService.php` (`deductNeedChangeInventory()`), but that code path was unreachable from any active route and was never ported into `ReportController::update()`. That legacy method remains as dead code and was not deleted or reused as part of this fix (its raw-SQL, non-ledger approach was not carried forward); the new implementation was designed independently against the current Observer/Service architecture.

---

## 11. Notifications

- Notifications are generated in response to workflow events (e.g. report status changes, assignment, approval/rejection) to inform the relevant user(s) without requiring them to poll report state manually.
- Notification creation should be triggered from the same Service/Controller code path that performs the underlying state change, so a notification is never sent for an event that did not actually occur (and never silently skipped for one that did).

---

## 12. Activity Logs

- Every state-changing action of consequence (create/approve/cancel/release on dispatches, purchase receipt posting, report status transitions, user management actions) should produce an `activity_logs` entry via `ActivityLogService`, recording `user_id`, `action`, `module`, `entity_type`, `entity_id`, a human-readable `details` string, optional structured `meta` (JSON), and `ip_address`.
- `activity_logs.user_id` is nullable with `setNull()` on user deletion — the audit record survives even if the acting user account is later removed.
- Activity logs are append-only: existing entries are not edited or deleted by normal application flow.
- **Known inconsistency:** `PurchaseReceiptPostingService` currently inserts its activity log entry directly via `DB::table('activity_logs')->insert()` rather than through `ActivityLogService`, unlike `DispatchService`. This is documented here as a known inconsistency in the current codebase, not a pattern to be replicated in new code (see `ARCHITECTURE.md` Coding Standards).

---

## 13. Reports (Dashboards/Analytics)

- Dashboard and reporting views aggregate across reports, inventory, and dispatch data (e.g. counts of open reports by status, low-stock items, pending dispatches/receipts) for `maintenance_admin`/`super_admin` visibility.
- Aggregate/analytics views are cached (`Cache::tags(['analytics'])`), and that cache is explicitly flushed by the inventory Observers whenever a transaction or stock entry is created or deleted, so dashboard figures reflect the latest ledger state rather than stale cached values.

---

## 14. General Business Rules (system-wide)

1. **Stock quantity is only ever changed through the ledger** (`InventoryTransaction`/`InventoryStockEntry`), never by direct field assignment — this applies uniformly across Purchase Receipts, Dispatch, Need Change, and any future workflow.
2. **Every approval action must be attributable** — record the acting user id and a timestamp, and prefer also recording an activity log entry.
3. **Status transitions are one-directional and validated** — every state machine in the system (Dispatch, Damage Report, Repair Request, Purchase Receipt) rejects invalid transitions (e.g. approving something already released, posting something already posted) with an explicit validation error rather than silently no-op'ing or allowing the transition.
4. **Deletion of a user never deletes their historical actions** — audit and ledger records referencing a deleted user retain the record with a null actor reference, rather than being deleted or reassigned.
5. **Deletion of a structural parent (building/floor) cascades to its children** — because a floor/room cannot meaningfully exist without its parent, unlike user references which are preserved.
6. **A gap between intended behavior and actual behavior (like the Need Change deduction gap) must be documented, not silently patched over or assumed away**, until a deliberate, separately-scoped change addresses it.
