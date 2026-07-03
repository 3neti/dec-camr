# ConsumptionSeriesPoint Contract

## Purpose

`ConsumptionSeriesPoint` is CAMR's first derived analytics series contract.

It turns accepted `TelemetryPoint` boundary readings into report-compatible consumption windows using the current hardened report formula:

```text
(end.whTotal - start.whTotal) * meterMultiplier
```

The contract exists so future analytics UI can consume a stable consumption series without duplicating report logic in Vue or hiding missing boundary data.

## Scope

This contract covers consumption series points only.

It does not implement:

- charts,
- routes,
- Vue pages,
- demand series,
- building comparison,
- forecast,
- report workbook changes.

## Relationship To TelemetryPoint

`ConsumptionSeriesPoint` depends on `TelemetryPoint`.

TelemetryPoint provides:

- timestamp,
- `whTotal`,
- meter/site/building/gateway context,
- confidence,
- identifier match strategy,
- source lineage.

ConsumptionSeriesPoint preserves lineage from the start and end TelemetryPoint records.

## Relationship To Reports

The calculation is intentionally report-compatible:

```text
(end.wh_total - start.wh_total) * meter_multiplier
```

The report endpoint currently drops windows whose delta is not positive. Analytics does not silently drop incomplete windows. It exposes them with confidence and missing-data metadata so analysts can distinguish "no consumption" from "not enough evidence."

## Conceptual Shape

```text
ConsumptionSeriesPoint
├── period
│   ├── periodStart
│   ├── periodEnd
│   └── grain
├── context
│   ├── meterIdentifier
│   ├── meterId
│   ├── meterName
│   ├── siteId
│   ├── siteCode
│   ├── buildingCode
│   ├── gatewayId
│   ├── gatewaySn
│   └── gatewayMac
├── value
│   ├── kwhTotal
│   └── multiplier
├── boundaryReadings
│   ├── startReading
│   └── endReading
├── missingData
├── confidence
└── sourceLineage
```

## Supported Grains

Current grains:

| Grain | Minutes | Report-compatible end boundary |
|---|---:|---:|
| `hourly` | 60 | `periodStart + 55 minutes` |
| `daily` | 1440 | `periodStart + 1435 minutes` |

The five-minute offset mirrors the existing report behavior.

## Boundary Selection Rule

For each consumption window:

- Start boundary uses the first `TelemetryPoint` at or after `periodStart - 5 minutes`.
- End boundary uses the first `TelemetryPoint` at or after the report-compatible end boundary.
- Hourly report-compatible end boundary is `periodStart + 55 minutes`.
- Daily report-compatible end boundary is `periodStart + 1435 minutes`.

If multiple readings exist after a boundary, the earliest qualifying reading is used. Later readings in the same window must not replace the first qualifying boundary reading.

## Fields

Top-level fields:

- `periodStart`: ISO-8601 start of the consumption window.
- `periodEnd`: ISO-8601 next period boundary.
- `grain`: currently `hourly` or `daily`.
- `context`: meter/site/building/gateway context.
- `kwhTotal`: calculated kWh value, or null when a boundary is missing.
- `startReading`: boundary TelemetryPoint summary, or null.
- `endReading`: boundary TelemetryPoint summary, or null.
- `multiplier`: meter multiplier used in calculation.
- `missingData`: missing boundary metadata.
- `confidence`: trust metadata.
- `sourceLineage`: calculation and source point metadata.

## Confidence

Supported confidence levels:

- `Calculated`: consumption was calculated from measured TelemetryPoint boundary readings and positive delta.
- `Incomplete`: a required boundary reading is missing or boundary TelemetryPoint confidence is incomplete/unknown.
- `Unknown`: both boundary readings exist, but the delta is zero or negative and should not be treated as measured consumption without review.

`ConsumptionSeriesPoint` does not produce `Measured` because consumption is derived from telemetry counters.

## Source Lineage

Source lineage includes:

- contract name,
- calculation formula,
- report compatibility marker,
- start TelemetryPoint ID,
- end TelemetryPoint ID,
- start identifier match strategy,
- end identifier match strategy,
- source contract.

This lineage is mandatory. Analytics must be able to explain how a consumption number was produced.

## Invariants

- Must use persisted telemetry through `TelemetryPoint`.
- Must not read fake Vue data.
- Must not change report formulas.
- Must not hide missing boundary readings.
- Must expose multiplier.
- Must expose start and end boundary readings.
- Must expose confidence.
- Must preserve TelemetryPoint identifier match strategy.
- Must not add routes, Vue pages, or charts.

## Example

```json
{
  "periodStart": "2026-07-01T00:00:00+08:00",
  "periodEnd": "2026-07-01T01:00:00+08:00",
  "grain": "hourly",
  "context": {
    "meterIdentifier": "MTR-AN-001",
    "meterId": 1,
    "meterName": "MTR-AN-001",
    "buildingCode": "BLDG-A"
  },
  "kwhTotal": 287.5,
  "multiplier": 1.25,
  "startReading": {
    "whTotal": 1000,
    "identifierMatchStrategy": "meter_name"
  },
  "endReading": {
    "whTotal": 1230,
    "identifierMatchStrategy": "meter_name"
  },
  "confidence": {
    "level": "Calculated"
  },
  "sourceLineage": {
    "calculation": "(end.whTotal - start.whTotal) * meterMultiplier",
    "sourceContract": "TelemetryPoint"
  }
}
```
