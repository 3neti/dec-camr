# CAMR Dashboard Vision

## Purpose

The dashboard should become the operator's first operational answer, not a landing page. It should show what is healthy, what needs attention, what changed recently, and where the operator should go next.

The design should preserve legacy CAMR workflow language while adopting the stronger monitoring patterns from the Warp prototype: compact KPI cards, visible online/offline posture, recent telemetry, and direct drill-down paths.

## Operator Goals

- Administrator: confirm system readiness, reach maintenance/user tasks quickly, and see access-related anomalies.
- Operations Engineer: identify offline or stale gateways immediately and drill into remediation.
- Maintenance Technician: see device/configuration issues that require field metadata updates.
- Analyst: see whether report windows have enough data before generating exports.

## KPI Layout

Use a dense, scan-friendly top band. Avoid a marketing-style hero or oversized decorative cards.

Recommended first row:

| KPI | Primary Value | Secondary Signal | Drill-down |
|---|---:|---|---|
| Gateways | Total gateways | Online / stale / offline | `/gateway` or gateway health view |
| Meters | Total meters | Active / stale / no recent telemetry | `/meter` or meter health view |
| Sites | Active sites | Sites with offline devices | `/site` |
| Telemetry | Readings in last window | Last received timestamp | live operations |
| Pending Updates | Count | CSV/location/force LP flags | gateway operations |
| Reports Ready | Populated windows | Last export / empty windows | reports |

## Alert Strategy

Alerts should be operational, not decorative. Show only states that imply operator action or awareness:

- Offline gateway: no recent communication beyond configured threshold.
- Stale gateway: delayed communication but not yet offline.
- Meter stale: meter has not produced expected readings.
- Pending update: gateway or meter has update flags waiting for pickup/reset.
- Report window incomplete: expected telemetry is missing for a selected reporting period.
- Scoped access anomaly: user access was changed recently or appears inconsistent.

Severity model:

| Severity | Meaning | UI Treatment |
|---|---|---|
| Critical | Work is blocked or device is offline | Red status, top alert list, drill-down |
| Warning | Attention needed soon | Amber status, grouped in attention list |
| Info | Recent operational change | Neutral activity feed |
| Success | Recovery or expected update | Green short-lived event |

## Health Indicators

Health status must be derived from real database fields populated by seed profiles and the telemetry simulator. Do not fake dashboard-only state.

Recommended health vocabulary:

- Online: recent `last_log_update` within threshold.
- Stale: `last_log_update` older than threshold but recoverable.
- Offline: no communication beyond critical threshold.
- Pending Update: update/reset/force-load-profile flag is set.
- Recovering: offline/stale device has recent new telemetry after a failure window.

## Gateway Status

Gateway status is the highest-priority operational panel.

Display:

- gateway serial number,
- MAC address,
- site/building context,
- last communication age,
- soft revision,
- pending CSV/location/force LP flags,
- latest meter count reporting through the gateway,
- action affordances for safe protocol operations where already implemented.

Primary gateway panel should answer:

- Which gateways are offline?
- Which gateways are stale?
- Which gateway has pending updates?
- Which gateway recovered recently?

## Meter Status

Meter status should be grouped by operational context:

- Site -> Building -> Gateway -> Meter
- Health status
- Last reading
- Latest kW / kWh when available
- Missing telemetry indicator

Avoid presenting meters as an unstructured flat list on the dashboard. The flat table belongs in maintenance; the dashboard should emphasize exceptions and summaries.

## Stale Communication

Staleness should be visible as relative age and status:

- `2m ago` online,
- `37m ago` stale,
- `6h ago` offline.

Use time-age display consistently across dashboard, gateway detail, meter detail, and live operations.

## Pending Updates

Pending updates should be presented as operational flags:

- CSV pending,
- location pending,
- force LP pending,
- update reset required.

Each flag should show:

- affected gateway/site,
- when it was set,
- whether reset action is available,
- last device pickup/reset state if known.

## Recent Telemetry

Recent telemetry should be compact:

- newest reading timestamp,
- active meter count,
- recent kW/kWh trend,
- recent abnormal gaps.

Do not make the dashboard a full analytics page. Use mini trends and "view details" links to the live operations console or reports.

## Quick Actions

Quick actions should be role-aware and limited to high-frequency tasks:

- Create Site / Gateway / Meter for maintenance/admin users.
- Open Offline Gateways for operations.
- Generate Consumption Report / Demand Report for analysts.
- Open User Site Access for administrators.
- Run gateway update-related actions only where backend behavior is already approved.

Quick actions must preserve legacy endpoints and permissions.

## Role-Specific Dashboard Differences

| Role | First Priority | Secondary Panels | Hidden or Reduced |
|---|---|---|---|
| Admin | System and access health | User access, entity counts | None except unavailable protocol actions |
| Operations Engineer | Gateway/meter health | Telemetry timeline, pending updates | User management |
| Maintenance Technician | Site/gateway/meter context | Metadata issues, pending config | User management and broad reports |
| Analyst | Report readiness | Data coverage, recent exports | Maintenance mutations |

The route protection remains backend-enforced. Dashboard personalization should not become the authorization layer.

## Drill-Down Navigation

Every dashboard item should lead to the next operator action:

- Offline Gateway -> Gateway detail or filtered gateway list.
- Stale Meter -> Meter detail or filtered meter list.
- Pending Update -> Gateway update panel.
- Report Ready -> Report filter page with preselected range if safe.
- Access Warning -> User site access page.

## Wireframe Concept

```text
CAMR Operator Console                                    User / Role
--------------------------------------------------------------------
Gateway Health | Meter Health | Telemetry | Pending Updates | Reports
--------------------------------------------------------------------
Needs Attention
[Critical] GW-RB-04 offline 6h       Site: SITEA       Open Gateway
[Warning]  3 meters stale            Building: BLDG-2   View Meters
[Info]     CSV update pending        Gateway: GW-RB-02  Review

--------------------------------------------------------------------
Operations Snapshot                         Recent Telemetry
Online Gateways    18 / 21                  08:00:00  MTR-001  12.4 kW
Stale Gateways      2                       08:00:00  MTR-014   4.9 kW
Offline Gateways    1                       07:59:45  MTR-008   7.1 kW

--------------------------------------------------------------------
Report Readiness              Quick Actions
Consumption: Ready            Site Maintenance
Demand: Ready                 Gateway Maintenance
SAP: Partial                  Consumption Report
Raw: No data for selected     Demand Report
```

## Reusable Concepts

Potentially reusable across CAMR, Track AI, x-change, and 3neti tools:

- Health KPI card with current value, risk count, and drill-down.
- Attention queue sorted by severity and recency.
- Status chip vocabulary with strict semantics.
- Recent activity feed that can be paused and filtered.
- Role-aware quick action rail.

