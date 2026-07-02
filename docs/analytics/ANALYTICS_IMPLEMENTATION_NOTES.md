# Analytics Implementation Notes

## Purpose

This is the implementation handbook for future CAMR Analytics Workbench work. It assumes the migration, Operator Console, Reports UX, telemetry simulator, and lifecycle scenario runner already exist.

## Non-Negotiables

- Do not change legacy report formulas.
- Do not change report export semantics.
- Do not change RTU/device protocol behavior.
- Do not hide authorization in Vue-only logic.
- Do not hardcode fake analytics data into Vue.
- Do not introduce charting or analytics dependencies without approval.
- Do not treat Analytics as another dashboard.
- Do not show estimated, incomplete, or missing data as measured data.

## Backend Assumptions

Analytics initially consumes existing CAMR data:

- `meter_data`,
- `meter_details`,
- `meter_rtu`,
- `meter_site`,
- `meter_building_table`,
- `meter_location_table`,
- report-hardened calculations where relevant.

Any future derived tables, caches, or materialized structures require an explicit data contract and review.

## Report Semantics That Must Never Change

Reports remain the formal export workflow. Analytics may reuse report calculations only when the overlap is explicit.

Never silently alter:

- consumption formulas,
- demand formulas,
- workbook filenames,
- workbook content types,
- route behavior,
- report validation meaning,
- exported terminology.

## Accessibility

Analytics must be usable without color-only interpretation.

Requirements:

- chart values need textual equivalents or summaries,
- confidence must be text-visible,
- selected filters must be visible,
- keyboard users must be able to reach filters and primary actions,
- empty and incomplete states must explain what is missing.

## Performance

Analytics can touch large telemetry volumes.

Guidance:

- aggregate in backend data contracts,
- avoid sending raw telemetry arrays unless needed,
- limit default time windows,
- use pagination or sampling for large tables,
- consider caching only after correctness is proven,
- document grain and aggregation in payloads.

## Charting Cautions

Do not choose a chart library until the first implementation slice needs one.

Chart implementation must support:

- responsive rendering,
- accessible labels,
- tooltips with units and confidence,
- stable time-series axes,
- export or snapshot strategy if approved.

## Simulator Relationship

The telemetry simulator should generate meaningful analytics data:

- complete windows,
- missing intervals,
- peak periods,
- stale devices,
- recovery windows,
- report-ready ranges.

Simulator scenarios should support analytics review, but must not encode analytics business logic.

## Scenario Runner Relationship

Lifecycle scenarios should orchestrate analytics demonstrations and journey tests:

- seed profile,
- simulator scenario,
- analytics question,
- expected evidence,
- optional export/share step.

The scenario runner remains orchestration only.

## Reusable Components

Prefer analytics-specific primitives:

- `TimeRangePicker`,
- `AggregationSelector`,
- `ConsumptionChart`,
- `DemandCurve`,
- `LoadProfile`,
- `ComparisonGrid`,
- `EnergySummaryCard`,
- `TrendIndicator`,
- `ExportPanel`.

Avoid generic chart wrappers until repeated usage proves a real pattern.

## Known Pitfalls

- Treating missing data as zero.
- Comparing different grains without labeling.
- Showing forecasts as measured facts.
- Copying Operator Console alert colors into historical analysis.
- Creating attractive charts before data contracts are accepted.
- Building a report replacement instead of an analytics workbench.

