# CAMR Operational Telemetry Simulator Strategy (Phase 0.25)

## Purpose

Phase 0.25 introduces a planned operational telemetry simulation layer that turns seeded CAMR entities into a living system for UI testing, operator demonstrations, and report validation.

## Why Static Data Is Not Enough

Seed data can verify structure, but operator confidence depends on behavioral signals:

- gateways and meters need evolving timestamps,
- health states need to shift between online/stale/offline,
- report windows need incremental enrichment,
- update and recovery workflows need observable side effects.

Without stateful simulation, modernized dashboards and live operations UX can only be superficially validated and may hide regressions in operational behavior.

## Simulator Goals

- generate realistic, deterministic telemetry over time,
- mutate persistence-backed fields (`meter_data`, last-log fields, revision flags),
- support both demonstration and stress workflows,
- make dashboard and journey behavior reproducible,
- expose clear hooks for route/API/CLI-driven operational exercises,
- preserve legacy operational semantics as the baseline.

## Implemented Commands

Implementation now uses the following command shape:

- `php artisan camr:simulate --profile=demo --duration=10m --speed=real`
- `php artisan camr:simulate --profile=heavy --duration=1h --speed=fast`
- `php artisan camr:simulate --scenario=offline-recovery`
- `php artisan camr:simulate --scenario=report-window`

Production guard is enforced by default in the command implementation; add `--allow-production` only for approved local/manual validation.

## Simulation Profiles

### Demo Profile Simulation

- short realistic cycles,
- balanced online/stale/offline mix,
- recurring report-window growth,
- limited volume for smooth local UX reviews.

### Stale/Offline Profile Simulation

- controlled heartbeat skips,
- intentional data dropouts,
- recovery windows,
- deterministic transitions for operator exercises.

### Heavy Profile Simulation

- high-frequency insert cadence,
- larger meter cardinality,
- elevated query load,
- used for scalability and pagination confidence.

## Simulation Scenarios

- normal operations: periodic readings with intermittent jitter,
- stale gateways: delayed updates for selected gateways,
- offline recovery: hard gap then controlled return,
- pending updates: set and clear update markers,
- report-window generation: build measurable windows with clear boundaries,
- heavy load: sustained burst periods for stress checks.

## Data Written

The simulator should persist to these domains:

- `meter_data` (new readings and timestamps),
- `meter_rtu` (`last_log_update`, `soft_rev`, update-related flags),
- `meter_details` (`last_log_update` and related metadata freshness),
- `meter_site` (`last_log_update` alignment with site context),
- report-derived fixture tables/aggregates if such staging is maintained.

## Safety Rules

- development/demo-only by default,
- production-disabled unless explicitly enabled,
- deterministic mode by default for reproducible journeys,
- dry-run preview mode for command validation,
- explicit profile selection and seeded scenario boundaries,
- avoid destructive side effects outside targeted simulation scope.

## Relationship To UI Tests

Layered operator journey tests should run after simulation initialization and, where needed, during a short controlled simulation interval.

This makes journey assertions materially stronger:

- state-dependent routing and visuals are testable,
- stale/offline transitions can be validated,
- report previews reflect time-based growth,
- UI modernization can change without breaking workflow timing semantics.

## Relationship To Demonstrations

Customer-facing demos should be runnable as two-step flows:

1. deterministic seed profile,
2. simulator scenario run,
3. workflow execution,
4. verification outcomes.

This sequence produces a professional console impression instead of static screenshots.

## Implementation Roadmap

Phase 0.25 is documented only in planning and should be implemented after dataset foundation and before full operator journeys.

Recommended order:

- finalize profile selection contract,
- define scenario catalog,
- implement a CLI entry command,
- implement deterministic, seed-safe mutation engine,
- add safety guardrails and execution telemetry,
- wire into local docs and demo scripts.
