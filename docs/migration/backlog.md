# Migration Backlog

| ID | Slice | Severity | Description | Source | Decision Required | Status |
|---|---|---|---|---|---|---|
| MIG-001 | RTU / Device Endpoints | Critical | RTU/device protocol endpoints include uncharacterized request/response contracts and side effects; MAC/address handling and reset actions need explicit preservation rules before implementation. | Go/No-Go assessment | Yes | Open |
| MIG-002 | Reports | Critical | Main XLSX report exports are high-impact and behavior-critical; exact workbook structure, sheet naming, and file format constraints must be confirmed before migration. | Go/No-Go assessment | Yes | Open |
| MIG-003 | Cross-cutting | High | Scoped-user authorization behavior appears inconsistent in legacy assumptions; needed policies/guards and scope checks must be confirmed before implementing Laravel 13 equivalents. | Go/No-Go assessment | Yes | Open |
| MIG-004 | Site, Gateway, Meter, User, Building | High | Destructive workflows (delete/update/reassign) must preserve confirmation and fallback behavior; data integrity and cascade effects need explicit legacy-defined expectations. | Go/No-Go assessment | Yes | Open |
| MIG-005 | Cross-cutting | High | DataTables-equivalent behavior is partially implicit; search, sort, pagination, and count semantics must match legacy expectations for UI parity. | Go/No-Go assessment | Yes | Open |
| MIG-006 | Configuration | High | Web settings/logo maintenance has presentation and persistence implications; expected file upload, fallback, and display behavior requires confirmation before migration. | Go/No-Go assessment | Yes | Open |
| MIG-007 | User Management | High | User site-access mutation behavior (including authorization and mutation side effects) is migration-sensitive and requires explicit legacy alignment before implementation. | Go/No-Go assessment | Yes | Open |
| MIG-008 | Authentication | High | Password reset side effect and transport is partially preserved with local password mutation but legacy mail dispatch semantics are not yet fully documented for Slice 1 implementation. | Slice 1 | Yes | Open |
| MIG-009 | Cross-cutting | Medium | Official slice order and completion markers must be reconciled because Gateway/Meter/Meter Location scaffolding is present as preview support but was not formally migrated. | Slice 7 variation review | Yes | Open |

Suggested statuses: Open, In Review, Resolved, Deferred, Accepted Legacy Behavior, Rejected.
