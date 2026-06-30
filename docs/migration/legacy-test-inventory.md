# Legacy Characterization Test Inventory

This inventory tracks transferred Laravel 8 characterization artifacts and their maturity in the Laravel 13 migration.

## Status Definitions

| Status | Meaning |
|---|---|
| `Copied` | Artifact exists in this repository but is not yet adapted or executable here. |
| `Ported` | Assertions have been translated to Laravel 13/Pest/Vue/Inertia conventions while preserving behavior. |
| `Enabled` | Test is included in the active slice command or CI path. |
| `Passing` | Enabled test passes against the Laravel 13 implementation. |
| `Blocked` | Progress is waiting on an unresolved architectural, fixture, dependency, or behavior question. |
| `Retired` | Test is no longer required; architect approval and a decision-log entry are required. |

Lifecycle:

```text
Copied -> Ported -> Enabled -> Passing
```

## Inventory

| Artifact | Source | Target | Owning slice | Status | Notes |
|---|---|---|---|---|---|
| Browser characterization README | `tests/Browser/README.md` | `tests/Browser/legacy-characterization/README.md` | Cross-cutting | Copied | Documents browser contract and gaps from Laravel 8. |
| Browser characterization spec | `tests/Browser/characterization.spec.js` | `tests/Browser/legacy-characterization/characterization.spec.js` | Cross-cutting | Copied | Must be reviewed and ported slice-by-slice for Vue/Inertia UI behavior. |
| XLSX browser helper | `tests/Browser/read-xlsx-text.php` | `tests/Browser/legacy-characterization/read-xlsx-text.php` | Reports | Copied | Helper may need adaptation after Laravel 13 export implementation exists. |
| Feature characterization README | `tests/Feature/Characterization/README.md` | `tests/Feature/LegacyCharacterizationReference/README.md` | Cross-cutting | Copied | Reference for porting feature behavior into Pest. |
| Legacy behavior feature test | `tests/Feature/Characterization/LegacyBehaviorTest.php` | `tests/Feature/LegacyCharacterizationReference/LegacyBehaviorTest.php` | Cross-cutting | Copied | Reference only until ported to Laravel 13 factories/seeders and Pest conventions. |
| Legacy browser seed test | `tests/Feature/Characterization/LegacyBrowserSeedTest.php` | `tests/Feature/LegacyCharacterizationReference/LegacyBrowserSeedTest.php` | Cross-cutting | Copied | Reference for fixture scenarios, not for wholesale schema bootstrap reuse. |
| Legacy characterization test case | `tests/Feature/Characterization/LegacyCharacterizationTestCase.php` | `tests/Feature/LegacyCharacterizationReference/LegacyCharacterizationTestCase.php` | Cross-cutting | Copied | Reference only; replace with Laravel 13 test builders/factories. |
| Legacy authentication contract tests | `tests/Feature/Characterization/LegacyBehaviorTest.php` | `tests/Feature/LegacyCharacterizationReference/LegacyAuthenticationTest.php` | Authentication | Passing | Covers Slice 1 auth behavior; 8 auth coverage tests currently passing in the auth slice command. Architect acceptance is still required before Slice 1 is treated as complete. Full suite now executes without route failures; remaining skips are deferred legacy namespace/test-bootstrap artifacts. |
| Legacy dashboard contract tests | `tests/Feature/Characterization/LegacyBehaviorTest.php` | `tests/Feature/DashboardTest.php` | Dashboard | Passing | `/site` legacy protected-route redirect message and authenticated dashboard page rendering are now covered for Slice 2. |
| Company feature contract tests | `tests/Feature/CompanyTest.php` | `tests/Feature/CompanyTest.php` | Company | Passing | Core company endpoints are now covered for Slice 3 at route, list, and mutation levels. |
| Legacy division feature contract tests | `tests/Browser/legacy-characterization/characterization.spec.js` (Division block) | `tests/Feature/DivisionTest.php` | Division | Passing | Division maintenance contract coverage now targets route, list, create, update, delete behavior at Slice 4 level. |
| Configuration file feature contract tests | `tests/Browser/legacy-characterization/characterization.spec.js` (Configuration File block) | `tests/Feature/ConfigurationFileTest.php` | Configuration | Passing | Configuration file list/create/update/delete contracts are now implemented in Laravel 13 test form for Slice 5. |

## Current Rule

Do not mark a copied artifact as complete. A migrated behavior is complete only when its owning test is `Passing` or explicitly `Retired` with architect approval recorded in `docs/migration/decisions.md`.
