# Remaining Migration Slices Plan

Official remaining slice order:

1. Slice 13 — Authorization Semantics
2. Slice 14 — DataTables Behavior Parity
3. Slice 15 — Destructive CRUD Hardening
4. Slice 16 — Reports Hardening
5. Slice 17 — RTU / Device Endpoints
6. Slice 18 — Backlog Cleanup / Release Readiness

This plan is implementation-only for the remaining migration work and does not alter application code, tests, routes, UI, database schema, or dependencies.

---

## Slice 13 — Authorization Semantics

### Purpose
- Remove the migration risk of inconsistent access control across legacy-style pages and modern Laravel 13 auth behavior.
- This slice appears after User Management because it directly governs how users, routes, and scoped data views are protected in all later modules.
- It removes high-risk migration behavior drift in scoped permissions, role checks, and site-level visibility.

### Backlog Mapping
- MIG IDs addressed:
  - `MIG-003` (Scoped-user authorization semantics)
- Partially addressed items:
  - `MIG-007` user site-access mutation (for authorization side effects and mutation boundaries)
- Dependencies:
  - Completed Authentication, User Management, and session contract (`loginID`) to avoid reworking gate mechanics while validating policy correctness.

### Legacy Reference
Inspect Laravel 8 artifacts to preserve intent before implementation:
- Middleware classes and auth filters in `app/Http/Middleware`.
- `routes/web.php` for auth + role-protected routes.
- Controllers that currently expose site/user-scoped data:
  - user management pages/controllers,
  - site/division/company/dashboard gates.
- Browser and feature characterization artifacts for negative-access behavior:
  - unauthorized access redirects,
  - forbidden route interactions,
  - session-gated views.
- Any role/matrix references in seed fixtures or SQL initializers.

### Characterization Requirements
- Already available:
  - Feature coverage from user/session-focused slices.
- Gaps to confirm before Slice 13 start:
  - explicit role-to-route denial tests for each privilege boundary,
  - scoped listing invariants (user cannot see/edit outside assigned scope),
  - clear error/redirect behavior for denied access.
- Browser behaviors to preserve:
  - protected page redirect (`You Have to Login First`-style semantics),
  - legacy modal/messages when scope blocks action.
- Tests to strengthen:
  - add missing role edge cases where existing tests only cover happy path.

### Expected Files
- `app/Http/Middleware/*` for gate and scope middleware.
- `app/Policies/*` for domain resource policies.
- `app/Http/Controllers/*` route entry points to apply policy checks.
- `app/Actions/*` for permission/authorization orchestration where it spans multiple services.
- `app/Http/Requests/*` for authorization-aware validation.
- `tests/Feature/*` for route-level scope matrices.
- `docs/migration/*` for updated decision/backlog references.

### Laravel 13 Architecture
- Prefer explicit policies for resource-level permission checks.
- Keep authorization in middleware/policies, not in view-layer conditionals.
- Use Form Requests for request-level authorization gates when endpoint-level context-specific validation is required.
- Use lightweight Actions for cross-cutting authorization orchestration only when logic spans multiple models/services.
- Keep DTO use minimal:
  - only if approved and needed for structured authorization input/output.
- Preserve existing Inertia patterns; authorization failures should resolve with legacy-compatible responses.

### Risks
- Behavioral risk: silently widening access or changing denial UX messages.
- Migration risk: inconsistent interpretation of legacy roles across legacy vs Fortify session contexts.
- Technical risk: policy/middleware mismatch causing 500s or inconsistent redirects under AJAX contexts.
- Governance risk: accepting broad access changes without explicit decision record.

### Definition of Done
- Migration backlog references are updated for any remaining authorization deltas.
- Characterization tests include both session-gated and policy-gated routes.
- Full Pest subset + full suite green.
- Pint/lint/types clean.
- Architect decision logged for any intentional authorization deviations.

---

## Slice 14 — DataTables Behavior Parity

### Purpose
- Restore legacy-equivalent list behavior for sort, search, pagination, and action rendering.
- This slice is required before production trust can depend on large-tabular UI and admin workflows.
- It addresses one of the highest user-visible migration risks where UI semantics can drift subtly while route coverage remains green.

### Backlog Mapping
- MIG IDs addressed:
  - `MIG-005` (DataTables behavior semantics)
- Partially addressed items:
  - Cross-module list behavior impacts in later slices that all depend on consistent payload contracts.
- Dependencies:
  - Authorization semantics must be stable to ensure filtered/paginated list endpoints are tested under correct access context.

### Legacy Reference
- List endpoints in Laravel 8 controllers for each migrated domain.
- JavaScript pagination/search/sort handling in legacy blade/js pages.
- Legacy characterization browser tests for list actions and row payload markers.
- Any server-side DataTable adapter or query helpers in legacy controllers/models.

### Characterization Requirements
- Already available:
  - route-level and basic response shape tests from existing slice suites.
- Gaps:
  - No-op search/sort defaults vs explicit query order.
  - Pagination off-by-one behavior (`page`, `per_page`, total count).
  - Server/client mismatch when filtering by free-text and hidden columns.
  - Actions column rendering stability under edge data sizes.
- Browser behaviors:
  - legacy action anchors (`editX`, `deleteX`) order and visibility.

### Expected Files
- `app/Http/Controllers/*` list endpoints.
- `app/Services/*` or `app/Actions/*` for query normalization/pagination helpers.
- `app/Models/*` scoped query scopes.
- `resources/js/components/*` and relevant list pages.
- `tests/Feature/*` parameterized list behavior tests.

### Laravel 13 Architecture
- Keep Eloquent query logic in dedicated query scopes to avoid ad-hoc controller SQL.
- Avoid framework-side complexity for parity unless legacy behavior depends on it.
- Use Actions only when sorting/filtering logic is reused across endpoints.
- Use Form Requests for endpoint-specific filters if validation of query keys is required.
- Maintain explicit legacy payload keys in API response arrays for UI parity.

### Risks
- Behavioral risk: breaking sorting/search semantics without obvious test failures.
- Migration risk: inconsistent query shapes across domains causing cascading UI breakage.
- Technical risk: performance regressions on large table datasets.
- Governance risk: accepting behavior “good enough” when parity requires strict determinism.

### Definition of Done
- Characterization test matrix proves search/sort/pagination behavior per module.
- Empty-result, partial-result, and multi-filter behavior covered.
- Full suite green, with lint/type checks clean.
- Backlog item `MIG-005` moved to accepted/closed only with explicit decision.

---

## Slice 15 — Destructive CRUD Hardening

### Purpose
- Prevent migration regressions in delete/update/reassign behavior for high-impact entities.
- This slice removes integrity and safety drift after all CRUD slices are present in practice.
- It is intentionally placed before report and RTU hardening to reduce irreversible-data-loss and integrity risk.

### Backlog Mapping
- MIG IDs addressed:
  - `MIG-004` (Destructive workflows across Site/Gateway/Meter/User/Building)
- Partially addressed items:
  - `MIG-007` where mutation of access state can trigger destructive side effects.
- Dependencies:
- Slices 3–11 domain implementations must be present to validate cross-entity effects safely.

### Legacy Reference
- Legacy destroy and mutate endpoints in `routes/web.php`.
- Controllers and model relations for delete/update/cascade behavior.
- Browser characterization for confirmation/cancel flows and success/error messaging.
- SQL constraints / foreign keys in legacy schema.

### Characterization Requirements
- Already available:
  - route and mutation tests in prior slice suites.
- Gaps:
  - cascade/reassignment expectations for dependent rows,
  - transactional behavior and rollback behavior,
  - forbidden/edge deletes (in-use items, protected roles),
  - legacy flash messages and confirmation contract text.
- Browser behaviors:
  - confirmation surfaces and post-delete redirect behavior.
- Strengthen tests with pre/post record counts and relation checks.

### Expected Files
- `app/Http/Controllers/*` delete/update action endpoints.
- `app/Actions/*` command-style mutation workflows.
- `app/Services/*` for related-record validation and re-parenting rules.
- `app/Models/*` relations and constraint-safe operations.
- `tests/Feature/*` destructive mutation matrices and relation assertions.

### Laravel 13 Architecture
- Use service-layer helpers where operations affect multiple aggregates.
- Keep controllers thin and transactional action-driven.
- Prefer policy checks plus domain services over controller-heavy branching.
- Avoid sweeping use of DB transactions unless a slice decision defines rollback semantics.
- Do not use DTOs unless they clarify structured mutation payloads already shared across slices.

### Risks
- Behavioral risk: deleting or remapping legacy-linked records differently than expected.
- Migration risk: hidden reliance on database-level cascades not present in Laravel 13 schema.
- Technical risk: orphaned rows and non-idempotent mutations.
- Governance risk: accepting destructive behavior without explicit message and confirmation compatibility.

### Definition of Done
- Mutation and deletion coverage includes rollback/consistency assertions.
- Scoped roles and authorization checks are enforced for destructive actions.
- Full suite + Pint/lint/types clean.
- Architect-logged decisions for any intentionally modernized destructive semantics.

---

## Slice 16 — Reports Hardening

### Purpose
- Finish the migration from route-only report scaffolding to behavior-correct report outputs.
- This slice removes remaining high-impact business-risk in `MIG-002` while ensuring report surfaces are now production-ready.
- It is delayed until security, data access, and destructive behavior are stable so report content reflects trusted source data.

### Backlog Mapping
- MIG IDs addressed:
  - `MIG-002` (Main report XLSX exports)
- Partially addressed items:
  - `MIG-001` where report/telemetry depends on downstream data quality.
- Dependencies:
  - Stable authentication, authorization, and deterministic list/query behavior.

### Legacy Reference
- `app/Http/Controllers/ReportController.php` and `config`/route mappings in Laravel 8.
- Legacy report templates and worksheet/format conventions.
- Legacy characterization for report route surfaces and payloads.
- Legacy SQL/reporting scripts for filter behavior and aggregate formulas.
- Existing seed/model relationships that shape report inputs.

### Characterization Requirements
- Already available:
  - Slice 12 route-surface and scaffold contract tests (if available).
- Gaps:
  - formula parity (period totals, aggregations, rounding rules),
  - workbook content and column semantics,
  - edge cases across empty/null data and scoped visibility,
  - binary/text MIME behavior.
- Browser behaviors:
  - download initiation and user-visible report naming/format expectations.
- Add contract tests for export payloads with deterministic fixtures.

### Expected Files
- `app/Actions/Reports/*` for report query/aggregation operations.
- `app/Http/Controllers/ReportController.php` and supporting request classes.
- `app/Http/Requests/*` for report filters.
- `app/Services/*` for reusable report transforms.
- `resources/js/pages/Reports.vue` if UI triggers or validation needs refinement.
- `tests/Feature/ReportTest.php` with hardening additions.
- `tests/Feature/LegacyCharacterizationReference/*` if report reference coverage exists.

### Laravel 13 Architecture
- Keep report composition in application services/actions to keep controllers thin.
- Use form requests for filter validation, especially for date/time and scope filters.
- Introduce DTOs only if migration already approves and only for structured report rows/response payloads.
- Prefer explicit response serializers for XLSX metadata and compatibility with legacy expectations.

### Risks
- Behavioral risk: silent arithmetic drift in historical calculations.
- Migration risk: altered file formats accepted by downstream consumers.
- Technical risk: performance and memory pressure on full dataset exports.
- Governance risk: moving from scaffold to hardened behavior without architect signoff.

### Definition of Done
- Report routes + XLSX exports validated against executable legacy expectations.
- Deterministic test fixtures for edge data and scope filters.
- Full suite + Pint/lint/types clean.
- Decision log updated for any tolerated report-semantic differences.
- Migration backlog `MIG-002` reconciled to accepted/closed status only after full validation.

---

## Slice 17 — RTU / Device Endpoints

### Purpose
- Complete hardening of protocol and ingestion endpoints consumed by devices.
- This is the last major high-risk integration class before release readiness.
- It removes Critical-risk gaps in the legacy protocol contract and side-effect behavior.

### Backlog Mapping
- MIG IDs addressed:
  - `MIG-001` (RTU/device protocol endpoints)
- Partially addressed items:
  - `MIG-005` (listing/search semantics for endpoint logs if any),
  - `MIG-004` (destructive side effects via reset endpoints).
- Dependencies:
  - Complete authorization and routing trust boundaries to avoid unauthenticated protocol access.

### Legacy Reference
- Laravel 8 controllers handling RTU endpoints:
  - `CAMRGatewayDeviceController`
  - gateway and meter device controllers as discovered in the legacy tree.
- Legacy `routes/web.php` entries under `/rtu/...`.
- Legacy browser or HTTP artifacts showing expected MAC/path handling and reset side effects.
- Legacy models for gateway, meter, and telemetry data persistence.

### Characterization Requirements
- Already available:
  - likely weak in current slice set; protocol endpoints may have partial route coverage only.
- Gaps:
  - content-type and response-body contracts,
  - status flag transitions and reset semantics,
  - malformed MAC/request payload handling,
  - authentication expectations for device endpoints,
  - deduplication/ordering/race behavior for repeated posts.
- Browser behaviors: likely minimal; prioritize contract tests and HTTP probes.
- Add high-confidence protocol fixtures and assertions for each route endpoint.

### Expected Files
- `app/Http/Controllers/*` RTU/device endpoints.
- `app/Actions/*` protocol intake and reset operations.
- `app/Http/Middleware/*` for IP/secret/token gating where required by legacy contract.
- `app/Models/*` for incoming telemetry persistence.
- `tests/Feature/*` endpoint-specific protocol tests.
- `tests/Unit/*` parser/normalizer tests for request payload maps.

### Laravel 13 Architecture
- Keep endpoint handlers explicit and defensive.
- Favor service-layer parsing/normalization for protocol payloads.
- Use policies/middleware for endpoint-level trust gating.
- Use jobs/queue only when explicit legacy behavior proves asynchronous processing.
- Avoid over-abstracting single-endpoint workflows.

### Risks
- Behavioral risk: protocol incompatibility causing missed telemetry or wrong reset behavior.
- Migration risk: accidental exposure of unauthenticated endpoints or changed side-effect semantics.
- Technical risk: parsing/normalization drift for legacy payload variants.
- Governance risk: pushing beyond protocol contract without architecture signoff.

### Definition of Done
- Endpoint contract tests cover update, download/reset, and ingest routes with fixture inputs.
- Critical legacy route behavior is validated (method, headers, payload, status updates).
- Full suite + Pint/lint/types clean.
- `MIG-001` closed only with explicit decision entry and contract evidence.

---

## Slice 18 — Backlog Cleanup / Release Readiness

### Purpose
- Convert remaining migration risk list into executable, tracked, and consciously accepted status.
- Resolve slice-boundary reconciliation debt including `MIG-009`.
- Create a controlled, evidence-based release entry by ensuring governance, docs, and quality gates are closed together.

### Backlog Mapping
- MIG IDs addressed:
  - `MIG-009` (slice-order/reconciliation and completion markers)
- Potentially unresolved IDs from earlier slices that persist:
  - `MIG-003`, `MIG-004`, `MIG-005`, `MIG-006`, `MIG-007`, `MIG-008`, `MIG-002`, `MIG-001` if not closed by prior slices.
- Dependencies:
  - All previous slices complete and reviewed.

### Legacy Reference
- Governance artifacts:
  - `docs/migration/backlog.md`
  - `docs/migration/legacy-test-inventory.md`
  - `docs/migration/decisions.md`
- Prior migration decisions and any pending “blocked” notes.
- Legacy test inventory for artifact-to-slice mappings and maturity.

### Characterization Requirements
- Already available:
  - slice-level feature evidence and legacy references.
- Gaps:
  - unresolved items lacking explicit decision and closure criterion,
  - slice completion markers that do not match actual slice acceptance status.
- Required actions:
  - close open docs-to-implementation mismatches,
  - add missing backlog decisions,
  - confirm no deferred behavior was mistakenly marked as complete.

### Expected Files
- `docs/migration/backlog.md`
- `docs/migration/legacy-test-inventory.md`
- `docs/migration/decisions.md`
- `docs/migration/remaining-slices-plan.md` updates if scope changes.

### Laravel 13 Architecture
- No runtime architecture changes; governance architecture only.
- Enforce strict doc/schema alignment between code, tests, and slice status.
- Maintain conservative decisions for any unresolved behavior without forced closure.

### Risks
- Behavioral risk: hidden mismatch between implementation and documented route/behavior acceptance.
- Migration risk: teams start production hardening from an inaccurate progress state.
- Technical risk: silent test/coverage rot after many slices.
- Governance risk: losing slice boundary controls after preview/scaffold work.

### Definition of Done
- All backlog items either resolved, deferred, or explicitly accepted with decision rationale.
- Legacy inventory statuses correctly reflect actual test maturity and slice acceptance.
- No contradictory “Passing” markers for unfinished domains.
- Final architect review gate recorded for full migration readiness milestone.

---

## Migration Completion Roadmap

### Remaining Implementation Effort
- Slice 13 to Slice 17 are behavior-hardening slices and will require coordinated controller/action/refactor cycles plus route- and model-level validation updates.
- Slice 18 is governance-only and should be interleaved at milestone boundaries to prevent status drift.

### Highest Remaining Risks
- RTU/protocol correctness (`MIG-001`) and report export correctness (`MIG-002`) carry highest production blast radius.
- Authorization and destructive operations (`MIG-003`, `MIG-004`) are the biggest security and data-integrity risks.
- DataTables semantics (`MIG-005`) and user-access side effects (`MIG-007`) remain likely subtle, user-visible gaps.

### Expected Order of Architect Reviews
- Review after each slice (13 through 17), with explicit acceptance for each slice before proceeding.
- Special governance review before Slice 17 RTU integration due to protocol-critical side effects.
- Final review at Slice 18 before release readiness lock.

### Dependencies Between Remaining Slices
- Slice 13 is prerequisite for enforcing and validating all protected endpoints in later hardening slices.
- Slice 14 depends on stable query contracts from prior entity implementations.
- Slice 15 depends on both authorization and route availability in all targeted entities.
- Slice 16 depends on reliable data scope, authorization, and deterministic data model state from prior slices.
- Slice 17 depends on hardened authorization and route protections, plus safe data persistence.
- Slice 18 depends on closure of all previous slices and clean traceability artifacts.

### Recommended Stopping Points
- Stop at each slice boundary for:
  - targeted feature test updates,
  - full gates,
  - decision log entry.
- Pause between Slice 16 and Slice 17 to validate release safety around high-impact write pathways.

### Recommended Release Readiness Milestones
- Milestone A: Slices 13–14 complete + full gates.
- Milestone B: Slices 15–16 complete + critical auth/data integrity risk closure.
- Milestone C: Slice 17 complete with signed protocol decision and fallback plan.
- Milestone D: Slice 18 governance completion with all backlog statuses reconciled.

### Slice Order Recommendation
- Keep official order from Slice 13 to Slice 18.
- No architectural reorder is recommended at this planning stage because cross-cutting risk containment depends on incremental hardening after previously scaffolded slices.
