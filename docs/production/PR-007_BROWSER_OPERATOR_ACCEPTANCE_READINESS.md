# PR-007 — Browser / Operator Acceptance Readiness

Status: Pending
Owner: Architect / QA
Blocking: Yes

## Purpose

Confirm CAMR is usable by real operators, analysts, administrators, and maintenance users through production-like browser workflows.

## Readiness Questions

- Can each primary persona reach their core workflow without reading internal documentation?
- Do `/site`, `/dashboard`, `/analytics`, maintenance pages, reports, and live operations render cleanly in production-like browsers?
- Are seeded/demo flows representative enough for customer or operator acceptance review?
- Are known UI limitations acceptable for release?
- Are browser smoke tests passing in the intended review environment?

## Evidence Checklist

| Evidence | Status | Notes |
|---|---|---|
| BT-001 through BT-015 passing | Complete | Browser suite currently validates primary smoke and persona paths. |
| Admin acceptance review completed | Pending | Include login, `/site`, users/access if appropriate. |
| Operations acceptance review completed | Pending | Include `/dashboard`, live SCADA, gateway/meter health, command surfaces. |
| Analyst acceptance review completed | Pending | Include `/analytics`, reports, export prep, evidence panels. |
| Maintenance acceptance review completed | Pending | Include site/gateway/meter maintenance surfaces. |
| Scoped-user review completed | Pending | Confirm restricted users see expected scope only. |
| Browser/device matrix decided | Pending | Include Chrome/Safari/Edge expectations if applicable. |
| Known UI limitations accepted or backlogged | Pending | Must not hide release-impacting UX gaps. |

## Operator Review Paths

| Persona | Primary Path | Expected Evidence |
|---|---|---|
| Administrator | Login → `/site` → maintenance → user/access review | Can administer core records and users. |
| Operations Engineer | Login → `/dashboard` → gateway/meter health → pending updates | Can identify operational state and next action. |
| Maintenance Technician | Login → `/site` → gateway/meter maintenance | Can inspect and update maintenance records within scope. |
| Analyst | Login → `/analytics` → reports → export prep | Can investigate historical data and reach formal report workflows. |

## Acceptance Gates

- Browser smoke suite passes.
- No JavaScript errors on primary surfaces.
- Primary personas complete expected review paths.
- Known UI rough edges are accepted, backlogged, or fixed.
- Production/demo credential posture from PR-003 is respected.

## Validation Commands

```bash
php artisan test --compact tests/Browser/OperatorJourney/AdminOperatorSmokeTest.php
php artisan test --compact
npm run lint:check
npm run types:check
```

Manual review setup:

```bash
php artisan migrate:fresh --force
php artisan camr:scenario analytics-demo --anchor="2026-07-01 08:00:00"
php artisan camr:scenario live-scada-demo --anchor="2026-07-01 08:00:00"
```

## Release Decision Impact

This wave blocks production release until accepted. Passing browser tests are necessary but not sufficient; operator review must also accept the workflows.

## Open Items

| Item | Owner | Status |
|---|---|---|
| Schedule operator acceptance review | Architect / QA | Open |
| Decide browser/device acceptance matrix | Architect / QA | Open |
| Record known UI limitations and release impact | Architect / QA | Open |
| Confirm BT suite in production-like environment | Team | Open |
