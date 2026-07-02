# Analytics Work Queue

## Purpose

This is the future implementation backlog for the CAMR Analytics Workbench. Tasks are intentionally small so implementation can proceed one item at a time with architect review between items.

## Queue

| ID | Title | Objective | Priority | Dependencies | Acceptance Criteria | Expected Files | Analyst Benefit |
|---|---|---|---|---|---|---|---|
| AN-001 | Analytics Data Inventory | Inventory available telemetry, meter, gateway, site, building, and report data for analytics. | High | Migration complete | Canonical sources, grains, gaps, and confidence concerns are documented. | `docs/analytics/` updates, possible feature tests later | Analysts know what data is available and trustworthy. |
| AN-002 | Analytics Data Contract | Define first backend contract for metric, time range, grain, confidence, and missing-data summary. | High | AN-001 | Contract returns real persisted data with no fake Vue values. | backend action/controller tests, future data contract files | UI can build on stable analytical data. |
| AN-003 | Time Range Picker | Build reusable analytical time-range selector. | High | AN-002 | Selector preserves selected range, timezone label, and valid boundaries. | Vue component, tests | Users can frame investigations clearly. |
| AN-004 | Aggregation Selector | Build grain and aggregation selector. | High | AN-002 | Selected grain and aggregation are visible and sent through approved contract. | Vue component, tests | Users can change analytical resolution safely. |
| AN-005 | Consumption Summary Cards | Add consumption headline cards with confidence and comparison context. | High | AN-002 | Cards show value, unit, period, comparison, and confidence. | Vue component, analytics page test | Energy Managers see the starting point quickly. |
| AN-006 | Consumption Trend | Add first consumption trend visualization. | High | AN-003, AN-004, AN-005 | Trend uses real contract data and handles empty/incomplete data. | Vue component, analytics tests | Users can see how consumption changed over time. |
| AN-007 | Demand Curve | Add demand curve with peak markers. | High | AN-002 | Peak timestamp, value, unit, and confidence are visible. | Vue component, tests | Finance and Engineering can inspect peak drivers. |
| AN-008 | Building Comparison | Add building comparison grid. | Medium | AN-006 | Grid ranks buildings and shows confidence/missing data. | Vue component, tests | Users can identify contributing buildings. |
| AN-009 | Load Profile Explorer | Add load profile investigation skeleton. | Medium | AN-006 | Profile shows shape, grain, and missing intervals. | Vue page/component, tests | Engineering can inspect operating patterns. |
| AN-010 | Analytics Empty State | Add reusable analytics empty/incomplete state. | High | AN-002 | Empty states explain missing filter, no data, incomplete data, or unsupported grain. | Vue component, tests | Users understand why analysis is unavailable. |
| AN-011 | Export Panel | Add analytics evidence export panel without replacing Reports. | Medium | AN-006 | Export path is explicit and does not alter report semantics. | Vue component, tests | Users can share analytical evidence. |
| AN-012 | Energy Manager Journey | Add smoke journey for abnormal consumption investigation. | High | AN-006, AN-008 | Journey moves from trigger to evidence to share/export outcome. | feature/browser tests | Confirms analytics supports real investigation. |
| AN-013 | Executive Review Journey | Add smoke journey for monthly executive review. | Medium | AN-005, AN-008 | Executive can review trend, top movers, and confidence summary. | feature/browser tests | Confirms concise review workflow. |

## Sizing Rule

If a task requires more than one new analytics data contract and one new major visualization, split it.

## Acceptance Gate

Each completed analytics task should report:

- files changed,
- data contracts used,
- analytics semantics preserved,
- report semantics preserved,
- components created/reused,
- tests or checks run,
- unresolved analytical risks.

