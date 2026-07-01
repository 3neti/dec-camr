# Operator Journey Foundation

This directory contains the reusable foundation used by UI Operator Journey planning.

No executable browser journeys are enabled yet. This layer is intentionally infrastructure-only for **Phase 0.5**.

## Directory

- `Support/OperatorPersona.php` — persona registry used by journey builders and future tests.
- `Support/OperatorScenario.php` — reusable high-level journey scenario metadata.
- `Support/OperatorJourneyFixture.php` — route-level fixture payloads used by journey orchestration.
- `Scenarios/*` — concrete journey definition placeholders and scenario scripts.

## Planned Next Use

- Wire these scenario definitions into browser tests during **Phase 1+**.
- Reuse fixture constants across journey helper specs and demonstration scripts.
