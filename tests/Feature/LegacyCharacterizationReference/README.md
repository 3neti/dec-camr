# Legacy Characterization Tests

These tests freeze Laravel 8 behavior as the contract for a future Laravel 13 rewrite.

## Safety

The suite runs `migrate:fresh`, so use only a disposable MySQL/MariaDB database.

The tests are guarded in two places:

- `CHARACTERIZATION_DB_TESTS=1` must be set.
- `DB_DATABASE` must include `test`, `testing`, or `characterization`.

## Local Database Setup

Example MySQL/MariaDB setup is available at:

```text
database/characterization/setup.sql.example
```

It contains:

```sql
CREATE DATABASE camr_robinsons_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'camr_robinsons_testing'@'localhost' IDENTIFIED BY '<local-test-password>';
GRANT ALL PRIVILEGES ON camr_robinsons_testing.* TO 'camr_robinsons_testing'@'localhost';
FLUSH PRIVILEGES;
```

Do not reuse production or development database credentials.

## Running

Create a local ignored env file:

```bash
cp .env.characterization.example .env.characterization.local
```

Edit `.env.characterization.local` with local-only test DB credentials. The file is ignored by Git.

Then run:

```bash
scripts/run-characterization-tests.sh
```

The runner first checks database connectivity. If it fails before PHPUnit starts, create/grant the database from `database/characterization/setup.sql.example` and update `.env.characterization.local`.

Alternatively, export values manually:

```bash
export CHARACTERIZATION_DB_TESTS=1
export DB_CONNECTION=mysql
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE=camr_robinsons_testing
export DB_USERNAME=camr_robinsons_testing
export DB_PASSWORD='<local-test-password>'

scripts/run-characterization-tests.sh
```

## Current Slice

The feature suite contains 11 tests for:

- login page rendering with seeded web settings
- successful login legacy session behavior
- failed login messages
- anonymous protected-route redirects
- admin site-list DataTables shape
- scoped user site-list filtering
- site detail dashboard availability
- gateway and meter create validation/success contracts
- CSV meter import contracts for missing, malformed, and valid files
- raw report JSON and Excel export dependency contract
- browser characterization seed verification

Feature tests should stay focused on request/response contracts, validation payloads, JSON shapes, and database-backed behavior that is awkward or slow to verify through a browser.

Browser-only behavior such as modal display, tab activation, report parameter forms, chart/table rendering, and popup/download triggers belongs in `tests/Browser/characterization.spec.js`.

## Remaining High-Value Feature Gaps

- Request-level contracts for Site Report, Consumption Report, and Demand Report.
- Request-level contracts for SAP, offline gateway, offline meter, and site as-built exports.
- Validation payloads for report endpoints when required parameters are missing.
- Request-level contracts for user, company, division, location, gateway, and meter update endpoints.
- Authorization contracts documenting current direct-access behavior at middleware/controller level.
- Database-backed session smoke behavior should remain in deployment smoke checks, not normal feature tests against production-like credentials.
