# CAMR Analytics Vision

## Purpose

The CAMR Analytics Workbench exists to help users understand historical energy behavior, operational patterns, and business impact. It is a parallel product to the Operator Console, not a new dashboard page.

The Operator Console answers:

- What is happening now?
- What needs attention?
- What should the operator do next?

Analytics answers:

- What happened?
- Why did it happen?
- How is it changing?
- What patterns are emerging?
- Which building, site, meter, or time window explains the change?

## Philosophy

Analytics must be investigation-first. The workbench should help a user move from a question to evidence, then from evidence to a decision. Charts are only useful when they support that investigation.

The design program follows this sequence:

```text
Vision
↓
Personas
↓
Grammar
↓
Data Foundation
↓
Journeys
↓
Design Language
↓
Component Library
↓
Implementation Roadmap
↓
Implementation Notes
↓
Work Queue
↓
Methodology
```

## Scope

Analytics should eventually support:

- historical consumption review,
- demand and peak analysis,
- load profile inspection,
- building and meter comparisons,
- abnormal usage investigation,
- power factor and quality review,
- missing telemetry and data quality review,
- executive summaries,
- future baselines and forecasting after foundational metrics are proven.

## Boundaries

Analytics must not change:

- legacy report formulas,
- report export semantics,
- RTU/device protocol behavior,
- Operator Console route behavior,
- authorization semantics,
- maintenance CRUD workflows.

Analytics may introduce new views, data contracts, and visualization primitives later, but those must be designed as separate implementation slices.

## Relationship With Operator Console

The Operator Console remains the live operational surface. It prioritizes freshness, current device state, pending updates, and operator action.

Analytics uses historical and aggregated data. It should be accessible from operator contexts, but it should not crowd the live console or turn operational alerts into historical analysis widgets.

## Relationship With Reports

Reports remain formal output workflows: filters, payloads, exports, workbook files, and downstream consumer compatibility.

Analytics may preview, explain, and compare energy behavior. When formal export is required, Analytics should either link to Reports or use approved analytics export behavior without breaking existing report contracts.

## Relationship With Telemetry Simulator

The telemetry simulator is essential for Analytics because static demo data cannot prove time-based behavior. Analytics design should use simulator scenarios to validate trends, missing data, recovery windows, peak periods, and report-ready ranges.

## Relationship With Lifecycle Scenario Runner

Lifecycle scenarios should eventually orchestrate analytics review flows:

- seed profile,
- telemetry simulation,
- analytics question,
- journey test or demonstration script,
- export/share outcome.

The scenario runner should remain orchestration only. It must not become analytics business logic.

## Analytics Is Not

Analytics is not another dashboard.

Analytics is not a replacement for Reports.

Analytics is not chart-first design.

Analytics is not a place to hardcode attractive demo numbers.

Analytics is not an excuse to reinterpret legacy report semantics without approval.

