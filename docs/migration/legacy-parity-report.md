# CAMR Legacy Parity Report

## Executive Summary

This report compares the legacy Laravel 8 CAMR application in:

```text
/Users/rli/PhpstormProjects/camr_robinsons
```

against the current Laravel 13 reimplementation in:

```text
/Users/rli/PhpstormProjects/dec-camr
```

The focus is UI/UX parity and RTU/device gateway posting behavior.

Overall status:

- Core migration parity is strong across authentication, maintenance routes, reports, authorization, dashboard/operator console, analytics foundation, and RTU protocol endpoints.
- The highest-risk RTU gateway posting path is implemented and test-covered through `POST /http_post_server.php`, with `public/http_post_server.php` treated as the authoritative legacy behavior.
- The current UI is no longer merely parity-oriented; it has evolved into an Operator Console with Dashboard, Reports UX, Live Operations MVP, Analytics Workbench foundation, simulator, replay, and lifecycle scenario support.
- Remaining gaps are mostly residual hardening, UX polish, and legacy edge-case parity. The main production-sensitive gap is continued validation of real field gateway behavior under production-like payload timing and malformed-payload conditions.

## Methodology

The comparison used these current Laravel 13 sources:

- `routes/web.php`
- `app/Http/Controllers/RtuProtocolController.php`
- `app/Actions/Rtu/IngestRtuTelemetryAction.php`
- `tests/Feature/RtuProtocolTest.php`
- `tests/Feature/RtuTelemetryReplayTest.php`
- `docs/migration/rtu-protocol-inventory.md`
- `docs/migration/backlog.md`
- `docs/migration/release-readiness.md`
- `docs/ui/TESTER_CHEAT_SHEET.md`
- `docs/ui/SCADA_SIMULATION_MANUAL.md`

The legacy Laravel 8 comparison used:

- `routes/web.php`
- `app/Http/Controllers/CAMRGatewayDeviceController.php`
- `public/http_post_server.php`
- `app/Http/Controllers/CAMRGatewayController.php`
- report and maintenance controller references where relevant.

Statuses used in this report:

| Status | Meaning |
|---|---|
| Parity | Current behavior appears equivalent for the known contract. |
| Substantial Parity | Current behavior is equivalent for primary use cases, with accepted residual differences. |
| Partial | Current behavior exists but lacks complete parity proof or UI exposure. |
| Gap | Legacy behavior or expected UX is missing or not yet sufficiently implemented. |
| Intentional Modernization | Current behavior intentionally improves presentation while preserving workflow. |

## RTU / Device Endpoint Parity

The public legacy protocol route surface is present in Laravel 13.

| Endpoint | Legacy Behavior | Current Behavior | Status | Notes |
|---|---|---|---|---|
| `GET /check_time.php` | Plain-text server timestamp. | Plain-text server timestamp. | Parity | Covered by `RtuProtocolTest`. |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/get_update_csv` | Returns `meter_rtu.update_rtu`, direct access may 500 for missing MAC. | Returns update flag; missing MAC aborts 500. | Parity | Current missing-MAC behavior preserves legacy fragility. |
| `GET .../get_content_csv` | If flag is `1`, returns up to 32 active meter rows as `meter_name,config_file,addressable_meter`. Empty when flag off. | Same shape and flag behavior. | Substantial Parity | Covered for representative rows. |
| `GET .../reset_update_csv` | Clears `update_rtu`; empty body. | Clears `update_rtu`; empty body; idempotent. | Substantial Parity | Idempotency is safer than legacy but compatible. |
| `GET .../get_update_location` | Returns `update_rtu_location`. | Returns `update_rtu_location`. | Parity | Covered. |
| `GET .../get_content_location` | If flag is `1`, returns `location = "SITE_CODE"\n`. | Same response shape. | Parity | Covered. |
| `GET .../reset_update_location` | Clears `update_rtu_location`; empty body. | Clears `update_rtu_location`; empty body. | Parity | Covered. |
| `GET .../rtu_remote_ssh` | Returns `update_rtu_ssh`. | Returns `update_rtu_ssh`. | Parity | Covered. |
| `GET .../force_lp` | Returns `update_rtu_force_lp`. | Returns `update_rtu_force_lp`. | Parity | Covered. |
| `GET .../reset_force_lp` | Clears `update_rtu_force_lp`; empty body. | Clears `update_rtu_force_lp`; empty body. | Parity | Covered. |
| `POST /http_post_server.php` | Direct PHP endpoint inserts telemetry when `save_to_meter_data == 1`, updates `meter_rtu`, `meter_details`, `meter_site`, returns `OK, timestamp`. | Laravel route preserves plain-text response, telemetry persistence, freshness side effects, and cache/event hooks. | Substantial Parity | Accepted with residual differences under `MIG-001`. |

## Gateway Posting Parity

Legacy behavior has two possible telemetry implementations:

- `public/http_post_server.php`
- `CAMRGatewayDeviceController::http_post_server()`

The current migration made a documented architectural decision to treat `public/http_post_server.php` as authoritative because deployed gateways likely post directly to `/http_post_server.php`.

Current parity strengths:

- Route shape is preserved: `POST /http_post_server.php`.
- CSRF is disabled for the protocol route.
- Response is plain text: `OK, YYYY-MM-DD HH:MM:SS`.
- Payload is form-compatible and does not require JSON.
- `save_to_meter_data = 1` persists `meter_data`.
- `save_to_meter_data != 1` acknowledges without persistence, matching the direct public script path.
- `meter_rtu.last_log_update` and `soft_rev` are updated.
- `meter_details.last_log_update` is updated.
- `meter_site.last_log_update` is updated.
- `mac_address` and `gateway_mac` compatibility is supported.
- Duplicate/corrected gateway posts are idempotently handled without changing gateway payload.
- `TelemetryIngested` event and cache invalidation support live UI refresh readiness without changing protocol output.

Current residual differences:

- Legacy direct script uses `INSERT IGNORE`; Laravel 13 uses an explicit identity check/update path. This is compatible for retries and corrected posts but not byte-for-byte identical.
- Legacy malformed payload behavior depends on PHP notices/warnings and direct `$_POST` access. Laravel 13 normalizes invalid numerics/timestamps more deliberately while preserving `OK` response behavior.
- Legacy missing gateway/meter side effects fail silently because update statements affect zero rows. Laravel 13 preserves acknowledgement but adds fallback matching by MAC or meter name for compatibility with replay/report-style identifiers.
- Real field-device traffic still needs production-like soak testing because fixture replay cannot prove every deployed gateway firmware permutation.

Recommendation:

- Keep `MIG-001` as `Accepted with Residual Differences`.
- Add a production-readiness checklist item for field-device packet capture replay if real gateway payload samples become available.
- Do not change gateway payload requirements.

## UI / UX Parity

The current UI has moved beyond legacy visual parity into an Operator Console. That is intentional modernization, but workflow parity remains the standard.

### Implemented and Visible

| Area | Current Endpoint | Status | Notes |
|---|---|---|---|
| Login | `/` and `POST /login-user` | Parity | Username login and `loginID` session behavior preserved. |
| Operator home / Site | `/site` | Intentional Modernization | `/site` remains primary CAMR operator home. |
| Compatibility dashboard | `/dashboard` | Intentional Modernization | Kept as compatibility route; not primary operator destination. |
| Company | `/company` | Substantial Parity | Maintenance workflow preserved with modern operator layout. |
| Division | `/division` | Substantial Parity | Maintenance workflow preserved. |
| Configuration | `/configuration_file` | Partial | Route surface exists; deeper web settings/logo behavior remains in `MIG-006`. |
| Site | `/site` | Substantial Parity | Site management plus operator home behavior. |
| Building | `/building` | Substantial Parity | Maintenance workflow migrated. |
| Meter Location | related endpoints | Substantial Parity | Backend and tests migrated; visible through maintenance workflows as applicable. |
| Gateway | `/gateway` | Substantial Parity | Maintenance workflow plus operational state visibility. |
| Meter | `/meter` | Substantial Parity | Maintenance workflow and import support migrated. |
| User Management | `/user`, `/user_site_access` | Substantial Parity | Admin-only enforcement implemented. `MIG-007` remains for side-effect validation. |
| Reports | report endpoints | Substantial Parity | Reports UX modernized; representative formulas/XLSX parity accepted with residual differences. |
| Analytics | `/analytics` | Intentional Modernization | New workbench foundation, not a legacy replacement. |

### UI / UX Gaps and Residuals

| Gap | Severity | Current Evidence | Recommendation |
|---|---|---|---|
| Configuration logo/web settings behavior | High | `MIG-006` remains open. | Compare legacy upload/display/fallback behavior and implement a focused hardening slice. |
| DataTables parity residual | High | `MIG-005` remains In Review. | Confirm sort/search/page behavior for all visible list surfaces after UI modernization. |
| User site-access side effects | High | `MIG-007` remains In Review. | Validate add/remove access side effects and scoped UI visibility against legacy. |
| Live Operations command workflow | Medium | Current command bar can open protocol endpoints. | Later wrap command execution in an in-app workflow with result logging, timeline event, and refresh. |
| Gateway detail investigation | Medium | Maintenance pages and dashboard/live panels exist. | Add deeper selected gateway/meter detail panels if operators need SCADA-style investigation. |
| Analytics scope visibility | Medium | Scope filtering and visibility have been added recently. | Continue to make scope obvious in every analytical view. |
| Report exhaustive workbook parity | Medium | `MIG-002` accepted with residual differences. | Keep long-term regression hardening for full workbook/cell/rounding parity. |
| Legacy copied browser specs | Medium | Browser characterization remains copied/reference in inventory. | Continue replacing brittle browser specs with operator journey tests. |

## Simulation and Live Data Readiness

Current support is stronger than legacy parity:

- `camr:seed-profile` creates deterministic entities.
- `camr:simulate` creates operational telemetry and health-state transitions.
- `camr:replay-telemetry` replays gateway-shaped CSV through live RTU ingest.
- `camr:scenario live-scada-demo` prepares a SCADA-style review environment.

This is not legacy behavior; it is validation infrastructure. It should be retained because it proves dashboard, analytics, reports, and live operations against moving data rather than static seed rows.

Recommended review commands:

```bash
php artisan migrate:fresh --force
php artisan camr:scenario live-scada-demo
```

Then review:

```text
/site
/analytics
/gateway
/meter
```

## Highest Priority Missing Work

1. Field-device packet replay hardening
   - Capture real gateway payloads if available.
   - Replay them through `camr:replay-telemetry` or a protected fixture path.
   - Confirm no payload shape change is required.

2. Configuration/web settings parity
   - Close `MIG-006`.
   - Focus on logo upload, persistence, fallback, and display.

3. DataTables/list parity closure
   - Close or explicitly accept residuals in `MIG-005`.
   - Confirm search/sort/page behavior after modern UI refactors.

4. User site-access side-effect closure
   - Close or explicitly accept residuals in `MIG-007`.
   - Confirm access mutation does not widen scoped visibility unexpectedly.

5. Live Operations SCADA workflow refinement
   - Keep maintenance pages separate from operations investigation.
   - Add in-app command result tracking later if operational usage requires it.

## Conclusion

The current Laravel 13 codebase substantially preserves the legacy Laravel 8 CAMR behavior while improving UI/UX architecture. RTU gateway posting endpoints are implemented, test-covered, and suitable for continued field validation. The most important remaining work is not broad feature migration; it is targeted residual hardening around real gateway payloads, configuration settings, list parity, and scoped-access side effects.

The project should continue treating protocol compatibility as production-critical and UI modernization as workflow-preserving operator-productivity work.
