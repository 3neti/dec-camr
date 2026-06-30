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

## Decisions

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
