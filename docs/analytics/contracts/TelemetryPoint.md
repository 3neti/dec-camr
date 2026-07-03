# TelemetryPoint Contract

## Purpose

`TelemetryPoint` is CAMR's canonical first analytics primitive.

It converts persisted operational telemetry into the smallest series-ready analytical shape:

```text
Time
↓
Value
↓
Confidence
↓
Context
```

The contract exists so later analytics work can build consumption, demand, power-quality, availability, and comparison series from a stable point shape instead of reading `meter_data` directly from every feature.

## Scope

`TelemetryPoint` covers raw telemetry points only.

It does not implement:

- consumption series,
- demand series,
- report formulas,
- chart data,
- analytics routes,
- Vue pages,
- rollups,
- forecasts.

Those belong to later analytics work items.

## Relationship To `meter_data`

`meter_data` remains the canonical raw telemetry source.

`TelemetryPoint` reads:

- `meter_data.id`,
- `meter_data.location`,
- `meter_data.meter_id`,
- `meter_data.datetime`,
- voltage/current/frequency/power/power-factor/energy fields,
- device state fields,
- source gateway MAC and firmware fields where present.

The point keeps raw identifiers because CAMR still has multiple telemetry identifier styles in circulation.

## Relationship To Reports

Reports continue to own formal workbook semantics and formulas.

`TelemetryPoint` does not replace report calculations. It provides the first analytics-safe point shape that later series contracts can use while preserving report-compatible identifiers and telemetry fields.

## Relationship To Dashboard And Live Operations

Dashboard and Live Operations remain optimized for current operational awareness.

`TelemetryPoint` is optimized for historical investigation. It can reuse the same persisted telemetry rows, but it exposes confidence and source lineage explicitly because analytics must explain where a number came from.

## Conceptual Shape

```text
TelemetryPoint
├── identifiers
│   ├── rawMeterIdentifier
│   ├── rawLocationIdentifier
│   ├── meterId
│   ├── meterName
│   ├── siteId
│   ├── siteCode
│   ├── buildingId
│   ├── buildingCode
│   ├── gatewayId
│   ├── gatewaySerial
│   └── gatewayMac
├── timestamp
├── measurements
│   ├── voltage
│   ├── current
│   ├── frequency
│   ├── powerFactor
│   ├── power
│   ├── energy
│   └── deviceState
├── confidence
└── sourceLineage
```

Current implementation names `rawLocationIdentifier` as `rawLocation`, and `gatewaySerial` as `gatewaySn`.

## Fields

Top-level fields:

- `id`: `meter_data.id`.
- `rawMeterIdentifier`: raw `meter_data.meter_id`.
- `rawLocation`: raw `meter_data.location`.
- `timestamp`: ISO-8601 form of `meter_data.datetime`.
- `context`: resolved meter/site/building/gateway context.
- `measurements`: grouped telemetry measurements.
- `confidence`: trust metadata.
- `sourceLineage`: provenance metadata.

## Context

Context fields:

- `meterId`
- `meterName`
- `siteId`
- `siteCode`
- `buildingId`
- `buildingCode`
- `gatewayId`
- `gatewaySn`
- `gatewayMac`

Context may be partially null when telemetry exists but no matching maintenance record is available.

## Measurements

Measurement groups:

- `voltage.a`, `voltage.b`, `voltage.c`
- `current.a`, `current.b`, `current.c`
- `frequency`
- `powerFactor`
- `power.watt`, `power.va`, `power.var`
- `energy.whDelivered`, `energy.whReceived`, `energy.whNet`, `energy.whTotal`
- `device.gatewayMacFromTelemetry`
- `device.softRev`
- `device.relayStatus`
- `device.gensetStatus`
- `device.secondaryTimestamp`

These are raw point measurements. Derived values belong to later series contracts.

## Confidence

Supported confidence levels:

- `Measured`: row has usable timestamp, meter context, and non-zero measurement signal.
- `Incomplete`: row exists but required context could not be resolved.
- `Unknown`: row has meter context but contains only zero numeric measurements, which may be true zero or legacy default values.

Future contracts may use:

- `Calculated`
- `Estimated`

Those are intentionally not produced by `TelemetryPoint` today because this contract does not calculate or estimate values.

## Source Lineage

Source lineage fields:

- `table`: currently `meter_data`.
- `rowId`: `meter_data.id`.
- `timestampColumn`: currently `datetime`.
- `meterIdentifierColumn`: currently `meter_id`.
- `locationColumn`: currently `location`.
- `joinedMeterContext`: boolean indicating whether meter context was resolved.
- `identifierMatchStrategy`: how meter context was resolved.

Source lineage is required. Analytics must be able to explain where a value came from before it can compare, aggregate, chart, or export that value.

## Identifier Matching

Current supported strategies:

| Strategy | Meaning |
|---|---|
| `meter_name` | `meter_data.meter_id` matched `meter_details.meter_name`. This is the report-compatible identifier path. |
| `meter_id` | `meter_data.meter_id` matched `meter_details.meter_id`. This preserves numeric/database ID compatibility. |
| `none` | No meter context was resolved. |

The strategy is part of the contract because CAMR is in a transition period where historical and simulated telemetry can use different identifier styles.

## Invariants

- `TelemetryPoint` must use persisted database state only.
- `TelemetryPoint` must not hardcode fake analytics values.
- `TelemetryPoint` must not calculate consumption or demand.
- `TelemetryPoint` must not hide unresolved meter context.
- `TelemetryPoint` must expose identifier match strategy.
- `TelemetryPoint` must preserve raw identifiers.
- `TelemetryPoint` must keep confidence separate from measurements.
- `TelemetryPoint` must not modify report formulas, dashboard behavior, routes, or Vue pages.

## Examples

Report-compatible telemetry:

```json
{
  "rawMeterIdentifier": "MTR-AN-001",
  "rawLocation": "BLDG-A",
  "context": {
    "meterId": 1,
    "meterName": "MTR-AN-001",
    "buildingCode": "BLDG-A",
    "gatewaySn": "GW-AN-001"
  },
  "confidence": {
    "level": "Measured"
  },
  "sourceLineage": {
    "table": "meter_data",
    "identifierMatchStrategy": "meter_name"
  }
}
```

Numeric compatibility telemetry:

```json
{
  "rawMeterIdentifier": "1",
  "context": {
    "meterId": 1,
    "meterName": "MTR-AN-001"
  },
  "sourceLineage": {
    "identifierMatchStrategy": "meter_id"
  }
}
```

Unmatched telemetry:

```json
{
  "rawMeterIdentifier": "MISSING-METER",
  "context": {
    "meterId": null
  },
  "confidence": {
    "level": "Incomplete",
    "missingContext": true
  },
  "sourceLineage": {
    "identifierMatchStrategy": "none"
  }
}
```
