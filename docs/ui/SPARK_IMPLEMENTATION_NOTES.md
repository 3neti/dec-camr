# Spark Implementation Notes

## Purpose

This is Spark's implementation handbook for the next CAMR UI work. It assumes the backend migration and UI foundation are complete and that no future UI slice should rediscover legacy workflow semantics.

## Non-Negotiables

- Do not change legacy route behavior.
- Do not change form field names.
- Do not change report formulas or export semantics.
- Do not move authorization enforcement into Vue-only logic.
- Do not introduce new dependencies without approval.
- Use existing Laravel 13 + Vue 3 + Inertia conventions.
- Preserve operator terminology unless architect-approved.

## Shared Components To Reuse

Existing:

- `resources/js/components/operator/OperatorPage.vue`
- `resources/js/components/operator/EntityForm.vue`
- `resources/js/components/operator/EntityTable.vue`
- `resources/js/components/operator/EntityActions.vue`
- `resources/js/components/operator/EmptyState.vue`
- `resources/js/components/operator/StatusChip.vue`
- `resources/js/components/operator/HealthBadge.vue`

Likely additions:

- `KpiCard`
- `AttentionList`
- `FilterBar`
- `ScopePill`
- `ReportFilterPanel`
- `ReportPreviewSummary`
- `ExportButton`
- `DownloadShelf`
- `GatewayHealthList`
- `MeterHealthGrid`
- `TelemetryTimeline`
- `OperationalCommandBar`

## UI Slice 3 — Dashboard

Priority:

- High. This is the first true operator console surface.

Likely files:

- `resources/js/pages/Dashboard.vue`
- `resources/js/components/operator/KpiCard.vue`
- `resources/js/components/operator/AttentionList.vue`
- `resources/js/components/operator/RecentTelemetryList.vue`
- dashboard controller/action files if props are missing
- relevant feature/browser smoke tests

Expected props:

- gateway counts by health state,
- meter counts by health state,
- telemetry recency,
- pending update counts,
- report readiness summary,
- role/user context,
- quick action links.

Expected layout:

- top KPI band,
- attention queue,
- operations snapshot,
- recent telemetry,
- quick action panel.

Implementation cautions:

- Do not show fake health. Derive from seeded/simulated persistence.
- Do not add dashboard-only status definitions that conflict with live operations.
- Keep role-specific quick actions backed by backend permission behavior.

Accessibility notes:

- Status must include text, not color alone.
- KPI cards must have visible labels and meaningful links.

Performance notes:

- Aggregate in backend/query layer where possible.
- Avoid fetching large telemetry arrays for the dashboard.

Operator workflow notes:

- Every alert should drill down to a next action.

Known pitfalls:

- Treating inactive meter status as offline telemetry.
- Showing pending update flags without a reset/action context.

Legacy behavior that must never change:

- Preserve `/dashboard` as a compatibility route.
- Use `/site` as the CAMR operator home and primary dashboard/console entry.
- Do not remove `/dashboard`.
- Do not make `/dashboard` the operator shell target unless explicitly approved.
- Operator navigation, logo links, and quick dashboard links should point to `/site`.
- Route behavior must remain unchanged.

## UI Slice 4 — Reports

Priority:

- High for analyst confidence.

Likely files:

- `resources/js/pages/Reports.vue`
- `resources/js/components/operator/ReportFilterPanel.vue`
- `resources/js/components/operator/ReportPreviewSummary.vue`
- `resources/js/components/operator/ExportButton.vue`
- `resources/js/components/operator/DownloadShelf.vue`
- report feature/journey tests

Expected props:

- report family options,
- available sites/buildings/meters,
- selected filters,
- preview summary,
- export state,
- empty-result status.

Expected layout:

- report family selector,
- filter panel,
- preview summary,
- representative rows,
- export/download shelf.

Implementation cautions:

- Preserve legacy report names.
- Preserve legacy date boundary behavior.
- Preserve filename/content-type behavior.
- Do not invent saved filters until storage approach is approved.

Accessibility notes:

- Date fields must be labeled.
- Export buttons must expose busy/disabled state.

Performance notes:

- Preview only representative rows for large results.
- Avoid rendering full XLSX-size datasets.

Operator workflow notes:

- Analyst should know before export whether data exists.

Known pitfalls:

- Making XLSX export feel asynchronous if backend is synchronous without clear state.
- Hiding empty results as blank tables.

Legacy behavior that must never change:

- Report formulas, grouping rules, workbook semantics, and export routes.

## UI Slice 5 — Live Operations

Priority:

- High. This is where CAMR becomes a living operator console.

Likely files:

- new or existing live operations page if route approved
- `resources/js/components/operator/GatewayHealthList.vue`
- `resources/js/components/operator/MeterHealthGrid.vue`
- `resources/js/components/operator/TelemetryTimeline.vue`
- `resources/js/components/operator/OperationalCommandBar.vue`
- `resources/js/components/operator/PendingUpdatePanel.vue`
- feature/browser journey tests

Expected props:

- gateway health rows,
- meter health rows,
- selected gateway/meter detail,
- telemetry timeline events,
- update flags,
- command availability.

Expected layout:

- filter/status bar,
- left health list,
- right detail panel,
- timeline,
- command bar.

Implementation cautions:

- Do not change RTU protocol URLs or response behavior.
- Do not require session auth for device protocol endpoints.
- UI commands must call approved app routes only.

Accessibility notes:

- Command buttons need explicit text and disabled reasons.
- Live/polling state must be visible.

Performance notes:

- Poll summaries, not full telemetry history.
- Use pause/refresh controls for live views.

Operator workflow notes:

- Offline/stale -> detail -> cause context -> safe command -> observable state change.

Known pitfalls:

- Confusing maintenance record status with live telemetry health.
- Rendering too much data in timelines.

Legacy behavior that must never change:

- RTU/device endpoint protocol behavior.

## UI Slice 6 — Operator Productivity

Priority:

- Medium after core surfaces are stable.

Likely files:

- operator table/filter components,
- shell/nav components,
- high-use maintenance pages,
- user/site access pages.

Expected props:

- search query,
- filter state,
- scoped access indicators,
- saved view metadata if approved.

Expected layout:

- compact filter bars,
- scope pills,
- quick jump/search,
- consistent action menus.

Implementation cautions:

- Do not hide unauthorized backend behavior behind client filters.
- Global search must respect backend authorization.

Accessibility notes:

- Search controls need labels or accessible names.
- Keyboard shortcuts require visible discovery before use.

Performance notes:

- Debounce only where backend calls are introduced.
- Preserve existing server-side DataTables/list behavior.

Operator workflow notes:

- Focus on fewer clicks to known destinations, not new workflows.

Known pitfalls:

- Creating a generic table framework too early.

Legacy behavior that must never change:

- Scoped visibility and authorization semantics.

## UI Slice 7 — Polish And Accessibility

Priority:

- Medium-high before release review.

Likely files:

- shared operator components,
- layout components,
- page-level empty/loading/error states,
- browser journey tests.

Expected props:

- loading state,
- empty state context,
- permission state,
- validation errors.

Expected layout:

- consistent loading and empty states,
- improved responsive behavior,
- keyboard-safe command surfaces.

Implementation cautions:

- Visual polish must not change workflow sequence.
- Avoid over-animation in operational tools.

Accessibility notes:

- Audit focus order.
- Verify mobile and desktop.
- Ensure status and errors are screen-reader understandable.

Performance notes:

- Watch for expensive charts/tables.
- Avoid client-only transforms over large datasets.

Operator workflow notes:

- Make repeated tasks feel predictable and stable.

Known pitfalls:

- Decorative polish reducing information density.

Legacy behavior that must never change:

- All migrated workflow contracts.

## Suggested Verification For Each Slice

- `php artisan test --compact`
- `vendor/bin/pint --dirty --format agent`
- `npm run lint:check`
- `npm run types:check`
- targeted operator journey or feature tests for affected workflow

## Design Vocabulary

Use these terms consistently:

- Online
- Stale
- Offline
- Pending Update
- Recovering
- Ready
- No Data
- Exporting
- Complete

Avoid synonyms that dilute meaning.
