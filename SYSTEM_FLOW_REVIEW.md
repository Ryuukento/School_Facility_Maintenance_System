# SYSTEM_FLOW_REVIEW.md

**Type:** Read-only workflow/UX audit ("Phase 4 — System Flow Optimization"). No code, database, or business-rule changes were made to produce this document.

**Method:** Direct inspection of `ARCHITECTURE.md`, `BUSINESS_RULES.md`, `ROADMAP.md`, `CHANGELOG.md`, `AI_INSTRUCTIONS.md`, `routes/web.php`, `public/frontend/includes/header.php`/`sidebar.php`, and `resources/views/*`, plus five delegated deep-reads of the ~24,000 lines of legacy page code in `public/frontend/pages/*.php`, cross-referenced against the live Laravel controllers/services.

**A note on prior documentation:** several findings below (the `need_change_status` "rejected" enum gap, and `inventory.php`'s edit-item modal still submitting `quantity`) are **already tracked** in `CHANGELOG.md`'s `[Unreleased]` section. This review corroborates them with concrete evidence and workflow impact rather than presenting them as new discoveries; they are marked *(known, tracked)* below.

---

## 1. Authentication (Login, Logout, Role Switching, Session)

### Current Flow
Single page (`public/frontend/pages/index.php`) toggles Sign In / Forgot Password / Register via JS. Login writes session state in three places at once (Laravel `auth_user`, legacy flat keys, raw `$_SESSION`) so Laravel `/api/*` and legacy pages share one identity. Role is a single DB column fixed at login; JS redirects purely on `user.role` to one of three dashboards. Registration is self-service (lands as `status='pending'`, role `user`) with a department **silently auto-assigned** (first active department, no user input). Forgot-password is a one-way "notify admin" stub; the actual admin-side fix is a manual temporary-password reset on the Users page. Logout is a two-step hybrid: JS calls `POST /api/auth/logout` (best-effort, failure silently ignored) then always hard-redirects to legacy `logout.php`, which destroys the native session.

### Strengths
- Centralized, consistent session bridging (one function, not scattered).
- Real server-side rate limiting on login (not just client-side theater).
- `force_profile_update` gate correctly boxes in admin-created accounts until they set their own password.

### Weaknesses
- The "Username" field is keyed/labeled `email` end-to-end (HTML id, JS var, request key) despite accepting either — a naming trap for future maintainers.
- Self-registration silently assigns a department with no user input or confirmation.
- A full self-service password-reset backend (`AuthController::forgotPasswordReset`, 6-digit code + 15-min expiry) exists and is **fully unused by the frontend** — dead code, half-migrated feature.
- `header.php`'s comment claims logout is "handled via server-side logout.php... NOT via JS," but `main.js` actually calls the API first — stale/inaccurate in-code documentation.
- If the `/api/auth/logout` fetch fails, the user is still logged out but with **no `LOGOUT` activity-log entry** — an audit gap directly caused by the swallow-and-continue logout design.
- No rate limiting or CAPTCHA on `/api/auth/register`, unlike login — open to pending-account spam.

### Bottlenecks
- Every password reset for every user requires a super_admin to manually open Users, find the card, and type a temp password — no bulk action, no automated delivery.
- New registrants always land in the generic pending bucket regardless of known role — every hire needs a synchronous admin action even when the role is already known offline.

### Missing Features
- No true self-service password reset UI (code already exists server-side, unused).
- No department selection/confirmation at registration.
- No visible per-user login history/audit on their own account page.
- No anti-automation on public registration.

### Recommended Improvements
- Rename the username field consistently; wire up or delete the dead reset-code flow; let registrants pick/confirm department; fix the stale logout comment and make the LOGOUT audit write independent of the API call's success; add register-endpoint rate limiting.

### Priority: **Medium-High**

---

## 2. Dashboard (Information hierarchy, Statistics, Notifications, Navigation)

### Current Flow
Login redirects to one of three role-specific dashboards (`dashboard.php` for super_admin/generic, `maintenance-dashboard.php` for maintenance_admin, `staff-dashboard.php` for maintenance_staff) — all three sharing one visual pattern: clickable KPI cards → doughnut/bar charts → a "Recent Reports" table with a link to `reports.php`. A fourth file, `super-admin-dashboard.php`, also exists.

### Strengths
- Consistent card→filtered-list interaction pattern across all three live dashboards.
- Genuinely role-scoped "recent reports" (department-scoped for admins, assignee-scoped for staff).
- `staff-dashboard.php` adds a useful low/out-of-stock inventory widget with CSV export not found elsewhere.

### Weaknesses
- **`maintenance-dashboard.php`'s "Low Stock" stat card is permanently broken** — the DOM target is never populated by its JS (a copy-paste regression versus the working version in `dashboard.php`).
- Dead code: a full "Created vs Completed" trend chart is built in `maintenance-dashboard.php` JS but has no matching `<canvas>` in the markup — never renders; a related `avgDays` value is computed but never displayed anywhere.
- `dashboard.php` (2,250 lines) is bloated with an entire Building/Room creation + building-print subsystem that logically belongs on `buildings-overview.php`, not the dashboard.
- All three dashboards independently re-implement (copy-pasted, with drift) the same month/year persistence, badge helpers, Chart.js config, and card-click wiring — the direct root cause of the low-stock and dead-chart bugs above.
- Dashboards double-fetch: stats come from a dedicated endpoint **and** a full `?per_page=200` reports pull merged client-side, because the stats endpoint alone is insufficient — doubling load on every dashboard view.

### Bottlenecks
No caching/pagination on "recent"/"today" queries; every dashboard visit re-scans reports client-side across three separate, near-identical implementations.

### Missing Features
No quick actions (create report, assign to me) from any dashboard despite it being the post-login landing page; no in-dashboard surfacing of unread notifications beyond the generic header bell.

### Recommended Improvements
Fix the low-stock stat wiring; delete the dead trend-chart/avgDays code (or wire it up properly); extract Building/Room management out of `dashboard.php` into `buildings-overview.php`; add a shared `dashboard-common.js` for the duplicated month/badge/chart logic; add quick-action buttons.

### Cross-cutting: `super-admin-dashboard.php` verdict (see §8 Navigation) — dead/orphaned, superseded by `dashboard.php`.

### Priority: **Medium**

---

## 3. Maintenance Reports (Create Report, Assign Report, Accept Job, Update Progress, Complete, History)

### Duplication verdict (answered first — it explains most of the weaknesses)
`reports.php` + `create-report.php` + `maintenance-report-detail.php` are the **live** pages for all three maintenance roles. `maintenance-reports-list.php` literally contains the comment *"Temporary consolidation: use a single All Reports page to avoid duplicate pages"* and immediately redirects to `reports.php` — its ~470 lines below that are dead. `maintenance-create-report.php` has no route and no live link anywhere — reachable only by guessing the URL. `report-detail.php` is worse: it's an accidental concatenation of two unrelated pages (a generic analytics viewer + a maintenance-report-detail UI, each with its own `session_start()`/header include) — if ever actually hit, it double-renders. `edit-report.php` is a fourth, further-diverged, unlinked orphan. **This is unfinished consolidation debt, not an intentional role split.**

### Current Flow
Create (`create-report.php`) → `POST /api/reports` → notifies admins in-app + emails super_admins. Triage: only super_admin/maintenance_admin can set "Assigned" from `maintenance-report-detail.php`; assignee picker is role-scoped. **There is no "Accept Job" step at all** — an assignee simply changes status themselves when ready; no acknowledge/decline action exists anywhere. Progress: maintenance_staff can only set `in_progress`/`completed` server-side, but complete requires a proof-of-completion image upload. Completed/closed reports can be "Reopened" by admins back to `in_progress`. History is limited to four timestamp fields — no status-change audit trail.

### Strengths
Solid server-side role/status transition matrix; completion-proof-photo requirement is a good accountability control; rich filtering/CSV export on `reports.php`.

### Weaknesses
- **Frontend/backend contract bug**: the status dropdown offers `maintenance_staff` a "Closed" option that the backend explicitly rejects (403) — a guaranteed failed-submission trap for that role.
- **The "Comment" field on every status update is captured by the UI and silently discarded** — never persisted anywhere, no status-history table exists.
- No accept/decline-assignment step.
- Three dead/orphaned duplicate pages (see verdict above), one of which (`report-detail.php`) is a landmine reachable via a real Laravel route.

### Bottlenecks
Every new report must pass through a super_admin (or maintenance_admin) for first assignment — no bulk-assign, no inline status changes from the list view (every transition requires a full navigation to the detail page).

### Missing Features
Accept/decline workflow; persisted status-change comments/audit trail; overdue auto-escalation notification (only a client-side dashboard filter exists); inline quick-actions from `reports.php`.

### Recommended Improvements
Fix the staff status-option mismatch; persist status comments into a lightweight history table and render a real timeline; add an explicit Accept/Decline step; delete or hard-redirect the three dead pages and repair or remove the broken `report-detail.php`; log every status transition to the activity log (not just Need Change).

### Priority: **High**

---

## 4. Need Change Workflow (Request, Approval, Inventory deduction, Installation, Completion)

### Current Flow
Request is **only** possible at report-creation time via a checkbox + item picker on `create-report.php` — there is no way to attach or edit a Need Change request once the report exists. Approval is a dedicated card on `maintenance-report-detail.php` but **rendered and enforced for `super_admin` only** — `maintenance_admin` cannot approve/reject despite having authority over every other part of the report lifecycle. `NeedChangeService::approve()` is well-engineered (locked, idempotent, stock-validated, routes through the ledger). **After deduction, the workflow simply stops** — there is no "Installation" or "Completion" state for the replacement part; the UI shows a permanent "DEDUCTED" badge with nothing tracking whether the part was actually installed, and a report's own completion is never cross-validated against its Need Change status.

### Strengths
`NeedChangeService` itself (locking, idempotency, ledger-routed mutation) is genuinely well built; status is surfaced with clear badges on both the list and detail views.

### Weaknesses
- Single-role (`super_admin`-only) approval gate is inconsistent with `maintenance_admin`'s authority everywhere else.
- `need_change_quantity` exists in the schema/API but has **no UI control** — always defaults to 1.
- *(known, tracked)* `reject_need_change` sets `need_change_status = 'rejected'`, a value **not present** in the column's declared enum (`pending`/`approved`/`deducted`/`failed`) — already flagged in `CHANGELOG.md [Unreleased]`; this review corroborates it end-to-end (UI writes it, guard logic on `maintenance-report-detail.php` reads the literal string back) and confirms it as a live functional risk, not just a schema nit.
- No notification is sent to the requester/assignee on approve or reject.

### Bottlenecks
Every replacement decision funnels through one role (super_admin), even on reports a maintenance_admin otherwise fully owns.

### Missing Features
Post-approval "Installed"/"Completed" state; mid-lifecycle request editing; quantity input; approve/reject notification to the requester.

### Recommended Improvements
Resolve the enum gap (already tracked); extend approval to `maintenance_admin` or document why not; add an "Installed" state gating report completion; add a quantity field; notify on approve/reject.

### Priority: **High** (enum gap + single-role bottleneck), **Medium** (missing installation/completion sub-step, quantity field)

---

## 5. Inventory (Purchase Receipt, Manual Adjustment, Deployment, Dispatch, Transactions, Low Stock, Inventory History)

### Current Flow
**Purchase Receipt → stock** is the only fully working intake path: create receipt → add line items → Post → `PurchaseReceiptPostingService` posts one `adjustment` transaction per line through the ledger. Minimum ~6 clicks/3 page loads, with no link from the posted receipt back to the affected item(s) — confirming stock requires a manual detour to `inventory.php`.

**Manual Adjustment**: two fully-built, audited backend endpoints exist (`ItemController::adjustStock`, `InventoryStockController::adjust`) — **neither has any UI caller**. Instead, `inventory.php`'s "Edit Item" modal is used as a de facto adjustment path, writing `quantity`/`status` via raw SQL with no reason field and far less accountability than the real endpoints — *(known, tracked: CHANGELOG.md already flags this modal still submits `quantity`)*.

**"Inventory Entry" (the page's primary intake button) is fully broken** — its JS POSTs to `/api/inventory-stock/entries`, but no matching POST route exists in `routes/web.php`; every submission 405s. Consequently, **"Inventory Entry History" can never show anything**, since Purchase Receipt postings write only to `inventory_transactions` while that history panel reads exclusively from `inventory_stock_entries` — two intake mechanisms populate two tables that are never reconciled.

**Transactions**: no ledger list page exists; `inventory-transactions.php` is a 10-line dead redirect stub. Per-item history is only available via a modal.

**Low Stock**: passive filter/chip only on the main Inventory page; a previously-built "status summary quick-filter cards" component is dead code (target DOM element missing, function never called). A real Low Stock view exists only on the separate `inventory-reports.php`.

**Dispatch/Deployment** (delegated deep-read): create → approve → release, each a full page reload; `receiver_user_id` is fully supported server-side and rendered in the UI but **never actually sent** by the release form, so "Receiver" is permanently blank for every dispatch. No stock is reserved between approval and release, so two approved dispatches for the same scarce item can race and fail unpredictably at release. Deployment Tracking's rows show `dispatch_code` as inert text with no link back to the dispatch detail page, forcing manual copy/search.

**Replacement duality**: `replacement-request.php` (bound to a failed Repair Request, creates+approves+releases a real Dispatch) and `replacement-tracking.php` (a read-only view over `maintenance_reports.need_change_*`) are **two entirely disjoint systems that never cross-reference each other** — a replacement fulfilled via one path is invisible in the other.

### Strengths
Purchase Receipt draft→posted flow is clean and correct; `inventory-reports.php` is genuinely complete and well-built; `PurchaseReceiptPostingService`/`InventoryAdjustmentService`/`DispatchService` all correctly implement the single-writer/ledger/locking architecture on the backend; dispatch role-gating matches API-level enforcement; Deployment Tracking already does the item↔receipt↔room join work in one table.

### Weaknesses
See Current Flow — the broken Inventory Entry button, the Edit-Item-as-adjustment workaround, the two unused adjust-stock endpoints, dead low-stock summary cards, the receiver-field gap, no approval-time stock reservation, no dispatch↔deployment cross-link, and the disjoint replacement systems are the concrete findings. Additionally: `maintenance_staff` is labeled "read-only" on the Inventory page yet can create and post Purchase Receipts — a side door into the same ledger; `suppliers-manage.php` has no nav link anywhere and no role gate, reachable only by guessing the URL, and both Purchase Receipts and Inventory Entry take supplier names as free text with no dedupe against it; category default low-stock thresholds are frozen behind hidden-but-still-submitted form fields.

### Bottlenecks
Purchase-receipt-to-visible-stock requires a manual cross-page detour; dispatch approve→release has no stock hold, so failures surface at the worst time; tracing "where is this item" requires manually cross-referencing Deployment Tracking against Dispatches.

### Missing Features
A real Manual Adjustment UI; a real transactions/ledger list page; reconciled intake history; receiver capture on dispatch release; stock reservation at approval; cross-linking between the two replacement systems; supplier autocomplete/dedupe.

### Recommended Improvements
Fix or remove the broken Inventory Entry path; build one real Adjust Stock action wired to the existing audited service and strip quantity-editing out of "Edit Item"; reconcile the two intake-history tables (or make Purchase Receipts also write stock entries); restore/remove the dead low-stock cards; wire `receiver_user_id` into the release form; reserve stock at approval; link Deployment Tracking rows to Dispatch detail; resolve the `maintenance_staff` read-only contradiction; surface Suppliers Management in the nav and receipt form.

### Priority: **Critical** (broken primary intake action, no real manual-adjustment path, two adjust-stock endpoints built and unused); **High** (dispatch receiver gap, no approval-time reservation, disjoint replacement systems); **Medium** (dead low-stock cards, suppliers orphaned, category-threshold fields)

---

## 6. Users (Permissions, Role transitions, User management)

### Current Flow
`users.php` (super_admin only) shows every non-super_admin user as a card grid. Two creation paths converge here: self-registration lands as **Pending** with Approve/Reject buttons; admin-created via "Add New User" lands **Active** immediately with `force_profile_update=1`. Approve/Reject/Deactivate/Activate/Reset-Password round out lifecycle actions. Activity Logs (super_admin/maintenance_admin) offer genuine filter/search/pagination, not a raw dump.

### Strengths
Solid role-transition guardrails (no self-deactivation, no touching super_admin via normal endpoints, idempotency checks); every action writes a consistent activity log entry; department-aware role labels; genuinely filterable/paginated Activity Log.

### Weaknesses
- **The Approve modal offers "Admin" as a role choice, but the backend validator only accepts `maintenance_admin`/`maintenance_staff`** — choosing it fails server-side with no graceful UI handling.
- **`GET /api/users` has no role middleware at all** — any authenticated user (any role) can call it directly and read the full name/email/role/status roster; access control exists only as a frontend page redirect, not an API guard. This is the clearest concrete security gap found in this review.
- **No user-edit endpoint exists** — role/department/designation cannot be changed post-creation; the only "fix" for a wrong role/department is Reject+recreate (pending only) or nothing at all (already-active users).
- Reject is a permanent hard delete, one confirm click, no undo, no soft-delete.
- Reset Password requires the admin to manually type and out-of-band relay a temp password — no generation, no email delivery.
- super_admin accounts are excluded from the list entirely — even a super_admin has no in-app visibility into peer super_admin accounts.

### Bottlenecks
Any role/department correction requires reject-and-recreate; a mass password-reset event has no bulk path and must be done one card at a time.

### Missing Features
Role/department edit; bulk actions; super_admin visibility; CSV export on Activity Logs; reason/notes captured on reject/deactivate.

### Recommended Improvements
Fix the Approve-modal role options to match the backend; add role middleware to `GET /api/users`; add a real Edit User capability; convert Reject to soft-delete or log full pre-delete detail; add CSV export to Activity Logs.

### Priority: **High** (Approve-modal bug and unguarded `GET /api/users` are both concrete, fixable, and security-relevant)

---

## 7. Notifications (Who receives them, When, Duplicated?, Missing?)

### Current Flow
Two triggers exist: new report submission (notifies admin-role recipients + emails super_admins) and report (re)assignment (notifies the new assignee) — **but the assignment trigger currently only exists in the legacy `ReportService.php` code path**, not in the live Laravel `ReportController::update()`, which handles all status/assignment transitions with **no notification-creation code at all**. As legacy files continue to be deleted during the ongoing migration, assignment notifications are at risk of silently disappearing entirely. Reading/deleting works through the header bell dropdown (top 10 unread) and a full `notifications-center.php` list (mark read / delete, capped at 100, no pagination).

### Strengths
Backend Notification model/controller tolerate schema drift gracefully; a working `read-all` endpoint exists server-side; unread counts are returned alongside the list to keep badges accurate.

### Weaknesses
- **"Mark all read" button is unreachable** — `notifications-center.php`'s JS wires a click handler to an element ID that doesn't exist in the page's markup; the backend endpoint works, there's simply no button.
- **Notifications aren't clickable through to their report** — `report_id` is already fetched by the API but never used to build a link in the list UI.
- **No notification when a report is completed/closed** — the original requester has no way to learn their issue was resolved except by manually checking the reports list. This is the single biggest gap for the system's core "close the loop" purpose.
- No inventory-event notifications (e.g., low-stock crossing) despite the data being surfaced elsewhere on dashboards.
- Assignment notifications only firing from the legacy path (see Current Flow) is a real regression risk during migration.

### Bottlenecks
Hard 100-row cap with no pagination; every mark-read/delete triggers a full list re-fetch instead of an in-place DOM update.

### Missing Features
Completion/closure notification to the requester; clickable notification rows; low-stock/inventory alerting; bulk delete.

### Recommended Improvements
Add the missing `#mark-all-read-btn` element (trivial — everything else already works); make rows link to their report; add a completion/closure notification in the live `ReportController::update()` and port the assignment-notification logic there before the legacy path disappears; add simple pagination.

### Priority: **High** — the missing completion notification is a core workflow gap, and the Laravel/legacy notification-trigger split is a live regression risk tied to an active migration in progress.

---

## 8. Navigation (Find everything? Duplicated menus? Clicks? Dead pages? Broken flow?)

### Current Flow
The live sidebar (`public/frontend/includes/sidebar.php`) is a single flat list: Dashboard, All Reports, Inventory (staff/admin), Damage Reports, Repair Requests, Dispatches (admin only), Inventory Reports (admin only), Purchase Receipts (admin only), Deployment Tracking (admin only), an "Analytics" section (admin only), User Management (super_admin only), Activity Logs (admin/super_admin), then an "Account" section (Account, Log Out). Links mix two addressing styles — direct `public_url('/frontend/pages/X.php')` paths and Laravel route aliases like `/dispatches` — functionally equivalent but stylistically inconsistent.

### Strengths
Role-aware nav-item visibility is enforced correctly (hidden items are truly gated, not just CSS-hidden, per the delegated agents' cross-checks against server-side role enforcement).

### Weaknesses
- **`$showCreateReport` is computed by role on `sidebar.php` and then immediately hardcoded to `false` one line later** — the "Create Report" nav link is unconditionally hidden for every role, regardless of permission. The only way to reach `create-report.php` is via the "+ New Report" button embedded inside `reports.php` itself.
- **`resources/views/*.blade.php` (18 files) are fully dead code** — confirmed via `grep` across `app/` and `routes/`: no controller or route anywhere calls `view()`. These Blade templates (dashboard, reports index/create/show, inventory index, admin/users, notifications, profile, auth/login, layouts, includes) are pure unused surface area — a strong signal that an earlier Blade-based UI effort was abandoned in favor of the legacy PHP pages, without ever being removed.
- **Several legacy pages are dead or broken but still exist on disk**, discovered across modules: `super-admin-dashboard.php` (orphaned, superseded by `dashboard.php`, uses stale session/path conventions), `profile.php` (orphaned, references a deleted legacy logout endpoint — would 404 if reached), `maintenance-reports-list.php` (redirect-only shell), `maintenance-create-report.php` and `edit-report.php` (unlinked orphans), `report-detail.php` (broken concatenation of two unrelated pages, reachable via a real route), `inventory-transactions.php` (10-line dead redirect stub).
- **`suppliers-manage.php` has no nav entry anywhere** and no role gate — reachable only by typing the URL directly.
- **No top-level nav entry for Buildings/Rooms** (`buildings-overview.php`) — only reachable via a dashboard summary card, not the sidebar, despite being a substantial (1,403-line) management surface.
- **Stray filesystem clutter in publicly-servable directories**: backup files sitting alongside live code (`public/frontend/pages/buildings-overview.php.bak`, `inventory.php.inventorybak`, `assets/js/main.js.headerbak`, several `.bak` CSS files, `includes/header.php.bak`/`.headerbak`, `includes/sidebar.php.sidebarbak`), and a `public/frontend/guide1/` folder containing 7 internal sidebar-redesign planning markdown documents — all sitting in a directory the web server can serve directly.
- The flat, ungrouped sidebar list (14 items, no collapsible sections beyond an "Analytics" and "Account" label) mixes report-workflow pages, inventory-workflow pages, and admin pages with no visual hierarchy — a user has to scan the whole list to find, e.g., Purchase Receipts vs. Inventory Reports vs. Deployment Tracking, three separately-named but closely related inventory-admin pages.
- One security-adjacent note surfaced incidentally: while reading `profile.php`, one delegated review agent encountered text embedded in that file's content styled to look like a system instruction block (referencing a "computer-use" tool). The agent correctly identified it as content, not a genuine instruction, and ignored it. **This is worth a follow-up look** at `profile.php` and its includes for injected/anomalous content, independent of this workflow review — flagging it here rather than acting on it.

### Bottlenecks
No search/command-palette across the ~40-page surface; finding a specific admin page relies entirely on recognizing its exact sidebar label.

### Missing Features
Nav entries for Suppliers and Buildings/Rooms; a grouped/collapsible sidebar structure separating Reports / Inventory / Admin sections; removal of dead-code pages so "which page is real" isn't a research question.

### Recommended Improvements
Un-hide (or deliberately remove) the Create Report link with an honest role check instead of a hardcoded `false`; delete the confirmed-dead `resources/views/*.blade.php` set or wire it up if there's a future intent to use it; delete or clean up the orphaned/dead legacy pages and stray backup/`.bak` files; move or remove `guide1/`'s internal docs out of the public web root; add nav entries for Suppliers and Buildings; group the sidebar into labeled sections (Reports, Inventory, Admin, Account).

### Priority: **High** — the hardcoded-hidden Create Report link and the volume of dead/orphaned pages (some reachable via real routes and actively broken) directly undermine "can users find everything" and pose real maintenance risk; the stray files in public directories are a lower-severity hygiene/minor-disclosure item.

---

## Top 20 Improvements Ranked by Impact

| # | Improvement | Module | Why it ranks here |
|---|---|---|---|
| 1 | Fix or remove the broken "Inventory Entry" intake button (missing POST route) | Inventory | Primary action, fully non-functional, visible to two roles |
| 2 | Build a real Manual Adjustment UI on the already-audited backend endpoints; stop using "Edit Item" for silent quantity changes | Inventory | Core ledger-integrity/accountability gap |
| 3 | Add completion/closure notification to the original report requester | Notifications | Core "close the loop" purpose of the whole system is currently missing |
| 4 | Add role middleware to `GET /api/users` | Users | Unauthenticated-by-role data exposure of the full user roster |
| 5 | Fix the Approve-modal role mismatch (offers "Admin," backend rejects it) | Users | Concrete broken action on a sensitive privilege-escalation path |
| 6 | Port assignment notifications into the live `ReportController::update()` before the legacy path is fully removed | Notifications | Active migration risk of silently losing a whole notification type |
| 7 | Fix staff status-option mismatch ("Closed" offered, backend 403s) | Maintenance Reports | Guaranteed failed submission for a whole role |
| 8 | Un-hide the hardcoded-`false` "Create Report" sidebar link | Navigation | A primary action is invisible in the main nav for every role |
| 9 | Persist status-change comments into a real audit trail | Maintenance Reports | No accountability trail for why a report's state changed |
| 10 | Add stock reservation at dispatch approval time | Inventory (Dispatch) | Prevents unpredictable release-time failures on scarce items |
| 11 | Wire `receiver_user_id` into the dispatch release form | Inventory (Dispatch) | Silent audit-trail gap on an already-built field |
| 12 | Reconcile Need Change approval authority (super_admin-only vs. maintenance_admin's authority elsewhere) | Need Change | Removes a single-role bottleneck inconsistent with the rest of the workflow |
| 13 | Resolve the `need_change_status` "rejected" enum gap *(already tracked)* | Need Change | Confirmed live functional risk on the reject path |
| 14 | Delete or repair the dead/broken duplicate report pages (`maintenance-reports-list.php`, `maintenance-create-report.php`, `edit-report.php`, `report-detail.php`) | Maintenance Reports / Navigation | Maintenance risk; one is an actively broken page reachable via a real route |
| 15 | Add a real "Installed/Completed" state for Need Change after inventory deduction | Need Change | Workflow currently has no end-state past "stock left the shelf" |
| 16 | Add a user-edit capability (role/department/designation) | Users | Removes the reject-and-recreate workaround pattern |
| 17 | Fix the broken "Mark all read" button and make notification rows link to their report | Notifications | Two small, high-visibility, already-half-built fixes |
| 18 | Cross-link or unify the two disjoint replacement systems (`replacement-request.php` vs. `replacement-tracking.php`) | Inventory | Currently two invisible-to-each-other views of "an item got replaced" |
| 19 | Delete confirmed-dead `resources/views/*.blade.php` (18 files) and stray `.bak`/backup files and `guide1/` docs from public directories | Navigation / hygiene | Reduces confusion about which code is live; minor info-disclosure cleanup |
| 20 | Add an explicit Accept/Decline-assignment step for Maintenance Reports | Maintenance Reports | Currently no acknowledgment step exists between assignment and work starting |

---
