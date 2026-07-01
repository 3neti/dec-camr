# Dashboard + Reports UX Walkthrough

## Purpose

This walkthrough captures the current Dashboard and Reports operator experience after UI-001 through UI-014 and before any Live Operations workspace implementation.

It is intended to answer three questions:

1. Is the current Dashboard useful as an operator console foundation?
2. Is the current Reports surface clear enough for analyst workflows?
3. Should Live Operations proceed by extending the current patterns, or should those patterns be reconsidered first?

## Setup Used

Commands executed:

```bash
php artisan migrate:fresh --force
php artisan camr:seed-profile --profile=demo
php artisan camr:simulate --profile=demo --duration=10m --speed=real --anchor="2026-07-01 08:00:00"
npm run build
```

Notes:

- The application was already available at `http://dec-camr.test/`.
- A separate terminal was already running `npm run dev`.
- `php artisan serve` was not used because the local `.test` host was already active.

## Review Users Used

Primary operator review user:

```text
Username: ops_admin_demo
Password: Demo@1234
```

Analyst review user:

```text
Username: analyst_demo
Password: Demo@1234
```

## Screenshots

Screenshots were not captured in this session.

Reason:

- Interactive browser automation was not available from this execution environment.
- The walkthrough is therefore based on:
  - live authenticated Inertia payloads from `http://dec-camr.test/`,
  - current seeded/simulated data,
  - implemented Vue component structure,
  - live export/header verification.

The screenshots directory was prepared but remains empty:

```text
docs/ui/screenshots/
```

## 1. Login

Current behavior:

- The login page remains legacy username-based.
- The login POST remains `/login-user`.
- Successful login redirects to `/site`.
- This preserves the migration decision that `/site` is the CAMR operator home, while `/dashboard` remains a compatibility route.

What works:

- Landing on `/site` is operationally safer than landing directly on `/dashboard` because it keeps the legacy maintenance/home flow intact.
- Username-based login preserves operator familiarity.
- The flow is simple and matches the expected CAMR contract.

What is confusing:

- None at the workflow level.
- The existence of both `/site` as operator home and `/dashboard` as the operator console requires discipline in future navigation decisions.

What should be reused later:

- Preserve the current login contract.
- Preserve `/site` as the main operator home target unless explicitly changed by architecture decision.

## 2. Dashboard Walkthrough

Route reviewed:

```text
/dashboard
```

Component foundation reviewed:

- `resources/js/pages/Dashboard.vue`
- `resources/js/components/operator/KpiCard.vue`
- `resources/js/components/operator/AttentionList.vue`
- `resources/js/components/operator/RecentTelemetryList.vue`
- `resources/js/components/operator/QuickActionGrid.vue`

Live seeded/admin dashboard data observed:

- Gateway total: `24`
- Gateway online: `24`
- Gateway stale: `0`
- Gateway offline: `0`
- Meter total: `60`
- Meter active: `60`
- Meter online: `60`
- Pending updates total: `17`
- Telemetry recent readings: `60`
- Report readiness: all five families `ready`

### 2.1 Operator Header / Context Banner

What is present:

- Operator name
- role chip
- access chip
- generated timestamp
- telemetry freshness summary
- attention queue count

What works:

- The banner reads like an operator console rather than a generic dashboard.
- Showing role and access context early is correct and should carry into Live Operations.
- The timestamped generated state reinforces that the page reflects current system posture, not a static mockup.

What is confusing:

- With the current `normal` simulation scenario, the banner does not immediately communicate operational tension because everything is healthy.
- The banner is structurally good, but the seed/simulator profile used for this walkthrough is too clean for a strong operations-first first impression.

Reuse recommendation:

- Keep this banner pattern.
- Extend it for Live Operations with stronger stale/offline emphasis and possibly last change/event summaries.

### 2.2 KPI Band

Current cards:

- Gateways
- Meters
- Telemetry
- Pending Updates
- Reports Ready

What works:

- The KPI selection is good.
- The cards are compact, readable, and actionable.
- The KPI cards already feel like reusable operator-console primitives.
- The open/review actions on each card are appropriate and should be preserved.

What is confusing:

- In the current demo state, `Gateways`, `Meters`, and `Reports Ready` all look healthy at once.
- Because the telemetry simulator scenario used here is `normal`, the KPI band under-represents incident-like conditions.
- `Reports Ready` is useful, but it currently reads more like a migration/readiness signal than a daily analyst/operator confidence signal.

Reuse recommendation:

- Keep the KPI card component.
- Keep the five-card mix.
- For Live Operations, derive more tension-focused KPI variants from the same component rather than redesigning the pattern.

### 2.3 Attention Queue

What works:

- The queue is severity-ordered.
- The descriptions are operational, not decorative.
- The queue is a strong bridge between summary and action.
- This is one of the most reusable patterns for the next phase.

What is confusing:

- In this seeded state, the queue is driven primarily by pending update work rather than outages or stale conditions.
- That means the queue demonstrates structure well, but not yet the full operational value of the pattern.

Reuse recommendation:

- Reuse this pattern directly in Live Operations.
- Do not replace it.
- Instead, feed it richer gateway-recovery and communication-failure signals.

### 2.4 Recent Telemetry List

What works:

- The list is compact.
- It gives immediate evidence that the system is alive.
- Status, relative age, site code, and location are all useful fields.
- The pattern is appropriate for both dashboard and future operations workspace side-panels.

What is confusing:

- Because all sampled rows are online in this walkthrough, the list does not yet demonstrate differentiation between online, stale, and offline telemetry narratives.

Reuse recommendation:

- Keep this exact component concept.
- Live Operations should extend it into a fuller timeline or event stream instead of discarding it.

### 2.5 Quick Action Grid

What works:

- The quick actions respect role context.
- The actions point into existing routes instead of inventing new behaviors.
- This is correct and conservative.

What is confusing:

- None structurally.
- The grid currently feels more maintenance/report-navigation oriented than investigation oriented.

Reuse recommendation:

- Keep the component and role-aware logic.
- Live Operations should add investigation-specific actions later rather than changing this pattern now.

### 2.6 Dashboard Overall Assessment

What works:

- The dashboard no longer feels like a placeholder.
- It now reads as a real operator console foundation.
- The visual hierarchy is materially better than a raw migrated page.
- The component library choices are sound.

What is still rough:

- The current walkthrough seed/simulator combination is too healthy for a high-value operations review.
- The dashboard needs more scenario contrast before it can fully prove Live Operations ergonomics.
- It is a good foundation, but not yet the full operational workspace.

What should be reused for Live Operations:

- operator header/context banner
- KPI cards
- attention queue
- recent telemetry panel
- quick actions

## 3. Reports Walkthrough

Routes reviewed:

- `/consumption_report` as admin
- `/consumption_report` as analyst
- export route `GET /download_consumption_report`

Component foundation reviewed:

- `resources/js/pages/Reports.vue`
- `resources/js/components/operator/ReportFamilySelector.vue`
- `resources/js/components/operator/ReportFilterPanel.vue`
- `resources/js/components/operator/ReportPreviewSummary.vue`
- `resources/js/components/operator/ReportEmptyState.vue`
- `resources/js/components/operator/ExportButton.vue`
- `resources/js/components/operator/DownloadShelf.vue`

### 3.1 Report Family Selector

What works:

- Legacy report family names remain recognizable.
- The selector is clearer than a flat legacy navigation cluster.
- Grouping `Building`, `Offline`, and `Site As-Built` on the shared report surface is understandable as a first pass.

What is confusing:

- Multiple family cards currently map to `/site_report`.
- That is acceptable for now, but not fully self-evident from the UI alone.

Reuse recommendation:

- Keep the selector pattern.
- Later report UX can improve readiness/status cues on each family card.

### 3.2 Filter Panel

What works:

- The filter panel is structurally clear.
- Scope and Date Range are separated correctly.
- Action buttons preserve the legacy route contract and field names.
- This is a strong reusable pattern for all report families.

What is confusing:

- The label `Building / Site ID` still reflects legacy ambiguity.
- It is behaviorally correct, but not ideal from a UX-language perspective.
- Because business semantics must remain unchanged, this should be handled carefully later rather than silently renamed.

Reuse recommendation:

- Keep the filter panel pattern.
- Reuse it for later report hardening and possibly future operations drill-down filters.

### 3.3 Preview Summary

What works:

- The preview summary is one of the best additions in the current reports UX.
- It gives context before export.
- Scope, range, rows, and units are visible in one place.
- This materially improves analyst confidence.

What is confusing:

- In the current implementation, the preview is still mostly a contract/status shell rather than a real computed preview state.
- That is acceptable for now, but it means the UX is ahead of the underlying demo-data fidelity in some cases.

Reuse recommendation:

- Keep this pattern.
- Extend it later rather than replacing it.

### 3.4 Empty State

What works:

- The explicit no-data state is strong.
- Showing current scope and range is correct.
- Showing next actions is much better than a blank or generic error.
- This is reusable beyond reports.

What is confusing:

- None structurally.
- The empty-state design is doing the right thing.
- The real issue is data-contract fidelity, not the empty-state UX itself.

Reuse recommendation:

- Reuse this pattern heavily in Live Operations and future report hardening.

### 3.5 Export Button State

What works:

- Export now has visible preparation/completion/error states.
- That is a major usability improvement over blind downloads.
- The component preserves legacy filenames and workbook routes.

What is confusing:

- The current review confirmed export headers and downloadable workbook responses, but not an interactive browser-driven visual state transition during this session.
- That is a tooling limitation of this walkthrough, not necessarily a product flaw.

Live evidence:

- Analyst export route returned `200 OK`.
- Content type was XLSX.
- Filename remained legacy-compatible.

Reuse recommendation:

- Keep this component as the export standard.

### 3.6 Download Shelf

What works:

- The concept is strong.
- The browser-session-local shelf is a good lightweight way to give analysts feedback without inventing backend report history storage.

What is confusing:

- This walkthrough could not visually confirm a populated shelf because browser-session interaction was not available in this execution environment.
- The component design itself is sound.

Reuse recommendation:

- Keep it.
- Later, if report history becomes a product requirement, treat that as a separate feature rather than forcing it into this current shelf.

### 3.7 Analyst Workflow Reality Check

This walkthrough confirmed an important real edge case.

Analyst-accessible report page:

- loads correctly
- preserves scoped-user access context
- preserves export route access

However, an actual seeded/simulated report generation request returned:

```json
{"draw":401,"recordsTotal":0,"recordsFiltered":0,"data":[]}
```

for:

- `site_id=1`
- `meter_id=MDTR-001`
- `2026-07-01 08:00` to `2026-07-01 09:00`

Interpretation:

- The current reports UX structure is good.
- The current demo seed/simulator/report-data contract is not yet fully aligned for analyst report generation.
- This matches the already known follow-up note: Phase 0 telemetry simulator identifiers and legacy report lookup semantics still need reconciliation.

This is the most important rough edge discovered in the walkthrough.

## 4. Concrete Operator Flow

Representative flow reviewed:

```text
Login
→ land on /site
→ open /dashboard
→ review KPI band, attention queue, telemetry recency, and report readiness
→ open /consumption_report
→ review report family selector and filter panel
→ choose site + meter + time range
→ attempt report generation
→ confirm empty-result behavior or export response
→ observe download/export behavior
```

Observed outcome in current state:

- Login flow works.
- Dashboard provides immediate operational context.
- Reports page is much clearer than the original scaffold state.
- Export route works.
- Real seeded analyst report generation currently still lands in an empty result path because of the known simulator/report identifier mismatch.

## Seed Data / Simulator Assessment

What works:

- The demo profile has enough entity density to make the app feel populated.
- The simulator successfully creates telemetry, pending update flags, and report-readiness counts.
- Dashboard data density is materially better than an empty install.

What is weak:

- The `normal` simulation run is too healthy for the strongest dashboard review.
- Report-generation semantics still diverge from the seed/simulator contract in analyst flow.

Recommendation:

- Keep the Phase 0 dataset and simulator direction.
- Before a future formal dashboard/demo review, run a more tension-rich scenario such as `offline-recovery`.
- Reconcile simulator meter identifiers with legacy report lookup semantics so seeded report generation is not artificially empty.

## Issues Found

### 1. Report generation and seed/simulator identifiers are not fully aligned

Impact:

- Real analyst report generation can return an empty JSON result even though the dashboard shows report readiness and telemetry density.

Priority:

- Medium for UI work
- Important before claiming full report-demo realism

Related follow-up:

- `UI-031` in `docs/ui/SPARK_WORK_QUEUE.md`

### 2. Dashboard review scenario is too healthy

Impact:

- The dashboard foundation looks structurally good, but the walkthrough underplays how useful it will be under stale/offline conditions.

Priority:

- Medium

Recommendation:

- Use `operations-gateway-recovery` or equivalent mixed-state simulation for future dashboard/live-ops reviews.

### 3. Report family mapping is still slightly opaque

Impact:

- `Building`, `Offline`, and `Site As-Built` all route through `/site_report`, which is technically correct but not fully self-explanatory.

Priority:

- Low

Recommendation:

- Revisit copy/status labeling later, without changing route behavior.

## Recommended Adjustments Before Live Operations

Recommended adjustments:

1. Keep the current dashboard and reports component patterns.
2. Proceed to Live Operations without redesigning the current dashboard/report foundation.
3. Treat the simulator/report identifier mismatch as a separate follow-up, not a blocker for UI-015.
4. Use a more incident-rich simulation scenario when reviewing the first Live Operations workspace.
5. Preserve the current attention queue, KPI card, recent telemetry, filter panel, preview summary, and empty-state patterns as the base visual language for the next phase.

## Recommendation on UI-015

Recommendation:

```text
Proceed to UI-015 — Live Operations Route Decision.
```

Reason:

- The dashboard and reports foundation is now strong enough to extend rather than replace.
- The main remaining weakness is not structural UI quality.
- The main remaining weakness is operational-data realism for certain report flows.
- That should be tracked and corrected, but it does not justify pausing Live Operations route/design work.

## Summary

Current assessment:

- Dashboard: good operator-console foundation
- Reports: strong workflow clarity with one important seeded-data realism gap
- Seed quality: good entity density, moderate operational realism
- Simulator usefulness: strong for general state, incomplete for report lookup parity
- Next step: proceed to Live Operations route decision using the current patterns as the foundation
