# BuildingConsumptionSummary Contract

## Purpose

`BuildingConsumptionSummary` compares buildings over a selected period using accepted `ConsumptionSeriesPoint` contracts.

It is the first analytics summary contract. It does not invent new formulas. It aggregates calculated consumption series points and preserves missing-data and confidence metadata.

## Scope

This contract covers building-level consumption summaries only.

Current AN-005 behavior summarizes all buildings available to the action. It does not yet accept site, building, or user-access scope arguments.

Before this contract feeds UI exposure, future analytics data contracts must support:

- site filtering,
- building filtering,
- user-access-aware scoping,
- scoped summaries that do not leak unauthorized building data.

It does not implement:

- Vue pages,
- charts,
- routes,
- demand summaries,
- tariff calculations,
- report workbook changes.

## Relationship To ConsumptionSeriesPoint

`BuildingConsumptionSummary` depends on `ConsumptionSeriesPoint`.

ConsumptionSeriesPoint provides:

- period,
- grain,
- meter/building/site context,
- `kwhTotal`,
- missing boundary metadata,
- confidence,
- source lineage.

The summary aggregates only `Calculated` series point values into `totalKwh`. Incomplete and unknown points are counted and exposed instead of hidden.

## Conceptual Shape

```text
BuildingConsumptionSummary
├── building
│   ├── buildingId
│   ├── buildingCode
│   └── buildingName
├── site
│   ├── siteId
│   └── siteCode
├── totalKwh
├── meterCount
├── seriesPointCount
├── missingData
├── comparison
├── confidence
└── sourceLineage
```

## Fields

Top-level fields:

- `buildingId`
- `buildingCode`
- `buildingName`
- `site`
- `totalKwh`
- `meterCount`
- `seriesPointCount`
- `missingData`
- `comparison`
- `confidence`
- `sourceLineage`

## Missing Data

Missing-data fields:

- `missingIntervalCount`
- `incompleteSeriesPointCount`
- `unknownSeriesPointCount`

These fields allow analytics screens to show that a building's total is based on partial evidence.

## Comparison

Comparison fields:

- `rankBasis`: currently `totalKwh`.
- `isTopConsumer`: true when the building has the highest positive `totalKwh` in the result set.
- `topConsumerKwh`: highest positive total in the result set.

This is intentionally minimal. More advanced variance, baseline, and benchmarking belong to later analytics work.

## Confidence

Supported confidence levels:

- `Calculated`: all included series points are calculated and no incomplete/unknown windows are present.
- `Incomplete`: no active meters exist, no series points exist, or at least one series point is incomplete.
- `Unknown`: at least one series point has unknown confidence but no incomplete point exists.

## Source Lineage

Source lineage includes:

- contract name,
- source contract,
- building table,
- meter table,
- included meter count,
- calculated series point count.

## Invariants

- Must use `ConsumptionSeriesPoint` as its source.
- Must not calculate directly from `meter_data`.
- Must not hide incomplete or unknown series points.
- Must not be exposed to user-facing analytics UI without scoped-access filtering.
- Must not change report formulas.
- Must not add routes, Vue pages, or charts.

## Example

```json
{
  "buildingCode": "BLDG-A",
  "buildingName": "Building A",
  "site": {
    "siteId": 1,
    "siteCode": "SITEA"
  },
  "totalKwh": 450,
  "meterCount": 2,
  "seriesPointCount": 2,
  "missingData": {
    "missingIntervalCount": 0,
    "incompleteSeriesPointCount": 0,
    "unknownSeriesPointCount": 0
  },
  "comparison": {
    "rankBasis": "totalKwh",
    "isTopConsumer": true
  },
  "confidence": {
    "level": "Calculated"
  }
}
```
