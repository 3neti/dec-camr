# Operator Journey Foundation

This directory contains the reusable foundation used by UI Operator Journey planning.

`AdminOperatorSmokeTest.php` is the first executable browser smoke journey. BT-002 covers the high-value browser smoke surfaces:

- `/` renders the legacy login page,
- the explicit `admin / 123456` credential hint is visible,
- login redirects to `/site`,
- `/site` renders the operator home surface,
- `/dashboard` renders the operator console surface,
- `/analytics` renders the analytics workbench,
- visited login, operator-home, dashboard, and analytics pages report no JavaScript errors.

Full multi-step operator journeys remain covered by feature tests until the browser journey suite is expanded.

## Directory

- `Support/OperatorPersona.php` — persona registry used by journey builders and future tests.
- `Support/OperatorScenario.php` — reusable high-level journey scenario metadata.
- `Support/OperatorJourneyFixture.php` — route-level fixture payloads used by journey orchestration.
- `Scenarios/*` — concrete journey definition placeholders and scenario scripts.

## Planned Next Use

- Expand browser coverage for Reports, Live Operations, and Analytics journeys.
- Reuse fixture constants across journey helper specs and demonstration scripts.
