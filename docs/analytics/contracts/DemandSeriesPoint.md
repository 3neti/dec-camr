# DemandSeriesPoint Contract

## Purpose

`DemandSeriesPoint` is CAMR's derived analytics contract for kW demand windows.

It turns accepted `TelemetryPoint` boundary readings into report-compatible demand values using the current hardened report formula:

```text
((max.whTotal - min.whTotal) / elapsedMinutes) * 60 * meterMultiplier
```

The contract exists so future analytics UI can inspect demand curves and peak markers without duplicating report logic in Vue.

## Scope

This contract covers demand series points only.

It does not implement:

- charts,
- routes,
- Vue pages,
- consumption series,
- building comparison,
- tariff calculation,
- report workbook changes.

## Relationship To TelemetryPoint

`DemandSeriesPoint` depends on `TelemetryPoint`.

TelemetryPoint provides:

- timestamp,
- `whTotal`,
- meter/site/building/gateway context,
- confidence,
- identifier match strategy,
- source lineage.

DemandSeriesPoint preserves lineage from the min and max TelemetryPoint records.

## Relationship To Reports

The calculation is intentionally report-compatible:

```text
((max.wh_total - min.wh_total) / elapsedMinutes) * 60 * meter_multiplier
```

The report endpoint currently emits only windows with positive `kw_demand`. Analytics keeps zero, negative, and incomplete windows visible with confidence metadata so analysts can distinguish demand absence from insufficient evidence.

## Conceptual Shape

```text
DemandSeriesPoint
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
│   ├── kwDemand
│   ├── elapsedMinutes
│   └── multiplier
├── boundaryReadings
│   ├── minReading
│   └── maxReading
├── peakMarker
├── missingData
├── confidence
└── sourceLineage
```

## Supported Grains

Current grains:

| Grain | Minutes | Min boundary rule | Max boundary rule |
|---|---:|---|---|
| `hourly` | 60 | first point at or after `periodStart - 5 minutes` | first point at or after `periodStart + 55 minutes` |
| `fifteen-minute` | 15 | last point at or before `periodStart + 14 minutes` | first point at or after `periodStart + 14 minutes` |

The boundary rules mirror the existing report behavior.

## Zero-Min Fallback

The legacy report behavior treats a min boundary reading with `wh_total = 0` as unusable for demand calculation. In that case, the min reading value and timestamp are replaced with the max reading value and timestamp before calculating demand.

Effect:

- elapsed minutes becomes `1` when min and max collapse to the same timestamp,
- `whDelta` becomes `0`,
- `kwDemand` becomes unavailable/null in the analytics contract,
- confidence becomes `Unknown`.

This prevents a legacy zero/default min counter from producing an artificial demand spike.

## Fields

Top-level fields:

- `periodStart`: ISO-8601 start of the demand window.
- `periodEnd`: ISO-8601 next period boundary.
- `grain`: currently `hourly` or `fifteen-minute`.
- `context`: meter/site/building/gateway context.
- `kwDemand`: calculated demand value, or null when insufficient evidence exists.
- `minReading`: lower boundary TelemetryPoint summary, or null.
- `maxReading`: upper boundary TelemetryPoint summary, or null.
- `elapsedMinutes`: elapsed minutes between min and max readings.
- `multiplier`: meter multiplier used in calculation.
- `peakMarker`: marks the highest positive demand value in the returned series.
- `missingData`: missing boundary metadata.
- `confidence`: trust metadata.
- `sourceLineage`: calculation and source point metadata.

## Confidence

Supported confidence levels:

- `Calculated`: demand was calculated from measured TelemetryPoint boundary readings and positive delta.
- `Incomplete`: a required boundary reading is missing or boundary TelemetryPoint confidence is incomplete/unknown.
- `Unknown`: both boundary readings exist, but the delta is zero or negative and should not be treated as measured demand without review.

`DemandSeriesPoint` does not produce `Measured` because demand is derived from telemetry counters.

## Source Lineage

Source lineage includes:

- contract name,
- calculation formula,
- report compatibility marker,
- min TelemetryPoint ID,
- max TelemetryPoint ID,
- min identifier match strategy,
- max identifier match strategy,
- source contract.

## Invariants

- Must use persisted telemetry through `TelemetryPoint`.
- Must not read fake Vue data.
- Must not change report formulas.
- Must not hide missing boundary readings.
- Must expose multiplier.
- Must expose elapsed minutes.
- Must expose min and max boundary readings.
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
  "kwDemand": 287.5,
  "elapsedMinutes": 60,
  "multiplier": 1.25,
  "confidence": {
    "level": "Calculated"
  },
  "sourceLineage": {
    "calculation": "((max.whTotal - min.whTotal) / elapsedMinutes) * 60 * meterMultiplier",
    "sourceContract": "TelemetryPoint"
  }
}
```
