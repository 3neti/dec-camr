# PR-008 — Production Go/No-Go

Status: Pending
Owner: Architect / Business Owner
Blocking: Yes

## Purpose

Record the final production release decision after all blocking readiness waves are accepted, accepted with waiver, or blocked with explicit release impact.

## Go/No-Go Rule

Production release is not authorized until PR-001 through PR-007 are accepted or explicitly waived by the responsible owner. Migration completion, passing tests, and browser smoke coverage are evidence inputs; they are not release authorization by themselves.

## Required Inputs

| Input | Status | Notes |
|---|---|---|
| PR-001 Environment & Deployment Readiness | Pending | Must be accepted or waived. |
| PR-002 Database, Backup & Restore Readiness | Pending | Must be accepted or waived. |
| PR-003 Security & Access Readiness | Pending | Must be accepted or waived. |
| PR-004 RTU / Gateway Ingestion Readiness | Pending | Must be accepted or waived. |
| PR-005 Observability & Incident Response | Pending | Must be accepted or waived. |
| PR-006 Performance & Load Readiness | Pending | Must be accepted or waived. |
| PR-007 Browser / Operator Acceptance Readiness | Pending | Must be accepted or waived. |
| Residual migration risks reviewed | Pending | Include `MIG-001`, `MIG-002`, `MIG-004`, `MIG-005`, `MIG-006`, `MIG-007`, and `MIG-009`. |
| Release owner assigned | Pending | Must own release execution. |
| Rollback owner assigned | Pending | Must own rollback execution. |
| Post-release monitoring owner assigned | Pending | Must monitor immediately after release. |

## Final Decision Template

Use this section when the release decision is ready.

```text
Decision Date:
Decision: Go | No-Go | Go with Waivers
Release Window:
Release Owner:
Rollback Owner:
Post-Release Monitoring Owner:

Accepted Waves:
Waived Waves:
Blocking Risks:
Residual Non-Blocking Risks:
Rollback Trigger:
Monitoring Window:
Business Owner Approval:
Architect Approval:
```

## Waiver Requirements

Every production waiver must include:

- waived wave or risk,
- reason for waiver,
- production impact,
- mitigation,
- owner,
- review date,
- rollback or containment plan.

## Go Criteria

- PR-001 through PR-007 are accepted or explicitly waived.
- No unowned blocking risks remain.
- RTU/gateway ingestion readiness is accepted or explicitly waived.
- Production credentials and demo affordances are safe.
- Backup/restore posture is accepted.
- Observability and incident ownership are accepted.
- Operator acceptance review is accepted.

## No-Go Criteria

Any of the following should block production release unless explicitly waived:

- no tested restore path,
- unsafe production credential/demo behavior,
- unverified live gateway ingest path,
- no incident owner or log access,
- unacceptable performance under production-like load,
- operator workflows rejected in acceptance review,
- unowned critical residual risk.

## Post-Release Verification

After release, verify:

| Check | Expected Result |
|---|---|
| Login | Admin and expected production users authenticate. |
| `/site` | Operator home loads and scoped data is correct. |
| `/dashboard` | Gateway/meter health and telemetry recency are visible. |
| `/analytics` | Analytics context renders with production data where available. |
| Reports | Representative report page and export path work. |
| RTU endpoint | Gateway-shaped telemetry post succeeds. |
| Logs | Application and ingest logs are visible. |
| Backup | First post-release backup is confirmed. |

## Release Decision Impact

This is the final production authorization gate. Until this document is completed and approved, production release remains pending.
