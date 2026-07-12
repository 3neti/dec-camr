# PR-004 — RTU / Gateway Ingestion Readiness

Status: Pending
Owner: Architect / Team
Blocking: Yes

## Purpose

Prove deployed gateways can continue posting live meter readings to CAMR without gateway payload changes, and that ingestion failures are visible and diagnosable.

## Non-Negotiable Constraint

Gateway payloads must not be changed for production idempotency, monitoring, or compatibility work. Any idempotency or replay-safety strategy must be server-side unless a separate firmware/device change is explicitly approved.

## Readiness Questions

- Can deployed gateways reach `POST /http_post_server.php` in the production network path?
- Does production preserve the legacy plain-text response shape expected by devices?
- What happens on malformed, partial, duplicate, missing-gateway, or missing-meter payloads?
- How are ingestion failures logged and correlated without changing payloads?
- How quickly does live telemetry appear in Dashboard, Live Operations, Reports, and Analytics?

## Evidence Checklist

| Evidence | Status | Notes |
|---|---|---|
| `POST /http_post_server.php` staging test completed | Pending | Use gateway-shaped payloads. |
| Safe protocol endpoint checks completed | Pending | Include update flags, CSV/location payloads, reset endpoints, force LP, remote SSH flag. |
| Telemetry replay completed | Pending | Use `docs/ui/SCADA_SIMULATION_MANUAL.md`. |
| Server-side duplicate/idempotency posture documented | Pending | Must not require payload changes. |
| Malformed payload behavior accepted | Pending | Tied to residual `MIG-001`. |
| Missing gateway/meter behavior accepted | Pending | Must be visible in logs or monitoring. |
| Dashboard/Analytics reflect ingested telemetry | Pending | Confirm live data appears in operator surfaces. |
| Endpoint logging/alerting confirmed | Pending | Must support troubleshooting deployed devices. |

## Production-Safe Idempotency Options

| Option | Payload Change? | Notes |
|---|---|---|
| Time-window duplicate detection | No | Derive duplicate key from gateway MAC, meter identifier, location, timestamp, and measured values. |
| Database uniqueness/index strategy | No | Use only if legacy duplicate behavior is accepted and schema impact is reviewed. |
| Soft duplicate annotation | No | Store duplicate but mark/log suspected duplicate for analysis. |
| Ingest audit log | No | Record request hash, remote IP, gateway MAC, timestamp, and result server-side. |

## Acceptance Gates

- Gateway-shaped payload posts successfully to staging or production-like target.
- Response remains protocol-compatible and non-JSON where required.
- Telemetry writes expected tables and updates expected last-log fields.
- Duplicate/malformed behavior is accepted or explicitly waived.
- Ingest failures are observable without requiring device-side changes.
- Dashboard and Analytics reflect newly ingested telemetry.

## Validation Commands

Use non-production unless explicitly approved:

```bash
php artisan test --compact tests/Feature/RtuProtocolTest.php
php artisan camr:replay-telemetry --file=database/fixtures/telemetry/scada-demo-readings.csv --dry-run
php artisan camr:replay-telemetry --file=database/fixtures/telemetry/scada-demo-readings.csv --anchor="2026-07-01 08:00:00"
php artisan test --compact tests/Browser/OperatorJourney/AdminOperatorSmokeTest.php
```

## Release Decision Impact

This wave blocks production release until accepted or explicitly waived. RTU compatibility is production-critical because uploaded profiles are backup workflows; live gateway posting is the primary meter-reading source.

## Open Items

| Item | Owner | Status |
|---|---|---|
| Run staging endpoint post with gateway-shaped payload | Team | Open |
| Decide server-side duplicate handling posture | Architect / Team | Open |
| Close or waive malformed payload residual under `MIG-001` | Architect | Open |
| Confirm endpoint logs and alerting | Team | Open |
