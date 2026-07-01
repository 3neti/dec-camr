# CAMR Live Operations Console

## Purpose

The live operations console should help operators investigate active gateway and meter problems. It is distinct from maintenance pages. Maintenance pages manage records; live operations explains current operational state.

The console should be backed by real telemetry, seeded operational states, and the telemetry simulator.

## Workspace Model

Recommended layout:

```text
Live Operations
--------------------------------------------------------------------
Filter Bar: Site | Status | Gateway | Search | Auto-refresh | Pause

Left: Gateway/Meter Health List        Right: Selected Entity Detail
--------------------------------------------------------------------
GW-RB-01 Online                         Gateway GW-RB-04
GW-RB-02 Stale                          Status: Offline
GW-RB-04 Offline                        Last communication: 6h ago
MTR-018 Stale                           Site: SITEA
                                        Pending: CSV update

                                        Timeline
                                        08:00 Reading received
                                        07:52 CSV reset requested
                                        02:00 Last successful telemetry
```

## Gateway Detail

Gateway detail should show:

- serial number,
- MAC address,
- IP address,
- site/building association,
- last communication,
- soft revision,
- update flags,
- force LP status,
- latest telemetry summary,
- affected meters,
- safe operational commands.

Commands must use existing approved endpoints and should be disabled or hidden based on backend authorization.

## Meter Detail

Meter detail should show:

- meter name and alternate/default name,
- gateway association,
- location/site/building context,
- last reading,
- latest kW/kWh values where available,
- freshness state,
- recent telemetry samples,
- report shortcut for the meter and time range.

## Telemetry Timeline

The timeline should tell the story of operational state:

- telemetry received,
- gateway stale threshold crossed,
- gateway offline threshold crossed,
- update flag set,
- update reset observed,
- force LP requested,
- soft revision changed,
- gateway recovered.

Use concise event rows with timestamp, status, source, and action link.

## Communication History

Communication history should support investigation:

- last successful contact,
- last failed or missing interval if known,
- recent gaps,
- count of readings in selected window,
- expected vs actual interval coverage.

Avoid pretending the app knows failure causes unless data supports it.

## Firmware And Update Status

Show `soft_rev` and update-related fields as operational facts:

- current soft revision,
- last changed,
- CSV/location update pending,
- force LP pending,
- reset state.

When exact legacy meaning is subtle, label conservatively and avoid over-explaining in UI.

## Force LP

Force LP should be presented as an operational command with high clarity:

- show current force flag state,
- show target gateway,
- confirm command before submission if destructive or disruptive,
- show last reset time if known,
- never change route behavior.

## Pending Updates

Pending updates should appear in:

- gateway health list,
- gateway detail header,
- dashboard attention queue,
- timeline.

This prevents flags from being hidden inside maintenance tables.

## Alarms

Initial alarms should be derived alerts, not a new alarm subsystem:

- offline threshold,
- stale threshold,
- missing report-window telemetry,
- pending update older than threshold,
- no meters reporting through an online gateway.

## Operational Commands

Command design:

- use icon + label,
- include disabled state and tooltip/reason,
- preserve backend route and payload,
- show success/failure feedback,
- append a timeline event if backend state confirms the effect.

Examples:

- Download CSV
- Download Location
- Reset CSV Update
- Reset Location Update
- Force LP
- Reset Force LP

## Investigation Workflow

Operations engineer path:

```text
Open dashboard
-> See "Gateway offline"
-> Open live operations
-> Filter offline
-> Select gateway
-> Check last communication and pending updates
-> Inspect affected meters
-> Trigger safe operation if appropriate
-> Observe simulator/telemetry recovery
-> Confirm status change or escalate
```

Maintenance technician path:

```text
Open site context
-> Select gateway/meter
-> Verify metadata and configuration
-> Correct association if needed
-> Return to live operations
-> Confirm fresh telemetry against corrected context
```

Analyst path:

```text
Open report page
-> See incomplete data coverage
-> Open live operations for affected site/meter
-> Confirm stale/offline source
-> Adjust report window or escalate
```

## Reusable Concepts

- `LiveStatusHeader`
- `GatewayHealthList`
- `MeterHealthGrid`
- `TelemetryTimeline`
- `OperationalCommandBar`
- `CommunicationSummary`
- `PendingUpdatePanel`
- `ActivityFeed`

