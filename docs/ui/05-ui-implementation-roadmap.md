# CAMR UI Implementation Roadmap

## Purpose

This roadmap converts the operator console vision into small implementation slices for Spark. Each slice should be independently reviewable, preserve legacy workflows, and use the Phase 0 data/simulator/journey foundation.

## Current Baseline

Completed:

- Phase 0: professional demonstration dataset.
- Phase 0.25: operational telemetry simulator.
- Phase 0.5: operator journey foundation and lifecycle scenario runner.
- Phase 1: navigation and operator shell.
- Phase 2: maintenance page shell modernization.
- Operator component library first pass.

Next work should proceed from shared primitives into operational surfaces.

## UI Slice 3 — Dashboard

Objective:

- Replace dashboard placeholders with a working operator dashboard backed by real seeded/simulated data.

Expected components:

- `KpiCard`
- `AttentionList`
- `HealthBadge`
- `StatusChip`
- `RecentTelemetryList`
- `QuickActionGrid`

Dependencies:

- Demo/heavy seed profiles.
- Telemetry simulator.
- Existing gateway/meter/report endpoints.

Operator benefit:

- Operators can identify offline/stale devices and recent system health immediately.

Implementation order:

1. Add KPI/query props from controller.
2. Build reusable KPI and attention components.
3. Render gateway/meter/report readiness summary.
4. Add role-aware quick actions.
5. Add tests/journey smoke for dashboard visibility.

## UI Slice 4 — Reports

Objective:

- Modernize report workflows with consistent filters, preview, and export state while preserving report behavior.

Expected components:

- `ReportFilterPanel`
- `DateRangeControl`
- `ReportPreviewSummary`
- `ExportButton`
- `DownloadShelf`
- `EmptyResultPanel`

Dependencies:

- Slice 16 report-hardening behavior.
- Demo report-ready data.
- Existing report routes and payloads.

Operator benefit:

- Analysts can generate and export reports with clearer filter intent and result feedback.

Implementation order:

1. Map legacy report families to tabs or segmented navigation.
2. Introduce shared report filter shell.
3. Add preview summaries for representative report families.
4. Add export state and download shelf.
5. Add empty-result UX.

## UI Slice 5 — Live Operations

Objective:

- Create the live operations workspace for gateway/meter investigation and operational commands.

Expected components:

- `GatewayHealthList`
- `MeterHealthGrid`
- `TelemetryTimeline`
- `CommunicationSummary`
- `OperationalCommandBar`
- `PendingUpdatePanel`

Dependencies:

- RTU/device endpoint compatibility.
- Telemetry simulator scenarios.
- Gateway/meter health data from existing persistence.

Operator benefit:

- Operations engineers can move from alert to investigation to safe action without bouncing through unrelated maintenance pages.

Implementation order:

1. Build filtered gateway/meter health list.
2. Add selected gateway detail panel.
3. Add telemetry timeline.
4. Add pending update and command states.
5. Add operations journey smoke tests.

## UI Slice 6 — Operator Productivity

Objective:

- Improve repeated work with filtering, saved views, quick jumps, and better action discoverability.

Expected components:

- `FilterBar`
- `ScopePill`
- `SavedViewControl`
- `GlobalSearch`
- `ActionMenu`

Dependencies:

- Stable dashboard/reports/live operations pages.
- Existing authorization semantics.

Operator benefit:

- Frequent users can reach task contexts faster and maintain scope awareness.

Implementation order:

1. Add shared filter/search primitives to high-use tables.
2. Add scope indicators for users/sites.
3. Add global search only after route targets are stable.
4. Add saved view support if storage approach is approved.

## UI Slice 7 — Polish And Accessibility

Objective:

- Finalize interaction details, keyboard flow, loading states, and responsive behavior.

Expected components:

- `SkeletonState`
- `LoadingOverlay`
- `ValidationSummary`
- `PermissionState`
- refined `EmptyState`

Dependencies:

- All major UI surfaces implemented.
- Journey tests for core personas.

Operator benefit:

- The console feels stable, predictable, and production-ready under normal and edge-case conditions.

Implementation order:

1. Audit keyboard and focus flow.
2. Normalize loading/empty/error states.
3. Validate mobile/tablet layouts.
4. Run browser journey smoke tests.
5. Address visual polish without changing workflow semantics.

## Recommended Review Gates

Each UI slice should report:

- files changed,
- components created/reused,
- routes preserved,
- field names preserved,
- operator journeys affected,
- tests/gates run,
- UI risks or deferred issues.

## Stop Points

Recommended review stop points:

- after Dashboard before Reports,
- after Reports before Live Operations,
- after Live Operations before productivity enhancements,
- after accessibility/polish before production release discussion.

