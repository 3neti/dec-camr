# Analytics Data Foundation

## Purpose

The Operator Console is interaction-driven. Analytics is data-driven. Before CAMR designs analytics screens, it must define which data is trusted, how it is aggregated, and how uncertainty is shown.

## Canonical Data Sources

Primary sources:

- `meter_data`: interval telemetry and electrical readings.
- `meter_details`: meter identity, status, site, building, gateway, multiplier, and role context.
- `meter_rtu`: gateway identity, communication state, firmware, update flags.
- `meter_site`: site/building context and communication freshness.
- `meter_building_table`: building code and building description.
- `meter_location_table`: physical location context.
- Reports: hardened report calculations and export semantics.
- Telemetry-derived structures: future contracts created from raw telemetry for analytics performance or clarity.

Data-source rule:

- Use raw canonical sources for traceability.
- Use derived structures only when their lineage and refresh rules are documented.

## Data Grain

Supported analytical grains should be introduced deliberately:

- 15-minute: closest to operational interval analysis and demand windows.
- Hourly: useful for daily patterns and report previews.
- Daily: useful for building comparison and manager review.
- Monthly: useful for finance and executive review.
- Yearly: useful for sustainability and long-term trend review.

Grain rule:

- Never compare values across grains without labeling the grain.
- Never imply a daily value is complete when underlying intervals are incomplete.

## Aggregation Rules

Common rules:

- Sum: consumption over a window.
- Average: representative operating values.
- Maximum: peak value.
- Minimum: valley value.
- Demand: maximum or interval demand according to approved report semantics.
- Count: availability, telemetry packets, communicating meters.

Aggregation rule:

- The aggregation method must be visible or inferable from labels.
- Analytics must distinguish total energy from peak demand.

## Missing Data

Missing, zero, estimated, and unknown are different.

Definitions:

- Missing: no reading exists for an expected interval.
- Zero: a reading exists and value is zero.
- Estimated: value is filled or inferred from a rule.
- Unknown: system cannot determine whether data is missing, zero, or unavailable.

Display rule:

- Missing data should reduce confidence.
- Zero should not reduce confidence if measured.
- Estimated data must be marked.
- Unknown should not be silently included in precise comparisons.

## Time

Analytics must treat time as a first-class domain concern.

Relevant time concepts:

- Gateway time: time reported by field device or meter source.
- Server time: time recorded by Laravel/database ingestion.
- Timezone: configured business timezone for analysis.
- DST: daylight saving transition rules where applicable.
- Clock drift: divergence between gateway and server time.
- Aggregation boundaries: start and end rules for hour/day/month windows.

Time rule:

- Analytics must define which timestamp drives each calculation.
- Boundary behavior must be consistent with existing report semantics unless changed by architect decision.

## Derived Metrics

Initial derived metrics:

- Consumption: energy over a time window.
- Demand: peak or interval demand over a time window.
- Availability: percent or count of expected telemetry present.
- Communication Rate: how often expected devices reported.
- Freshness: age of last reading or gateway communication.
- Load Factor: average load divided by peak load.
- Power Factor: real power relationship to apparent power.

Derived metric rule:

- Each derived metric needs a definition, grain, source fields, and confidence behavior before visualization.

## Data Trust

Analytics should expose confidence levels:

```text
Measured
Calculated
Estimated
Incomplete
```

Measured:

- Direct reading from telemetry.

Calculated:

- Deterministic result from measured values.

Estimated:

- Inferred or filled value using approved rules.

Incomplete:

- Value affected by missing or insufficient data.

Trust rule:

- Operator Console can often show current state without confidence labels.
- Analytics must show confidence when users make historical, financial, or performance decisions.

## Data Contract Requirement

Before any Analytics workspace is implemented, create an Analytics Data Contract slice.

That slice must define:

- selected entities,
- selected time range,
- grain,
- metric,
- values,
- comparison context,
- confidence,
- missing-data summary,
- source lineage.

