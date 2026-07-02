# Analytics Implementation Roadmap

## Purpose

This roadmap converts the Analytics Design Program into future implementation slices. It does not authorize implementation.

## Analytics-001 — Analytics Data Contract

Objective:

- Define backend data contracts for analytics metrics, entity scope, time range, grain, confidence, and missing data.

Dependencies:

- report hardening,
- telemetry simulator,
- authorization semantics.

Expected components:

- none, data contract only.

Analyst benefit:

- future UI can display trustworthy analytics without fake frontend data.

Review gate:

- architect accepts metric definitions and confidence behavior.

## Analytics-002 — Consumption Workspace

Objective:

- Provide the first consumption investigation workspace.

Dependencies:

- Analytics-001,
- `ConsumptionChart`,
- `TimeRangePicker`,
- `AggregationSelector`.

Expected components:

- `ConsumptionChart`,
- `EnergySummaryCard`,
- `TrendIndicator`,
- `AnalyticsEmptyState`.

Analyst benefit:

- Energy Manager can inspect consumption over time and compare a period.

Review gate:

- consumption values match approved contract and report semantics where overlapping.

## Analytics-003 — Demand Workspace

Objective:

- Analyze demand curves, peaks, and demand windows.

Dependencies:

- Analytics-001,
- demand calculation contract.

Expected components:

- `DemandCurve`,
- `EnergySummaryCard`,
- `AnomalyMarker`.

Analyst benefit:

- Finance and Engineering can identify peak drivers.

Review gate:

- demand semantics and peak windows are explicit.

## Analytics-004 — Historical Comparison

Objective:

- Compare buildings, meters, sites, and periods.

Dependencies:

- Analytics-002,
- `ComparisonGrid`.

Expected components:

- `ComparisonGrid`,
- `BuildingSelector`,
- `MeterSelector`,
- `TrendIndicator`.

Analyst benefit:

- Users can identify which entity changed and by how much.

Review gate:

- comparison context and confidence are visible.

## Analytics-005 — Load Profile Explorer

Objective:

- Inspect interval patterns and operating signatures.

Dependencies:

- Analytics-001,
- enough interval telemetry.

Expected components:

- `LoadProfile`,
- `TimeRangePicker`,
- `BaselineOverlay` if approved.

Analyst benefit:

- Engineering can inspect shape, cycling, and sustained loads.

Review gate:

- profile displays grain and missing intervals clearly.

## Analytics-006 — Power Factor / Quality

Objective:

- Review power factor and related electrical quality signals.

Dependencies:

- field availability in `meter_data`,
- approved aggregation rules.

Expected components:

- `PowerFactorTrend`,
- `ComparisonGrid`,
- `InsightCallout`.

Analyst benefit:

- Engineering can identify quality issues and cost-risk signals.

Review gate:

- metric definitions and units are accepted.

## Analytics-007 — Executive Analytics

Objective:

- Create concise high-level analytical summary for executives.

Dependencies:

- stable consumption, demand, and comparison contracts.

Expected components:

- `EnergySummaryCard`,
- `TrendIndicator`,
- `InsightCallout`,
- compact comparison view.

Analyst benefit:

- executives can see direction, risk, and opportunity quickly.

Review gate:

- no unapproved forecast or confidence-hiding summaries.

## Analytics-008 — Forecasting

Objective:

- Introduce forecast and baseline overlays only after measured and calculated analytics are stable.

Dependencies:

- approved baseline and forecast methodology,
- sufficient historical data.

Expected components:

- `ForecastOverlay`,
- `BaselineOverlay`,
- confidence interval display.

Analyst benefit:

- planning users can evaluate expected future behavior.

Review gate:

- forecast is clearly marked as future-facing and uncertain.

## Analytics-009 — Analytics Journey Sweep

Objective:

- Validate end-to-end analytics personas and investigations.

Dependencies:

- Analytics-002 through Analytics-007 as applicable.

Expected components:

- no new components unless gaps appear.

Analyst benefit:

- confirms the workbench supports real investigations, not just screens.

Review gate:

- Energy Manager, Engineering, Finance, Auditor, and Executive journeys are green or explicitly deferred.

