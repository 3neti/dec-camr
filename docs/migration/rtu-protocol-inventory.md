# RTU / Device Protocol Inventory

Scope: Slice 17 Protocol Discovery only.

## References

- Laravel 8 reference controller: `app/Http/Controllers/CAMRGatewayDeviceController.php`
- Laravel 8 RTU routes: `routes/web.php`
- Legacy redirect middleware: `app/Http/Middleware/RedirectToNewURL.php`
- Legacy direct endpoint: `public/http_post_server.php`
- Legacy rewrite behavior: `public/.htaccess`
- RTU model flags and fields: `app/Models/GatewayModel.php`

This document records what must be preserved before implementing Slice 17.

## Endpoint Matrix

| Endpoint | Purpose | Request | Response | Side Effects | Characterized | Risk |
|---|---|---|---|---|---|---|
| `GET /check_time.php` (and possible redirected variant from middleware) | Return server timestamp for gateway time checks | No required request fields. `RedirectToNewURL` may append `/{key}/{value}` segments for query params. | Plain text body: `YYYY-MM-DD HH:MM:SS` | Read-only | Partial | Medium |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/get_update_csv` | Read CSV update flag for gateway | Path parameter: `mac` (gateway MAC). No body. | Plain text integer/flag (`0|1` expected) echoed from `GatewayModel.update_rtu` | Read-only | Partial | High |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/get_content_csv` | Return active meter config CSV for gateway if update flag enabled | Path parameter: `mac`. No required request fields. | Plain text payload, CSV-style rows (`meter_name,config_file,addressable_meter\\n`), or empty on flag 0 | Read-only | Partial | High |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_csv` | Clear CSV update flag after successful download | Path parameter: `mac`. No body. | Empty body (no explicit output) | Write: `meter_rtu.update_rtu = 0` for gateway | Partial | High |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/get_update_location` | Read site-code update flag for gateway | Path parameter: `mac`. No body. | Plain text flag (`0|1`) echoed from `GatewayModel.update_rtu_location` | Read-only | Partial | High |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/get_content_location` | Return gateway site code payload if update flag enabled | Path parameter: `mac`. No body. | Plain text `location = "SITE_CODE"\n` when flag=1, otherwise empty | Read-only | Partial | High |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_location` | Clear site-code update flag | Path parameter: `mac`. No body. | Empty body (no explicit output) | Write: `meter_rtu.update_rtu_location = 0` | Partial | High |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/rtu_remote_ssh` | Read SSH update flag | Path parameter: `mac`. No body. | Plain text flag (`0|1`) from `GatewayModel.update_rtu_ssh` | Read-only | Partial | Medium |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/force_lp` | Read force-load-profile flag | Path parameter: `mac`. No body. | Plain text flag (`0|1`) from `GatewayModel.update_rtu_force_lp` | Read-only | Partial | Medium |
| `GET /rtu/index.php/rtu/rtu_check_update/{mac}/reset_force_lp` | Clear force-load-profile flag | Path parameter: `mac`. No body. | Empty body (no explicit output) | Write: `meter_rtu.update_rtu_force_lp = 0` | Partial | High |
| `POST /http_post_server.php` (legacy direct file, not Laravel route) | Ingest meter telemetry packet | Form body (`application/x-www-form-urlencoded` or equivalent): `save_to_meter_data`, `meter_id`, `location`, `datetime`, `gateway_mac`, `soft_rev`, voltage/current/frequency/power/energy and demand fields, optional angles/relay status | Plain text `OK, YYYY-MM-DD HH:MM:SS` | Write: inserts into `meter_data`; if `save_to_meter_data == 1`, updates `meter_rtu.last_log_update`, `meter_rtu.soft_rev`, `meter_details.last_log_update`, `meter_site.last_log_update` | High Gap | Critical |
| `POST` equivalent in controller (`CAMRGatewayDeviceController@http_post_server`) | Ingest meter telemetry packet (currently no route discovered in legacy `web.php`) | Form body: same shape as above plus optional `gateway_mac`; no formal schema validation | Plain text `OK, YYYY-MM-DD HH:MM:SS` on both save branches | Write: inserts into `meter_data`; currently does **not** update `meter_rtu.last_log_update`, `meter_details.last_log_update`, or `meter_site.last_log_update` in observed code path | Partial | Critical |

## Protocol Compatibility Characteristics

### Response protocol style
- All implemented RTU endpoints in Laravel 8 use `echo` with implicit output.
- No explicit `Content-Type` headers are set in controller actions.
- No explicit status responses are returned.
- Practical protocol type by behavior: `text/plain`-style raw string payloads.

### CSV/HTTP payload forms
- `csv_download` emits newline-delimited, plain-text CSV-like content.
- `site_code_download` emits single-line quoted assignment-style text.
- `http_post_server` endpoints emit short plain-text confirmation.

### Auth/NAC
- No `isLoggedIn` or Fortify auth middleware on RTU routes.
- No session/CSRF assumptions visible in controller actions.
- Legacy behavior is effectively public/protocol endpoint access (subject to any external network-level controls outside app code).

## Detailed Endpoint Behavior Notes

### `check_time`
- Legacy code: `date('Y-m-d H:i:s')` and `echo`.
- Middleware may redirect `/check_time.php` to `/check_time/{key}/{value}` variant by appending query pairs into path segments.
- No guard rails for missing/invalid query values.

### `csv_update_status`
- Reads `request()->segment(5)` to extract `{mac}`; brittle on unexpected path depth.
- Queries `GatewayModel::select('update_rtu')->where('gateway_mac', $mac)->get(); echo $data[0]['update_rtu'];`
- 500 risk when gateway record absent due direct index access.

### `csv_download`
- Reads gateway with `update_rtu`, `rtu_id` by `gateway_mac`.
- When `update_rtu == 1`, fetches active meters joined on config files where `meter_details.rtu_idx = gateway rtu_id` and `meter_status = 'Active'`.
- Applies `skip(0)->take(32)` hard limit; no explicit `ORDER BY` visible.
- For each row, emits `meter_name,config_file,addressable_meter` as CSV line.
- `meter_name` may be replaced by `'1'` when `meter_default_name == 1`; otherwise uses `meter_name` with default fallback logic.

### `csv_update_status_reset`
- Looks up gateway by `gateway_mac` and sets `update_rtu = 0`; response body remains empty.

### `site_code_update_status`
- Returns `update_rtu_location` for `{mac}`.

### `site_code_download`
- Returns `location = "$site_code"\n` only when `update_rtu_location == 1`.
- Otherwise returns empty output.

### `site_code_update_status_reset`
- Sets `update_rtu_location = 0`; empty output.

### SSH / Force-load routes
- Read-only flag endpoints (`gateway_ssh`, `force_load_profile_status`) return stored flags.
- Only force-load route has matching reset endpoint.

### `http_post_server` / `http_post_server_A`
- `http_post_server_A` exists and returns `OK, timestamp`.
- `http_post_server` parses large numeric payload, normalizes via arithmetic coercion, and stores into `MeterDataModel` regardless of `save_to_meter_data` branch (insert always occurs, with optional fields included differently).
- `makeHidden(['gateway_mac'])` used only in one branch.
- No authentication, no validation layer, no explicit error handling for malformed payload.
- Inconsistent behavior versus legacy `public/http_post_server.php`:
  - direct script updates last-log fields on related entities when `save_to_meter_data == 1`.
  - controller path does not show those side effects.

## Device Lifecycle / Workflow (Observed intent)

1. Gateway checks server time via `check_time.php`.
2. Gateway polls status endpoints:
   - `.../get_update_csv`
   - `.../get_update_location`
   - `.../rtu_remote_ssh`
   - `.../force_lp`
3. For each returned flag=1, gateway fetches corresponding content endpoint:
   - CSV content via `.../get_content_csv`
   - location content via `.../get_content_location`
4. Gateway may call reset endpoints to clear flags after successful fetch:
   - `.../reset_update_csv`
   - `.../reset_update_location`
   - `.../reset_force_lp`
5. Gateway periodically posts meter samples to `http_post_server.php` (legacy direct endpoint) and expects `OK, timestamp` response.

## Characterization Review (Existing vs Missing)

### Existing legacy characterization
- None observed that directly cover RTU/device endpoints, redirects, or payload/side-effect contracts.

### Missing characterization required before Slice 17 implementation
- No behavior tests for:
  - any update-status endpoints
  - CSV/site payload exact content and error behavior
  - reset endpoint semantics and idempotency
  - HTTP POST telemetry payload validation and persistence contracts
- No tests for error-paths:
  - missing/invalid `{mac}`
  - gateway not found
  - partially missing form fields in telemetry uploads
- No tests for sequence contracts (poll/download/reset, upload frequency).
- No tests for content-type, status codes, connection failure behavior, or concurrency.

## Protocol Compatibility Risks

| Risk | Severity |
|---|---|
| `public/http_post_server.php` protocol is not represented as a Laravel route and appears to be a separate direct PHP endpoint, creating ambiguity in destination behavior for devices | Critical |
| Reset endpoints and flag endpoints have no explicit error responses and may return HTTP 500 for missing gateway MAC | Critical |
| `request()->segment(5)` indexing and fixed segment expectation creates path-shape fragility | High |
| `csv_download`/`site_code_download` response shape/empty output behavior not explicitly specified under all states | High |
| `take(32)` row limit and no documented ordering could change payload ordering/determinism | Medium |
| No auth/rate-limiting and public exposure may be acceptable by legacy protocol but requires explicit production hardening decision | High |
| Direct telemetry endpoint (`public/http_post_server.php`) and controller `http_post_server` diverge on downstream timestamp/soft-rev updates | Critical |

## Implementation Recommendations (No implementation executed in this task)

- Preserve public endpoint exposure shape exactly as `/check_time.php` and `/rtu/index.php/rtu/rtu_check_update/{mac}/...`.
- Preserve raw protocol response style and minimal output framing for compatibility.
- Keep response bodies as plain text/CSV by default; avoid JSON wrappers unless protocol contract requires it.
- Introduce explicit route-level/transport-level safeguards only where they do not alter legacy payload contract.
- Use an endpoint boundary action per workflow:
  - status checks
  - content download
  - reset operations
  - telemetry ingest
- Use a dedicated service for gateway flag lookups and updates to avoid duplication while keeping side effects explicit.
- Add integration tests in Slice 17 implementation phase for every endpoint above, including missing-resource and malformed-input behavior.
- Decide explicitly whether to normalize all telemetry ingestion to the legacy direct script path or route it through Laravel while preserving `http_post_server` side effects.
