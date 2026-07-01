# CAMR Report Experience

## Purpose

Reports are a production workflow, not a secondary utility. The UI must make filter intent, data coverage, preview results, export status, and downloaded files clear while preserving legacy report semantics and file contracts.

The report experience should support Raw, SAP, Site/Building, Consumption, Demand, Offline, and Site As-Built flows.

## Analyst Workflow

Recommended analyst path:

```text
Choose report family
-> Select site/building/meter scope
-> Select date/time range
-> Choose grouping or interval
-> Preview result summary
-> Export XLSX/CSV where supported
-> Confirm download in shelf/history
```

The UI should reduce accidental exports by making mandatory filters explicit before submission.

## Filter UX

Use a dedicated `ReportFilterPanel` pattern with consistent sections:

- Report family
- Scope: company/division/site/building/gateway/meter where applicable
- Date range
- Interval: hourly/daily/15-minute where applicable
- Output type: preview/export
- Advanced legacy options only where already supported

Filter controls should preserve legacy terminology:

- Raw Data
- KW Demand
- KWh Consumption
- SAP
- Building
- Offline Gateway/Meter
- Site As-Built

## Saved Filters

Saved filters are useful, but should come after the base report workflow is stable.

Recommended saved filter model:

- user-scoped,
- report-family-specific,
- stores filter values only,
- never changes backend report semantics,
- includes a visible "last used" timestamp.

Initial implementation can use local/persisted UI preferences only if backend support is not yet approved.

## Date Selection

Date selection is a high-risk area because report semantics depend on boundaries.

Rules:

- Use explicit start and end fields.
- Show timezone or local-time assumption when relevant.
- Preserve legacy inclusive/exclusive behavior as implemented.
- Provide common presets only if they map to deterministic date windows:
  - Today
  - Yesterday
  - Last 7 days
  - Current month
  - Previous month

Do not silently reinterpret legacy report date boundaries.

## Report Grouping

Grouping should be visible before submission:

| Report | Likely Grouping Controls |
|---|---|
| Raw | meter, timestamp |
| SAP | site/building/account mapping where applicable |
| Site/Building | site, building, meter |
| Consumption | hourly/daily |
| Demand | hourly/15-minute |
| Offline | gateway/meter status |
| Site As-Built | site/building/gateway/meter inventory |

If grouping behavior is fixed by legacy report logic, show it as read-only context instead of a control.

## Report Previews

Preview should answer:

- Did the filters match data?
- How many rows are expected?
- What time range was used?
- What units are present?
- Are totals/aggregates plausible?
- Is export available?

Preview should show representative rows, not the entire workbook for large reports.

Suggested preview layout:

```text
Report: KWh Consumption
Scope: SITEA / Building A
Range: 2026-07-01 00:00 -> 2026-07-01 23:59
Rows: 24 hourly intervals
Coverage: 21 of 24 expected intervals

Metric                 Value
Total Consumption      1,284.50 kWh
Peak Hour              14:00
Meters Included        18

[Preview Table: first 10 rows]
[Export XLSX] [Export CSV if supported]
```

## Export Progress

Initial exports may be synchronous if the current backend produces immediate responses. The UI should still have an export state:

- idle,
- preparing,
- downloading,
- failed,
- complete.

For future large exports, the same surface can become queued without changing the analyst workflow.

## Download Shelf

Add a compact download shelf after core report pages are stable:

- last exported file,
- report family,
- timestamp,
- filters summary,
- file type,
- download again if backend supports it.

This is especially valuable for demos and analyst workflows, but it should not imply permanent export history unless the backend stores it.

## Historical Exports

Historical exports are optional and should be treated as a later enhancement.

If implemented, record:

- user,
- report family,
- filter summary,
- generated filename,
- created timestamp,
- status,
- expiry/retention if files are stored.

## Empty-Result UX

Empty results must be explicit and useful:

- show the selected range and scope,
- say no data was found,
- provide safe next actions:
  - adjust date range,
  - check telemetry freshness,
  - open live operations,
  - open meter/gateway maintenance if role allows.

Do not show a blank table with no explanation.

## Large Report UX

For large reports:

- show estimated row count where available,
- preserve filter panel while results load,
- avoid rendering massive tables on first load,
- preview representative rows,
- export full results,
- use pagination/virtualization only if needed.

## Wireframe Concept

```text
Reports
--------------------------------------------------------------------
[Raw Data] [KW Demand] [KWh Consumption] [SAP] [Building] [Offline]

Filters
Site: [SITEA v]   Building: [All v]   Meter: [All v]
Range: [2026-07-01] to [2026-07-02]   Interval: [Hourly v]
                                      [Preview] [Export XLSX]

--------------------------------------------------------------------
Preview Summary
Rows: 24      Coverage: 21/24 intervals      Units: kWh
Status: Ready for export

--------------------------------------------------------------------
Representative Rows
Time        Meter        Consumption     Demand
08:00       MTR-001      12.4 kWh        3.1 kW
09:00       MTR-001      13.0 kWh        3.3 kW

Download Shelf
KWh_Consumption_SITEA_20260701.xlsx     Complete     Download
```

## Reusable Concepts

- `ReportFilterPanel`
- `DateRangeControl`
- `ReportPreviewSummary`
- `ExportButton`
- `ExportState`
- `DownloadShelf`
- `EmptyResultPanel`

