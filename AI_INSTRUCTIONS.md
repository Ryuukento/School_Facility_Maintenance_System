# AI_INSTRUCTIONS.md

## PhilCST Centralized School Facility Maintenance Reporting System — Permanent AI Development Guide

**Document status:** Living reference. This is the permanent instruction manual for every AI contributor working on this codebase, present or future. It sits above individual tasks: no instruction given in a single conversation or ticket overrides this document unless the human directing that work explicitly and knowingly says so. This document does not define architecture or business rules itself — it defines how any AI contributor must behave with respect to `ARCHITECTURE.md`, `BUSINESS_RULES.md`, `CHANGELOG.md`, and `ROADMAP.md`, which remain the substantive sources of truth.

---

## Project Overview

The PhilCST Centralized School Facility Maintenance Reporting System is a platform for managing school facility maintenance operations end-to-end: staff report facility damage and repair needs; maintenance administrators and super administrators triage, approve, and resolve those reports; physical assets (buildings, floors, rooms) and inventory (spare parts, consumables) are tracked; procurement (purchase receipts) and internal distribution (dispatches) move inventory through the organization; and every state-changing action is expected to be attributable via an audit trail.

The system is a **hybrid legacy + Laravel application in the middle of an incremental migration**. The Laravel side (`app/*`) is the actively developed portion and follows an MVC + Service Layer + Observer Pattern, with a ledger-based inventory model at its core: all stock quantity changes flow through an append-only `inventory_transactions` (or `inventory_stock_entries`) record, and only a small set of Observers are permitted to translate that ledger into the materialized `Item.quantity`/`reserved_quantity` fields. The legacy procedural PHP side (`public/backend/*`, `public/frontend/*`) is being phased out, not extended; some of it is confirmed dead code, and some remains live via a session-bridging middleware that keeps native PHP sessions and Laravel sessions in agreement.

The project maintains a deliberate, layered documentation set — `ARCHITECTURE.md` (structural truth), `BUSINESS_RULES.md` (behavioral truth), `CHANGELOG.md` (historical record), and `ROADMAP.md` (forward plan) — precisely so that both human and AI contributors can operate on a shared, current understanding of the system rather than reconstructing it from code alone each time. This document exists to make sure that shared understanding is actually used.

---

## AI Responsibilities

Every AI contributor working on this codebase is expected to:

- **Follow documentation first.** Treat `ARCHITECTURE.md` and `BUSINESS_RULES.md` as authoritative for structure and behavior respectively. Do not derive architecture or business rules from reading code alone when a governing document already exists — read the document first, then confirm the code still matches it.
- **Respect the existing architecture.** Work within the Controller → Service → Observer layering and the ledger-based inventory model. Do not introduce a new architectural pattern to solve a problem the existing patterns already solve.
- **Never invent business rules.** If a requested behavior is not described in `BUSINESS_RULES.md`, do not assume or guess what it should be. Either find the answer in the documentation, ask the human directing the work, or explicitly flag the ambiguity — never silently decide.
- **Prefer consistency over cleverness.** A solution that matches the existing pattern (even if a more elegant one exists in the abstract) is preferred over a novel one that requires a reviewer to evaluate a new approach from scratch.
- **Ask for clarification if documentation conflicts**, or if a task's instructions appear to conflict with `ARCHITECTURE.md`, `BUSINESS_RULES.md`, or `ROADMAP.md`. Do not silently pick a side and proceed.
- **Respect scope.** If asked to fix one thing, fix that thing — do not fold in unrelated refactors, cleanups, or "while I'm here" changes not requested.
- **Keep documentation and code in sync.** Any change to behavior is incomplete until the relevant documentation is updated in the same change (see Documentation Rules below).
- **Default to the least destructive interpretation of an instruction.** When a task could be read narrowly or broadly, and the broad reading would touch code, migrations, or documentation not mentioned in the task, prefer the narrow reading and surface the ambiguity rather than assuming permission.

---

## Mandatory Reading Order

Before starting any task on this codebase, every AI contributor must read, in this order:

1. **`ARCHITECTURE.md`** — structural and layering truth: folder structure, system modules, database overview, the inventory architecture, application layers, security architecture, and coding standards.
2. **`BUSINESS_RULES.md`** — behavioral truth: authentication/roles, every workflow's state machine, the inventory ledger's transaction-type vocabulary, and any documented functional gaps (read these especially carefully — they describe current behavior, not aspirational behavior).
3. **`CHANGELOG.md`** — historical record of what has already been built, decided, or found, so past findings are not rediscovered or contradicted.
4. **`ROADMAP.md`** — the forward plan: which phase the project is currently in, what is explicitly deferred, and what is out of scope until a later phase.
5. **`AI_INSTRUCTIONS.md`** (this document) — the behavioral contract governing how the first four documents are to be applied.

Skipping this reading order is not permitted, even for a task that appears small. A change that looks trivial in isolation (e.g. "just update this field") can silently violate a documented rule (e.g. writing `Item.quantity` directly) if the documentation was not consulted first.

---

## Engineering Rules

These rules are binding for all new work, derived directly from `ARCHITECTURE.md` and `BUSINESS_RULES.md`:

1. **Controllers must remain thin.** They validate input, authorize the action, and delegate to a Service or a simple Eloquent read. They must never contain multi-step business logic or direct stock mutation.
2. **Business logic belongs in Services.** Any operation spanning more than one write, requiring a status-transition check, or requiring row-locking belongs in `app/Services/*`.
3. **Inventory stock changes must only happen through an `InventoryTransaction` (or `InventoryStockEntry`) and its Observer.** No controller, service, script, or migration-adjacent code may perform stock-affecting logic any other way.
4. **Never update `Item.quantity` or `Item.reserved_quantity` directly.** These are derived, materialized values. Only `InventoryTransactionObserver`/`InventoryStockEntryObserver` may assign them.
5. **Use `DB::transaction()` with `lockForUpdate()` for multi-step workflows** that read a row's current state and then conditionally write based on it — this applies to stock checks and status-transition checks alike.
6. **Preserve audit logs.** Every state-changing action of consequence must produce an `activity_logs` entry via `ActivityLogService`, with a real actor id (or a preserved-null reference if the actor was later deleted) and a human-readable description.
7. **Preserve backward compatibility where practical**, especially the legacy/Laravel session bridge (`SyncLegacyPhpSession`) and any still-live legacy page — do not break a working code path as a side effect of unrelated work.
8. **Avoid duplicated business logic.** Role normalization, stock-status derivation, and transition-validity rules must be implemented once and reused — never re-implemented per call site. Do not add a new role-alias or duplicate a normalization block; if role logic must change, prefer consolidating it (see `ROADMAP.md` Phase 3).
9. **Prefer Eloquent relationships over duplicated raw queries.** Where a relationship already exists on a model, use it rather than hand-rolling an equivalent query.
10. **Never extend the legacy `public/backend/*` or `public/frontend/*` surface** unless explicitly instructed by the human directing the work. New functionality belongs in `app/` and `resources/`.
11. **Status fields are always derived, never set directly.** `Item.status` (`available`/`low_stock`/`out_of_stock`) must be recomputed from `quantity`/`reorder_level`, never assigned as an arbitrary literal.
12. **Migrations are the only way to change schema.** Never hand-edit the database schema outside of a new migration file, and never edit an existing migration that has already been applied anywhere.

---

## Documentation Rules

Whenever a change alters business behavior, the AI contributor must, in the same change:

- **Update `BUSINESS_RULES.md`** to describe the new behavior accurately — including removing or rewriting any "known gap" language that the change resolves.
- **Update `CHANGELOG.md`** with a dated entry describing what changed and why, following the existing milestone-based format.
- **Update `ROADMAP.md`** if the change completes, alters, or invalidates a milestone, phase, or Technical Debt Queue item — including moving a resolved item out of the queue.
- **Update `ARCHITECTURE.md`** if the change alters folder structure, layering, or a structural rule (e.g. closing the `Observer::deleted()` locking gap requires updating ARCHITECTURE.md §5.3).

A change that alters behavior without a corresponding documentation update is **not complete**, regardless of whether the code itself is correct.

---

## Code Quality Standards

- **Readability.** Code should be understandable by a new contributor (human or AI) without requiring them to run it first. Prefer explicit, descriptive names over abbreviations or clever shorthand.
- **Naming.** Follow existing project conventions (e.g. `snake_case` for database columns, `PascalCase` for classes, `camelCase` for methods) rather than introducing a new convention within an otherwise consistent file.
- **Small, reviewable commits/changes.** Each change should be scoped to a single objective, sized so a human reviewer can hold the entire change in mind — especially critical for anything touching the inventory ledger.
- **SOLID principles**, applied pragmatically: single-responsibility for Services and Observers (as already established), but not imposed as abstraction for its own sake where the existing codebase is intentionally direct.
- **Laravel best practices.** Use Eloquent conventions (relationships, casts, scopes, model events) idiomatically; use the framework's validation, session, and exception-handling facilities rather than reinventing them.
- **Error handling.** Follow the existing centralized exception-to-JSON translation in `bootstrap/app.php` (`ValidationException` → 422, `ModelNotFoundException` → 404, generic 500 with no internals leaked in production). Do not introduce ad-hoc error-handling patterns in individual controllers.
- **Validation.** Validate input inline in controllers using `Validator`/`$request->validate()`, consistent with current practice (no `FormRequest` classes exist yet; do not introduce one unless explicitly asked to establish that pattern project-wide).
- **Testing.** Any new Service, Observer, or Controller business logic must ship with a PHPUnit Feature test using `RefreshDatabase` — not merely a manual `scripts/smoke_*.php` script. This is a hard requirement, not a nice-to-have, per `ARCHITECTURE.md`'s Future Architecture Rules.

---

## Implementation Workflow

Every implementation task follows this sequence, without skipping steps:

```
Architecture Review
        │   Confirm the task is consistent with ARCHITECTURE.md,
        │   or identify the explicit, deliberate amendment required.
        ▼
Documentation
        │   Confirm the expected behavior against BUSINESS_RULES.md
        │   before writing any code.
        ▼
Planning
        │   Scope the change: objectives, affected layers, risk,
        │   test plan. Present this plan before implementing.
        ▼
Implementation
        │   Controllers stay thin; business logic in Services;
        │   stock mutation only via Observers.
        ▼
Testing
        │   PHPUnit Feature tests (RefreshDatabase) for new
        │   business-logic paths.
        ▼
Review
        │   Verify against ARCHITECTURE.md coding standards and
        │   BUSINESS_RULES.md correctness.
        ▼
Documentation Update
        │   ARCHITECTURE.md / BUSINESS_RULES.md / CHANGELOG.md /
        │   ROADMAP.md updated in the same change.
        ▼
Merge
```

An AI contributor must not jump straight to Implementation because a task "seems simple." The Architecture Review and Planning steps are what catch violations before they are written, not after.

---

## Forbidden Actions

The following are never permitted, regardless of how a task is phrased:

- **Never bypass the Observer.** No code may assign `Item.quantity`/`Item.reserved_quantity` outside `InventoryTransactionObserver`/`InventoryStockEntryObserver`.
- **Never silently change business rules.** If a task requires a behavioral change, it must be accompanied by an explicit `BUSINESS_RULES.md` update — never shipped as a quiet code-only change.
- **Never introduce direct stock mutations** (raw `UPDATE items SET quantity = ...`, or an Eloquent `save()` that sets the field directly) for any reason, including "just this once" data fixes.
- **Never remove or weaken audit logging** to simplify an implementation, reduce noise, or work around a bug.
- **Never mix business logic into Controllers.** A controller that grows a multi-step conditional stock check or transition rule must have that logic extracted into a Service instead.
- **Never introduce duplicate workflows** — a second code path that achieves the same business outcome as an existing Service/Controller (e.g. a new ad-hoc dispatch-release endpoint) instead of reusing or extending the existing one.
- **Never extend the legacy `public/backend/*`/`public/frontend/*` surface** without explicit instruction.
- **Never hand-edit the database schema outside a migration**, and never edit an already-applied migration.
- **Never commit hardcoded credentials**, including as a "temporary" seeder convenience.
- **Never fix a documented known gap (e.g. the Need Change deduction gap, the `Observer::deleted()` locking gap) as an incidental side effect of unrelated work.** These are tracked, scoped roadmap items (`ROADMAP.md`) — closing one is its own deliberate task with its own planning and documentation update, not a drive-by fix.

---

## When Unsure

If at any point the correct action is unclear — because the documentation is silent on a scenario, because two documents appear to conflict, because a task's instructions seem to require violating an Engineering Rule or Forbidden Action above, or because the required business decision (e.g. "what quantity should be deducted in this edge case?") is not something the AI is positioned to decide unilaterally — **stop and ask for clarification.**

Do not guess. Do not proceed on the most plausible-sounding interpretation and hope it is correct. Do not silently choose between two conflicting rules. A clarifying question costs little; a confidently wrong change to a safety-critical ledger, an authorization check, or a published business rule can cost a great deal, and may not be noticed until well after it has caused harm.

---

## Closing Statement

This document is the highest-level engineering guide for AI contributors to this project. It must be read and followed before implementing any feature, fix, or refactor — in conjunction with, and never in place of, `ARCHITECTURE.md`, `BUSINESS_RULES.md`, `CHANGELOG.md`, and `ROADMAP.md`. No task instruction, however explicit, supersedes the rules in this document unless the human directing the work explicitly and knowingly overrides them for that specific task.
