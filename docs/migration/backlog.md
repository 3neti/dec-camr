# Migration Backlog

| ID | Slice | Severity | Description | Source | Decision Required | Status |
|---|---|---|---|---|---|---|
| MIG-001 | RTU / Device Endpoints | Critical | RTU protocol-safe endpoints and telemetry ingestion are now contract-tested and implemented for `check_time`, flag status, content download/reset, `rtu_remote_ssh`, `force_lp`, and `POST /http_post_server.php`. `public/http_post_server.php` is treated as authoritative for device ingress. | Go/No-Go assessment | Yes | Accepted with Residual Differences |
| MIG-002 | Reports | Critical | Main XLSX report exports and report calculations have representative behavioral coverage in Slice 16 (route compatibility, validation, payload structure, representative values, boundary behavior, workbook generation, worksheet presence, and representative headers/fields). Exhaustive parity remains residual. | Go/No-Go assessment | Yes | Accepted with Residual Differences |
| MIG-003 | Cross-cutting | High | Scoped-user authorization now has explicit middleware enforcement and scoped site-list filtering (`user_access_group` for non-ALL users); confirm denial and scoped behavior across remaining migrated surfaces before closure. | Go/No-Go assessment | Yes | Resolved |
| MIG-004 | Site, Gateway, Meter, User, Building | Slice 15 destructive semantics are accepted for high-risk cases (logged-in admin deletion, last-admin deletion, dependency-blocked deletes). Missing/nonexistent ID response consistency is still backlogged as residual cleanup and does not block Slice 15 approval. | Go/No-Go assessment | Yes | In Review |
| MIG-005 | Cross-cutting | High | DataTables-equivalent behavior is partially implicit; search, sort, pagination, and count semantics must match legacy expectations for UI parity. | Go/No-Go assessment | Yes | In Review |
| MIG-006 | Configuration | High | Web settings/logo maintenance has presentation and persistence implications; expected file upload, fallback, and display behavior requires confirmation before migration. | Go/No-Go assessment | Yes | Open |
| MIG-007 | User Management | High | User site-access mutation is now admin-restricted in route enforcement; confirm legacy expectation for non-admin access and whether UI-level non-admin visibility was intended to remain read-only. | Go/No-Go assessment | Yes | In Review |
| MIG-008 | Authentication | High | Password reset side effect and transport is partially preserved with local password mutation but legacy mail dispatch semantics are not yet fully documented for Slice 1 implementation. | Slice 1 | Yes | Open |
| MIG-009 | Cross-cutting | Medium | Official slice order and completion markers must be reconciled because Gateway/Meter/Meter Location scaffolding is present as preview support but was not formally migrated. | Slice 7 variation review | Yes | Open |

Suggested statuses: Open, In Review, Resolved, Deferred, Accepted Legacy Behavior, Rejected.
