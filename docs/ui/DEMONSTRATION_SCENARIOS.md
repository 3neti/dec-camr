# CAMR Operational Demonstration Scenarios

## Purpose

This document defines repeatable demonstration scripts that validate operator readiness and make UI progress visible while preserving workflow semantics.

Scenario flow is now explicitly phase-aware:

- Seed Demo Dataset
- Run Telemetry Simulator (Phase 0.25)
- Execute operator journey scripts

These scenarios are not test implementations yet; they define scripts that can later be executed manually or automated.

## Principles

- Seed state must be deterministic.
- Scenarios should be fast and repeatable.
- Focus on workflow outcomes and safety outcomes.
- Scenarios should align with operator personas.

## Scenario Set A — Administrator Readiness

### A1 — Basic Tenant/Access Provisioning

1. Seed Demo dataset.
2. Login as admin.
3. Open Company maintenance.
4. Open Divisions and verify hierarchy.
5. Open Sites and assign site access to a scoped operator.
6. Validate that scoped user role changes persist and are visible.
7. Run short telemetry simulation burst with recovery scenario.
8. Confirm user access changes remain coherent after state transition.
9. Logout.

### A2 — Scoped Access Validation

1. Seed Demo dataset.
2. Login as admin and create/modify scoped user.
3. Login as scoped user in separate session.
4. Verify access scope list behavior in relevant maintenance pages.
5. Run simulator with `--scenario=report-window` and confirm no unintended route leakage.

## Scenario Set B — Operations Engineer Path

### B1 — Gateway Health + Recovery Flow

1. Seed Demo dataset with at least one offline and one stale gateway.
2. Login as operations user.
3. Open Dashboard and identify offline/stale devices.
4. Open target offline gateway detail.
5. Download CSV payload.
6. Execute Force LP action.
7. Run simulator with `--speed=real --duration=2m`.
8. Verify status transition markers from stale to online or still offline depending on scenario intent.

### B2 — Update Flag Verification

1. Seed with pending update markers.
2. Visit gateway/meter management context.
3. Verify update indicators map to expected entities.
4. Trigger one safe recovery operation and verify status message.
5. Run `--scenario=report-window` to confirm simulator does not invalidate report lineage.

## Scenario Set C — Maintenance Technician Path

### C1 — Site-to-Meter Maintenance

1. Seed Demo dataset.
2. Login as maintenance role.
3. Navigate: Site → Gateway → Meter.
4. Update config-related metadata.
5. Start simulator for 30s to generate fresh meter data.
6. Save and validate in-page confirmation + list update visibility.

### C2 — Meter Location + Building Context

1. Seed Demo dataset.
2. Navigate to maintenance site area and inspect location/building records.
3. Perform create/update where permitted.
4. Run short `--scenario=offline-recovery` simulation.
5. Verify no workflow drift in breadcrumbs/context transitions and no stale-state confusion.

## Scenario Set D — Analyst Path

### D1 — Report Family Validation

1. Seed Demo profile with report-ready telemetry window.
2. Run simulator for a warm-up window (`--duration=1m --speed=fast`) so recent data is present.
2. Login as analyst.
3. Generate Consumption report with representative filters.
4. Generate Demand report with representative filters.
5. Export XLSX from both workflows.
6. Verify files and content-type are as expected.

### D2 — Empty and Boundary Cases

1. Seed Demo profile and choose boundary filter window with no data.
2. Run simulator against alternate profile with `--scenario=report-window` for controlled window control.
2. Run Raw or Site report.
3. Verify user-facing empty-result behavior and return path.

## Scenario Set E — Demonstration Readiness Sweep

### E1 — End-to-End Live Demo Flow

1. Seed Demo dataset.
2. Run simulator for 60–120s before opening dashboard to create realistic freshness differences.
2. Login as operations role.
3. Open Dashboard.
4. Open offline item and confirm indicator.
5. Navigate to report page and generate one operational report.
6. Export XLSX and confirm workbook output.
7. Validate no unexpected errors during normal navigation.

## Scenario Set F — Release Preparation Scripts

### F1 — Large Data Readiness

1. Seed Heavy profile.
2. Smoke through pagination and list endpoints.
3. Confirm dashboard, list, and report pages remain responsive.
4. Verify action availability remains coherent under realistic load.

### F2 — Fresh Install Validation

1. Reset + seed Minimal profile.
2. Validate core pages are all reachable and operational.
3. Validate no placeholder-only behavior in core slices.

## Suggested Execution Cadence

- Weekly before UI slices.
- Run relevant scenario set before major slice merge.
- Re-run subset after navigation or component replacement.

## Repository Structure Recommendation

- `docs/ui/demo-scenarios/` for markdown scenario definitions.
- `docs/ui/roles/` for persona-specific scenario notes (future).
- `tests/Browser/operator_journeys/` for future browser implementations.
- `tests/Feature/` for page-level flow coverage.
