# Slice 7 Variation Report

## 1. Executive Summary

Current work is **substantially outside official Slice 7 scope**.

The modified set is dominated by **Gateway/Meter/Meter-location scaffolding** plus preview data/UI support. It does **not** implement the official Slice 7 (Building) behavior, while it introduces substantial later-slice contracts and visible pages.

## 2. Files Changed Inventory

| File | Classification |
|---|---|
| `database/seeders/DatabaseSeeder.php` | Preview/support scaffolding |
| `routes/web.php` | Cross-cutting |
| `tests/Feature/DashboardTest.php` | Cross-cutting |
| `docs/migration/legacy-test-inventory.md` | Cross-cutting |
| `docs/migration/decisions.md` | Cross-cutting |
| `app/Actions/Gateway/CreateGatewayAction.php` | Later slice: Gateway |
| `app/Actions/Gateway/DeleteGatewayAction.php` | Later slice: Gateway |
| `app/Actions/Gateway/GetGatewayAction.php` | Later slice: Gateway |
| `app/Actions/Gateway/ListGatewaysAction.php` | Later slice: Gateway |
| `app/Actions/Gateway/UpdateGatewayAction.php` | Later slice: Gateway |
| `app/Actions/Meter/CreateMeterAction.php` | Later slice: Meter |
| `app/Actions/Meter/DeleteMeterAction.php` | Later slice: Meter |
| `app/Actions/Meter/GetMeterAction.php` | Later slice: Meter |
| `app/Actions/Meter/ImportMetersAction.php` | Later slice: Meter |
| `app/Actions/Meter/ListMetersAction.php` | Later slice: Meter |
| `app/Actions/Meter/UpdateMeterAction.php` | Later slice: Meter |
| `app/Actions/Site/CreateSiteAction.php` | Preview/support scaffolding |
| `app/Actions/Site/DeleteSiteAction.php` | Preview/support scaffolding |
| `app/Actions/Site/GetSiteAction.php` | Preview/support scaffolding |
| `app/Actions/Site/ListSitesAction.php` | Preview/support scaffolding |
| `app/Actions/Site/UpdateSiteAction.php` | Preview/support scaffolding |
| `app/Http/Controllers/GatewayController.php` | Later slice: Gateway |
| `app/Http/Controllers/MeterController.php` | Later slice: Meter |
| `app/Http/Controllers/SiteController.php` | Preview/support scaffolding |
| `app/Http/Requests/Gateway/CreateGatewayRequest.php` | Later slice: Gateway |
| `app/Http/Requests/Gateway/UpdateGatewayRequest.php` | Later slice: Gateway |
| `app/Http/Requests/Meter/CreateMeterRequest.php` | Later slice: Meter |
| `app/Http/Requests/Meter/ImportMetersRequest.php` | Later slice: Meter |
| `app/Http/Requests/Meter/UpdateMeterRequest.php` | Later slice: Meter |
| `app/Http/Requests/Site/CreateSiteRequest.php` | Preview/support scaffolding |
| `app/Http/Requests/Site/UpdateSiteRequest.php` | Preview/support scaffolding |
| `app/Models/Gateway.php` | Later slice: Gateway |
| `app/Models/Meter.php` | Later slice: Meter |
| `app/Models/MeterLocation.php` | Later slice: Meter Location |
| `app/Models/Site.php` | Preview/support scaffolding |
| `database/factories/GatewayFactory.php` | Later slice: Gateway |
| `database/factories/MeterFactory.php` | Later slice: Meter |
| `database/factories/MeterLocationFactory.php` | Later slice: Meter Location |
| `database/factories/SiteFactory.php` | Preview/support scaffolding |
| `database/migrations/2026_06_30_120003_create_meter_site_table.php` | Preview/support scaffolding |
| `database/migrations/2026_06_30_120004_create_meter_location_table.php` | Later slice: Meter Location |
| `database/migrations/2026_06_30_120005_create_meter_rtu_table.php` | Later slice: Gateway |
| `database/migrations/2026_06_30_120006_create_meter_details_table.php` | Later slice: Meter |
| `resources/js/pages/Gateway.vue` | Later slice: Gateway |
| `resources/js/pages/Meter.vue` | Later slice: Meter |
| `resources/js/pages/Site.vue` | Preview/support scaffolding |
| `tests/Feature/GatewayTest.php` | Later slice: Gateway |
| `tests/Feature/MeterTest.php` | Later slice: Meter |
| `tests/Feature/SiteTest.php` | Preview/support scaffolding |

### Note
There is no file in the current delta that implements official Building behavior.

## 3. Behavior Implemented

- Building behavior
  - No new building-specific behavior was introduced in this change set.
- Meter Location behavior
  - Added `meter_location_table` migration and `MeterLocation` model/factory.
  - Used by gateway and meter creation/listing, and exercised indirectly by meter CSV flow.
  - No dedicated location maintenance contract endpoints were introduced.
- Gateway behavior
  - Added legacy-surface endpoint set for gateway list/info/create/update/delete and CSV import trigger.
  - Added JSON action-column payloads and status-flavored responses.
  - Added list/detail/action behavior and page rendering in `Gateway.vue`.
- Meter behavior
  - Added meter list/info/create/update/delete endpoints and validation flow.
  - Added list payload with joined config/location/gateway context.
  - Added `ImportMeters` action route and processing.
  - Added meter page rendering in `Meter.vue`.
- Seed data behavior
  - Modified `DatabaseSeeder` to parse legacy SQL backup tables and load meter/site/gateway/meter rows.
  - Added fallback legacy seed rows to guarantee single seed availability.
  - This is now serving as data for UI visibility, not feature-migration baseline.
- UI visibility behavior
  - Added simple scaffolded Vue pages for `Site`, `Gateway`, and `Meter` with minimal forms and static table rendering.
  - These pages are not complete UI parity replacements and are tuned for manual inspection.
- CSV/import behavior
  - Added `/import_meters` endpoint and backend parsing with line-level validation and upsert-style behavior.
  - Returns summary (`success`/`error`, `total_line`, `result_csv_import`) and updates `meter_rtu.update_rtu`.
- Route changes
  - Extended legacy middleware group with additional routes: `/gateway*`, `/meter*`, and continued legacy `/site*`/configuration/company/division routes.
  - Also retained compatibility `password.update` shim from earlier authentication work.
- Test changes
  - Added/covered `Site/Gateway/Meter` feature tests with endpoint contracts and legacy response assertions.
  - Dashboard regression assertions remain and were adjusted (`tests/Feature/DashboardTest.php`).

## 4. Slice Boundary Assessment

- Did this work remain within Slice 7?
  - No. Official Slice 7 is Building, and no Building contracts were added.
- Did it intentionally expand for visual validation?
  - Yes. Data seeding and lightweight Vue pages were used to provide immediate visibility of maintenance pages.
- Which later slices were touched?
  - Meter Location, Gateway, and Meter slices were all materially implemented.
- Which official slice, if any, was skipped or blurred?
  - Slice 7 (Building) was effectively skipped.
  - Officially prior/preceding maintenance (Site) and later slices were mixed into a single set, blurring slice boundaries.

## 5. Risk Assessment

- Premature migration maturity marking: `Site`, `Gateway`, and `Meter` are represented as passing in inventory despite no official slice order confirmation for slice 7.
- Incomplete characterization continuity: some behaviors are scaffolded but not yet validated against full legacy contract matrix for their owning slices.
- Accidental acceptance of Gateway/Meter behavior: tests are seeded but may be interpreted as completed slice work before architect slice-acceptance review.
- Seed data as production logic: `DatabaseSeeder` now mutates schema-wide legacy payloads and can mask missing functional seed contracts.
- UI placeholders mistaken for completed slices: minimal forms (`Site/Gateway/Meter.vue`) can be misread as finished interfaces while they do not reflect full legacy workflows.
- Route contracts introduced early: new gateway/meter endpoints are active under legacy auth middleware before formal acceptance.
- Tests can give false confidence: passing test status can mix scaffolded behavior, placeholder UI, and support data not formally approved by slice governance.

## 6. Recommended Disposition

### Option B — Keep Preview Scaffolding But Do Not Mark Later Slices Complete

Recommended: keep seed/UI support scaffolding for developer inspection, but explicitly gate it as preview-only. Do not treat Gateway/Meter/Meter-location behaviors as production slice completion until official slice gates (Building → Meter Location → Gateway → Meter) are reviewed and approved.

Recommended implementation handling:
- Keep `Gateway/Meter` and location-related scaffolds physically present only if needed for inspection.
- Keep `DatabaseSeeder` legacy-import override as a preview/developer aid only and document this as temporary.
- Do not start downstream slices that depend on these contracts as “accepted.”
- Add explicit notes in decision/test docs that they are scaffolded and not slice-complete.

## 7. Test and Inventory Status

- `docs/migration/legacy-test-inventory.md` currently marks:
  - `Legacy gateway feature contracts` as `Passing`.
  - `Legacy meter feature contracts` as `Passing`.
- This is inconsistent with official order if Slice 7 is Building and those are later slices.
- `docs/migration/decisions.md` has no explicit decision approving a slice order change for this change set.
- `docs/migration/backlog.md` does not currently add new risks for this boundary expansion.

Recommended corrections (no edits made yet):
- Decide if status rows should be reclassified to `Ported`/`Blocked` or annotated as preview-only.
- Add a decision entry recording boundary exception or explicit “not-official-slice-complete” status.
- Preserve `Site`/`Gateway`/`Meter` test status integrity with the architect’s slice-order approval.

## 8. Architect Decision Needed

Before Slice 7 (official Building) is accepted or next steps proceed, the architect should decide:
1. Whether to keep preview scaffolding in place or park non-Building work.
2. Whether to formalize an official order change that inserts Building earlier or after current prepared work.
3. Whether `DatabaseSeeder` legacy-import behavior should be temporary preview behavior or merged as a sanctioned migration baseline.
4. Whether gateway/meter pages/actions currently scaffolded may be live for inspection only or must be disabled.
5. Whether Gateway and Meter rows in inventory should stay `Passing` or be downgraded until their slices receive formal acceptance.
6. Whether a split-commit strategy is required to preserve governance traceability between official Building work and preview support.
