# CAMR Maintenance Design System

## Purpose

The current operator component library is a good first extraction from the maintenance pages. The next step is to clarify how it should evolve without becoming a generic design system or hiding legacy compatibility.

The maintenance design system should keep CAMR workflows familiar while reducing repeated page markup.

## Current Component Library

Current components:

- `OperatorPage`
- `EntityForm`
- `EntityTable`
- `EntityActions`
- `EmptyState`
- `StatusChip`
- `HealthBadge`

These are enough for the approved maintenance shell refactor and should remain small.

## Recommended Component Hierarchy

```text
OperatorPage
  PageHeader
  OperatorSection
    EntityForm
      EntityField
      ValidationSummary
    EntityTable
      EntityActions
      EmptyState
      StatusChip / HealthBadge
```

Do not move legacy route URLs or field names into hidden component magic. They should remain visible in page-level definitions or clearly typed configuration.

## Page Composition

Maintenance pages should follow this order:

1. Page title and context.
2. Create/update form.
3. Optional selected-record context.
4. Existing records table.
5. Empty/permission state.

For higher-density domains like Gateway, Meter, and User, later slices can add:

- search,
- filter,
- scoped view indicator,
- action column,
- status column.

## Reusable Layouts

Use consistent layout patterns:

- `OperatorPage`: page title, optional description, page-level slots.
- `OperatorSection`: unimplemented but recommended wrapper for page bands or functional areas.
- `EntityForm`: legacy POST form wrapper with explicit field definitions.
- `EntityTable`: record list with typed columns and row key.
- `EntityActions`: action group for table rows or detail panels.

Avoid putting cards inside cards. Use one card per form, one card per table, and unframed page sections where appropriate.

## Responsive Behavior

Maintenance forms:

- one column on mobile,
- two columns on tablet,
- three or four columns only when labels remain readable.

Tables:

- horizontal scroll is acceptable for legacy maintenance tables,
- action columns should remain reachable,
- future high-density tables should support sticky first/action columns only if needed.

Dashboard/operations:

- KPI cards stack on mobile,
- attention list should appear before charts,
- command bars should wrap without hiding destructive labels.

## Spacing

Recommended rhythm:

- page sections: `space-y-6`,
- form field groups: `space-y-2`,
- cards: compact headers and dense content,
- table cells: enough padding to scan, not oversized.

Operational tools should feel efficient. Avoid oversized hero spacing.

## Typography

Use restrained operational hierarchy:

- page title: clear but not oversized,
- card titles: compact,
- table text: readable and dense,
- status labels: short and consistent,
- numeric KPIs: emphasized.

Do not use negative letter spacing or viewport-based font scaling.

## Icons

Use icons where they clarify action or entity:

- Building: site/building context,
- Radio: gateway,
- Zap/Activity: meter/telemetry,
- Users: users/access,
- Download: exports,
- AlertTriangle: attention,
- Refresh/Rotate: reset/update flows.

Prefer `lucide-vue-next` where already available or project-approved. Do not introduce new icon dependencies without approval.

## Color Semantics

Use color as a semantic layer:

- Green: healthy/online/complete.
- Amber: stale/pending/requires attention.
- Red: offline/failure/destructive/blocked.
- Blue or neutral: informational/navigation.
- Gray: inactive/unknown.

Avoid making the entire UI one dominant hue. Status color should highlight operational meaning, not create decoration.

## Accessibility

Baseline requirements:

- every form input has a visible label,
- buttons have clear text or accessible labels,
- status is not color-only,
- table headers are semantic,
- focus states remain visible,
- keyboard users can reach actions in predictable order,
- disabled actions include a visible reason where practical.

## Keyboard Navigation

Recommended future patterns:

- `/` or focused search only after global search is implemented.
- Tab order: filters -> form fields -> submit -> table actions.
- Escape closes dialogs/sheets.
- Enter submits only when form intent is clear.

Do not introduce keyboard shortcuts until journey tests cover them.

## Missing Operator Components

Recommended additions, in implementation order:

| Component | Purpose | Priority |
|---|---|---|
| `PageHeader` | Title, subtitle, role/context actions | High |
| `KpiCard` | Compact dashboard metric | High |
| `AttentionList` | Dashboard alert queue | High |
| `FilterBar` | Search/status/scope filtering | High |
| `ScopePill` | Site/user access visibility | High |
| `ReportFilterPanel` | Consistent report filters | High |
| `ExportButton` | Export state and downloads | Medium |
| `TelemetryTimeline` | Live operations investigation | High |
| `CommandBar` | Safe operational actions | Medium |
| `ValidationSummary` | Form error grouping | Medium |

## Implementation Cautions

- Keep legacy field names visible and testable.
- Keep form actions explicit.
- Do not over-generalize tables before Gateway/Meter/User needs are clear.
- Add slots when a component needs domain-specific content rather than adding many conditional props.
- Prefer typed page-level arrays for fields/columns over ad hoc templates when the pattern is simple.

## Reusable Concepts Beyond CAMR

Reusable across other operator tools:

- compact status chips,
- health badges,
- KPI card primitive,
- attention queue,
- command bar,
- report filter panel,
- activity/timeline feed.

Generalize only after CAMR has proven the shape through real pages.

