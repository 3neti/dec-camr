# DataTrustIndicator Contract

## Purpose

`DataTrustIndicator` is CAMR's reusable analytics trust primitive.

It gives points, series, summaries, comparisons, and future exports a common way to say:

```text
How much confidence should a user place in this value?
```

This contract exists because Analytics must expose uncertainty instead of hiding it behind precise-looking numbers.

## Scope

This contract covers trust metadata only.

It does not implement:

- charts,
- routes,
- Vue pages,
- report formulas,
- rollups,
- forecasts,
- user-facing analytics screens.

## Relationship To Existing Contracts

Current analytics contracts already expose confidence arrays:

- `TelemetryPoint`
- `ConsumptionSeriesPoint`
- `DemandSeriesPoint`
- `BuildingConsumptionSummary`

`DataTrustIndicator` standardizes that concept so later UI and export work can consume trust consistently without learning each contract's internal missing-data shape.

It is additive in AN-006. Existing contracts are not rewritten to depend on it yet.

## Conceptual Shape

```text
DataTrustIndicator
├── level
├── reason
├── missingIntervalCount
├── source
│   ├── contract
│   └── sourceContract
├── warning
└── sourceLineage
```

## Levels

Supported levels come from `TelemetryPointConfidence`:

| Level | Meaning |
|---|---|
| `Measured` | Value comes directly from a telemetry measurement with usable context. |
| `Calculated` | Value is derived from measured values using a documented formula. |
| `Estimated` | Value is inferred or filled and must be visually distinguished from measured/calculated values. |
| `Incomplete` | Required evidence is missing. |
| `Unknown` | Evidence exists, but interpretation is ambiguous. |

## Fields

Top-level fields:

- `level`: trust level.
- `reason`: machine-readable and reviewable explanation.
- `missingIntervalCount`: count of missing expected intervals or boundaries.
- `source`: normalized source context.
- `warning`: user-facing caution message, or null when no warning is required.
- `sourceLineage`: original source lineage from the point, series, or summary.

## Warning Philosophy

Warnings should be present when the analytics value can mislead a user.

Examples:

- `Incomplete` should warn that expected evidence is missing.
- `Unknown` should warn that the value cannot be confidently interpreted.
- `Estimated` should warn that the value is not fully measured.
- `Measured` and `Calculated` usually do not need warnings when source lineage is complete.

Warnings are not a substitute for source lineage. They are a concise human-facing summary of uncertainty.

## Source Lineage

`DataTrustIndicator` must preserve source lineage rather than replacing it.

For example:

- a `ConsumptionSeriesPoint` trust indicator should keep its start/end TelemetryPoint IDs,
- a `DemandSeriesPoint` trust indicator should keep min/max TelemetryPoint IDs,
- a `BuildingConsumptionSummary` trust indicator should keep its source contract and included meter counts.

Analytics must be able to explain where a value came from before it can compare, chart, export, or summarize that value.

## Invariants

- Must not hide incomplete or unknown evidence.
- Must not downgrade source lineage into a generic boolean.
- Must not treat `Estimated` as equivalent to `Measured`.
- Must not convert missing data into zero.
- Must not add routes, Vue pages, charts, or report changes.
- Must be safe to attach to future analytics responses.

## Examples

Calculated summary:

```json
{
  "level": "Calculated",
  "reason": "Building total calculated from complete consumption series points.",
  "missingIntervalCount": 0,
  "source": {
    "contract": "BuildingConsumptionSummary",
    "sourceContract": "ConsumptionSeriesPoint"
  },
  "warning": null
}
```

Incomplete series:

```json
{
  "level": "Incomplete",
  "reason": "Consumption window is missing a required boundary reading.",
  "missingIntervalCount": 1,
  "warning": "Analysis is incomplete because 1 expected interval(s) are missing."
}
```

Unknown value:

```json
{
  "level": "Unknown",
  "reason": "Consumption delta is zero or negative and should not be treated as measured consumption without review.",
  "missingIntervalCount": 0,
  "warning": "Analysis includes values that cannot be confidently interpreted without review."
}
```
