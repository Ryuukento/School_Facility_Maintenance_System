# ARCHITECTURE_VIOLATIONS.md

## Targeted Architecture Review — Critical Violations from WORKING_TREE_AUDIT

**Document status:** Point-in-time investigation, captured **2026-07-21**, drilling into the two High-risk findings flagged in `WORKING_TREE_AUDIT.md`. Read-only: no source file, migration, or database record was modified to produce this report. Evidence was gathered via direct file inspection, `git show`/`git diff` against `HEAD`, and grep against the six point-in-time SQL dumps under `db_backups/` (a live database connection was attempted via `php artisan migrate:status` but the local MySQL server is not currently running, so live-schema confirmation relies on the backup files instead — this is disclosed explicitly wherever it matters below).

---

## Violation 1 — `ItemController` Inventory Mutation Bypass

### `store()` implementation (current working tree, [ItemController.php:77-128](app/Http/Controllers/Api/ItemController.php))

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'category_id' => ['nullable', 'integer', 'exists:inventory_categories,id'],
        'brand' => ['nullable', 'string', 'max:255'],
        'model' => ['nullable', 'string', 'max:255'],
        'quantity' => ['required', 'integer', 'min:0'],          // ← line 84
        'unit_type' => ['nullable', 'string', 'max:50'],
        'item_condition' => ['nullable', 'string', 'max:50'],
        'status' => ['nullable', 'string'],                       // ← line 87 (see note below)
        'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        'inventory_room_id' => ['nullable', 'integer', 'exists:inventory_rooms,id'],
        'reorder_level' => ['nullable', 'integer', 'min:0'],
        'low_stock_threshold_override' => ['nullable', 'integer', 'min:0'],
        'description' => ['nullable', 'string'],
    ]);

    // ... duplicate-name-in-room guard (lines 95-113) ...

    $item = Item::query()->create($validated);   // ← line 115
    // ...
}
```

### `update()` implementation (current working tree, [ItemController.php:135-166](app/Http/Controllers/Api/ItemController.php))

```php
public function update(Request $request, Item $item)
{
    $validated = $request->validate([
        'name' => ['sometimes', 'string', 'max:255'],
        'category_id' => ['sometimes', 'nullable', 'integer', 'exists:inventory_categories,id'],
        'brand' => ['sometimes', 'nullable', 'string', 'max:255'],
        'model' => ['sometimes', 'nullable', 'string', 'max:255'],
        'quantity' => ['sometimes', 'integer', 'min:0'],          // ← line 142
        'unit_type' => ['sometimes', 'nullable', 'string', 'max:50'],
        'item_condition' => ['sometimes', 'nullable', 'string', 'max:50'],
        'status' => ['sometimes', 'nullable', 'string'],           // ← line 145 (see note below)
        'room_id' => ['sometimes', 'nullable', 'integer', 'exists:rooms,id'],
        'inventory_room_id' => ['sometimes', 'nullable', 'integer', 'exists:inventory_rooms,id'],
        'reorder_level' => ['sometimes', 'nullable', 'integer', 'min:0'],
        'low_stock_threshold_override' => ['sometimes', 'nullable', 'integer', 'min:0'],
        'description' => ['sometimes', 'nullable', 'string'],
    ]);

    $item->update($validated);   // ← line 153

    // ... activity log ...
}
```

### Exact lines where `quantity`/`reserved_quantity` are written

| Line | Statement | Effect |
|---|---|---|
| `ItemController.php:84` | `'quantity' => ['required', 'integer', 'min:0']` | Accepts client-supplied `quantity` into `$validated` for `store()`. |
| `ItemController.php:115` | `$item = Item::query()->create($validated);` | **Mass-assigns `quantity` directly to a new `Item` row** — no `InventoryTransaction` is created. |
| `ItemController.php:142` | `'quantity' => ['sometimes', 'integer', 'min:0']` | Accepts client-supplied `quantity` into `$validated` for `update()`. |
| `ItemController.php:153` | `$item->update($validated);` | **Mass-assigns `quantity` directly onto an existing `Item` row** — no `InventoryTransaction` is created, no `Observer` hook fires for a ledger entry (Eloquent's `updated`/`saving` events fire, but no ledger row exists to drive `InventoryTransactionObserver`, since that Observer is registered against the `InventoryTransaction`/`InventoryStockEntry` models, not `Item` itself). |

`reserved_quantity` is **not** currently present in either `$validated` array, so this specific controller does not yet write it directly — but it **is** listed in `Item::$fillable` (confirmed in `app/Models/Item.php`), so it is only one careless `$request->all()` or one added validation rule away from the same bypass. This is flagged as a latent extension of the same risk, not a currently-exercised one.

**Closely related, same-root-cause finding (not separately requested, noted for completeness):** both `store()` and `update()` also accept a raw `status` string directly from the client (lines 87/145) and write it via the same `create()`/`update()` calls. `BUSINESS_RULES.md` §3 and `ARCHITECTURE.md` §5.2 point 5 state `Item.status` must **always be derived** from `quantity`/`reorder_level`, never set directly — this is a second violation of the same "Item.* ledger-governed fields must not be client-writable" principle, introduced via the same two methods, for the same underlying reason.

### Why this bypasses the documented `InventoryTransaction` architecture

`ARCHITECTURE.md` §5.2 (Single writer principle) states plainly: *"Only the Observer ever assigns a new value to `Item.quantity`/`reserved_quantity`. Every other piece of code... is only allowed to express intent by creating an `InventoryTransaction` row."* `ARCHITECTURE.md` §8 (Coding Standard #3) and §9 (Future Rule #2) restate this as binding for all new code, and `AI_INSTRUCTIONS.md`'s Forbidden Actions list explicitly names "Never introduce direct stock mutations."

`Item::create($validated)` and `$item->update($validated)` are ordinary Eloquent mass-assignment calls. Because `quantity` is listed in `Item::$fillable`, Eloquent will happily set it as an ordinary attribute and persist it with a plain `INSERT`/`UPDATE` statement. **No `InventoryTransaction` row is created, so:**

- There is no ledger entry explaining *why* the quantity changed, *who* changed it, or *by how much* relative to the prior value.
- `InventoryTransactionObserver` never fires (it observes `InventoryTransaction`/`InventoryStockEntry` model events, not `Item` events), so none of the locking, validation, or status-rederivation logic in `ARCHITECTURE.md` §5.2 points 3-5 runs.
- The audit trail described in `BUSINESS_RULES.md` §6 ("full stock history... is derivable entirely from `inventory_transactions`") is silently broken for any item edited through this endpoint: its `quantity` can change with zero corresponding ledger rows, making the ledger an *incomplete*, not authoritative, history from that point forward.
- Because `status` is also directly writable, a caller could set `status = 'available'` on an item with `quantity = 0`, producing a display that actively contradicts the item's real stock state — the exact drift `ARCHITECTURE.md` §5.2 point 5 says can "never silently drift out of sync" under the documented architecture.

### Intentional, accidental, or incomplete?

Confirmed via `git show HEAD:app/Http/Controllers/Api/ItemController.php`: the last-committed version of this file contains **only** an `index()` method with two simple filters — `store()`, `show()`, `update()`, `destroy()`, and `history()` do not exist at `HEAD` at all. This is entirely new, uncommitted work, not inherited legacy debt.

The surrounding code quality argues against sloppiness: both methods include careful validation, a duplicate-name-in-room guard, proper `ActivityLogService` logging, and consistent response shaping — this is not rushed or careless code in general. The most likely explanation is **incomplete architectural application, not a deliberate decision to bypass the ledger**: `Item` was evidently approached as an ordinary CRUD resource (name/brand/model/category — descriptive "item master data" fields), and `quantity` was included in the same form/validation/mass-assignment path as those descriptive fields without cross-referencing the ledger rule that governs it specifically. This is a textbook instance of the exact failure mode `ARCHITECTURE.md` §5 exists to prevent: a resource has both ordinary attributes and one ledger-governed attribute, and a general-purpose "edit this resource" endpoint was built without carving the ledger-governed field out into its own governed path.

For `store()` specifically, there is a weaker case for "intentional": setting an initial `quantity` at item-creation time superficially resembles what `PurchaseReceiptPostingService` does when it creates a *new* `Item` to receive a receipt line — except that service always creates the new item at `quantity = 0` and then applies the actual quantity via a proper `adjustment`-type `InventoryTransaction` (per `BUSINESS_RULES.md` §4, rule 4). `ItemController::store()` does not follow that pattern; it writes the requested initial `quantity` straight onto the new row. This is best read as an *incomplete* port of the correct pattern already established elsewhere in the codebase, not a considered architectural exception.

**Assessment: incomplete work, most likely unintentional.**

### Recommended architecture-compliant solution (not implemented)

- Remove `quantity` (and `status`) from the mass-assignable/validated field set in **both** `store()` and `update()`. Neither method should be able to set these fields via `Item::create()`/`$item->update()`.
- **`store()`:** create the new `Item` at `quantity = 0` unconditionally (mirroring `PurchaseReceiptPostingService`'s existing convention for newly-created items). If the caller supplied a nonzero initial quantity, express it as a single `adjustment`-type `InventoryTransaction` created immediately after the `Item` row exists, with a clear `reference_note` (e.g. `"Initial stock on item creation"`) and `performed_by` set to the acting user — letting `InventoryTransactionObserver` apply and audit the quantity exactly as it already does for purchase receipts.
- **`update()`:** drop `quantity` from this endpoint's editable surface entirely. If administrators need to correct/adjust an existing item's quantity, that should be its own explicit action — e.g. a dedicated stock-adjustment endpoint/Service method that creates an `adjustment`-type `InventoryTransaction` — never a side effect of editing descriptive metadata like name/brand/model.
- Remove `status` from both validated sets; let it continue to be derived exclusively by the Observer's existing quantity/reorder-level logic. If a status needs to be reflected immediately after a metadata-only edit (e.g. after `reorder_level` changes), re-derive it via the same shared derivation logic the Observer uses — do not accept it as client input.
- `reorder_level`/`low_stock_threshold_override` may safely remain directly editable via `update()` (they are threshold/configuration fields, not ledger quantities) — but note, as a smaller related consistency gap, that changing `reorder_level` through this endpoint does not currently trigger a re-derivation of `status`, even though `status` depends on `quantity` vs. `reorder_level`. This is worth addressing in the same change since it is adjacent, but is not itself a ledger-integrity violation.
- This preserves all of the already-built CRUD scaffolding (duplicate checking, activity logging, response shaping) — the fix is narrowly about routing the one ledger-governed field (and the one derived field) through the architecture's existing, correct mechanism instead of Eloquent's generic mass assignment.

---

## Violation 2 — Edited Migration: `2026_04_07_000700_add_need_change_fields_to_maintenance_reports_table.php`

### What changed compared to the original intent of the migration

`git show HEAD:database/migrations/2026_04_07_000700_add_need_change_fields_to_maintenance_reports_table.php` shows the **original, committed** `up()` method performed two steps:

1. Add six nullable `need_change_*` columns to `maintenance_reports` (unchanged in the edit — still present).
2. In a **second**, separate `Schema::table(...)` call, add two foreign key constraints:
   - `need_change_item_id` → `items.id`, `nullOnDelete()`
   - `need_change_approved_by` → `users.user_id`, `nullOnDelete()`

The **current working-tree version** deletes step 2 entirely and replaces it with a comment (written in Tagalog):

```php
// 2. (TEMPORARY FIX) Huwag munang maglagay ng foreign key constraint para sa need_change_item_id at need_change_approved_by
// Pwede itong idagdag manually kapag sure na ang data at structure
```

(Translation: *"(TEMPORARY FIX) Don't add the foreign key constraint for `need_change_item_id` and `need_change_approved_by` yet. It can be added manually later once the data and structure are confirmed."*)

**Net effect of the edit:** `up()` now only adds the six columns and never attempts either foreign key. **Critically, `down()` was not updated to match** — it still unconditionally attempts:

```php
$table->dropForeign(['need_change_approved_by']);
$table->dropForeign(['need_change_item_id']);
```

guarded only by `Schema::hasColumn(...)` (column existence), not by any check for whether the *constraint* exists. On any environment where `up()` runs as currently written (no FK ever created) and this migration is later rolled back, `down()`'s `dropForeign()` calls will fail against a nonexistent constraint, breaking `migrate:rollback` for this migration. This `up()`/`down()` asymmetry is itself evidence the edit was applied as a quick, narrowly-scoped patch rather than a fully-considered migration rewrite.

### Whether this migration appears to have already been applied

**Confirmed: yes, applied.** Every one of the six point-in-time SQL backups in `db_backups/` that contains a `migrations` table dump records `2026_04_07_000700_add_need_change_fields_to_maintenance_reports_table` as applied at batch `3`:

- `sfms_backup_2026-04-26_123433.sql`
- `sfms_backup_2026-05-04_215001.sql`
- `sfms_backup_2026-05-04_215614.sql`
- `pre_final_fix_all_databases_20260520_110257.sql`
- `role_normalization_backup_20260615_110955.sql` (the most recent backup available)

A live-database check via `php artisan migrate:status` was attempted but could not complete — the local MySQL server (`127.0.0.1:3306`) is not currently running in this environment — so the finding above rests on the backup evidence, which spans April 26 through June 15 and is unanimous.

**Additional finding on the foreign keys themselves:** inspecting the actual `CREATE TABLE maintenance_reports` schema captured in these same backups (including the earliest, April 26) shows the six `need_change_*` columns present in every backup, but **no foreign key constraint on `need_change_item_id` or `need_change_approved_by` in any of them** — only `assigned_to` and `created_by` carry constraints. In other words, the FK-adding step of the migration, **as originally written and committed**, does not appear to have taken effect in any observed environment, even before this file was edited. This cannot be fully explained from the artifacts available (it could mean the original FK step failed at runtime in every environment yet the migration was still recorded as applied — unusual for Laravel's normal failure handling — or it could mean the FKs were manually dropped out-of-band before the earliest available backup). Either way, the live schema already matched what the *edited* migration now describes, before the edit was made — which lowers this specific instance's practical impact, but does not change the fact that the mechanism used (editing an already-applied file) is unsafe.

### Why editing an existing, already-applied migration is risky

Laravel's migration runner tracks "has this migration run" by **filename** in the `migrations` table — not by a hash of the file's contents. This has concrete consequences once a file is edited after running anywhere:

1. **The file stops being a truthful record.** Every environment where this migration already ran did so against whatever the file said *at that time*. The file no longer describes how those databases actually got to their current state.
2. **New/fresh environments silently diverge.** A fresh clone, a new developer's local database, or a CI database that has never run this migration will execute the **edited** version and skip the FK-adding step entirely — while any environment that ran the *original* version (if one exists outside the available backups) would have attempted to add the FKs. Migrations are idempotent by filename, not by content, so this divergence produces no error, no warning, and no visible signal that two environments now disagree.
3. **`migrate:fresh` becomes the only reliable way to know current behavior**, which is unsafe to run against any environment holding real data (it drops everything first) — undermining the entire point of migrations as an incremental, low-risk schema history.
4. **Rollback correctness degrades**, as shown concretely above: `down()` was left out of sync with the edited `up()`, so rollback on an environment matching the new `up()` behavior will error out attempting to drop foreign keys that were never created.
5. This is precisely the rule `ARCHITECTURE.md` §2 states in the migrations note ("Any new table or column must be introduced via a migration, never... by editing an existing migration after it has been applied anywhere") and `AI_INSTRUCTIONS.md` Rule 12 ("never edit an existing migration that has already been applied anywhere").

### Recommended safest Laravel approach (not implemented)

1. **Revert** `2026_04_07_000700_add_need_change_fields_to_maintenance_reports_table.php` to its original, already-applied form — restoring the FK-adding `Schema::table(...)` block exactly as committed at `HEAD` — so the file continues to truthfully describe what has already run everywhere.
2. **Express the "don't enforce this FK yet" decision as a new, additive migration** (e.g. `2026_07_21_XXXXXX_drop_need_change_foreign_keys_from_maintenance_reports_table.php`), whose `up()`:
   - Checks whether each foreign key actually exists (Laravel has no built-in `Schema::hasForeignKey()`; this typically means querying `information_schema.KEY_COLUMN_USAGE`/`information_schema.TABLE_CONSTRAINTS`, or wrapping the `dropForeign()` call so a missing-constraint error is tolerated) before attempting to drop it — so the migration is safe to run regardless of whether a given environment's FK step from the original migration actually took effect.
   - Drops the two foreign keys if present.
   
   And whose `down()` re-adds them, keeping the migration reversible and honest in both directions.
3. Given the evidence that the FKs never existed in any observed backup, this new migration will likely be a safe no-op almost everywhere it runs — but it still needs to exist, both so history remains an accurate, replayable record and so any environment where the original FK step *did* succeed (none confirmed, but not provably ruled out beyond the available backups) converges to the same end state as everywhere else.
4. **Separately:** if the actual engineering decision is to permanently not enforce these two foreign keys (rather than "add manually later," as the current comment suggests), that decision belongs in `BUSINESS_RULES.md`/`ARCHITECTURE.md` as a documented, deliberate data-integrity rule — not as an inline comment promising a future manual step that has no owner or deadline. A "TODO: add later" comment with no tracking is exactly the kind of undocumented drift `ROADMAP.md`'s Engineering Philosophy and `AI_INSTRUCTIONS.md`'s Documentation Rules exist to prevent.

---

## Summary

### Root Cause

- **Violation 1:** New CRUD endpoints (`ItemController::store()`/`update()`) were built by treating `Item.quantity` (and `Item.status`) as ordinary, directly-editable resource attributes — the same way `name`/`brand`/`model` are treated — rather than recognizing them as ledger-governed/derived fields requiring the `InventoryTransaction` → `Observer` path already established elsewhere in the same codebase (e.g. `PurchaseReceiptPostingService`).
- **Violation 2:** A data/schema problem with two foreign keys (likely a pre-existing constraint-violation or type-mismatch issue, though the precise original cause could not be confirmed from available artifacts) was resolved by editing the already-applied migration file that originally added those FKs, instead of writing a new, additive migration to remove them — violating the project's "migrations are the only way to change schema, and are never edited after applying" rule.

### Risk

- **Violation 1 — High.** This is a live, currently-uncommitted code path that, if merged and deployed as-is, creates a second, unaudited way to change on-hand stock, directly undermining the single most safety-critical guarantee in the system (`ARCHITECTURE.md` §5). It also allows `status` to be set inconsistently with actual `quantity`.
- **Violation 2 — Medium, mitigated by observed evidence, but the practice itself remains High-risk if repeated.** In this specific instance, all available evidence indicates the live schema already lacked the FKs everywhere checked, so the edit likely does not change current real-world behavior for already-migrated environments. However, the *editing-an-applied-migration* pattern itself is unconditionally risky per Laravel's filename-based tracking, and the accompanying `down()`/`up()` asymmetry is a confirmed, separate latent bug regardless of the FK question.

### Impact

- **Violation 1:** Silent stock-accuracy drift for any item edited through this endpoint; a broken audit trail (ledger no longer fully explains item quantity history); a `status` value that can visibly contradict `quantity`; erosion of the exact guarantee `ROADMAP.md` Phase 1 is meant to certify before further workflows (e.g. Phase 2's Need Change fix) are built on top of the ledger.
- **Violation 2:** Low *immediate* impact given the evidence gathered (no environment checked shows the FKs ever active), but a real, demonstrated *process* impact: a fresh environment migrating from scratch today will end up in a different historical trajectory than environments that ran the original file, and a rollback of this migration will currently error. The underlying data-integrity question (why these FKs couldn't be added in the first place) remains unresolved and undocumented.

### Recommended Resolution

- **Violation 1:** Strip `quantity`/`status` from `ItemController::store()`/`update()`'s writable field sets; route initial/adjusted quantities through a proper `adjustment`-type `InventoryTransaction`, following the existing `PurchaseReceiptPostingService` pattern; let `status` remain Observer-derived only. (Detailed in Violation 1's "Recommended architecture-compliant solution" above.)
- **Violation 2:** Revert the migration file to its original, already-applied form; express the FK removal as a new, existence-checked, reversible migration; document the underlying "why can't these FKs be enforced" decision explicitly in `BUSINESS_RULES.md`/`ARCHITECTURE.md` rather than leaving it as an inline TODO comment. (Detailed in Violation 2's "Recommended safest Laravel approach" above.)

### Priority

| Violation | Priority | Rationale |
|---|---|---|
| **Violation 1 — `ItemController` bypass** | **Critical — resolve before Phase 1 sign-off** | Directly contradicts the exact guarantee Phase 1 (`ROADMAP.md`) exists to certify; leaving it unresolved while "stabilizing" the same subsystem elsewhere is internally inconsistent. |
| **Violation 2 — edited migration** | **High — resolve before committing the pending working tree, but not blocking on further data-integrity archaeology** | The observed real-world impact is limited given current evidence, but the unsafe editing pattern and the `up()`/`down()` asymmetry should not be committed to history as-is; fixing the *mechanism* (new migration instead of edited one) does not require first fully resolving why the original FK step never took hold. |

Neither violation was modified as part of this investigation. Both remain open findings pending an explicit decision and a separate, deliberate implementation change.
