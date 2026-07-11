# CAMR Tester Cheat Sheet

For the complete SCADA simulation and replay manual, see:

```text
docs/ui/SCADA_SIMULATION_MANUAL.md
```

## Project Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure the local `.env` database values for your machine before running migrations. Do not invent or commit environment secrets.

## Fresh Database Setup

```bash
php artisan migrate:fresh --force
php artisan camr:seed-profile --profile=minimal
```

Use `minimal` for fast developer checks and fresh-install smoke tests.

```bash
php artisan migrate:fresh --force
php artisan camr:seed-profile --profile=demo
```

Use `demo` for UI review, screenshots, operator journeys, and customer-style demonstrations.

```bash
php artisan migrate:fresh --force
php artisan camr:seed-profile --profile=heavy
```

Use `heavy` for pagination, performance, high-volume list checks, and report-load validation.

Supported seed profiles:

```text
minimal
demo
heavy
```

## Default Demo Users

Seed profile users were confirmed from:

- `routes/console.php`
- `database/seeders/Profiles/AbstractProfileSeeder.php`
- `database/seeders/Profiles/MinimalProfileSeeder.php`
- `database/seeders/Profiles/DemoProfileSeeder.php`
- `database/seeders/Profiles/HeavyProfileSeeder.php`

All `camr:seed-profile` profile users use:

```text
Demo@1234
```

The login page shows the quick test credential when enabled. By default outside production, use:

```text
Username: admin
Password: 123456
```

The profile command also creates a baseline admin user that matches this hint.

| Persona | Username | Password | Role / Access | Notes |
|---|---|---|---|---|
| Baseline Admin | `admin` | `123456` | Admin / ALL | Created by `camr:seed-profile` for all profiles and shown as the login-page quick test credential. |
| Minimal Admin | `admin` | `123456` | Admin / ALL | Minimal profile uses the same baseline admin credential shown on the login page. |
| Minimal Operations | `ops_minimal` | `Demo@1234` | User / Selected | Scoped operations user for minimal profile. |
| Minimal Analyst | `analyst_minimal` | `Demo@1234` | User / Selected | Scoped analyst user for minimal profile. |
| Demo Admin | `ops_admin_demo` | `Demo@1234` | Admin / ALL | Main demo administrator. |
| Demo Operations Engineer | `ops_eng_demo` | `Demo@1234` | User / Selected | Operations journey user. |
| Demo Maintenance Technician | `maintenance_demo` | `Demo@1234` | User / Selected | Maintenance journey user. |
| Demo Analyst | `analyst_demo` | `Demo@1234` | User / Selected | Report journey user. |
| Heavy Admin | `ops_admin_heavy` | `Demo@1234` | Admin / ALL | Heavy profile administrator. |
| Heavy Operations A | `ops_heavy_a` | `Demo@1234` | User / Selected | Heavy profile operations user. |
| Heavy Operations B | `ops_heavy_b` | `Demo@1234` | User / Selected | Heavy profile operations user. |
| Heavy Maintenance | `maint_heavy` | `Demo@1234` | User / Selected | Heavy profile maintenance user. |
| Heavy Analyst | `analyst_heavy` | `Demo@1234` | User / Selected | Heavy profile analyst user. |

Legacy `php artisan db:seed` also creates an `admin` user at `admin@example.com` with password `123456`.

## Telemetry Simulator

```bash
php artisan camr:simulate --profile=demo --duration=10m --speed=real
php artisan camr:simulate --profile=demo --scenario=offline-recovery --anchor="2026-07-01 08:00:00"
php artisan camr:simulate --dry-run
```

Supported profiles:

```text
minimal
demo
heavy
```

Supported scenarios:

```text
normal
offline-recovery
report-window
analytics-demo
```

Supported speeds:

```text
slow
real
fast
```

Deterministic behavior:

- Deterministic mode is enabled by default.
- Fixed default anchor is `2026-07-01 08:00:00`.
- Custom anchors must use `Y-m-d H:i:s`.

```bash
php artisan camr:simulate --profile=demo --scenario=report-window --anchor="2026-07-01 08:00:00"
```

Dry-run behavior:

- validates options,
- reports expected coverage,
- does not insert `meter_data`,
- does not update gateway/meter/site last-log fields.

Production safety:

- `camr:simulate` is disabled in production unless `--allow-production` is passed intentionally.

## Telemetry Replay

Use replay when you want gateway-shaped CSV rows to pass through the live RTU ingest path.

```bash
php artisan camr:replay-telemetry --file=database/fixtures/telemetry/scada-demo-readings.csv --anchor="2026-07-01 08:00:00"
php artisan camr:replay-telemetry --file=database/fixtures/telemetry/scada-demo-readings.csv --dry-run
php artisan camr:replay-telemetry --file=database/fixtures/telemetry/scada-demo-readings.csv --loop --anchor="2026-07-01 08:00:00"
```

Replay fixture:

```text
database/fixtures/telemetry/scada-demo-readings.csv
```

Replay writes through the live ingest action used by `/http_post_server.php`; it does not insert directly into `meter_data`.

## Lifecycle Scenario Runner

```bash
php artisan camr:scenario --list
php artisan camr:scenario fresh-install-smoke
php artisan camr:scenario operations-gateway-recovery
php artisan camr:scenario analytics-demo
php artisan camr:scenario live-scada-demo
php artisan camr:scenario analyst-report-export --dry-run --anchor="2026-07-01 08:00:00"
```

Available scenarios:

| Scenario | Persona | Seed Profile | Simulator Scenario | Purpose |
|---|---|---|---|---|
| `admin-provisioning` | Administrator | demo | normal | Admin provisioning and site-user workflow. |
| `operations-gateway-recovery` | Operations Engineer | demo | offline-recovery | Offline/stale gateway recovery validation. |
| `maintenance-meter-update` | Maintenance Technician | demo | normal | Site, gateway, and meter maintenance path. |
| `analyst-report-export` | Analyst | demo | report-window | Report/export workflow preparation. |
| `analytics-demo` | Energy Manager | demo | analytics-demo | Analytics showcase readiness with normal consumption, abnormal consumption, demand peak, incomplete windows, and unknown windows. |
| `live-scada-demo` | Operations Engineer | demo | file-replay | Dashboard and Analytics review using replayed gateway-shaped telemetry. |
| `fresh-install-smoke` | Administrator | minimal | normal | Fast fresh-install baseline check. |
| `heavy-data-readiness` | Operations Engineer | heavy | report-window | High-volume UI/performance readiness. |

Use `--dry-run` to validate the scenario without seeding or simulator writes.

Use `--no-seed` when the desired seed profile is already loaded and you only want scenario simulation.

Use `--no-simulate` when you only want the seed profile state or are validating static setup.

Anchor behavior:

- A custom `--anchor="2026-07-01 08:00:00"` overrides the scenario default.
- `fresh-install-smoke` and `heavy-data-readiness` have default anchor `2026-07-01 08:00:00`.
- Other scenarios use the simulator default unless an anchor is provided.

Analytics demo:

```bash
php artisan migrate:fresh --force
php artisan camr:scenario analytics-demo
```

Use this before reviewing future Analytics Workbench UI. It prepares deterministic telemetry for `2026-07-01` with normal consumption, abnormal high consumption, demand peaks, incomplete data, unknown zero-delta data, and building comparison output.

Live SCADA demo:

```bash
php artisan migrate:fresh --force
php artisan camr:scenario live-scada-demo
```

Use this when reviewing CAMR as a live operational console backed by replayed gateway-style telemetry.

Production safety:

- `camr:scenario` is disabled in production for mutating runs unless `--allow-production` is passed intentionally.

## Local Development Server

```bash
php artisan serve
npm run dev
```

When tests or Inertia pages need a Vite manifest:

```bash
npm run build
```

If the app is served through Herd, use the Herd site URL and still run `npm run dev` for local Vite development.

Current Herd URL:

```text
http://dec-camr.test/
```

## Current App Endpoints

Operator console:

```text
/site
```

Analytics Workbench shell:

```text
/analytics
```

Reports:

```text
/sap_report
/raw_report
/site_report
/consumption_report
/demand_report
```

## Current UI Evaluation

Use this section to review the UI that is currently visible in the web app.

Recommended setup:

```bash
php artisan migrate:fresh --force
php artisan camr:seed-profile --profile=demo
php artisan camr:simulate --profile=demo --duration=10m --speed=real --anchor="2026-07-01 08:00:00"
```

Keep Vite running in a separate terminal:

```bash
npm run dev
```

Open:

```text
http://dec-camr.test/
```

Login:

```text
Username: admin
Password: 123456
```

### Primary Operator Entry

| Purpose | Endpoint | Notes |
|---|---|---|
| Login | `/` | Legacy username login. |
| Operator home | `/site` | Primary CAMR operator home/dashboard entry. Navigation and logo links should target this route. |
| Compatibility dashboard | `/dashboard` | Compatibility route only. Do not treat this as the main operator shell unless explicitly approved. |

### Maintenance UI

These pages have the current operator-shell and maintenance-page modernization work.

| Area | Endpoint | What to Check |
|---|---|---|
| Company | `/company` | Card shell, form spacing, list layout, action column. |
| Division | `/division` | Card shell, form spacing, list layout, action column. |
| Configuration | `/configuration_file` | Configuration File List spacing and maintenance layout. |
| Site | `/site` | Operator home behavior, site management, dashboard-style panels. |
| Building | `/building` | Building maintenance layout and seeded building data. |
| Gateway | `/gateway` | Gateway maintenance plus seeded/simulated gateway state. |
| Meter | `/meter` | Meter maintenance plus seeded/simulated meter state. |
| User Management | `/user` | Admin-only user management and User List spacing. |
| User Site Access | `/user_site_access` | Admin-only site-access workflow. |
| Security Settings | `/settings/security` | Security page should stay inside the app shell. |

### Reports UI

These pages use the Reports UX foundation.

| Report | Endpoint | What to Check |
|---|---|---|
| Raw Report | `/raw_report` | Report family selector, filters, preview/empty state, export controls. |
| SAP Report | `/sap_report` | Report workflow clarity and export path. |
| Site / Building Report | `/site_report` | Site/building report filters and export path. |
| Consumption Report | `/consumption_report` | Hourly/daily filter workflow and download behavior. |
| Demand Report | `/demand_report` | Hourly/15-minute filter workflow and download behavior. |

### Live Operations UI

Live Operations MVP components are currently surfaced through the operator console work rather than a separate `/operations` route.

Use the demo simulator scenario when evaluating operational tension:

```bash
php artisan camr:scenario operations-gateway-recovery
```

Then review:

```text
/site
/gateway
/meter
```

Look for:

- gateway health,
- meter health,
- telemetry recency,
- pending updates,
- recovery/stale/offline signals,
- quick operational actions where visible.

### Analytics Workbench Status

Analytics work through AN-013 is currently component and contract foundation only.

There is no analytics web endpoint yet.

Implemented analytics components are not expected to appear in the app until a later analytics page/workspace task integrates them.

Current analytics components:

| Work Item | Component | Visible Endpoint |
|---|---|---|
| AN-007 | `TimeRangePicker.vue` | Not exposed yet. |
| AN-008 | `AggregationSelector.vue` | Not exposed yet. |
| AN-009 | `ConsumptionSummaryCard.vue` | Not exposed yet. |
| AN-010 | `ConsumptionTrend.vue` | Not exposed yet. |
| AN-011 | `DemandCurve.vue` | Not exposed yet. |
| AN-012 | `BuildingComparisonGrid.vue` | Not exposed yet. |
| AN-013 | `LoadProfileExplorer.vue` | Not exposed yet. |

To evaluate Analytics today, review the component files directly or wait for the future analytics workspace integration. Do not expect new navigation links or endpoints for AN-007 through AN-013 yet.

### Protocol / Device Endpoints

These are external protocol endpoints, not browser UI pages.

| Purpose | Endpoint |
|---|---|
| RTU time check | `/check_time.php` |
| Telemetry ingestion | `/http_post_server.php` |
| RTU update protocol | `/rtu/index.php/rtu/rtu_check_update/{mac}/...` |

Do not use these for visual UI review.

## Testing Commands

```bash
php artisan test --compact
php artisan test --compact tests/Feature/OperatorJourneySmokeTest.php
php artisan test --compact tests/Feature/Ui/ScenarioRunnerTest.php
php artisan test --compact tests/Browser/OperatorJourney/AdminOperatorSmokeTest.php
vendor/bin/pint --dirty --format agent
npm run lint:check
npm run types:check
npm run build
```

Browser smoke tests require the browser test dependencies and Chromium browser binary:

```bash
composer install
npm install
npx playwright install chromium
```

BT-012 validates the real-browser legacy login, `/site`, `/dashboard`, `/analytics`, first analyst analytics evidence-control smoke path, live SCADA dashboard replay smoke path, gateway recovery dashboard smoke path, live operations command surface smoke path, maintenance CRUD shell smoke path across Company, Division, Site, Building, Gateway, and Meter, mobile viewport smoke for `/site`, `/dashboard`, and `/analytics`, report workflow smoke for SAP, Raw, Site, Consumption, and Demand reports, and a Consumption Report export/download-shelf journey. Deeper multi-step journeys remain covered by feature tests until the browser journey suite is expanded.

## Cache Clearing / Reset Commands

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

Use these when:

- `.env` changes are not reflected,
- routes appear stale,
- Inertia pages or views behave unexpectedly,
- cached config points at the wrong database,
- a recent route/config change does not appear locally.

## Recommended Tester Flow

```text
1. Fresh migrate
2. Seed demo profile
3. Run telemetry simulator
4. Start app
5. Login as admin
6. Run scenario
7. Run tests
8. Build frontend
```

Copy-paste version:

```bash
php artisan migrate:fresh --force
php artisan camr:seed-profile --profile=demo
php artisan camr:simulate --profile=demo --duration=10m --speed=real --anchor="2026-07-01 08:00:00"
npm run dev
```

In another terminal:

```bash
php artisan serve
```

Then log in with:

```text
Username: ops_admin_demo
Password: Demo@1234
```

## Troubleshooting

| Issue | Likely Cause | Fix |
|---|---|---|
| Missing Vite manifest | Frontend assets were not built. | `npm run build` |
| Frontend changes not visible | Vite dev server is not running. | `npm run dev` |
| Stale routes/config/views | Laravel cache is stale. | `php artisan optimize:clear` |
| Wrong seed profile | Database was seeded with a different profile. | `php artisan migrate:fresh --force && php artisan camr:seed-profile --profile=demo` |
| Invalid scenario | Scenario key is misspelled. | `php artisan camr:scenario --list` |
| Invalid simulator profile | Profile is not `minimal`, `demo`, or `heavy`. | `php artisan camr:simulate --profile=demo --dry-run` |
| Invalid simulator scenario | Scenario is not `normal`, `offline-recovery`, or `report-window`. | `php artisan camr:simulate --scenario=normal --dry-run` |
| Invalid simulator anchor | Anchor is not `Y-m-d H:i:s`. | `php artisan camr:simulate --anchor="2026-07-01 08:00:00" --dry-run` |
| Empty dashboard or lists | Seed profile was not run or database was reset. | `php artisan camr:seed-profile --profile=demo` |
| No telemetry rows | Simulator was not run or used dry-run. | `php artisan camr:simulate --profile=demo --duration=10m --speed=real` |
| Login user not found | Wrong seed profile or fresh database. | `php artisan camr:seed-profile --profile=demo` |
| Login password fails | Existing local database was seeded before the baseline admin was aligned to the login hint. | Run `php artisan migrate:fresh --force` and reseed, or update the local `admin` password to `123456`. |
