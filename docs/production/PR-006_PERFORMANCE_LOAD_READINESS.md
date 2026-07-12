# PR-006 — Performance & Load Readiness

Status: Pending
Owner: Team
Blocking: Yes

## Purpose

Confirm CAMR performs acceptably under production-like data volume, telemetry frequency, report usage, analytics usage, and operator browser workflows.

## Readiness Questions

- What production data volume is expected for companies, divisions, sites, buildings, gateways, meters, and telemetry rows?
- What telemetry posting rate is expected from gateways?
- Which pages are used under operational pressure?
- Which reports or exports are slowest under realistic historical data?
- Which queries require indexes, rollups, caching, or operational limits?

## Evidence Checklist

| Evidence | Status | Notes |
|---|---|---|
| Expected production data volume documented | Pending | Include telemetry retention horizon. |
| Heavy dataset run completed | Pending | Use heavy seed or production-like anonymized data. |
| Telemetry replay/load run completed | Pending | Include ingest rate and duration. |
| List endpoint timings recorded | Pending | Include maintenance and DataTables-style endpoints. |
| Dashboard timings recorded | Pending | Include live SCADA scenario. |
| Analytics timings recorded | Pending | Include selected building and portfolio mode. |
| Report/export timings recorded | Pending | Include consumption, demand, raw, site, and SAP reports. |
| Database bottlenecks documented | Pending | Include slow queries and index candidates. |

## Performance Surfaces

| Surface | Why It Matters |
|---|---|
| `/site` | Primary operator entry and site visibility. |
| `/dashboard` | Live operator console and SCADA-style status. |
| `/analytics` | Historical investigation and data-heavy contracts. |
| Maintenance lists | Daily administration and scoped visibility. |
| Report exports | Formal operational/billing workflows. |
| `/http_post_server.php` | Live gateway telemetry ingestion. |
| `meter_data` queries | Core source for dashboard, reports, analytics, and ingestion validation. |

## Acceptance Gates

- Production-like dataset is loaded or simulated.
- Core browser pages remain usable under expected data volume.
- Telemetry ingestion does not block operator UI workflows.
- Report and analytics response times are accepted or documented with limits.
- Production-blocking slow queries are resolved or explicitly waived.

## Suggested Validation Flow

```bash
php artisan migrate:fresh --force
php artisan camr:seed-profile --profile=heavy
php artisan camr:simulate --profile=heavy --duration=1h --speed=fast --anchor="2026-07-01 08:00:00"
php artisan camr:replay-telemetry --file=database/fixtures/telemetry/scada-demo-readings.csv --anchor="2026-07-01 08:00:00"
php artisan test --compact tests/Browser/OperatorJourney/AdminOperatorSmokeTest.php
```

Manual timing review:

- Load `/site`, `/dashboard`, `/analytics`, `/gateway`, `/meter`, `/consumption_report`, and `/demand_report`.
- Record page response time and browser usability.
- Record slow reports/exports separately from operator-console pages.

## Release Decision Impact

This wave blocks production release until accepted or explicitly waived. A waiver must name the affected workflow, expected impact, mitigation, and owner.

## Open Items

| Item | Owner | Status |
|---|---|---|
| Define production-like data volume target | Team | Open |
| Run heavy dataset timing pass | Team | Open |
| Run telemetry ingest/replay timing pass | Team | Open |
| Document slow query/index findings | Team | Open |
