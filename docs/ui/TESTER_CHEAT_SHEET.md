# CAMR Tester Cheat Sheet

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

The profile command also creates a baseline admin user when needed.

| Persona | Username | Password | Role / Access | Notes |
|---|---|---|---|---|
| Baseline Admin | `admin` | `Demo@1234` | Admin / ALL | Created by `camr:seed-profile` for all profiles when `admin@demo.local` does not already exist. |
| Minimal Admin | `admin_phase0` | `Demo@1234` | Admin / ALL | Minimal profile admin user; updates `admin@demo.local` if it already exists from the profile command. |
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

Legacy `php artisan db:seed` creates a separate `admin` user at `admin@example.com` with password `123456`. Prefer `camr:seed-profile` users for UI/operator testing.

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

## Lifecycle Scenario Runner

```bash
php artisan camr:scenario --list
php artisan camr:scenario fresh-install-smoke
php artisan camr:scenario operations-gateway-recovery
php artisan camr:scenario analyst-report-export --dry-run --anchor="2026-07-01 08:00:00"
```

Available scenarios:

| Scenario | Persona | Seed Profile | Simulator Scenario | Purpose |
|---|---|---|---|---|
| `admin-provisioning` | Administrator | demo | normal | Admin provisioning and site-user workflow. |
| `operations-gateway-recovery` | Operations Engineer | demo | offline-recovery | Offline/stale gateway recovery validation. |
| `maintenance-meter-update` | Maintenance Technician | demo | normal | Site, gateway, and meter maintenance path. |
| `analyst-report-export` | Analyst | demo | report-window | Report/export workflow preparation. |
| `fresh-install-smoke` | Administrator | minimal | normal | Fast fresh-install baseline check. |
| `heavy-data-readiness` | Operations Engineer | heavy | report-window | High-volume UI/performance readiness. |

Use `--dry-run` to validate the scenario without seeding or simulator writes.

Use `--no-seed` when the desired seed profile is already loaded and you only want scenario simulation.

Use `--no-simulate` when you only want the seed profile state or are validating static setup.

Anchor behavior:

- A custom `--anchor="2026-07-01 08:00:00"` overrides the scenario default.
- `fresh-install-smoke` and `heavy-data-readiness` have default anchor `2026-07-01 08:00:00`.
- Other scenarios use the simulator default unless an anchor is provided.

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

## Testing Commands

```bash
php artisan test --compact
php artisan test --compact tests/Feature/OperatorJourneySmokeTest.php
php artisan test --compact tests/Feature/Ui/ScenarioRunnerTest.php
vendor/bin/pint --dirty --format agent
npm run lint:check
npm run types:check
npm run build
```

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
| Login password fails | Mixing legacy `db:seed` credentials with profile credentials. | Use profile password `Demo@1234`; legacy `db:seed` admin uses `123456`. |

