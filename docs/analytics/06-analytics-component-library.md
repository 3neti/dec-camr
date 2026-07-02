# Analytics Component Library

## Purpose

This document defines analytics primitives before implementation. These are not generic design-system components. They are reusable building blocks for CAMR analytical investigations.

## ConsumptionChart

Purpose:

- Show energy consumed over time.

Conceptual props:

- series,
- grain,
- unit,
- time range,
- confidence,
- comparison series.

Dependencies:

- consumption data contract.

Accessibility:

- provide tabular equivalent or summary.
- expose period and units in labels.

Reuse potential:

- CAMR, Treasury cost review, sustainability tools.

## DemandCurve

Purpose:

- Show demand behavior and peak windows.

Conceptual props:

- demand series,
- peak marker,
- demand window,
- comparison period,
- confidence.

Dependencies:

- demand calculation contract.

Accessibility:

- peak must be text-visible, not color-only.

Reuse potential:

- CAMR and operational energy platforms.

## LoadProfile

Purpose:

- Show shape of usage across a day, week, or selected period.

Conceptual props:

- interval series,
- grain,
- overlay series,
- peak and valley markers.

Dependencies:

- interval telemetry.

Accessibility:

- support keyboard inspection of key points where practical.

Reuse potential:

- CAMR, Track AI time-series review, Heartbeat operational patterns.

## ComparisonGrid

Purpose:

- Compare buildings, meters, sites, or periods.

Conceptual props:

- rows,
- metrics,
- comparison baseline,
- sort key,
- confidence flags.

Dependencies:

- entity and metric contracts.

Accessibility:

- use semantic table structure.

Reuse potential:

- high across 3neti analytical products.

## TimeRangePicker

Purpose:

- Select analytical time windows.

Conceptual props:

- start,
- end,
- presets,
- allowed range,
- timezone label.

Dependencies:

- available data window.

Accessibility:

- labeled fields and keyboard navigation.

Reuse potential:

- high.

## AggregationSelector

Purpose:

- Choose grain and aggregation method.

Conceptual props:

- grain options,
- aggregation options,
- selected grain,
- selected aggregation.

Dependencies:

- metric capabilities.

Accessibility:

- visible selected state and explanation.

Reuse potential:

- high.

## MeterSelector

Purpose:

- Select meters for analysis.

Conceptual props:

- meters,
- selected meter IDs,
- scope,
- status,
- search.

Dependencies:

- authorization and meter context.

Accessibility:

- search and selection must be keyboard-accessible.

Reuse potential:

- CAMR and device-heavy platforms.

## BuildingSelector

Purpose:

- Select buildings or sites for comparison.

Conceptual props:

- buildings,
- selected building IDs,
- hierarchy,
- scope,
- search.

Dependencies:

- site/building context.

Accessibility:

- expose hierarchy in text.

Reuse potential:

- CAMR and facility-oriented products.

## EnergySummaryCard

Purpose:

- Show analytical headline values with context.

Conceptual props:

- label,
- value,
- unit,
- comparison,
- confidence,
- period.

Dependencies:

- summary metric contract.

Accessibility:

- comparison direction must be text-visible.

Reuse potential:

- high.

## TrendIndicator

Purpose:

- Show direction and magnitude of change.

Conceptual props:

- current,
- previous,
- variance,
- direction,
- confidence.

Dependencies:

- comparison contract.

Accessibility:

- include text direction and percentage or absolute value.

Reuse potential:

- high.

## BaselineOverlay

Purpose:

- Overlay expected behavior against actual behavior.

Conceptual props:

- baseline series,
- actual series,
- confidence,
- baseline method.

Dependencies:

- approved baseline method.

Accessibility:

- identify baseline as calculated or estimated.

Reuse potential:

- future analytics platforms.

## ForecastOverlay

Purpose:

- Show projected behavior after forecasting is approved.

Conceptual props:

- forecast series,
- confidence interval,
- actual series,
- model note.

Dependencies:

- approved forecasting method.

Accessibility:

- forecast must be visually and textually distinct from measured data.

Reuse potential:

- future concept.

## ExportPanel

Purpose:

- Export analytical evidence without replacing Reports.

Conceptual props:

- export options,
- selected range,
- selected entities,
- confidence summary,
- status.

Dependencies:

- approved export behavior.

Accessibility:

- status and errors must be announced.

Reuse potential:

- high.

## InsightCallout

Purpose:

- Highlight a meaningful analytical observation.

Conceptual props:

- title,
- evidence,
- severity or emphasis,
- confidence,
- drill-down target.

Dependencies:

- explicit insight rule or human-authored note.

Accessibility:

- avoid color-only emphasis.

Reuse potential:

- high.

## AnomalyMarker

Purpose:

- Mark unusual values on charts or timelines.

Conceptual props:

- timestamp,
- metric,
- reason,
- confidence,
- linked evidence.

Dependencies:

- anomaly detection rule.

Accessibility:

- marker must be reachable and described.

Reuse potential:

- future analytical products.

