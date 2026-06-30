# Release Readiness Dashboard

| Slice | Scope | Backlog IDs | Status | Full Gates | Architect Approval | Notes |
|---|---|---|---|---|---|---|
| 1 | Authentication | MIG-008 | Approved | Yes | Approved | Username-based legacy login preserved with `loginID` session compatibility. |
| 2 | Dashboard | MIG-003 | Approved | Yes | Approved | Legacy `/site` entry retained alongside modern `/dashboard` behavior. |
| 3 | Company | MIG-004 | Approved | Yes | Approved | Company maintenance route surface and payloads are migrated. |
| 4 | Division | MIG-004 | Approved | Yes | Approved | Division maintenance route surface and payloads are migrated. |
| 5 | Configuration | MIG-006 | Approved | Yes | Approved | Configuration surface migrated; deeper logo/web setting persistence semantics pending verification in hardening. |
| 6 | Site | MIG-004 | Approved | Yes | Approved | Site maintenance migrated with legacy maintenance actions. |
| 7 | Building | MIG-004, MIG-009 | Approved | Yes | Approved | Building migration complete; slice order/reconciliation with later previews tracked separately. |
| 8 | Meter Location | MIG-004, MIG-009 | Approved | Yes | Approved | Meter Location migrated from scaffolded flow; preview boundaries called out in MIG-009. |
| 9 | Gateway | MIG-004, MIG-001, MIG-009 | Approved | Yes | Approved | Officially on route/mutation contract; preview-support reconciliation is required to close MIG-009. |
| 10 | Meter | MIG-004, MIG-001, MIG-009 | Approved | Yes | Approved | Officially migrated; preview/official reconciliation still governed by MIG-009. |
| 11 | User Management | MIG-003, MIG-007 | Approved | Yes | Approved | High-risk access and mutation routes are covered and gated. |
| 12 | Reports | MIG-002 | Passing | Yes | Approved | Scaffold accepted; report formulas, aggregations, and XLSX contents deferred to Slice 16. |
| 13 | Authorization Semantics | MIG-003, MIG-007 | In Review | Yes | Accepted | User-session + role + scoped-site gates are implemented and passing in `tests/Feature/UserTest.php`; `/user*` and `/user_site_access*` remain admin-only. `MIG-007` remains In Review until user-site-access side-effect validation is complete. |
| 14 | DataTables Behavior Parity | MIG-005 | In Review | Yes | Pending | List endpoints and tests were implemented for draw/search/pagination/count behavior. Slice 14 remains under release-review while route/action semantics remain monitored for deferred ambiguities. |
| 15 | Destructive CRUD Hardening | MIG-004 | Approved | Yes | Approved | Delete hardening, dependency-blocked guards, and transaction boundaries are implemented and green on full gates. Missing/nonexistent-ID cleanup remains in MIG-004 and does not block Slice 15 approval. |
| 16 | Reports Hardening | MIG-002 | Approved | Yes | Approved | Representative report behavior validated. Residual workbook parity improvements tracked under MIG-002. |
| 17 | RTU / Device Endpoints | MIG-001 | Pending | No | Pending | Not started; protocol and side-effect contracts remain critical. |
| 18 | Backlog Cleanup / Release Readiness | MIG-009 (and any residual items) | Pending | No | Pending | Not started; close governance gaps and confirm final readiness criteria. |
