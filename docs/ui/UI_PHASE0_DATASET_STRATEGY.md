# CAMR UI Phase 0 — Professional Demonstration Dataset Strategy

## Purpose

Phase 0 of UI modernization is **data foundation first**. The operator console can only be judged through realistic data density and operational variance. Before redesigning Vue views, the app should boot into a believable operational state that demonstrates the real CAMR domain.

## Goals

- Enable immediate visual and functional confidence after install.
- Exercise workflow edges without introducing behavior changes.
- Support operator journey testing with deterministic, reproducible fixtures.
- Offer repeatable profiles for developer setup, customer demos, and performance validation.

## Current Context

The repository already includes a migration-oriented seeding path that can import legacy SQL backups through `DatabaseSeeder` and then layer additional synthetic data for Laravel-side operations. This is a strong foundation for a robust Phase 0.

For UI modernization, data quality matters as much as quantity:
- entity graphs must be coherent,
- status signals must vary,
- and report inputs/outputs must be meaningfully populated.

## Data Model Coverage Requirements

Dataset must include all operational entities with realistic relationships and variance.

### Must-have entity set

- Companies
- Divisions
- Sites
- Buildings
- Meter locations
- Gateways
- Meters
- Users
- User site-access mappings
- Meter details / meter sites metadata
- Meter data time series
- Offline / pending-update markers
- Report-relevant historical snapshots

### Operational-state coverage

- Online devices
- Offline devices
- Stale devices (delayed heartbeat)
- Gateways with pending update flags
- Gates/meter details with mixed firmware and revision values
- Users with different roles and scoped visibility

### Reporting coverage

- Recent interval coverage for Raw / SAP / Site / Consumption / Demand / Offline / As-built flows.
- Known boundary cases (empty window, single-row window, cross-day boundaries).

## Seed Profiles

### 1) Minimal Profile (Developer)

Purpose: fast boot and focused workflow testing.

Recommended characteristics:
- 1–2 companies
- 1 division
- 2–3 sites
- 1–2 buildings per site
- 3–6 meters total
- 2–3 users (admin + scoped operator + analyst)
- Sparse telemetry (recent + one stale) for status card verification
- Lightweight report fixture for one report family

Use case:
- local developer loops,
- quick test runs,
- low DB load.

### 2) Demo Profile (Customer / screenshots / demos)

Purpose: believable production-like narrative.

Recommended characteristics:
- 3 companies with multiple divisions
- 6–12 sites and 12–20 buildings
- 12–24 gateways
- 40+ meters with mixed assignment patterns
- 25+ meter locations
- 4–8 users across role spectrum
- Rich telemetry stream for last 7–14 days
- Explicit offline/offline-risk device set
- Predefined report-ready date windows
- Pending update examples (force LP/config refresh/readiness markers)

Use case:
- sales demos,
- UX review,
- analyst workflow validation.

### 3) Heavy Profile (Performance / hardening)

Purpose: pagination, list performance, DataTables-style behaviors, and report load.

Recommended characteristics:
- 5–10 companies and divisional hierarchy
- 100+ sites,
- 200+ meters,
- 50+ gateways,
- high-cardinality user access combinations
- large telemetry volume for aggregation performance
- broad historical report windows and large payload generation

Use case:
- stress validation,
- UI scaling checks,
- query/index tuning before release.

## Data Realism Rules

1. Preserve relational consistency:
   - meter belongs to location/building/site,
   - site belongs to division/company,
   - users constrained by scope and visibility rules.
2. Preserve operational semantics:
   - timestamps must be coherent,
   - stale/online status should derive from consistent `last_log_update` and heartbeat patterns,
   - report windows should align with available meter data.
3. Mix normal, warning, and error states:
   - not all entities can be healthy,
   - include known problematic states intentionally.
4. Keep deterministic generators:
   - avoid random-only seeds where reproducibility is needed for automated journeys.
5. Keep test intent clear:
   - names and IDs should imply scenario roles (e.g., `SITE-A1-ONLINE`, `SITE-B2-STALE`).

## Suggested Seeding Architecture (No Implementation in this phase)

- Keep base migratory fixtures (legacy import compatibility) as optional base.
- Add dedicated profile-specific seeders/classes for:
  - `MinimalSeedProfile`
  - `DemoSeedProfile`
  - `HeavySeedProfile`
- Introduce one command pattern (or documented manual toggle) to pick profile deterministically.
- Keep report fixtures close to realistic operational values so visual demos match expected report outputs.

### Implemented Command (Phase 0 Foundation)

- `php artisan camr:seed-profile --profile=minimal|demo|heavy`
- The command is deterministic and idempotent for repeated runs.

### Operator-Ready Baseline

- Seed with `--profile=demo` for first-use operator walkthroughs.
- Pair with `camr:simulate` before dashboard and journey validation to activate live behavior.

## Suggested File Inventory

Recommended files to create in future implementation (not yet implemented):

- `database/seeders/Profiles/MinimalProfileSeeder.php`
- `database/seeders/Profiles/DemoProfileSeeder.php`
- `database/seeders/Profiles/HeavyProfileSeeder.php`
- `database/seeders/SeedProfileManager.php` (or command layer)
- Dedicated factory states for health/role/report scenarios

## Phase 0.25 — Operational Telemetry Simulator (Planned Bridge)

Seeded rows establish structure. Phase 0.25 adds a stateful layer that mutates persistence so the UI can be evaluated against operational behavior.

### Simulator Responsibilities (Future Implementation)

- Select active gateways per interval and generate realistic meter values.
- Persist readings into `meter_data` with deterministic timestamps.
- Update gateway/meter `last_log_update` fields to drive freshness states.
- Mutate `soft_rev` and related revision markers where legacy semantics indicate updates/recovery.
- Toggle gateway states through normal, stale, and offline life-cycles.
- Raise and clear pending update flags through scenario-driven actions (e.g., force LP-like resets).
- Keep report windows populated with expected progression of values.
- Simulate missing/malformed telemetry and recovery behavior safely.

### Why this belongs before journey tests

- Operators should validate behavior against a changing network state, not a static dashboard.
- Journey and demonstration evidence becomes materially stronger when status transitions can be observed over time.
- Dashboard and report UX quality is only meaningful when data evolves between requests.

### Data written expectations

- `meter_data` receives periodic inserts for targeted meter IDs.
- `meter_rtu.soft_rev` and `meter_rtu.last_log_update` reflect activity and update cycles.
- `meter_details.last_log_update` and `meter_site.last_log_update` provide location/device context for freshness checks.

### Suggested command shape (planning only)

- `php artisan camr:simulate --profile=demo --duration=10m --speed=real`
- `php artisan camr:simulate --profile=heavy --duration=1h --speed=fast`
- `php artisan camr:simulate --scenario=offline-recovery`
- `php artisan camr:simulate --scenario=report-window`

## Migration-Ready Acceptance Criteria

Phase 0 is complete when:
- a fresh install with selected profile shows meaningful data immediately,
- every core domain has at least one healthy and one warning/offline example,
- operator journeys can be run without synthetic-only mock screens,
- report previews are non-empty for core families in demo profile,
- stale/offline/update marker semantics are visible in list and dashboard contexts.
