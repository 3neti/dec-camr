# PR-003 — Security & Access Readiness

Status: Pending
Owner: Architect / Team
Blocking: Yes

## Purpose

Confirm CAMR authentication, authorization, scoped access, and credential handling are safe for real production users.

## Readiness Questions

- Are production login hints, demo users, and demo passwords disabled or explicitly environment-gated?
- Are `/user*` and `/user_site_access*` routes admin-only in production?
- Do scoped users see only authorized sites, gateways, meters, reports, and analytics contexts?
- Are destructive actions protected by both auth and role/scope enforcement?
- Is `MIG-007` accepted, closed, or explicitly waived before release?

## Evidence Checklist

| Evidence | Status | Notes |
|---|---|---|
| Legacy username login verified | Pending | Confirm username, not email, remains the login identifier. |
| Production demo credential behavior decided | Pending | Remove, disable, or environment-gate `admin / 123456` hints. |
| Admin-only route audit completed | Pending | Include `/user`, `/user_list`, `/user_site_access`, and mutation routes. |
| Scoped access audit completed | Pending | Include site, gateway, meter, reports, analytics, and dashboard surfaces. |
| Browser persona smoke accepted | Pending | BT-015 provides release persona smoke coverage. |
| User site-access side effects reviewed | Pending | Tied to `MIG-007`. |
| Password policy and reset posture reviewed | Pending | Confirm legacy-compatible reset behavior is acceptable. |
| Production session/cookie settings reviewed | Pending | Confirm secure cookie and session driver settings. |

## Route Surfaces To Review

| Surface | Expected Production Posture |
|---|---|
| `/` and `/login-user` | Public login, username-based, legacy message behavior preserved. |
| `/site`, `/dashboard`, `/analytics` | Authenticated and scoped where data is user-specific. |
| `/user*` | Admin-only. |
| `/user_site_access*` | Admin-only. |
| Maintenance mutations | Authenticated; role/scope posture accepted. |
| Report pages and exports | Authenticated; scoped site access enforced. |
| RTU/device endpoints | Public protocol endpoints only where required by deployed devices. |

## Acceptance Gates

- Full Pest suite passes.
- Browser smoke suite passes.
- Production demo credential/login-hint behavior is explicitly safe.
- Admin-only route review is accepted.
- Scoped access review is accepted.
- `MIG-007` is closed or explicitly waived for production.

## Validation Commands

```bash
php artisan test --compact tests/Feature/UserTest.php
php artisan test --compact tests/Feature/SiteTest.php
php artisan test --compact tests/Feature/ReportTest.php
php artisan test --compact tests/Feature/AnalyticsWorkbenchTest.php
php artisan test --compact tests/Browser/OperatorJourney/AdminOperatorSmokeTest.php
php artisan test --compact
```

## Release Decision Impact

This wave blocks production release until accepted or explicitly waived. Security waivers must name the residual risk, owner, mitigation, and review date.

## Open Items

| Item | Owner | Status |
|---|---|---|
| Decide production handling for visible demo login hint | Architect / Team | Open |
| Complete scoped-user production access review | Architect / Team | Open |
| Close or waive `MIG-007` | Architect | Open |
| Review RTU public endpoint exposure | Architect / Team | Open |
