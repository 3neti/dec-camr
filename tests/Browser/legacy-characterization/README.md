# Browser Characterization Tests

These tests capture high-risk legacy browser behavior for the future rewrite.

They are opt-in and must use a dedicated characterization database. Do not run them against production credentials.

## Requirements

- Node.js
- Playwright available locally, for example through `node_modules/.bin/playwright`
- Chromium installed for Playwright with `npx playwright install chromium`
- `.env.characterization.local` configured for a disposable database whose name includes `test`, `testing`, or `characterization`

## Run

```bash
BROWSER_CHARACTERIZATION_TESTS=1 scripts/run-browser-characterization-tests.sh
```

The runner:

- loads `.env.characterization.local`
- rebuilds and seeds the characterization database through `LegacyBrowserSeedTest`
- starts `php artisan serve` on `127.0.0.1:8000` by default
- runs `tests/Browser/characterization.spec.js`

Use `BROWSER_APP_PORT` or `BROWSER_BASE_URL` to change the local server target.

## Coverage Inventory

The browser suite currently characterizes these legacy contracts:

- Public login page rendering.
- Failed login messaging.
- Admin login and site-list DataTables JSON.
- Anonymous redirects from representative protected direct URLs.
- Building/Site create validation rendering without inserting data.
- Admin seeded site detail dashboard load.
- Site detail Location, Gateway, and Meter tab activation plus list contracts.
- Site detail Gateway and Meter create validation rendering without inserting data.
- Site detail Location, Gateway, and Meter update modal display for seeded fixtures.
- Site as-built Meter/Gateway List export trigger, XLSX response headers, and seeded workbook cell content.
- Location create validation, seeded update modal display, and successful create/duplicate/update/delete using a disposable site-scoped row.
- Gateway CSV meter import modal for missing file, bad CSV, and valid CSV.
- Offline Gateway and Offline Meter list contracts plus XLSX download response headers and seeded workbook cell content.
- Raw Data report generation and Excel popup/download trigger.
- Building/Site Report generation and Excel popup/download trigger.
- SAP report generation and Excel popup/download trigger.
- Hourly KWh Consumption report generation and Excel popup/download trigger.
- Hourly KW Demand report generation and Excel popup/download trigger.
- Missing-parameter validation messages for Raw Data, Building/Site, SAP, Consumption, and Demand report generation endpoints.
- UI-rendered missing-parameter validation messages for stable Raw Data, Building/Site, SAP, Consumption, and Demand report fields.
- Scoped-user site-list filtering.
- Scoped-user report menus and assigned-building report selectors.
- Current scoped-user direct URL access gaps for unassigned site details, admin maintenance URLs, and Configuration File read/validation routes.
- User maintenance list contract, create validation, and selected building-access display.
- Division maintenance list contract, create validation, update modal display, and successful create/duplicate/update/delete using a disposable row.
- Configuration File maintenance list contract, create validation, update modal display, and successful create/duplicate/update/delete using a disposable row.
- Company maintenance list contract, create validation, and update modal display.
- Company maintenance successful create, duplicate rejection, update, and delete using a disposable row.
- Account profile modal validation plus name/email update and restore without changing password.
- Password reset page rendering, missing-email validation response, and unknown-email modal behavior.
- Known-email password reset with array mail, password mutation check, and credential restore.

## Remaining High-Value Gaps

- Additional successful create/update/delete flows using disposable test-only rows for site-scoped screens where referential integrity risk is acceptable.
- UI-level report validation for start/end time fields remains covered only at endpoint level because legacy scripts do not consistently render those fields.
