# ROADMAP.md

## PhilCST Centralized School Facility Maintenance Reporting System — Engineering Roadmap

**Document status:** Living reference. This is the master engineering plan for the project. Every implementation — whether performed by a human engineer or an AI contributor — must follow the phase sequencing, priorities, and definitions of done described here. This document is subordinate to `ARCHITECTURE.md` (structural/layering authority) and `BUSINESS_RULES.md` (workflow/state-machine authority): if a roadmap item implies a structural or behavioral change, that change must be reflected in those documents at the same time it lands, not afterward. This document does not itself redefine architecture or business rules — it sequences and prioritizes work against them.

---

## Project Vision

The long-term vision for this system is to become the **single, authoritative digital system of record** for all school facility maintenance operations at PhilCST — fully realized as a modern Laravel application, with the legacy procedural PHP surface (`public/backend/*`, `public/frontend/*`) retired entirely rather than perpetually maintained alongside it.

Concretely, the system should reach a state where:

- Every workflow — reporting, triage, approval, procurement, dispatch, deployment, replacement — is implemented once, in `app/`, following the Controller → Service → Observer layering, with no legacy procedural equivalent left running in parallel.
- The inventory ledger (`inventory_transactions` / `inventory_stock_entries`) remains the single, provable source of truth for stock state, with no workflow able to bypass it — including workflows, like Need Change, that currently do not yet honor this guarantee end-to-end.
- Every approval, every stock movement, and every status transition is attributable and auditable by construction, not by convention.
- The system is safe to extend by multiple contributors (human or AI) over time because the architecture, business rules, and roadmap are documented and kept in sync with the code, rather than living only in the heads of whoever wrote a given feature.
- Facility staff, maintenance administrators, and super administrators each have an interface suited to their role, built on a consistent Laravel/Blade foundation rather than a patchwork of legacy PHP includes and modern pages.

This vision is deliberately incremental: the system does not need a "big bang" rewrite to get there. Every phase in this roadmap is a step that narrows the gap between current, documented behavior and this vision, without ever leaving the system in a half-working state.

---

## Engineering Philosophy

This project is governed by the following standing principles. They apply to all future work regardless of who or what performs it.

1. **Architecture First.** No implementation begins without first checking `ARCHITECTURE.md`. If a task appears to require a new pattern, the correct response is to propose a deliberate, documented architectural change — not to invent an undocumented one inline.
2. **Documentation First.** Business behavior is defined by `BUSINESS_RULES.md`, not by tribal knowledge or by whatever the code happens to currently do. If code and documentation disagree, that is treated as a defect in one of the two, to be resolved deliberately — never left to silently diverge.
3. **Business Rules First.** Workflow and state-machine behavior (approvals, transitions, ledger semantics) must be validated against `BUSINESS_RULES.md` before implementation, and any intentional behavioral change updates that document in the same change.
4. **Incremental Development.** The migration from legacy PHP to Laravel is deliberately incremental. No phase in this roadmap assumes or requires a disruptive rewrite of a working subsystem.
5. **One Feature at a Time.** Each unit of work should map to a single, clearly-scoped capability or fix. Bundling unrelated changes (e.g. a bug fix plus an unrelated refactor) is discouraged because it makes review and rollback harder.
6. **No Rushed Refactoring.** Refactoring (e.g. consolidating role-alias normalization) is scheduled as its own explicit roadmap item with its own review, not smuggled in as a side effect of unrelated feature work.
7. **Small, Reviewable Changes.** Changes should be sized so a reviewer can hold the entire change in mind — this is especially critical for anything touching the inventory ledger, where correctness is safety-critical.
8. **Always Preserve Backward Compatibility.** Legacy and Laravel surfaces currently coexist via `SyncLegacyPhpSession` and the root `index.php` dispatcher. No change may break this bridge, or any still-live legacy page, until that surface is explicitly and deliberately retired in its own scoped change.
9. **Ledger Integrity Is Non-Negotiable.** Every phase that touches inventory must preserve the single-writer principle (`InventoryTransactionObserver`/`InventoryStockEntryObserver` are the only mutators of `Item.quantity`/`reserved_quantity`). No roadmap item may propose or justify a shortcut around this rule.
10. **Known Gaps Are Documented, Not Silently Patched.** Where current behavior deviates from intended behavior (e.g. the Need Change deduction gap, the `Observer::deleted()` locking gap), the deviation is tracked as a scheduled roadmap item, not fixed opportunistically inside unrelated work.

---

## Development Lifecycle

Every unit of work in this project — feature, fix, or refactor — follows this sequence:

```
Architecture Review
        │   Confirm the change is consistent with ARCHITECTURE.md, or
        │   identify the deliberate, documented amendment required.
        ▼
Documentation
        │   Confirm/update BUSINESS_RULES.md expectations for the
        │   behavior being touched, before writing code.
        ▼
Planning
        │   Scope the change: objectives, affected layers, risk,
        │   test plan. For nontrivial work, this is a written plan
        │   reviewed before implementation begins.
        ▼
Implementation
        │   Controllers stay thin; business logic lives in Services;
        │   stock mutation flows exclusively through Observers.
        ▼
Testing
        │   New business-logic paths ship with a PHPUnit Feature
        │   test (RefreshDatabase), per Future Architecture Rule #4
        │   in ARCHITECTURE.md — not merely a manual smoke script.
        ▼
Review
        │   Code review against ARCHITECTURE.md coding standards and
        │   BUSINESS_RULES.md workflow correctness.
        ▼
Documentation Update
        │   ARCHITECTURE.md / BUSINESS_RULES.md / CHANGELOG.md /
        │   ROADMAP.md updated to reflect the new reality, in the
        │   same change — never as a follow-up "someday" task.
        ▼
Merge
```

No phase in the Master Development Roadmap below may skip a step in this lifecycle. In particular: **no implementation work starts without a preceding planning step**, and **no change is considered complete until documentation reflects it.**

---

## Master Development Roadmap

Each phase below lists Objectives, Deliverables, Dependencies, Risks, and Definition of Done, followed by a Complexity / Risk / Priority estimate. Phases are numbered for reference; numbering indicates logical sequence, not strictly mandatory calendar order — Phase 3 (Security/RBAC) work, for instance, may be interleaved with Phase 1 where risk warrants, but Phase 1 should not be considered complete before Phase 0 is closed.

---

### PHASE 0 — Project Foundation

**Status: Completed**

- **Objectives:** Establish a shared, authoritative understanding of the system's architecture and business rules before any further engineering work proceeds.
- **Deliverables:** `ARCHITECTURE.md`, `BUSINESS_RULES.md`, `CHANGELOG.md` (completed 2026-07-21); this `ROADMAP.md`.
- **Dependencies:** None.
- **Risks:** None outstanding — this phase is closed. Residual risk is that future changes drift from these documents without a corresponding update; mitigated by the Development Lifecycle's mandatory Documentation Update step.
- **Definition of Done:** Documentation set exists, is internally consistent, and has been reviewed and confirmed understood by the engineering lead. ✅ Met.
- **Complexity:** Low · **Risk:** Low · **Priority:** N/A (complete)

---

### PHASE 1 — Inventory Core Stabilization

- **Objectives:** Close the known integrity gap in the inventory ledger subsystem — the single most safety-critical part of the system — before building further workflows on top of it.
- **Deliverables:**
  - Fix `InventoryTransactionObserver::deleted()` to acquire `lockForUpdate()` before reversing quantity math, consistent with `creating()`/`created()`.
  - Route `PurchaseReceiptPostingService`'s activity logging through `ActivityLogService` instead of a direct `DB::table('activity_logs')->insert()` call.
  - PHPUnit Feature tests (RefreshDatabase) covering `InventoryTransactionObserver`, `InventoryStockEntryObserver`, and `PurchaseReceiptPostingService::postReceipt()` — including concurrent-mutation scenarios for the locking fix.
- **Dependencies:** Phase 0 documentation set (baseline understanding of the ledger design in `ARCHITECTURE.md` §5).
- **Risks:** Any change to the Observer touches the single most safety-critical code path in the system; an incorrect fix could silently corrupt stock quantities. Mitigation: the fix is narrowly scoped to adding locking (no logic change to the math itself), and must ship with concurrency-oriented tests before merge.
- **Definition of Done:** `deleted()` uses the same lock discipline as `creating()`/`created()`; activity logging is consistent across ledger-affecting services; new Feature tests pass and cover the previously-untested paths; `ARCHITECTURE.md` §5.3 and the Unreleased section of `CHANGELOG.md` are updated to reflect the closed gaps.
- **Complexity:** Medium · **Risk:** High (touches safety-critical ledger code) · **Priority:** Critical

---

### PHASE 2 — Need Change Workflow

- **Objectives:** Close the documented functional gap where approving a Need Change request has no inventory effect (`BUSINESS_RULES.md` §10), bringing live behavior in line with the schema's original intent (`need_change_deducted_at`).
- **Deliverables:**
  - Extend `ReportController::update()`'s `approve_need_change` path (or extract the logic into a new/existing Service, per the Controllers-are-thin standard) to create an appropriate `InventoryTransaction` (deploy/dispose, as determined by business decision — see Risks) through the standard Observer-mediated path, and to set `need_change_deducted_at`.
  - Explicit decision and documentation of quantity-resolution rules: which item, how much quantity, and what happens if insufficient stock is available at approval time (reject the approval? partially approve? queue it?).
  - Feature tests covering: successful deduction, insufficient-stock rejection, rejection path (must remain a no-op on inventory), and idempotency (approving an already-approved request must not double-deduct).
- **Dependencies:** Phase 1 (the ledger subsystem this workflow will now depend on must be stabilized and well-tested first).
- **Risks:** This is a **behavioral change to a documented gap**, not a bug fix to broken logic — the business rule for "what should happen" must be deliberately decided (see Deliverables) before implementation, since the legacy dead code (`ReportService::deductNeedChangeInventory()`) is not necessarily the correct model to port forward as-is. Risk of scope creep if this is conflated with a broader Need Change UX redesign — it should not be.
- **Definition of Done:** Approving a Need Change request reliably produces an `InventoryTransaction`, correctly updates `Item.quantity` via the Observer, sets `need_change_deducted_at`, and is fully covered by Feature tests; `BUSINESS_RULES.md` §10 is rewritten to describe the new behavior (replacing the "Functional gap" framing) and `CHANGELOG.md` records the change with a real date.
- **Complexity:** Medium · **Risk:** Medium-High (financial/stock-accuracy consequences if the deduction rule is wrong) · **Priority:** High

---

### PHASE 3 — Security and RBAC

- **Objectives:** Reduce security and authorization risk accumulated from the legacy migration: role-alias duplication, stale CSRF exemptions, and hardcoded credentials.
- **Deliverables:**
  - Consolidate role-alias normalization (e.g. `admin_maintenance` vs `maintenance_admin`) into a single canonical location (e.g. a helper/service or accessor on `User`), replacing the ~9 duplicated call sites.
  - Remove the stale `backend/api/*` CSRF exemption in `bootstrap/app.php`, now that the legacy endpoint files it referenced no longer exist in the working tree.
  - Replace hardcoded plaintext administrator credentials in `database/seeders/DatabaseSeeder.php` with an environment-variable-driven or prompted seeding approach.
  - Evaluate introducing a formal authorization layer (Laravel Policies/Gates, or a permissions package) as a candidate for replacing ad-hoc role-string comparisons — **evaluation only in this phase**; adoption would be a separate, later roadmap item if approved.
- **Dependencies:** None blocking; independent of Phases 1–2, and may be interleaved with them if a security finding is judged urgent.
- **Risks:** Role-normalization consolidation touches authorization checks across many call sites — a mistake here could over- or under-grant access. Mitigation: consolidate behind a single tested helper and update call sites one at a time with test coverage, not as a single sweeping find-and-replace.
- **Definition of Done:** One canonical role-normalization function/helper exists and all call sites use it; the stale CSRF exemption is removed and verified to have no live dependents; seeder credentials are no longer committed in plaintext; findings and decisions are reflected in `ARCHITECTURE.md` §7 and the Unreleased section of `CHANGELOG.md` is cleared of these items (moved to a dated entry).
- **Complexity:** Medium · **Risk:** Medium (authorization-sensitive) · **Priority:** High

---

### PHASE 4 — Architecture Cleanup

- **Objectives:** Continue the incremental retirement of the legacy procedural PHP surface without disrupting still-live functionality.
- **Deliverables:**
  - Inventory audit of `public/backend/*` and `public/frontend/*`: definitively classify each file as dead code (safe to remove) or still-live (bridged via `SyncLegacyPhpSession`/root `index.php`).
  - Remove confirmed dead code (e.g. `public/backend/services/ReportService.php` and other unrouted controllers/services) once Phase 2 has confirmed nothing from it needs to be ported forward.
  - For still-live legacy pages, produce a migration plan (not the migration itself) for porting each to a Laravel/Blade equivalent.
- **Dependencies:** Phase 2 (must confirm no dead-code logic, e.g. the legacy Need Change deduction method, is still needed as reference before deletion).
- **Risks:** Misclassifying a "dead" file that is actually reachable through some undocumented route would cause a live regression. Mitigation: classification must be verified by tracing actual route registration, not by inspection alone, and removals should be staged with a rollback-ready commit history.
- **Definition of Done:** A documented classification of every legacy file exists; all confirmed-dead files are removed; `ARCHITECTURE.md` §2/§1.3 is updated to reflect the reduced legacy footprint; a concrete follow-on migration plan exists for any still-live legacy page.
- **Complexity:** Medium · **Risk:** Medium (risk of misclassifying live code as dead) · **Priority:** Medium

---

### PHASE 5 — UI / UX Modernization

- **Objectives:** Continue moving user-facing pages from legacy PHP-include templates (`public/frontend/`) to Laravel Blade views (`resources/`), using the existing shared theme system and `components.js` component library for consistency.
- **Deliverables:**
  - Prioritized list of remaining legacy-rendered pages, ranked by usage frequency and by role (staff/maintenance_admin/super_admin).
  - Blade equivalents for the highest-priority pages, reusing `@extends`/`@yield`/`@section` layout inheritance and the existing `components.js` widgets (`SearchableSelect`, toast, confirm dialog, alert, `fetchJson`) rather than introducing a new frontend framework or component library.
  - Visual/behavioral parity verification against the legacy page being replaced, for each ported page.
- **Dependencies:** Phase 4 (legacy classification should exist before deciding what to port vs. delete).
- **Risks:** UI parity regressions are easy to introduce silently (a missing validation message, a broken role-conditional element). Mitigation: manual parity check plus role-based smoke test for each ported page before it replaces the legacy version.
- **Definition of Done:** Each targeted legacy page has a Blade equivalent in active use, confirmed at parity for its role-specific behavior; no new frontend framework/dependency has been introduced; `ARCHITECTURE.md` §2/§6 reflects the updated split between legacy and Laravel-native UI.
- **Complexity:** Medium-High (volume of pages) · **Risk:** Medium (regression risk, low technical risk) · **Priority:** Medium

---

### PHASE 6 — Testing

- **Objectives:** Close the sparse-test-coverage gap identified in the architecture audit, moving verification from manual `scripts/smoke_*.php` scripts to an automated PHPUnit Feature test suite.
- **Deliverables:**
  - Feature test coverage (RefreshDatabase) for `DispatchService`'s full lifecycle (create/approve/cancel/release), including the pre-release availability validation and its all-or-nothing release guarantee.
  - Feature test coverage for `PurchaseReceiptPostingService::postReceipt()`, including item-resolution branches (existing item by id, existing item by room+name match, new item creation).
  - Feature test coverage for both Observers, including the concurrency/locking behavior addressed in Phase 1.
  - A documented decision on the future of `scripts/smoke_*.php`: retire once Feature test parity is reached, or retain as a manual pre-deploy sanity check — not left ambiguous.
- **Dependencies:** Phases 1–3 (tests should target stabilized, not moving, behavior).
- **Risks:** Writing tests against currently-undocumented edge-case behavior may surface additional latent bugs; these should be logged as new Technical Debt items, not silently fixed inside the testing phase.
- **Definition of Done:** Feature tests exist and pass for all Service/Observer business logic described above; CI (or the project's equivalent verification step) runs the suite on every change; `ARCHITECTURE.md`'s "Testing note" in §2 is updated to reflect the new coverage baseline.
- **Complexity:** Medium-High · **Risk:** Low (additive, non-behavior-changing) · **Priority:** High

---

### PHASE 7 — Performance

- **Objectives:** Validate and, where necessary, improve system responsiveness under realistic load, particularly for the cached analytics/dashboard views and the ledger-heavy inventory queries.
- **Deliverables:**
  - Baseline performance measurement of dashboard/report aggregation queries (`DashboardController`, `ReportController` analytics paths) and of `inventory_transactions` queries at representative data volumes.
  - Review of the `Cache::tags(['analytics'])` invalidation strategy for correctness (no stale reads) and efficiency (no excessive invalidation) under concurrent ledger writes.
  - Index review for `inventory_transactions`, `activity_logs`, and other high-growth append-only tables, proposed via new migrations (migration authorship, not execution, is the deliverable of this phase's planning step — actual migrations are implementation work following the standard lifecycle).
- **Dependencies:** Phase 1 (ledger stabilization should precede performance tuning of the same subsystem).
- **Risks:** Premature optimization without a measured baseline could add complexity without benefit. Mitigation: this phase starts with measurement, and only proposes changes backed by data.
- **Definition of Done:** A documented performance baseline exists; any identified bottleneck has either been addressed (via a scoped, reviewed change following the standard lifecycle) or explicitly logged in the Technical Debt Queue with supporting data.
- **Complexity:** Medium · **Risk:** Low-Medium · **Priority:** Medium

---

### PHASE 8 — Deployment

- **Objectives:** Formalize a repeatable, safe deployment process for the hybrid legacy/Laravel application, appropriate for an institutional (school) production environment.
- **Deliverables:**
  - Documented deployment procedure (environment configuration, migration execution order, cache/config clearing, legacy asset handling under `public/`).
  - Environment-variable-driven configuration audit (dependent on Phase 3's seeder-credential remediation being complete).
  - Rollback procedure documentation for a failed deployment, with particular attention to migration reversibility given the append-only ledger design.
- **Dependencies:** Phase 3 (credential hygiene), Phase 6 (test suite as a deployment gate).
- **Risks:** The legacy/Laravel session bridge (`SyncLegacyPhpSession`) and the root `index.php` dispatcher are unusual enough that a generic Laravel deployment guide would be insufficient; deployment documentation must explicitly account for both stacks.
- **Definition of Done:** A written deployment runbook exists, has been executed at least once (e.g. to a staging environment) without deviation from the documented steps, and includes an explicit rollback procedure.
- **Complexity:** Medium · **Risk:** Medium (production-facing) · **Priority:** Medium

---

### PHASE 9 — Future Enhancements

- **Objectives:** Provide a holding area for capabilities beyond the current documented scope, to be individually scoped and prioritized once Phases 0–8 have landed.
- **Deliverables:** See **Future Ideas** below — this phase produces no implementation deliverables at this time, only a maintained backlog.
- **Dependencies:** Varies per idea; each would need its own dependency analysis when promoted out of this phase.
- **Risks:** Primary risk is scope creep — items here must not be pulled forward into earlier phases without deliberately re-sequencing this roadmap.
- **Definition of Done:** Not applicable until an item is promoted to its own phase with its own Objectives/Deliverables/Dependencies/Risks/DoD.
- **Complexity:** N/A · **Risk:** N/A · **Priority:** Low (by definition — this is the backlog, not the active plan)

---

## Milestones

| Milestone | Marks the completion of | Significance |
|---|---|---|
| **M0 — Documentation Baseline** | Phase 0 | Architecture, business rules, changelog, and roadmap exist and are authoritative. *(Achieved 2026-07-21.)* |
| **M1 — Ledger Integrity Certified** | Phase 1 | The single most safety-critical subsystem has no known locking gaps and has automated test coverage. |
| **M2 — Need Change Fully Functional** | Phase 2 | Every documented workflow that claims to affect inventory actually does so; no silent no-op approval paths remain. |
| **M3 — Security Debt Cleared** | Phase 3 | No hardcoded credentials, no stale CSRF exemptions, single canonical role-normalization path. |
| **M4 — Legacy Footprint Minimized** | Phase 4 | Dead legacy code removed; remaining live legacy surface has a concrete retirement plan. |
| **M5 — UI Consolidated** | Phase 5 | Majority of user-facing pages served from Laravel/Blade rather than legacy PHP includes. |
| **M6 — Automated Verification Baseline** | Phase 6 | Feature test suite is the primary correctness gate; manual smoke scripts are supplementary or retired. |
| **M7 — Performance Validated** | Phase 7 | Dashboard/ledger performance is measured and within acceptable bounds at realistic data volumes. |
| **M8 — Production-Ready Deployment Process** | Phase 8 | A documented, exercised deployment and rollback runbook exists. |
| **M9 — Legacy-Free Milestone (aspirational)** | Beyond Phase 4/5 completion | `public/backend/*` and `public/frontend/*` fully retired; system is Laravel-only. This is the practical realization of the Project Vision. |

---

## Known Risks

1. **Ledger correctness risk.** Any defect in Observer logic (locking, validation, or math) has direct, real-world consequences on physical inventory accuracy. All ledger-touching work must be treated with the highest review scrutiny, per Phase 1.
2. **Legacy/Laravel session bridge fragility.** `SyncLegacyPhpSession` is a load-bearing piece of infrastructure with no formal test coverage; a regression here could silently break authentication for either stack without an obvious error signal.
3. **Legacy dead-code misclassification.** Removing a file believed to be dead code (Phase 4) could cause a live regression if an undocumented route or include still reaches it.
4. **Migration-order fragility.** Because `database/migrations/*` is the sole source of schema truth and the ledger is append-only, any future migration must be additive and carefully sequenced — a destructive migration against `inventory_transactions` or `activity_logs` would destroy audit history.
5. **Role-alias drift.** Until Phase 3 consolidates role normalization, any new code path that checks role strings without accounting for known aliases (e.g. `admin_maintenance`) risks silently under- or over-authorizing an action.
6. **AI-contributor drift from documented rules.** Because this project is explicitly designed to be maintained in part by AI contributors, there is a standing risk that generated code satisfies a literal instruction while violating an architectural or business rule not restated in that instruction. Mitigation: this roadmap and its companion documents are to be treated as binding context for every contribution, not optional background reading.
7. **Test-coverage gap during the transition window.** Until Phase 6 lands, most correctness verification remains manual, which means regressions in untested paths (most of the system, at present) may not be caught before reaching users.

---

## Technical Debt Queue

### Critical
- `InventoryTransactionObserver::deleted()` does not acquire `lockForUpdate()` before reversing quantity math — a real, narrow race-condition exposure in the most safety-critical subsystem. *(Phase 1)*
- Need Change approval has no inventory effect despite the schema (`need_change_deducted_at`) and UI implying it does — a functional regression from incomplete migration. *(Phase 2)*

### High
- Role-alias normalization (`admin_maintenance` vs `maintenance_admin`) is duplicated across roughly nine call sites instead of centralized. *(Phase 3)*
- Hardcoded plaintext administrator credentials in `database/seeders/DatabaseSeeder.php`. *(Phase 3)*
- Sparse automated test coverage — only a `UserFactory` exists; most verification relies on manual `scripts/smoke_*.php` scripts. *(Phase 6)*

### Medium
- Stale `backend/api/*` CSRF exemption in `bootstrap/app.php`, referencing legacy endpoint files already removed from the working tree. *(Phase 3)*
- `PurchaseReceiptPostingService` logs activity via direct `DB::table('activity_logs')->insert()` instead of `ActivityLogService`, inconsistent with `DispatchService`. *(Phase 1)*
- Dead legacy code under `public/backend/services/` (e.g. `ReportService.php`) and other unrouted legacy controllers remain in the working tree. *(Phase 4)*

### Low
- No `FormRequest` classes exist; input validation is performed inline in controllers via `Validator`/`$request->validate()`. Not currently a correctness problem, but a consistency/maintainability improvement candidate.
- No Laravel Policy/Gate layer or permission package is in use; authorization is ad-hoc role-string comparison. Evaluation deferred to Phase 3; adoption (if any) would be its own future item.

---

## Future Ideas

*The following are documented for future consideration only. None are scheduled, scoped, or approved for implementation. They must not be implemented without first being promoted through Phase 9 into a properly scoped phase of their own, with Objectives/Deliverables/Dependencies/Risks/Definition of Done.*

- Formal notification delivery beyond in-app (e.g. email/SMS alerts for critical low-stock or overdue-repair conditions).
- A reporting/export capability (e.g. CSV/PDF export of dashboards) for administrative record-keeping outside the system.
- A mobile-friendly or dedicated mobile view for staff submitting damage reports from the field.
- A supplier/vendor management module (formalizing the currently free-text supplier identity recorded on purchase receipts).
- A Policy/Gate-based authorization layer to replace ad-hoc role-string checks, if Phase 3's evaluation recommends it.
- A "replacement" concept promoted from its current composed deploy/dispose transaction pair (per `BUSINESS_RULES.md` §6) into a first-class tracked entity, if operational reporting needs prove this necessary.
- Multi-school / multi-tenant support, if the system's scope ever expands beyond a single institution.
- Real-time dashboard updates (e.g. via WebSockets/broadcasting) instead of the current cached, request-driven analytics model.

---

## Immediate Next Step

**Phase 1 — Inventory Core Stabilization** is the next phase to be implemented.

Rationale: the inventory ledger is the most safety-critical subsystem in the system (per `ARCHITECTURE.md` §5), it already has a known, documented, narrow correctness gap (the `Observer::deleted()` locking omission), and every later phase — Need Change (Phase 2), Testing (Phase 6), Performance (Phase 7) — either directly depends on this subsystem or builds tests/behavior on top of it. Stabilizing it first minimizes the chance that later work has to be redone or re-verified against a shifting foundation.

This document does not implement Phase 1. Per the Development Lifecycle, Phase 1 begins with its own Architecture Review and Planning step before any code is touched.
