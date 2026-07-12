# PR-005 — Observability & Incident Response

Status: Pending
Owner: Team
Blocking: Yes

## Purpose

Ensure CAMR production failures are visible, diagnosable, owned, and actionable, especially around live gateway ingestion and operator-facing workflows.

## Readiness Questions

- Where do production application logs, web server logs, queue logs, scheduler logs, and browser/runtime errors go?
- How are RTU ingestion failures detected?
- How are stale/offline gateways surfaced to operators and engineers?
- Who responds to incidents and how are they escalated?
- What is the runbook for login failure, telemetry outage, report/export failure, analytics data gap, and database restore?

## Evidence Checklist

| Evidence | Status | Notes |
|---|---|---|
| Application logging destination documented | Pending | Include retention and access. |
| Web server/PHP error logs documented | Pending | Include RTU endpoint visibility. |
| Browser/runtime error inspection path documented | Pending | Include production-safe process. |
| RTU ingest failure monitoring defined | Pending | Include malformed/missing gateway/missing meter cases. |
| Stale/offline gateway alert posture defined | Pending | Dashboard visibility alone may not be enough. |
| Incident owner and escalation path assigned | Pending | Include after-hours expectation if any. |
| Incident runbook drafted | Pending | Include common operator and ingestion failures. |
| Post-incident review process defined | Pending | Include how residual risks feed backlog. |

## Minimum Incident Runbook Sections

| Incident | First Checks | Escalation Trigger |
|---|---|---|
| Login unavailable | App health, database, session driver, auth logs. | Multiple users blocked or admin cannot login. |
| Gateway telemetry stopped | RTU endpoint logs, network, gateway MAC, latest `last_log_update`. | Critical sites stale beyond accepted window. |
| Reports/export failure | App logs, workbook path, request filters, database query health. | Formal billing/export workflow blocked. |
| Analytics data missing | Telemetry availability, selected context, identifier mapping, confidence state. | Production investigation blocked. |
| Database performance issue | Slow queries, locks, telemetry write rate, report/analytics queries. | Operator UI degraded or ingest delayed. |
| Restore required | Backup availability, restore owner, validation checklist. | Data corruption or unrecoverable operational failure. |

## Acceptance Gates

- Logs are accessible to the release support owner.
- At least one synthetic error or staging failure is observable end-to-end.
- RTU ingest failure can be detected and investigated.
- Stale/offline device state is visible to operators and support.
- Incident runbook is reviewed and assigned.

## Validation Commands / Checks

Environment-specific checks may include:

```bash
php artisan optimize:clear
php artisan test --compact tests/Feature/RtuProtocolTest.php
php artisan test --compact tests/Browser/OperatorJourney/AdminOperatorSmokeTest.php
```

Manual checks:

- Trigger a staging-only failed RTU payload and confirm log visibility.
- Confirm Dashboard stale/offline indicators after SCADA replay or simulator scenario.
- Confirm support owner can access logs without SSH guesswork.

## Release Decision Impact

This wave blocks production release until accepted. A production system without observable gateway ingestion failures is not ready for live meter operations.

## Open Items

| Item | Owner | Status |
|---|---|---|
| Select logging/monitoring destination | Team | Open |
| Define RTU ingest alert rule | Team | Open |
| Assign incident response owner | Team | Open |
| Draft and review incident runbook | Team | Open |
