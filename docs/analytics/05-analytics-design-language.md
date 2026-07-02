# Analytics Design Language

## Purpose

Visualization begins only after vision, personas, grammar, data foundation, and journeys are clear. The design language defines which visual forms fit which analytical question.

## Chart Selection

Line charts:

- Use for trends over time.
- Best for consumption, demand, power factor, and rolling averages.
- Avoid too many overlapping lines without filtering.

Stacked area:

- Use for contribution over time.
- Best when showing how meters or buildings contribute to total consumption.
- Avoid when exact individual values matter.

Bar charts:

- Use for ranked or discrete comparisons.
- Best for building totals, daily totals, and top contributors.

Heat maps:

- Use for intensity over two dimensions.
- Best for hour-of-day by day-of-week load patterns.

Calendar heat maps:

- Use for long-range daily patterns.
- Best for executive or sustainability review.

Scatter plots:

- Use for relationship and outlier detection.
- Best for demand versus consumption or load factor versus peak.

Histograms:

- Use for distribution.
- Best for demand interval distribution or power factor distribution.

Treemaps:

- Use for contribution at a glance.
- Best for portfolio-level building or meter contribution.
- Avoid for precise comparison.

Sankey diagrams:

- Future concept.
- Use only if energy-flow relationships are well-defined and valuable.

Tables:

- Use when exact values, traceability, sorting, or export confidence matter.
- Tables remain essential for auditors and finance.

KPI cards:

- Use for headline metrics.
- Must include period, comparison context, and confidence when analytical.

Comparison grids:

- Use when entities need side-by-side review.
- Best for building, site, and meter comparisons.

## Color Philosophy

Analytics should not inherit Operator Console red/amber/green dominance.

Operator colors communicate immediate action state. Analytics colors should communicate:

- trend direction,
- comparison grouping,
- confidence,
- historical context,
- anomaly emphasis.

Rules:

- Do not rely on color alone.
- Use labels, markers, and patterns for accessibility.
- Reserve strong warning colors for analytical anomalies or low confidence, not ordinary variance.
- Use consistent colors for entity comparison within a view.

## Interaction

Interactions should support investigation:

- select entity,
- change time range,
- change grain,
- compare period,
- inspect point,
- filter out noise,
- export or share evidence.

Do not add interactions that create visual novelty without improving investigation.

## Zoom And Brushing

Use zoom and brushing when:

- the user needs to isolate a time window,
- high-resolution intervals exceed readable width,
- anomaly windows need focused inspection.

Zoom must preserve context. Users should not lose the selected time range or comparison reference.

## Selection

Selection should be explicit:

- selected building,
- selected meter,
- selected range,
- selected aggregation,
- selected comparison.

Analytics should avoid hidden state that changes results without visible context.

## Density

Analytics can be denser than Operator Console, but density must remain structured:

- summary first,
- evidence second,
- raw detail third.

Use progressive disclosure for technical detail.

## Tooltips

Tooltips should show:

- timestamp or bucket,
- metric value,
- unit,
- confidence,
- source or aggregation note when relevant.

Tooltips should not be the only place where critical meaning appears.

## Empty States

Empty analytical states must explain why no result exists:

- no readings,
- missing required filter,
- insufficient confidence,
- unsupported grain,
- outside available data window.

Empty states should suggest the next useful action.

