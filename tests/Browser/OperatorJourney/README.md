# Operator Journey Foundation

This directory contains the reusable foundation used by UI Operator Journey planning.

`AdminOperatorSmokeTest.php` is the first executable browser smoke journey. BT-010 covers the high-value browser smoke surfaces, the first analyst analytics journey, live SCADA dashboard smoke, gateway recovery smoke, live operations command surface smoke, maintenance CRUD shell smoke, responsive mobile smoke, and report workflow smoke:

- `/` renders the legacy login page,
- the explicit `admin / 123456` credential hint is visible,
- login redirects to `/site`,
- `/site` renders the operator home surface,
- `/dashboard` renders the operator console surface,
- `/analytics` renders the analytics workbench,
- the analyst can see analytics evidence controls and major workbench sections,
- the live SCADA replay scenario renders dashboard telemetry, gateway, meter, pending update, and report readiness sections,
- the gateway recovery scenario renders offline/stale attention, gateway health, timeline, command, and pending update sections,
- the operations command surface renders approved RTU-safe command affordances without executing protocol endpoints,
- the Company, Division, Site, Building, Gateway, and Meter maintenance pages render create, list, and action affordances,
- the `/site`, `/dashboard`, and `/analytics` core operator surfaces render under a mobile viewport,
- the SAP, Raw, Site, Consumption, and Demand report pages render filter, preview, and download shelf sections,
- visited login, operator-home, dashboard, and analytics pages report no JavaScript errors.

Full multi-step operator journeys remain covered by feature tests until the browser journey suite is expanded.

BT-011 keeps the browser suite maintainable by centralizing the legacy browser session setup. New browser journeys should authenticate through the same `loginID` session compatibility path instead of only relying on Laravel guard state.

## Directory

- `Support/OperatorPersona.php` — persona registry used by journey builders and future tests.
- `Support/OperatorScenario.php` — reusable high-level journey scenario metadata.
- `Support/OperatorJourneyFixture.php` — route-level fixture payloads used by journey orchestration.
- `Scenarios/*` — concrete journey definition placeholders and scenario scripts.

## Planned Next Use

- Expand browser coverage for Reports, Live Operations, and Analytics journeys.
- Reuse fixture constants across journey helper specs and demonstration scripts.
