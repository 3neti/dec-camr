# Production Readiness Compass

This compass governs CAMR production release readiness after completion of the Laravel migration, Operator Console work, Analytics Workbench foundation, SCADA simulation/replay support, and browser smoke coverage.

Migration completion does not equal production authorization. Production release remains a separate business and engineering decision.

## Current Compass Pointer

| Field | Value |
|---|---|
| Current phase | Production Readiness |
| Current wave | PR-001 — Environment & Deployment Readiness |
| Final target | PR-008 — Production Go/No-Go |
| Release posture | Pending |
| Primary rule | No production release until all blocking readiness waves are accepted or explicitly waived. |

## Source Artifacts

This compass summarizes production readiness and references existing evidence rather than duplicating it.

| Artifact | Purpose |
|---|---|
| `docs/migration/release-readiness.md` | Migration slice readiness and residual migration posture. |
| `docs/migration/release-decision-log.md` | Release-level decisions for high-risk migration areas. |
| `docs/migration/backlog.md` | Residual migration risks and unresolved work items. |
| `docs/ui/TESTER_CHEAT_SHEET.md` | Developer, QA, seed, simulator, endpoint, and browser-test operating reference. |
| `docs/ui/SCADA_SIMULATION_MANUAL.md` | SCADA simulation, replay, and operational review workflows. |


## Wave Playbooks

| Wave | Playbook |
|---|---|
| PR-001 | `docs/production/PR-001_ENVIRONMENT_DEPLOYMENT_READINESS.md` |
| PR-002 | `docs/production/PR-002_DATABASE_BACKUP_RESTORE_READINESS.md` |
| PR-003 | `docs/production/PR-003_SECURITY_ACCESS_READINESS.md` |
| PR-004 | `docs/production/PR-004_RTU_GATEWAY_INGESTION_READINESS.md` |
| PR-005 | `docs/production/PR-005_OBSERVABILITY_INCIDENT_RESPONSE.md` |
| PR-006 | `docs/production/PR-006_PERFORMANCE_LOAD_READINESS.md` |
| PR-007 | `docs/production/PR-007_BROWSER_OPERATOR_ACCEPTANCE_READINESS.md` |
| PR-008 | `docs/production/PR-008_PRODUCTION_GO_NO_GO.md` |

## Readiness Waves

| Wave | Scope | Status | Evidence | Blocking? | Owner | Notes |
|---|---|---|---|---|---|---|
| PR-001 | Environment & Deployment Readiness | In Progress | Pending | Yes | Team | Confirm hosting, environment variables, build process, queue/scheduler/runtime setup, and deployment rollback path. |
| PR-002 | Database, Backup & Restore Readiness | Pending | Pending | Yes | Team | Confirm migration strategy, production import approach, backups, restore drills, and retention policy. |
| PR-003 | Security & Access Readiness | Pending | Pending | Yes | Architect / Team | Confirm auth, admin-only surfaces, scoped access, production demo credential removal/configuration, and least-privilege posture. |
| PR-004 | RTU / Gateway Ingestion Readiness | Pending | Pending | Yes | Architect / Team | Confirm live endpoint behavior, malformed payload posture, idempotency strategy without gateway payload changes, and endpoint observability. |
| PR-005 | Observability & Incident Response | Pending | Pending | Yes | Team | Confirm logs, metrics, browser/runtime errors, gateway ingest monitoring, alert routing, and incident runbook. |
| PR-006 | Performance & Load Readiness | Pending | Pending | Yes | Team | Confirm list endpoints, reports, analytics, telemetry ingestion, browser pages, and database indexes under realistic load. |
| PR-007 | Browser / Operator Acceptance Readiness | Pending | BT-001 through BT-015 passing | Yes | Architect / QA | Confirm real-user acceptance against seeded/demo/live-SCADA workflows and production-like browser environments. |
| PR-008 | Production Go/No-Go | Pending | Pending | Yes | Architect / Business Owner | Final release authorization after all blocking waves are accepted or explicitly waived. |

## PR-001 — Environment & Deployment Readiness

Purpose:
- Prove the Laravel 13 application can be deployed, configured, built, and rolled back safely in the intended production environment.

Required evidence:
- Production hosting target is selected and documented.
- Required PHP, Node, database, queue, cache, mail, storage, and scheduler settings are known.
- `.env` production requirements are documented without exposing secrets.
- Frontend build process is confirmed.
- Rollback approach is documented.

Acceptance gates:
- Deployment dry run or staging deployment succeeds.
- `npm run build` succeeds in deployment-equivalent conditions.
- Production-only demo behavior is disabled or explicitly gated.
- Application boots with production-like config.
- Rollback path is executable by the release owner.

Production decision impact:
- Blocks production release until accepted.

## PR-002 — Database, Backup & Restore Readiness

Purpose:
- Prove production data can be migrated, protected, restored, and validated.

Required evidence:
- Production database creation and access plan.
- Backup schedule and retention policy.
- Restore test result.
- Data import or cutover plan if legacy data is migrated.
- Post-restore validation checklist.

Acceptance gates:
- Backup can be created and restored in a non-production environment.
- Restore validation confirms users, sites, gateways, meters, telemetry, reports, and access records are coherent.
- Destructive commands are guarded in production.

Production decision impact:
- Blocks production release until accepted.

## PR-003 — Security & Access Readiness

Purpose:
- Confirm authentication, authorization, scoped access, and production credentials are safe for real users.

Required evidence:
- Username-login contract verified.
- Admin-only user-management and user-site-access routes verified.
- Scoped user behavior verified across operator, maintenance, report, and analytics surfaces.
- Demo login hint and demo credentials are removed, disabled, or environment-gated for production.

Acceptance gates:
- Full Pest suite passes.
- Browser smoke suite passes.
- Security review confirms no demo-only affordance is production-visible unless explicitly approved.
- `MIG-007` user site-access side-effect residual is accepted, closed, or explicitly waived.

Production decision impact:
- Blocks production release until accepted or explicitly waived.

## PR-004 — RTU / Gateway Ingestion Readiness

Purpose:
- Prove deployed gateway devices can continue posting telemetry without payload changes.

Required evidence:
- `POST /http_post_server.php` behavior verified in staging or production-like environment.
- Protocol-safe endpoints verified.
- Malformed payload behavior documented.
- Duplicate post/idempotency posture documented without changing gateway payloads.
- Telemetry replay evidence from `docs/ui/SCADA_SIMULATION_MANUAL.md`.

Acceptance gates:
- Telemetry replay writes expected database state.
- Live dashboard and analytics reflect ingested telemetry.
- Endpoint logs are sufficient to investigate device issues.
- Alerts or monitoring exist for ingest failure, stale gateways, and missing telemetry.

Production decision impact:
- Blocks production release until accepted or explicitly waived.

## PR-005 — Observability & Incident Response

Purpose:
- Ensure production failures are visible, diagnosable, and actionable.

Required evidence:
- Application logs are available to operators/engineers.
- Browser/runtime errors can be inspected.
- Gateway ingestion failures are observable.
- Incident response owner and escalation path are defined.
- Runbook covers common failures.

Acceptance gates:
- Error logging is verified in staging.
- Telemetry ingest failure is detectable.
- Stale/offline gateway state is visible in UI and/or monitoring.
- Incident response runbook exists and is reviewable.

Production decision impact:
- Blocks production release until accepted.

## PR-006 — Performance & Load Readiness

Purpose:
- Confirm the application performs acceptably with production-like data volume and telemetry frequency.

Required evidence:
- Heavy seed profile or production-like dataset test.
- Telemetry simulation/replay under expected ingest volume.
- List, report, analytics, dashboard, and live-operations timings.
- Identified database/index bottlenecks documented.

Acceptance gates:
- Core pages load acceptably under realistic data volume.
- Telemetry ingestion does not block operator UI workflows.
- Reports and analytics have acceptable response behavior or documented limits.
- Any production-blocking performance issue is resolved or explicitly waived.

Production decision impact:
- Blocks production release until accepted or explicitly waived.

## PR-007 — Browser / Operator Acceptance Readiness

Purpose:
- Confirm the product is usable by real operators, analysts, and administrators through browser workflows.

Required evidence:
- `BT-001` through `BT-015` browser smoke tests passing.
- QA/operator review of `/site`, `/dashboard`, `/analytics`, maintenance pages, reports, live-SCADA signals, and scoped persona behavior.
- Tester flow from `docs/ui/TESTER_CHEAT_SHEET.md` exercised.

Acceptance gates:
- Browser smoke suite passes in the intended review environment.
- No JavaScript errors on primary surfaces.
- Primary personas can complete their expected review paths.
- Known UI limitations are accepted or backlogged.

Production decision impact:
- Blocks production release until accepted.

## PR-008 — Production Go/No-Go

Purpose:
- Record the final production release decision.

Required evidence:
- PR-001 through PR-007 accepted or explicitly waived.
- Remaining residual risks are visible and assigned.
- Business owner and architect approve the release posture.
- Rollback and incident ownership are clear.

Acceptance gates:
- Final release checklist is complete.
- No unowned blocking risks remain.
- Release window, rollback owner, and post-release monitoring owner are recorded.

Production decision impact:
- This is the final production authorization gate.

## Residual Risks Feeding Production Readiness

| Risk | Source | Production Handling |
|---|---|---|
| RTU malformed payload and duplicate-post/idempotency details | `MIG-001` | Resolve or explicitly waive in PR-004. |
| Exhaustive report workbook parity | `MIG-002` | Confirm residual is acceptable in PR-007/PR-008. |
| Missing/nonexistent destructive delete ID response consistency | `MIG-004` | Confirm non-blocking or close before PR-008. |
| DataTables/list residual parity posture | `MIG-005` | Confirm accepted status before PR-008 if still marked blocked elsewhere. |
| Web settings/logo maintenance residuals | `MIG-006` | Confirm production branding/config needs before PR-001/PR-007. |
| User site-access mutation side effects | `MIG-007` | Resolve or waive in PR-003. |
| Gateway/Meter preview reconciliation residuals | `MIG-009` | Confirm no preview-only status remains production-blocking. |

## Production Compass Rules

- Do not mark production release approved because migration is complete.
- Do not hide residual risks by moving them out of the compass.
- Do not treat demo seed, simulator, or browser smoke success as production telemetry proof by itself.
- Do not change gateway payload requirements for production idempotency; use server-side strategies only unless device firmware changes are separately approved.
- Every blocking wave must end as one of: `Accepted`, `Accepted with Waiver`, or `Blocked`.
- `PR-008` remains `Pending` until explicitly approved by the architect and business owner.
