# CAMR Operator Journey Testing Strategy

## Purpose

This strategy turns operator workflows into the acceptance standard for UI modernization. It enables safe page/component redesign by proving behavior through operator-centered tests.

The migration already proved backend behavior through characterization. UI modernization should use the same principle:

- preserve workflow,
- verify outcomes,
- avoid brittle implementation coupling.

## Test Architecture by Layer

### Layer 1 — Component Tests

Purpose: fast, deterministic verification of reusable UI primitives.

Suggested focus:
- `StatusChip` renders online/warn/offline states with correct semantics.
- KPI cards show computed values and labels consistently.
- Validation display behavior across form components.
- Table action renderers for permission-aware rendering.
- Report filter component validation and submission state.

Recommended location (future):
- `resources/js/components/__tests__/`
- `tests/Feature/` or browser-oriented suites can import shared component expectations through a component test runner.

### Layer 2 — Page Interaction Tests

Purpose: verify Inertia page behavior without full browser overhead.

Suggested focus:
- Company/Division/Site/Gateway/Meter/User maintenance flows.
- List behavior and mutation states (create/edit/delete actions).
- Route/state coupling and flash-message behavior.
- Scoped visibility and form-level validation.

Recommended location (future):
- `tests/Feature/*Page*Test.php` under existing Pest structure.

### Layer 3 — Operator Journey Tests (most important)

Purpose: verify end-to-end operator work in realistic context.

Test as role-based narratives:

- Administrator journey
  - Login → Company → Division → Site → Create/Assign User → Logout
- Operations Engineer journey
  - Login → Dashboard → Offline Gateway → Gateway Detail → Download CSV → Force LP → Verify status
- Maintenance Technician journey
  - Login → Site → Gateway → Meter → Update configuration → Verify update flag
- Analyst journey
  - Login → Consumption Report → Demand Report → Export XLSX → Verify workbook assertions

Implementation can remain non-browser until phase 1, but structure should prepare for browser-based end-to-end behavior.

Layer 3 should execute after Phase 0.25, because meaningful journey validation depends on realistic live state transitions:

Seed Demo Dataset → Run Telemetry Simulator → Login as role user → Navigate workflow → Observe operational transitions → Verify stateful result.

Recommended location (future):
- `tests/Browser/OperatorJourney/`

Current infrastructure introduced in Phase 0.5:

- `tests/Browser/OperatorJourney/Support/OperatorPersona.php`
- `tests/Browser/OperatorJourney/Support/OperatorScenario.php`
- `tests/Browser/OperatorJourney/Support/OperatorJourneyFixture.php`
- `tests/Browser/OperatorJourney/Scenarios/ScenarioCatalog.php`
- `tests/Browser/OperatorJourney/README.md`

### Layer 4 — Operational Demonstration Scripts

Purpose: repeatable manual/automated demo flows for production-like evidence.

- Scripts execute a seeded state sequence and verify key outputs.
- Not test assertions first-class.
- Useful for release demos and training.

Recommended location (future):
- `docs/ui/demo-scripts/`

## Browser vs Feature Coverage

For UI modernization, browser coverage is useful once component + feature layers are stable. Priority is:

1. Foundation components
2. Critical page interactions
3. Journeys
4. Visual/performance smoke scenarios (optional)

## Evidence Style

- Prefer behavior assertions over selector fragility.
- Assert outcomes and operators' next actionable state, not DOM aesthetics.
- Keep tests resilient to markup refactors.

## Gate for Slice Progression

A UI slice may proceed when:
- relevant journeys remain green,
- component behavior for that slice is covered,
- no prior workflow regressions are uncovered,
- data profile supports the scenario.

## Relationship to Operational Telemetry Simulator

Static state is sufficient for basic navigation, but journeys for operations must prove behavioral response to change:

- status transitions from online to stale/offline and back,
- update-flag life cycle,
- report windows that expand as telemetry accumulates,
- recovery and refresh operations producing visible side effects.

The simulator phase is therefore required prior to full Layer 3 execution for high-confidence acceptance.

## Notable Risks to Address

- Route-name churn in SPA navigation.
- Legacy text/field naming changes breaking workflow recognition.
- Scoped access behavior that hides content instead of preventing access.
- Flaky browser tests due to async telemetry rendering.

## Suggested Tooling Sequence

- Pest feature tests first for fast loops.
- Browser tests for cross-page operator workflows and long-form journeys.
- Optional Playwright/Cypress parity if browser suite requires richer orchestration.

## Suggested Test Naming Convention

- `...Test.php` for feature tests,
- `OperatorJourney` prefix for cross-domain flows,
- `*Smoke` for high-signal regression tests.

## Success Criteria for this Layer

- At least one smoke journey per persona exists.
- Journeys cover both read and action paths.
- Failure paths (permissions, invalid states, stale data, empty report windows) are represented.
