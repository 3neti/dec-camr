# PR-002 — Database, Backup & Restore Readiness

Status: Pending
Owner: Team
Blocking: Yes

## Purpose

Prove production CAMR data can be created, migrated or imported, backed up, restored, and validated without losing operational integrity.

## Readiness Questions

- Is production starting from fresh Laravel 13 seed data, migrated Laravel 8 data, imported SQL backup data, or a hybrid?
- What is the authoritative database cutover source?
- How are telemetry history, users, scoped access, sites, gateways, meters, reports, and RTU state validated after restore?
- What is the backup frequency and retention policy?
- Who can execute restore, and how quickly must restore complete?

## Evidence Checklist

| Evidence | Status | Notes |
|---|---|---|
| Production database engine/version selected | Pending | Must match Laravel 13 compatibility and expected load. |
| Cutover data source documented | Pending | Identify fresh, migrated, imported, or hybrid start. |
| Migration/import dry run completed | Pending | Include command transcript or checklist result. |
| Backup schedule defined | Pending | Include full backup and incremental/binlog posture if applicable. |
| Restore drill completed | Pending | Restore must happen in non-production before release. |
| Restore validation checklist completed | Pending | Confirm app-level and database-level integrity. |
| Telemetry retention expectation documented | Pending | Confirm how much historical `meter_data` is needed at launch. |
| Production destructive command guard reviewed | Pending | Confirm no accidental `migrate:fresh`, demo seed, or simulator writes. |

## Data Validation Checklist

After restore or import, validate:

| Area | Validation |
|---|---|
| Users | Admin and scoped users can authenticate with expected credentials. |
| Access | `user_access_group` scope matches expected site access. |
| Company/Division/Site/Building | Hierarchy is complete and visible in maintenance pages. |
| Gateway/Meter/Meter Location | Device relationships are complete and queryable. |
| Telemetry | `meter_data` rows match expected meter identifiers and timestamps. |
| RTU state | `meter_rtu`, update flags, `last_log_update`, and `soft_rev` are coherent. |
| Reports | Representative reports return expected data and exports. |
| Analytics | Analytics Workbench has a valid selected context when historical data exists. |

## Acceptance Gates

- Database backup can be created and restored in a non-production environment.
- Restored app boots and passes smoke checks.
- Data validation checklist is complete.
- Production data cutover has a rollback posture.
- Any missing historical data limitation is explicitly accepted.

## Validation Commands

Use environment-appropriate equivalents:

```bash
php artisan migrate --pretend
php artisan test --compact tests/Feature/RtuProtocolTest.php
php artisan test --compact tests/Feature/ReportTest.php
php artisan test --compact tests/Feature/AnalyticsWorkbenchTest.php
php artisan test --compact tests/Browser/OperatorJourney/AdminOperatorSmokeTest.php
```

## Release Decision Impact

This wave blocks production release until backup and restore are proven. A working deployment without a tested restore path is not production-ready.

## Open Items

| Item | Owner | Status |
|---|---|---|
| Decide production cutover source | Architect / Team | Open |
| Define backup and retention policy | Team | Open |
| Complete restore drill | Team | Open |
| Complete post-restore data validation checklist | Team | Open |
