# Analytics Workbench Methodology

## Purpose

This methodology captures the repeatable process for designing an Analytics Workbench. CAMR is the first application, but the method should transfer to other 3neti platforms.

## Method Sequence

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
Architect Review
↓
Implementation
```

## Vision Before Implementation

Define what the analytical product is for before deciding pages, charts, or components.

For CAMR:

- Analytics explains historical energy behavior.
- It does not replace live operations.
- It does not replace formal reports.

## Personas Before Pages

Analytics exists for different decisions than operator workflows.

Design must identify:

- investigators,
- reviewers,
- executives.

Each class needs different depth, cadence, and evidence.

## Grammar Before Charts

Analytics needs a shared language before visualization.

Terms such as consumption, demand, baseline, variance, and forecast must have relationships and limits. A chart without agreed meaning creates false confidence.

## Data Foundation Before Visualization

Analytics is data-driven. The data foundation must define:

- canonical sources,
- grain,
- aggregation rules,
- missing data behavior,
- time semantics,
- derived metrics,
- confidence.

No analytics visualization should be implemented before its data contract is accepted.

## Journeys Before Screens

Analytics workflows are investigations.

A good journey starts with a question, gathers evidence, supports a decision, and ends with a share/export outcome when needed.

## Data Contracts Before Components

Components must consume accepted data contracts.

Do not build charts from ad hoc frontend transformations unless the transformation is presentational only.

## Components Before Workspaces

Once contracts are stable, build reusable primitives before assembling large workspaces.

This prevents duplicated chart, filter, empty-state, and export patterns.

## Work Queue Before Coding

Implementation should proceed through small work items with clear acceptance criteria.

The work queue should be specific enough that Spark or a future Codex model can execute one item at a time without rediscovering architecture.

## Architect Review Between Slices

Each analytics slice should stop for review before proceeding.

Review should cover:

- data semantics,
- confidence behavior,
- journey fit,
- accessibility,
- performance,
- report compatibility,
- scope control.

## Reuse Across 3neti

The methodology can apply beyond CAMR.

Track AI:

- investigate operational trends, exceptions, and model behavior over time.

x-change:

- analyze settlement behavior, participant movement, and exception patterns.

Treasury:

- review cash movement, variance, exposure, and forecast confidence.

Licensing:

- analyze usage, entitlement changes, renewal risk, and customer patterns.

Heartbeat:

- review uptime, incident patterns, recovery behavior, and service health trends.

Future platforms:

- use the same sequence whenever users need historical investigation rather than live operation.

## Core Rule

Do not start with charts.

Start with the decision the user needs to make, the data needed to support it, and the confidence required to trust it.

