# Legacy Laravel 8 Hand-Off

## Mission

Reimplement the Laravel 8 CAMR application as a fresh Laravel 13 application while preserving observable business behavior.

This repository is not an upgraded Laravel 8 codebase. It should use Laravel 13, Inertia.js, Vue 3, Laravel Boost, Pest, and the conventions already present in this application.

Reference implementation:

```text
/Users/rli/PhpstormProjects/camr_robinsons
```

## Sources Of Truth

Use sources in this order:

1. Characterization tests.
2. Laravel 8 reference implementation.
3. Laravel Boost and Laravel 13 conventions.
4. Architect decisions recorded in `docs/migration/decisions.md`.

When tests define behavior, implementation adapts to the tests. When tests are silent, inspect Laravel 8 for business intent. Do not mechanically translate Laravel 8 source code.

## Characterization Test Maturity

Every transferred characterization artifact must be tracked in `docs/migration/legacy-test-inventory.md`.

Statuses:

| Status | Meaning |
|---|---|
| `Copied` | The artifact exists in this repository but is not yet adapted or executable here. |
| `Ported` | Assertions have been translated to Laravel 13/Pest/Vue/Inertia conventions while preserving behavior. |
| `Enabled` | The test is included in the active slice command or CI path. |
| `Passing` | The enabled test passes against the Laravel 13 implementation. |
| `Blocked` | Progress is waiting on an unresolved architectural, fixture, dependency, or behavior question. |
| `Retired` | The test is no longer required; architect approval and a decision-log entry are required. |

Lifecycle:

```text
Copied -> Ported -> Enabled -> Passing
```

A copied test is not a completed migration contract. A behavior is complete only when its owning characterization is `Passing` or explicitly `Retired` with approval.

## Route Compatibility Policy

Default policy:

- Preserve existing Laravel 8 external routes whenever practical.
- Preserve user deep links.
- Preserve browser workflows expected by characterization tests.

Laravel 13 may internally use route model binding, resource controllers, named routes, policies, and modern controller structure.

External route behavior should remain compatible unless the architect approves a breaking change.

If a route must change:

- introduce a redirect when appropriate,
- document the decision in `docs/migration/decisions.md`,
- update characterization only after approval.

## Fixture Strategy

Recreate business scenarios, not Laravel 8 infrastructure.

Use Laravel 13 conventions:

- factories,
- seeders,
- Pest datasets,
- reusable test builders,
- services where they clarify fixture intent.

Do not import the Laravel 8 schema bootstrap wholesale. Every migrated slice owns the fixtures required to prove its behavior.

Feature characterization tests should be ported slice-by-slice while preserving behavioral assertions. Laravel 8 schema assumptions, helper utilities, and legacy bootstrapping should be replaced by Laravel 13 factories, seeders, services, and testing conventions.

Preserve behavior. Modernize implementation.

## UI Compatibility Contract

Vue 3 and Inertia.js are required.

The legacy implementation is not the UI implementation contract. Preserve:

- user workflows,
- navigation,
- screen organization,
- terminology,
- validation behavior,
- interaction sequence,
- operational efficiency.

The DOM structure is not part of the compatibility contract.

Legacy browser tests that depend on brittle selectors may be modernized after review so they verify user-visible behavior rather than HTML implementation details.

The objective is behavioral compatibility rather than DOM compatibility.

## Slice Order

Use this order unchanged:

```text
Authentication
        ↓
Dashboard
        ↓
Company
        ↓
Division
        ↓
Configuration
        ↓
Site
        ↓
Building
        ↓
Meter Location
        ↓
Gateway
        ↓
Meter
        ↓
User Management
        ↓
Reports
        ↓
RTU / Device Endpoints
```

## Slice Execution Loop

For every slice:

1. Review transferred characterization tests for the slice.
2. Review the corresponding Laravel 8 implementation only to clarify intent.
3. Consult Laravel Boost and relevant local skills.
4. Port or adapt the slice tests.
5. Implement with Laravel 13, Inertia, Vue 3, Pest, and modern conventions.
6. Run targeted characterization tests.
7. Run Pest.
8. Run Pint.
9. Run available type/lint checks.
10. Document behavioral gaps and architectural decisions.
11. Stop for review before beginning the next slice.

## Definition Of Done

A slice is complete only when:

- characterization tests for the slice are passing,
- Pest is green,
- Pint is clean,
- Laravel Boost guidance has been followed,
- Vue/Inertia implementation is complete,
- no legacy jQuery patterns remain,
- legacy fixtures have been replaced with Laravel 13 factories, seeders, or builders,
- behavioral gaps have been documented,
- architectural review is complete,
- migration inventory reflects current test maturity.

## High-Risk Modules

Review Laravel 8 carefully before implementing:

- RTU/device protocol endpoints,
- report generation and XLSX exports,
- authorization semantics,
- destructive CRUD workflows,
- DataTables behavior.

If uncertainty remains, stop and request architectural guidance rather than guessing.
