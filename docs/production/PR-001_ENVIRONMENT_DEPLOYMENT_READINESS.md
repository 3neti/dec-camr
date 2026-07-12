# PR-001 — Environment & Deployment Readiness

Status: In Progress
Owner: Team
Blocking: Yes

## Purpose

Prove the Laravel 13 CAMR application can be deployed, configured, built, operated, and rolled back safely in the intended production environment.

## Readiness Questions

- Where will CAMR be hosted for staging and production?
- Which PHP, Node, database, cache, queue, mail, scheduler, storage, and web server versions are required?
- Which environment variables are required, optional, secret, or production-disabled?
- How is the frontend build generated and deployed?
- How is a failed deployment rolled back?
- Who owns the deployment window and post-deploy verification?

## Evidence Checklist

| Evidence | Status | Notes |
|---|---|---|
| Hosting target selected | Pending | Record Laravel Cloud, VPS, Herd-proxy, or other target. |
| Staging environment available | Pending | Must match production shape closely enough for release validation. |
| Production `.env` inventory documented | Pending | Document names and purpose only; do not record secrets. |
| PHP/runtime version confirmed | Pending | Laravel 13 app currently targets PHP 8.4. |
| Node/build process confirmed | Pending | `npm run build` must succeed in deployment-equivalent conditions. |
| Database connection confirmed | Pending | Include migration and import constraints. |
| Queue/scheduler posture documented | Pending | Confirm whether any scheduled jobs or queues are required at release. |
| Storage/logging paths writable | Pending | Include uploads, exports, cache, logs, and compiled views. |
| Rollback path documented | Pending | Must include code rollback and database rollback posture. |
| Post-deploy smoke checklist drafted | Pending | Include login, `/site`, `/dashboard`, `/analytics`, reports, and RTU endpoint check. |

## Acceptance Gates

- Staging deployment succeeds.
- Production-equivalent `npm run build` succeeds.
- Application boots with production-like config.
- Required secrets are provided through environment management, not committed files.
- Demo-only affordances are disabled or explicitly environment-gated.
- Rollback owner and rollback steps are documented.

## Validation Commands

Use these only in the appropriate environment:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --pretend
```

## Release Decision Impact

This wave blocks production release until accepted. If deployment cannot be reproduced in staging or rolled back safely, production authorization must remain pending.

## Open Items

| Item | Owner | Status |
|---|---|---|
| Select production hosting target | Team | Open |
| Produce production environment variable inventory | Team | Open |
| Confirm rollback procedure | Team | Open |
| Confirm demo credential/login-hint production gating | Architect / Team | Open |
