# Release Decision Log

This log governs production-readiness decisions for the remaining migration hardening work. It is separate from backlog tracking and architecture decisions.

The remaining work is now treated as:
- security hardening
- behavioral hardening
- production hardening

It is not intended to be a feature implementation tracker.

## 2026-06-30 — Authorization Semantics Release Decision (Slice 13)

Status:
- Accepted

Affected Slice(s):
- Slice 13 — Authorization Semantics

Summary:
- Role and scope semantics now have explicit middleware coverage (`EnsureLegacyAuthenticated` + `EnsureLegacyAdmin`), scoped site listing for non-ALL users, and non-admin forbidden assertions on all user-management routes.

Evidence:
- Slice 13 tests in `tests/Feature/UserTest.php` and `tests/Feature/SiteTest.php` now assert:
  - legacy login requirement for user routes,
  - non-admin forbidden access to user-management and user-site-access routes,
  - scoped site list visibility for non-ALL users,
  - role-scoped action behavior on site-user list payloads.
- Full gates are green (`php artisan test --compact`, `vendor/bin/pint --dirty --format agent`, `npm run lint:check`, `npm run types:check`).

Residual Risk:
- Legacy compatibility for non-admin user visibility and denied-route UX remains subject to explicit review.
- `MIG-007` remains In Review for full validation of user site-access mutation side effects.

Decision:
- Accepted

Release Impact:
- Production release on Slice 13 authorization semantics is conditionally accepted; release risk remains open until `MIG-007` follow-up is validated and closed.

Owner:
- Architect / Team (to be recorded)

Follow-up (if any):
- Keep `MIG-007` in In Review until user site-access mutation side effects are fully validated against legacy behavior.
- Update release status for this decision when side-effect validation is complete.

## 2026-06-30 — DataTables Behavior Release Decision (Slice 14)

Status:
- Blocked

Affected Slice(s):
- Slice 14 — DataTables Behavior Parity

Summary:
- Slice 14 parity scaffolding is implemented with list-action request parsing and response assertions for draw/count/search/pagination behavior.
- `MIG-005` remains unresolved where list payload parity edge cases still require release-level closure after full-suite and legacy comparison evidence are aligned.

Evidence:
- Slice 14 added/updated feature tests for `/company_list`, `/division_list`, `/configuration_file_list`, `/site/list`, `/site/user/list`, `/getBuilding`, `/getMeterLocation`, `/getGateway`, `/getMeter`, and `/user_list`.

Residual Risk:
- Search/sort/pagination regressions can alter operator workflows and create incorrect data visibility ordering.
- Residual compatibility for action-column and scoped-list details is still being verified against legacy edge cases.

Decision:
- Blocked

Release Impact:
- Production release remains blocked on Slice 14 until legacy-compatibility decisions for `MIG-005` are explicitly closed.

Owner:
- Architect / Team (to be recorded)

Follow-up (if any):
- Execute list-equivalence contract tests and resolve remaining payload mismatches before release.

## 2026-06-30 — Destructive CRUD Release Decision (Slice 15)

Status:
- Accepted

Affected Slice(s):
- Slice 15 — Destructive CRUD Hardening

Summary:
- Slice 15 destructive hardening is implemented with dependency-blocking deletes, user deletion cleanup, and full suite/lint/type gates green.
- Four edge cases were classified:
  - deleting logged-in admin: Covered
  - deleting last remaining admin: Covered
  - deleting missing/nonexistent IDs: Backlogged
  - dependency-blocked deletes returning 500: Covered

Evidence:
- `php artisan test --compact`
- `vendor/bin/pint --dirty --format agent`
- `npm run lint:check`
- `npm run types:check`
- Slice 15 feature tests include blocked-delete assertions across Company/Division/Configuration/Site/Building/Gateway/MeterLocation/Meter/User and side-effect validation for meter and user deletes.

Residual Risk:
- Missing-ID response semantics are unresolved and await architect approval (legacy behavior may have returned generic 500s for null find paths; current Laravel action behavior is inconsistent across actions).

Decision:
- Accepted

Release Impact:
- Slice 15 is accepted for progression; release readiness remains contingent on Slice 16 and remaining cross-cutting items.

Owner:
- Architect / Team (to be recorded)

Follow-up (if any):
- Validate rollback/consistency behavior and resolve `MIG-004` missing-ID response semantics before finalizing residual risk.

## 2026-06-30 — Reports Release Decision (Slice 16)

Status:
- Accepted

Affected Slice(s):
- Slice 16 — Reports Hardening

Summary:
- Slice 16 reports hardening is accepted as production-ready at a representative level.
- Route registration, validation behavior, payload structure, representative aggregations, and representative XLSX headers/business fields are now covered with passing evidence.

Evidence:
- `tests/Feature/ReportTest.php` (route compatibility, validation, scoped access, representative payloads, boundary checks, and workbook assertions).
- `php artisan test --compact tests/Feature/ReportTest.php`
- `php artisan test --compact`
- `vendor/bin/pint --dirty --format agent`
- `npm run lint:check`
- `npm run types:check`

Residual Risk:
- Remaining work concerns exhaustive workbook parity, not representative behavioral correctness:
  - cell-by-cell workbook parity
  - exhaustive aggregation matrix coverage
  - rounding and edge-case parity
  - worksheet ordering/formatting variants
  - broader fixture permutations

Decision:
- Accepted with residual differences.

Release Impact:
- Acceptable for production provided future regression hardening continues on residual reporting parity work.
- `MIG-002` remains open as residual work and is classified as accepted-with-residual-differences.

Owner:
- Architect / Team (to be recorded)

Follow-up (if any):
- Track residual workbook parity work under `MIG-002`; this residual is non-blocking for slice approval.

## 2026-07-01 — Reports Slice 16 Approval With Residual Differences

Status:
- Approved

Affected Slice(s):
- Slice 16 — Reports Hardening

Summary:
- Representative parity for reports has moved beyond route-surface scaffolding into behavioral coverage across validation, payloads, boundaries, scoped access, and export behavior.
- Full Slice 16 gates are green after the targeted representative assertions were added.

Evidence:
- `tests/Feature/ReportTest.php` (route registration, validation, scope checks, payload structure/counts, representative aggregation/values, representative XLSX sheet/headers/fields, and filename/content-type).
- `php artisan test --compact tests/Feature/ReportTest.php`
- `php artisan test --compact`
- `vendor/bin/pint --dirty --format agent`
- `npm run lint:check`
- `npm run types:check`

Residual Risk:
- Remaining work concerns exhaustive workbook parity and edge-case aggregation permutations, not representative behavioral correctness.

Decision:
- Accepted with residual differences.

Release Impact:
- Residuals are tracked under `MIG-002`; production impact is acceptable for now with planned regression-hardening follow-up.

Owner:
- Architect

Follow-up (if any):
- Continue residual parity work under `MIG-002` while tracking as non-blocking.

## 2026-06-30 — RTU / Device Endpoints Release Decision (Slice 17)

Status:
- Deferred

Affected Slice(s):
- Slice 17 — RTU / Device Endpoints

Summary:
- Pending validation of protocol compatibility, reset semantics, and payload contracts.

Evidence:
- Pending completion of Slice 17 endpoint-level characterization and hardening.

Residual Risk:
- Incompatible protocol or reset behavior can impact device interoperability and operational safety.

Decision:
- Deferred

Release Impact:
- Production release cannot be granted on device integration without confirmed protocol parity and side-effect correctness.

Owner:
- Architect / Team (to be recorded)

Follow-up (if any):
- Document legacy protocol expectations and prove parity for every RTU route before signoff.

## Final Release Authorization (Slice 18)

Status:
- Deferred

Affected Slice(s):
- Slice 13 — Authorization Semantics
- Slice 14 — DataTables Behavior Parity
- Slice 15 — Destructive CRUD Hardening
- Slice 16 — Reports Hardening
- Slice 17 — RTU / Device Endpoints
- Slice 18 — Backlog Cleanup / Release Readiness

Summary:
- Pending completion of all remaining hardening slices and closure of release-affecting residual risk.

Evidence:
- Pending.

Residual Risk:
- Any unresolved critical behavior gap in security, data integrity, reporting, or RTU interoperability.

Decision:
- Pending

Release Impact:
- Migration is not yet approved for production.

Owner:
- Architect / Team (to be recorded)

Follow-up (if any):
- Fill this entry after Slice 18 governance pass and evidence review.

## 2026-06-30 — Reports Slice 16 Hardening Evidence Added

Status:
- Blocked

Affected Slice(s):
- Slice 16 — Reports Hardening

Summary:
- Report hardening now includes concrete feature assertions for route registration, required-field validation, scoped access enforcement, payload shape/count behavior, and XLSX export filename/content-type behavior for all configured report surfaces.
- Representative report coverage is now exercised in `tests/Feature/ReportTest.php` and all required acceptance gates are green.

Evidence:
- `php artisan test --compact tests/Feature/ReportTest.php`
- `php artisan test --compact`
- `vendor/bin/pint --dirty --format agent`
- `npm run lint:check`
- `npm run types:check`

Residual Risk:
- Formula-level parity and worksheet-level workbook fidelity against legacy templates remain unresolved (ordering, labels, totals, empty-result rendering, and template semantics).
- `MIG-002` remains in review until legacy comparison outcomes are signed.

Decision:
- Blocked

Release Impact:
- Slice 16 remains blocked from release-ready status until `MIG-002` is resolved.

Owner:
- Codex

Follow-up (if any):
- Add explicit legacy comparison fixtures and workbook content assertions for each report family, then update this decision for final release posture.
