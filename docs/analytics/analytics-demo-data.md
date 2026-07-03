# Analytics Demo Data

## Purpose

`analytics-demo` prepares a deterministic analytics showcase dataset before any Analytics UI route is mounted.

The scenario exists because analytics needs patterns, not just rows. It uses the existing demo seed profile, then writes real `meter_data` telemetry through the simulator layer so future analytics pages can display meaningful examples immediately.

## Command

```bash
php artisan camr:scenario analytics-demo
```

Deterministic anchor:

```text
2026-07-01 08:00:00
```

The generated analytics window is:

```text
2026-07-01 00:00:00 through 2026-07-01 23:55:00
```

## Data Story Produced

The scenario uses the first four active demo meters across distinct buildings.

| Pattern | Purpose | Expected Analytics Evidence |
|---|---|---|
| Normal consumption | Stable baseline building/meter | Calculated hourly consumption with a normal daily curve. |
| Abnormal high consumption | Clear high-consumption comparison target | A second building consumes significantly more than the baseline and ranks first in building comparison. |
| Daily load profile | Operator-readable shape | Low overnight load, rising morning load, midday peak, and evening decline. |
| Demand peak | Demand-curve readiness | The high-consumption meter has an obvious demand peak. |
| Incomplete window | Missing-data readiness | A meter has a missing boundary reading and produces `Incomplete` confidence. |
| Unknown / zero-delta window | Ambiguous analytics readiness | A meter has a zero-delta window and produces `Unknown` confidence. |
| Building comparison | Ranked comparison readiness | Multiple buildings produce meaningful comparison output. |
| Load profile explorer | Coherent investigation readiness | Consumption and demand series exist for the same high-consumption context. |

## Confidence States

The scenario naturally exercises:

- `Measured` at the `TelemetryPoint` level.
- `Calculated` for complete consumption, demand, and building summaries.
- `Incomplete` for missing boundary intervals.
- `Unknown` for zero-delta / non-informative windows.

## Boundaries

This scenario does not:

- mount an Analytics UI route,
- create charts,
- create a new seed profile,
- change report formulas,
- change report routes,
- change dashboard/operator-console behavior.

## Future UI Use

Future analytics UI review should start with:

```bash
php artisan migrate:fresh --force
php artisan camr:scenario analytics-demo
```

Then open the future Analytics Workbench once an analytics route exists.

Until then, the scenario is validated through analytics contract tests.
