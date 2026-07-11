# CAMR SCADA Simulation Manual

## Purpose

This manual explains how to make CAMR behave like a live SCADA-style operator console during development, QA, UI review, analytics review, and demonstrations.

CAMR has three simulation entrypoints:

- `camr:simulate` generates deterministic telemetry from seeded CAMR entities.
- `camr:replay-telemetry` reads gateway-shaped CSV rows and sends them through the live RTU ingest path.
- `camr:scenario` orchestrates seed profiles plus simulator or replay steps into repeatable operator scenarios.

Use this manual when you want the Dashboard, Live Operations, Reports, and Analytics screens to show meaningful operational data instead of empty or static records.

## Safety Rules

- Simulation and replay write real database state unless `--dry-run` is used.
- Mutating simulation commands are disabled in production unless `--allow-production` is explicitly passed.
- Prefer `php artisan migrate:fresh --force` before demos when you need a clean, deterministic state.
- Use `--dry-run` before unfamiliar scenario or replay commands.
- File replay uses the live RTU ingest action. It does not insert directly into `meter_data`.
- Gateway payload shape is preserved. The simulator/replay layer must adapt to CAMR, not require gateway payload changes.

## Quick Start

### Standard operator UI review

```bash
php artisan migrate:fresh --force
php artisan camr:scenario operations-gateway-recovery
npm run dev
```

Open:

```text
http://dec-camr.test/site
```

Login:

```text
Username: ops_admin_demo
Password: Demo@1234
```

### Analytics review

```bash
php artisan migrate:fresh --force
php artisan camr:scenario analytics-demo
npm run dev
```

Open:

```text
http://dec-camr.test/analytics
```

Use this when reviewing consumption, demand, building comparison, load profile, and data-trust behavior.

### Live SCADA replay review

```bash
php artisan migrate:fresh --force
php artisan camr:scenario live-scada-demo
npm run dev
```

Open:

```text
http://dec-camr.test/site
http://dec-camr.test/analytics
```

Use this when you want dashboard and analytics data produced by replayed gateway-style telemetry.

## Seed Profiles

Use seed profiles to create the entities that simulation acts on.

```bash
php artisan camr:seed-profile --profile=minimal
php artisan camr:seed-profile --profile=demo
php artisan camr:seed-profile --profile=heavy
```

| Profile | Use For | Notes |
|---|---|---|
| `minimal` | Fast developer checks and fresh-install smoke tests | Smallest useful dataset. |
| `demo` | UI review, screenshots, operator journeys, and customer-style demos | Recommended default. |
| `heavy` | Pagination, DataTables, performance, and high-volume checks | Use when testing density and load. |

Default profile password:

```text
Demo@1234
```

Common demo users:

| Persona | Username | Password | Notes |
|---|---|---|---|
| Demo Admin | `ops_admin_demo` | `Demo@1234` | Primary review login. |
| Demo Operations Engineer | `ops_eng_demo` | `Demo@1234` | Operations workflow user. |
| Demo Maintenance Technician | `maintenance_demo` | `Demo@1234` | Maintenance workflow user. |
| Demo Analyst | `analyst_demo` | `Demo@1234` | Report and analytics workflow user. |

## Telemetry Simulator

The simulator generates telemetry from existing seeded companies, sites, buildings, gateways, meters, and meter locations.

Examples:

```bash
php artisan camr:simulate --profile=demo --duration=10m --speed=real
php artisan camr:simulate --profile=demo --scenario=offline-recovery --anchor="2026-07-01 08:00:00"
php artisan camr:simulate --profile=demo --scenario=report-window --duration=65m --speed=fast
php artisan camr:simulate --profile=demo --scenario=analytics-demo --duration=24h --speed=fast
php artisan camr:simulate --dry-run
```

Supported options:

| Option | Values | Purpose |
|---|---|---|
| `--profile` | `minimal`, `demo`, `heavy` | Chooses entity volume. |
| `--duration` | Examples: `10m`, `1h`, `24h` | Chooses simulated time span. |
| `--speed` | `slow`, `real`, `fast` | Chooses simulator cadence. |
| `--scenario` | `normal`, `offline-recovery`, `report-window`, `analytics-demo` | Chooses operational behavior. |
| `--deterministic` | `1`, `0`, `true`, `false` | Enables reproducible output by default. |
| `--anchor` | `Y-m-d H:i:s` | Sets deterministic time anchor. |
| `--dry-run` | flag | Validates without writes. |
| `--allow-production` | flag | Allows intentional production execution. |

Deterministic default anchor:

```text
2026-07-01 08:00:00
```

## Simulator Scenarios

| Scenario | Purpose | Best For |
|---|---|---|
| `normal` | Generates ordinary telemetry with periodic gateway update flags. | General dashboard checks. |
| `offline-recovery` | Creates mixed gateway status with stale/offline and recovering entities. | Live Operations and gateway health review. |
| `report-window` | Creates telemetry windows suitable for report/export workflows. | Reports UX and report tests. |
| `analytics-demo` | Creates normal consumption, high consumption, demand peaks, incomplete windows, unknown windows, and ranked building comparison signals. | Analytics Workbench review. |

## Telemetry Replay

Telemetry replay reads gateway-shaped CSV rows and passes each valid row through the same live RTU ingest action used by `POST /http_post_server.php`.

Canonical fixture:

```text
database/fixtures/telemetry/scada-demo-readings.csv
```

Replay command:

```bash
php artisan camr:replay-telemetry --file=database/fixtures/telemetry/scada-demo-readings.csv --anchor="2026-07-01 08:00:00"
```

Dry run:

```bash
php artisan camr:replay-telemetry --file=database/fixtures/telemetry/scada-demo-readings.csv --dry-run
```

Looped replay:

```bash
php artisan camr:replay-telemetry --file=database/fixtures/telemetry/scada-demo-readings.csv --loop --anchor="2026-07-01 08:00:00"
```

Supported options:

| Option | Values | Purpose |
|---|---|---|
| `--file` | CSV file path | Required replay source. |
| `--speed` | `slow`, `real`, `fast` | Validates replay pace semantics. |
| `--anchor` | `Y-m-d H:i:s` or `now` | Shifts fixture timestamps. |
| `--dry-run` | flag | Validates without writes. |
| `--loop` | flag | Replays the fixture twice with shifted timestamps. |
| `--allow-production` | flag | Allows intentional production execution. |

Required replay identity fields:

| Field | Meaning |
|---|---|
| `save_to_meter_data` | Must be `1` for rows intended to persist. |
| `meter_id` | Legacy/report-compatible meter identifier. |
| `location` | Legacy location/building identifier used by reports and RTU ingest side effects. |
| `datetime` | Gateway reading timestamp. |
| `mac_address` | Gateway MAC address. |

Replay summary fields:

| Field | Meaning |
|---|---|
| `Rows seen` | CSV rows considered, including malformed rows. |
| `Rows replayed` | Rows with enough identity data to submit to ingest. |
| `Rows saved` | Rows acknowledged and saved by live ingest. |
| `Rows failed` | Rows skipped before ingest because required identity fields were missing. |

## Lifecycle Scenarios

Lifecycle scenarios coordinate seed profile setup plus simulation or replay.

```bash
php artisan camr:scenario --list
php artisan camr:scenario operations-gateway-recovery
php artisan camr:scenario analytics-demo
php artisan camr:scenario live-scada-demo
```

Available scenarios:

| Scenario | Persona | Seed Profile | Simulator | Purpose |
|---|---|---|---|---|
| `admin-provisioning` | Administrator | `demo` | `normal` | Admin provisioning and site-user workflow. |
| `operations-gateway-recovery` | Operations Engineer | `demo` | `offline-recovery` | Offline/stale gateway recovery validation. |
| `maintenance-meter-update` | Maintenance Technician | `demo` | `normal` | Site, gateway, and meter maintenance path. |
| `analyst-report-export` | Analyst | `demo` | `report-window` | Report/export workflow preparation. |
| `analytics-demo` | Energy Manager | `demo` | `analytics-demo` | Analytics showcase readiness. |
| `live-scada-demo` | Operations Engineer | `demo` | `file-replay` | Live-ingest-backed dashboard and analytics review. |
| `fresh-install-smoke` | Administrator | `minimal` | `normal` | Fast fresh-install baseline check. |
| `heavy-data-readiness` | Operations Engineer | `heavy` | `report-window` | High-volume UI/performance readiness. |

Useful options:

```bash
php artisan camr:scenario live-scada-demo --dry-run
php artisan camr:scenario analytics-demo --anchor="2026-07-01 08:00:00"
php artisan camr:scenario operations-gateway-recovery --no-seed
php artisan camr:scenario fresh-install-smoke --no-simulate
```

## What To Verify In The UI

### Dashboard / Operator Console

Open:

```text
http://dec-camr.test/site
```

Verify:

- KPI band is populated.
- Gateway health shows online/stale/offline state.
- Meter health shows active meter state.
- Attention queue has actionable gateway or meter items.
- Recent telemetry list shows current readings.
- Pending update panel shows flags when scenario data includes them.
- Operational command bar exposes RTU protocol actions without changing route behavior.

### Analytics Workbench

Open:

```text
http://dec-camr.test/analytics
```

Verify:

- Time range has data.
- Consumption summary is not empty.
- Demand curve has calculated demand where data allows.
- Building comparison ranks multiple buildings for `analytics-demo`.
- Load profile explorer shows compatible consumption and demand evidence.
- Scope and query-state indicators remain visible.
- Data trust states are visible without implying false certainty.

### Reports

Open:

```text
/raw_report
/consumption_report
/demand_report
/sap_report
/site_report
```

Verify:

- Filter panel is usable.
- Preview summary responds to selected filters.
- Export buttons produce workbook responses.
- Download shelf records export attempts in the browser session.

## Troubleshooting

| Problem | Likely Cause | Fix |
|---|---|---|
| Dashboard is empty | Seed or simulator was not run. | `php artisan camr:scenario operations-gateway-recovery` |
| Analytics has no data | Analytics scenario was not run, or date window does not match data. | `php artisan camr:scenario analytics-demo` |
| Live replay shows no rows saved | Fixture path is wrong or identity fields are missing. | Run replay with `--dry-run`, then check `Rows failed`. |
| Invalid anchor error | Anchor format is wrong. | Use `--anchor="2026-07-01 08:00:00"` or replay `--anchor=now`. |
| Unsupported scenario | Scenario key is wrong or stale docs were used. | `php artisan camr:scenario --list` |
| Unsupported simulator scenario | Simulator scenario is not one of the supported values. | Use `normal`, `offline-recovery`, `report-window`, or `analytics-demo`. |
| Missing Vite manifest | Frontend assets were not built and dev server is not running. | `npm run dev` or `npm run build` |
| Stale routes/config/views | Laravel cache is stale. | `php artisan optimize:clear` |
| Production guard failure | Command is running in production. | Do not override unless intentional; use `--allow-production` only with approval. |

## Recommended Review Flows

### Fast smoke

```bash
php artisan migrate:fresh --force
php artisan camr:scenario fresh-install-smoke
```

Use for baseline operator navigation and seeded entity visibility.

### Operations incident review

```bash
php artisan migrate:fresh --force
php artisan camr:scenario operations-gateway-recovery
```

Use for gateway health, meter health, attention queue, pending update, and operational command review.

### Analytics showcase

```bash
php artisan migrate:fresh --force
php artisan camr:scenario analytics-demo
```

Use for energy-manager-style analytics review.

### SCADA replay

```bash
php artisan migrate:fresh --force
php artisan camr:scenario live-scada-demo
```

Use when reviewing CAMR as a live telemetry console backed by gateway-shaped readings.
