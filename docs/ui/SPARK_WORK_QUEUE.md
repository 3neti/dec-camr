# Spark Work Queue

## Purpose

This is Spark's small-task implementation backlog for the CAMR operator console. Tasks are intentionally narrow so implementation can proceed quickly after quota resets.

## Queue

| ID | Title | Objective | Priority | Difficulty | Dependencies | Acceptance Criteria |
|---|---|---|---|---|---|---|
| UI-001 | Dashboard Data Contract | Define dashboard props from existing persisted state. | High | Medium | Demo seeds, simulator | Dashboard receives gateway, meter, telemetry, pending update, and report readiness data without fake UI state. |
| UI-002 | KPI Card Component | Add compact reusable KPI card. | High | Low | Operator components | Renders label, value, secondary status, icon slot, and optional link. |
| UI-003 | Attention List Component | Add severity-sorted attention queue. | High | Medium | Status semantics | Shows critical/warning/info rows with drill-down targets and empty state. |
| UI-004 | Dashboard First Pass | Replace placeholder dashboard with KPI band and attention list. | High | Medium | UI-001, UI-002, UI-003 | Operators can see gateway/meter health and drill into maintenance/live contexts. |
| UI-005 | Recent Telemetry List | Add compact recent telemetry panel. | High | Medium | Simulator data | Shows latest readings, age, meter, and status without loading full history. |
| UI-006 | Quick Action Grid | Add role-aware dashboard quick actions. | Medium | Low | Navigation config | Actions link to existing routes and respect visible role context. |
| UI-007 | Dashboard Smoke Journey | Add smoke coverage for dashboard operator state. | High | Medium | Scenario runner | Fresh install and gateway recovery scenarios expose useful dashboard data. |
| UI-008 | Report Family Selector | Introduce report family navigation preserving legacy names. | High | Low | Reports page | Raw, KW Demand, KWh Consumption, SAP, Building, Offline, Site As-Built remain recognizable. |
| UI-009 | Report Filter Panel | Build reusable report filter shell. | High | Medium | Report route contracts | Filters submit expected fields and do not alter backend semantics. |
| UI-010 | Report Preview Summary | Add preview summary before export. | High | Medium | Report payloads | Shows rows, range, scope, units, and empty result state. |
| UI-011 | Export Button State | Add export button with busy/complete/error states. | Medium | Low | Existing export routes | Exports preserve filenames/content types and show clear state. |
| UI-012 | Download Shelf | Add recent download shelf for current session. | Medium | Medium | Export state | Shows latest generated report family, filename, and status. |
| UI-013 | Report Empty State | Add explicit no-data state. | High | Low | Report preview | Empty windows explain selected scope/range and next actions. |
| UI-014 | Analyst Report Journey | Add smoke journey for report preview/export. | High | Medium | UI-008 to UI-013 | Analyst can generate and export representative report without workflow drift. |
| UI-015 | Live Operations Route Decision | Confirm route/page target for live operations workspace. | High | Low | Architect approval if new route | Route decision is documented before UI implementation. |
| UI-016 | Gateway Health List | Build gateway health list component. | High | Medium | Dashboard status semantics | Lists online/stale/offline/pending update gateways with drill-down. |
| UI-017 | Meter Health Grid | Build meter health grid/list component. | High | Medium | Meter telemetry data | Shows meter status, latest reading, and context. |
| UI-018 | Telemetry Timeline | Build event timeline component. | High | Medium | Simulator events or derived state | Shows readings, stale/offline transitions, update flags, and recovery events. |
| UI-019 | Operational Command Bar | Add command surface for approved safe actions. | Medium | Medium | RTU protocol routes | Commands preserve routes/payloads and expose disabled reasons. |
| UI-020 | Pending Update Panel | Add panel for CSV/location/force LP flags. | High | Medium | Gateway state | Flags are visible with age and available reset/review action. |
| UI-021 | Operations Gateway Recovery Journey | Add journey using `operations-gateway-recovery`. | High | Medium | Live operations first pass | Operator can identify offline gateway, inspect it, and observe recovery state. |
| UI-022 | Filter Bar Component | Add shared search/status/scope filter bar. | Medium | Medium | Stable table pages | Can be reused by maintenance/live operations without changing backend behavior. |
| UI-023 | Scope Pill Component | Add site/user scope visibility indicator. | Medium | Low | Authorization semantics | Scoped pages show visible user/site context. |
| UI-024 | Maintenance Action Column | Extend entity table action support. | Medium | Medium | EntityActions | View/edit/delete actions remain compatible with legacy routes. |
| UI-025 | Validation Summary | Add reusable validation summary pattern. | Medium | Low | EntityForm | Form errors are visible and accessible. |
| UI-026 | Loading And Skeleton States | Normalize loading states. | Medium | Low | Major UI surfaces | Dashboard/report/live pages do not flash blank content. |
| UI-027 | Responsive Layout Audit | Verify mobile/tablet behavior. | Medium | Medium | Major UI surfaces | No overlapping text, unreachable actions, or broken table controls. |
| UI-028 | Keyboard Flow Audit | Review focus and tab order. | Medium | Medium | Major UI surfaces | Operators can navigate forms, filters, and actions by keyboard. |
| UI-029 | Accessibility Pass | Add aria labels and semantic improvements. | Medium | Medium | UI-027, UI-028 | Status, commands, errors, and tables are accessible. |
| UI-030 | Final UI Journey Sweep | Run persona smoke journeys after polish. | High | Medium | All UI slices | Administrator, operations, maintenance, and analyst smoke paths remain green. |

## Task Sizing Rule

If a task touches more than one major page and one shared component, split it before implementation.

## Acceptance Gate For Spark Tasks

Each completed task should report:

- files changed,
- behavior preserved,
- routes/field names preserved,
- tests or checks run,
- unresolved risks.

