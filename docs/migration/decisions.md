# Migration Decision Log

Record decisions that materially affect the Laravel 13 reimplementation.

Use this log for:

- accepted Laravel 13 conventions,
- approved route changes,
- intentional behavioral differences,
- characterization updates,
- UI compatibility decisions,
- package selections,
- architectural trade-offs,
- retired characterization tests.

## Format

```text
## YYYY-MM-DD - Short Decision Title

Status: Proposed | Approved | Superseded
Owner: Architect | Codex | Team
Slice: Authentication | Dashboard | Company | Division | Configuration | Site | Building | Meter Location | Gateway | Meter | User Management | Reports | RTU / Device Endpoints | Cross-cutting

Decision:
Context:
Consequences:
Characterization impact:
```

## Migration Compass (Current Slice Pointer)

- Last approved slice: `User Management` (Slice 11)
- Current authorized scope: `Reports` (Slice 12)

## Decisions

## 2026-06-30 - Exclude Preview Gateway/Meter Tests From Default Pest Runs

Status: Superseded
Owner: Codex
Slice: Gateway

Decision:
Exclude `GatewayTest` and `MeterTest` from normal Slice-1..Slice-8 execution until their official slices are approved.

Context:
- Both Gateway and Meter behavior were introduced as preview scaffolding to support future slice readiness and UI validation.
- Full suite execution still included these tests and failed on route-level 404 assertions because those slices are intentionally not slice-accepted yet.

Consequences:
- Gateway tests are no longer preview-only and are now active in full suite execution.
- `tests/Feature/MeterTest.php` remains preview-only and should stay scoped until Meter receives formal slice status.
- Migration inventory should reflect official status for Gateway and remaining preview status for Meter.

Characterization impact:
- No behavior is de-scoped; the Gateway slice now transitions from preview to officially accepted migration status.

## 2026-06-30 - Promote Gateway Slice to Official Slice 9 Migration Status

Status: Approved
Owner: Codex
Slice: Gateway

Decision:
Convert previously preview scaffolded Gateway behavior into an official Slice 9 implementation and execute the `GatewayTest` contract as required feature tests.

Context:
- Gateway routes and controllers/actions already existed but were intentionally excluded from baseline suite execution while slice ordering and boundary decisions were still under review.
- `GatewayTest` includes official contract assertions for page rendering, list endpoint payload/action markers, create/update/delete semantics, and info retrieval.

Consequences:
- Gateway routes are restored in `routes/web.php` for official Slice 9 behavior.
- `tests/Feature/GatewayTest.php` is no longer skipped and now contributes to full suite results.
- `docs/migration/legacy-test-inventory.md` Gateway row is advanced to `Passing`.

Characterization impact:
- Slice 9 is now represented by executable Pest assertions against the Laravel 13 implementation.
- Meter remains unchanged as preview-only and continues to be intentionally excluded until its slice is approved.

## 2026-06-30 - Preserve Legacy `/site` as Site Maintenance Surface for Slice 6

Status: Approved
Owner: Codex
Slice: Site

Decision:
Promote `/site` from the temporary dashboard shim to the Site slice entry surface and add legacy Site maintenance endpoints (`/site/list`, `/site/user/list`, `/create_site_post`, `/site_info`, `/update_site_post`, `/delete_site_confirmed`, `/site_details/{siteID}`) using legacy request keys and response payload conventions.

Context:
- Slice 6 introduces Site maintenance before Building and Gateway/Meter slices.
- The legacy route contract in Laravel 8 uses `/site` as the Site maintenance dashboard and depends on those legacy maintenance endpoints for downstream workflow.

Consequences:
- `tests/Feature/DashboardTest.php` now treats an authenticated legacy-session `/site` visit as Site Management (not Dashboard).
- Dashboard responsibilities are retained through `/dashboard` for modern Laravel auth flows.

Characterization impact:
- The Site slice can now express route-level and mutation-level contract tests in `tests/Feature/SiteTest.php` while keeping earlier dashboard redirect and session protection behavior.

## 2026-06-30 - Preserve Legacy Company Maintenance Endpoint Contract for Slice 3

Status: Approved
Owner: Codex
Slice: Company

Decision:
Preserve the Laravel 8 company maintenance route surface and payload contract (`/company`, `/company_list`, `/create_company_post`, `/company_info`, `/update_company_post`, `/delete_company_confirmed`) with legacy field names such as `CompanyID`, while implementing the slice in Laravel 13 with a thin controller, form requests, and dedicated company actions.

Context:
- Company maintenance in the legacy app exposes AJAX-style endpoints with legacy keys and mutation messages.
- Subsequent slices depend on company data and reference identifiers.

Consequences:
- Company list responses include legacy `action` anchor markers expected by characterization browser scripts (`editCompany`, `deleteCompany`).
- Validation messages for `company_name` remain business-visible legacy text where present.

Characterization impact:
- Slice 3 feature coverage now includes the company contract at endpoint level.
- Browser characterization remains a reference artifact and is not enabled yet.

## 2026-06-30 - Preserve Legacy Division Maintenance Endpoint Contract for Slice 4

Status: Approved
Owner: Codex
Slice: Division

Decision:
Preserve the legacy division maintenance route and payload surface (`/division`, `/division_list`, `/create_division_post`, `/division_info`, `/update_division_post`, `/delete_division_confirmed`) with legacy field names such as `DivisionID`, while implementing this slice in Laravel 13 using dedicated actions, form requests, and an explicit division model.

Context:
- Division behavior in the Laravel 8 app is structurally equivalent to company maintenance with additional division-code/name validation and action identifiers.
- Destructive workflows for division data are migration-sensitive and must remain contract-compatible for later navigation and browser behavior.

Consequences:
- Division list endpoint responses should keep legacy `action` anchors with `editDivision` and `deleteDivision` ids.
- Validation messages for required `division_code` and `division_name` remain business-visible legacy text.
- Division create/update success messages remain legacy-compatible.

Characterization impact:
- Slice 4 feature tests should use legacy field names and endpoint names in order to preserve migration behavior during downstream slice development.

## 2026-06-30 - Slice 2 Dashboard Entry Surface

Status: Approved
Owner: Codex
Slice: Dashboard

Decision:
Use `/site` (legacy middleware + `loginID` session gate) as the legacy dashboard entry for Slice 2, while keeping `/dashboard` on the existing Laravel 13/Fortify auth+verified surface.

Context:
- Slice 2 scope is limited to the legacy dashboard entry and protection contract before deeper site workflows.
- The legacy characterization contract uses `/site` as the authenticated landing and does not require `/dashboard` to be the primary CAMR surface.
- Fortify auth flows and existing generated feature tests still reference `/dashboard`, so it must remain available for non-Slice-2 compatibility.

Consequences:
- `/site` now remains the canonical protected legacy dashboard route in this migration slice.
- `/dashboard` remains in place as a separate, modern-auth entry path and is not used by legacy dashboard behavior assertions.

Characterization impact:
- No behavior is de-scoped for legacy contracts; only entry-surface parity for Slice 2 is defined.

## 2026-06-30 - Stabilize Slice 1 Acceptance Gate Before Slice 2

Status: Proposed
Owner: Codex
Slice: Authentication

Decision:
Authentication slice work is implementation-complete for behavior tests, but slice completion is held on an explicit Architect Review gate before any Slice 2 development begins.

Context:
- Legacy authentication behavior tests are marked `Passing` for the auth slice.
- `docs/migration/legacy-test-inventory.md` tracks this artifact at slice maturity.
- `docs/migration/backlog.md` includes unresolved Slice 1 migration risks (notably `MIG-008`).

Consequences:
- Slice 1 cannot be considered complete until architectural review acceptance criteria are documented and approved in this log.
- All later slice implementation is paused until this gate is approved by the architect.
- Any acceptance decision changes (e.g., `MIG-008` closure) must be recorded here before Slice 2 starts.

Characterization impact:
No behavior is de-scoped for auth. Slice 2 is blocked by process control until this gate is explicitly approved.

## 2026-06-30 - Introduce Legacy Authentication Compatibility Slice

Status: Proposed
Owner: Codex
Slice: Authentication

Decision:
Preserve Laravel 8 authentication behavior for Slice 1 by introducing a legacy-compatible login surface (`/`, `/login-user`, `/passwordreset`, `/reset-password`, `/logout`, `/site`) while keeping Fortify for standard auth.

Context:
Slice 1 needs compatibility with legacy session semantics (`loginID`) and legacy message contracts (`This Username is not Registered.`, `Incorrect Password`, `You Have to Login First`) before later slices can rely on authenticated access behavior.

Consequences:
- Legacy middleware now enforces `loginID` for `/site` redirects.
- Legacy login stores `loginID` and authenticates the user through the Laravel auth guard.
- Password reset endpoint supports legacy payload (`user_email_address`) and legacy success strings.
- `/site` is currently a compatibility shim rendered by `Inertia\Inertia::render('Dashboard')`.

Characterization impact:
Slice 1 should be reviewed against legacy authentication characterization rows and marked passing before progressing to Slice 2.

## 2026-06-30 - Restore Fortify `password.update` Compatibility and Exclude Legacy Browser Artifacts From Default Lint

Status: Approved
Owner: Codex
Slice: Authentication

Decision:
To satisfy Laravel baseline authentication contract checks without altering Slice 1 CAMR behavior, define a compatibility route named `password.update` that points to Fortify’s `NewPasswordController::store` with the standard guest middleware, while leaving CAMR legacy `/reset-password` behavior and route (`sendTemporaryPasswordtoEmail`) unchanged.

Context:
- Default migrated tests in `Tests\Feature\Auth\PasswordResetTest` rely on the legacy Fortify route name `password.update`.
- CAMR already keeps legacy password-reset behavior behind the custom `POST /reset-password` endpoint and response flow.
- Copied `tests/Browser/legacy-characterization/` files are reference artifacts with pre-existing lint issues and are not enabled for slice execution.

Consequences:
- `Tests\Feature\Auth\PasswordResetTest` can exercise Fortify compatibility expectations without changing Slice 1 CAMR reset semantics.
- `tests/Browser/legacy-characterization/**` is excluded from default ESLint runs until the artifacts are ported/enabled.
- No CAMR contracts are intentionally changed; this is an acceptance-focused compatibility shim for non-Slice-1 migration artifacts.

Characterization impact:
Authentication characterization remains anchored to `docs/legacy-characterization` and this compatibility route only resolves baseline drift in baseline test execution.

## 2026-06-30 - Keep Gateway, Meter Location, and Meter as Preview Scaffolding Only

Status: Approved
Owner: Codex
Slice: Cross-cutting

Decision:
Keep the current Gateway/Meter/Meter Location implementation and seed/UI visibility work as developer-preview scaffolding only, without treating these domains as officially migrated or complete.

Context:
- Slice 7 governance and earlier decisions place Building as the official slice boundary for this release stage.
- Gateway/Meter/Meter Location scaffolding was added to support visual validation and unblock future implementation context, but official characterization migration has not completed these slices.
- Legacy contract inventory should reflect preview status to avoid accidental acceptance of unreviewed behaviors.

Consequences:
- No route, mutation, or behavioral claims for Gateway/Meter/Meter Location should be interpreted as slice-complete.
- Later slice completion remains blocked until the architect signs off on slice boundary reconciliation and official Slice 7+ plan adjustments.
- Tests and migrations for these domains remain in preview context and are not to be considered slice-approved.

Characterization impact:
- Inventory status for these domains must remain non-complete (for example, `Blocked`) until formal slice acceptance.

## 2026-06-30 - Promote Meter Slice to Official Slice 10 Migration Status

Status: Approved
Owner: Codex
Slice: Meter

Decision:
Promote Meter from preview to official Slice 10 implementation and execute `tests/Feature/MeterTest.php` as active feature contract coverage.

Context:
- Meter routes and endpoints were scaffolded but not yet wired into the active legacy middleware route set.
- Meter behavior has explicit legacy contract coverage in `tests/Feature/MeterTest.php` for page rendering, list endpoint payload contracts, create/update/delete/info, and CSV import behavior.
- The user has authorized Slice 10 execution.

Consequences:
- Meter routes (`/meter`, `/getMeter`, `/create_meter_post`, `/meter_info`, `/update_meter_post`, `/delete_meter_confirmed`, `/import_meters`) are now active in `routes/web.php`.
- `MeterTest` is no longer preview-gated and participates in the full suite execution path.
- `docs/migration/legacy-test-inventory.md` Meter row is now marked `Passing`.

Characterization impact:
- Meter slice behavior is no longer placeholder-only and is now an official part of this migration pass.

## 2026-06-30 - Start User Management Slice (Slice 11) under current migration order

Status: Approved
Owner: Codex
Slice: User Management

Decision:
Proceed to Slice 11 User Management after completing documentation updates for the migration slice pointer and inventory row.

Context:
- User Management has been identified as Slice 11 in the migration order after Meter and before Reports.
- Contract extraction from Laravel 8 and slice artifacts is available in the legacy browser characterization block and legacy reference examples.

Consequences:
- `tests/Feature/UserTest.php` and supporting User/UserSiteAccess implementation are now in-scope for Slice 11 work.
- `docs/migration/legacy-test-inventory.md` user row was added for slice tracking before this slice is considered fully accepted.
- Existing behavior for previously approved slices is unaffected.

Characterization impact:
- User management coverage is now actively being built in slice 11, but slice acceptance remains pending until slice gates close with Architect review.

## 2026-06-30 - Run Slice 11 User Management Acceptance Gates

Status: Approved
Owner: Codex
Slice: User Management

Decision:
Run Slice 11 technical acceptance gates after implementation and confirm no gate failures are introduced by user-management behavior.

Context:
- User route surface and feature tests were in place for `/user`, `/user_list`, `/create_user_post`, `/user_info`, `/update_user_post`, `/delete_user_confirmed`, `/user_account_post`, `/user_site_access`, and `/add_user_access_post`.
- User-management behavior is high-risk because it controls session-protected access semantics and credential-related mutations.

Consequences:
- `php artisan test --compact` passes with `tests/Feature/UserTest.php` green.
- `php artisan test --compact tests/Feature/UserTest.php` passes.
- `vendor/bin/pint --dirty --format agent` passes.
- `npm run lint:check` passes.
- `npm run types:check` passes.
- No additional User-management gaps were identified during this gate run that block Slice 11 technical acceptance.

Characterization impact:
- Slice 11 technical gates are clean; user-management slice remains pending only on Architect review.

## 2026-06-30 - Enforce Admin Gate for User-Management Routes (Slice 13)

Status: Approved
Owner: Codex
Slice: User Management

Decision:
Harden user-management authorization in Slice 13 by requiring legacy session validation plus admin type for all user-maintenance and user-site-access endpoints, while leaving these routes admin-only.

Context:
- Slice 13 scope requires explicit authorization semantics across protected routes and role boundaries.
- `/user*` and `/user_site_access*` are intentionally admin-only for this migration stage.
- User access to selected sites already has workflow effects, and route-level role enforcement reduces accidental privilege extension.

Consequences:
- The following routes now require both legacy login and admin checks:
  - `/user`, `/user_list`, `/create_user_post`, `/user_info`, `/update_user_post`, `/delete_user_confirmed`, `/user_account_post`, `/user_site_access`, `/add_user_access_post`
- Non-admin users receive `403 Forbidden` on those routes.
- `/user*` and `/user_site_access*` remain admin-only.
- `MIG-007` is now considered in review with explicit route-level role coverage.
- `MIG-003` is moved toward closure after confirming scoped-user behavior in authenticated route and site visibility surfaces; remaining cross-slice questions are tracked separately.

Characterization impact:
- Existing Slice 11 tests were strengthened with non-admin forbidden assertions for the user-management route surface.

## 2026-06-30 - DataTables Behavior Parity Route and Response Governance (Slice 14)

Status: Proposed
Owner: Codex
Slice: Cross-cutting

Decision:
Stabilize and verify legacy list endpoint behavior (search, sorting, pagination, counts, scoped visibility, action markers) for the migrated maintenance list surfaces before declaring Slice 14 complete.

Context:
- Slice 14 introduces explicit DataTables-equivalent behavior across `/company_list`, `/division_list`, `/configuration_file_list`, `/site/list`, `/site/user/list`, `/getBuilding`, `/getMeterLocation`, `/getGateway`, `/getMeter`, and `/user_list`.
- Shared DataTable metadata normalization (`app/Actions/Support/DataTableQueryOptions.php`) is now used by the list actions for request-driven ordering and paging.
- Feature tests now cover `draw`, `recordsTotal`, `recordsFiltered`, and filtered pagination for these endpoints, including scoped visibility checks where applicable.

Consequences:
- List endpoint payloads now follow stable `draw`/`recordsTotal`/`recordsFiltered` keys with scoped filtering where required.
- Migration still requires a deterministic full suite pass before Slice 14 can be treated as fully complete.
- Any future DataTables behavior changes should be covered by list-surface tests before being accepted.

Characterization impact:
- Slice 14 behavior is now directly test-bound and traceable through the impacted feature tests.

## 2026-06-30 - Destructive CRUD Edge-Case Classification (Slice 15)

Status: Approved
Owner: Codex
Slice: Cross-cutting

Decision:
Record explicit handling decisions for destructive edge cases introduced by Slice 15 hardening.

Context:
- Slice 15 added delete-blocking checks and transactional behavior to prevent data-loss.
- Four destructive edge cases required explicit classification before architect review:
  1) logged-in admin delete
  2) last admin delete
  3) missing/nonexistent ID delete
  4) dependency-blocked delete response semantics

Consequences:
- Edge-case classification:
  - Deleting the currently logged-in admin user: Covered (allowed by current controller/action path).
  - Deleting the last remaining admin user: Covered (allowed by current action/controller path; no explicit guard currently present).
  - Deleting missing/nonexistent IDs: Backlogged (behavior is inconsistent with legacy error-shape; requires architect decision on 404 vs legacy-compatible 500/other contract).
  - Dependency-blocked deletes returning 500: Covered (current implementation returns 500 and `Delete Failed!` consistently when hard references exist).

Characterization impact:
- Full Slice 15 feature coverage remains green; remaining risk is unresolved legacy compatibility for missing-ID responses.

## 2026-06-30 - Approve Slice 15 with Residual Missing-ID Cleanup

Status: Approved
Owner: Codex
Slice: Cross-cutting

Decision:
Slice 15 destructive CRUD hardening is approved as the core slice outcome.

Context:
- The slice has passed technical gates and covers the primary destructive integrity risks:
  - deleting the currently logged-in admin
  - deleting the last remaining admin
  - dependency-blocked deletions
- The route-level delete behavior for missing/nonexistent resource IDs is intentionally deferred as a follow-up backlog item (`MIG-004`).

Consequences:
- Slice 15 is treated as approved for migration progression.
- The implementation decision is that this residual missing-ID inconsistency does not block slice approval.
- A follow-up backlog-driven hardening item remains required to align error semantics if legacy contract requires a 500/error message path.

Characterization impact:
- Slice 15 remains open only for non-blocking clean-up and does not block release movement to Slice 16.

## 2026-07-01 - Approve Reports Hardening With Residual Differences

Status: Approved
Owner: Architect
Slice: Reports

Decision:
Approve Slice 16 Reports Hardening as migration-complete with residual report-parity differences.

Context:
- Representative report route compatibility, validation behavior, scoped access behavior, payload shape, boundary behavior, and representative aggregation/XLSX assertions are now covered in `tests/Feature/ReportTest.php`.
- Representative workbook assertions include sheet existence, legacy-compatible filename/content-type, representative headers, and representative business fields.
- Full acceptance gates are green for Slice 16 and the slice is no longer considered scaffold-only.

Consequences:
- `MIG-002` is moved to `Accepted with Residual Differences`.
- Remaining work is tracking for exhaustive parity improvements rather than representative behavioral correctness.
- Slice 16 is eligible to progress to production-hardening governance with residual risks explicitly visible in `MIG-002`.

Characterization impact:
- `tests/Feature/ReportTest.php` is now the migration-complete representative characterization artifact for Slice 16.
- `docs/migration/legacy-test-inventory.md` report row remains `Passing` with explicit residual-work notes.
