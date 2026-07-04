# Analytics Work Queue

## Purpose

This is the future implementation backlog for the CAMR Analytics Workbench. Tasks are intentionally small so implementation can proceed one item at a time with architect review between items.

## Queue

| ID | Title | Objective | Priority | Dependencies | Acceptance Criteria | Expected Files | Analyst Benefit |
|---|---|---|---|---|---|---|---|
| AN-001 | Analytics Data Inventory | Inventory available telemetry, meter, gateway, site, building, and report data for analytics. | High | Migration complete | Canonical sources, grains, gaps, and confidence concerns are documented. | `docs/analytics/` updates, possible feature tests later | Analysts know what data is available and trustworthy. |
| AN-002 | TelemetryPoint Contract | Define the smallest canonical point contract for timestamped telemetry, source lineage, confidence, and entity context. | High | AN-001 | Contract returns real persisted telemetry with no fake Vue values and distinguishes measured, incomplete, and unknown values. | backend action/controller tests, future data contract files | Later series work has a trustworthy point shape. |
| AN-003 | ConsumptionSeries Contract | Define consumption series points from accepted telemetry points and report-compatible consumption semantics. | High | AN-002 | Series exposes period, kWh, boundary readings, multiplier, missing interval summary, and confidence. | backend action/controller tests, future data contract files | Energy Managers can trust consumption trends before charts exist. |
| AN-004 | DemandSeries Contract | Define demand series points from accepted telemetry points and report-compatible demand semantics. | High | AN-002 | Series exposes period, kW demand, elapsed minutes, peak marker, multiplier, missing interval summary, and confidence. | backend action/controller tests, future data contract files | Finance and Engineering can inspect peak drivers safely. |
| AN-005 | BuildingConsumptionSummary Contract | Define building-level consumption summary contract for comparisons across a selected period. | High | AN-003 | Summary exposes building context, total kWh, meter count, missing-data summary, comparison value, and confidence. | backend action/controller tests, future data contract files | Analysts can compare buildings without ad hoc frontend aggregation. |
| AN-006 | DataTrustIndicator Contract | Define reusable confidence and source-lineage object used by analytics points, series, and summaries. | High | AN-002, AN-003, AN-004 | Trust indicator describes level, reason, missing intervals, source, and user-facing warning. | backend action/controller tests, future shared types | Users can see uncertainty instead of receiving false precision. |
| AN-007 | Time Range Picker | Build reusable analytical time-range selector. | High | AN-002 | Selector preserves selected range, timezone label, and valid boundaries. | Vue component, tests | Users can frame investigations clearly. |
| AN-008 | Aggregation Selector | Build grain and aggregation selector. | High | AN-003, AN-004 | Selected grain and aggregation are visible and sent through approved series contracts. | Vue component, tests | Users can change analytical resolution safely. |
| AN-009 | Consumption Summary Cards | Add consumption headline cards with confidence and comparison context. | High | AN-003, AN-006 | Cards show value, unit, period, comparison, and confidence. | Vue component, analytics page test | Energy Managers see the starting point quickly. |
| AN-010 | Consumption Trend | Add first consumption trend visualization. | High | AN-003, AN-007, AN-008, AN-009 | Trend uses real contract data and handles empty/incomplete data. | Vue component, analytics tests | Users can see how consumption changed over time. |
| AN-011 | Demand Curve | Add demand curve with peak markers. | High | AN-004, AN-006 | Peak timestamp, value, unit, and confidence are visible. | Vue component, tests | Finance and Engineering can inspect peak drivers. |
| AN-012 | Building Comparison | Add building comparison grid. | Medium | AN-005, AN-010 | Grid ranks buildings and shows confidence/missing data. | Vue component, tests | Users can identify contributing buildings. |
| AN-013 | Load Profile Explorer | Add load profile investigation skeleton. | Medium | AN-010, AN-011 | Profile shows shape, grain, and missing intervals. | Vue page/component, tests | Engineering can inspect operating patterns. |
| AN-014 | Analytics Demo Data Readiness | Add a deterministic lifecycle scenario that creates analytics-showcase telemetry patterns before UI mounting. | High | AN-002, AN-003, AN-004, AN-005, AN-006 | `camr:scenario analytics-demo` produces normal consumption, abnormal consumption, demand peak, incomplete data, unknown data, and building comparison readiness. | simulator/scenario files, feature tests, docs | Reviewers can evaluate future analytics UI against data that tells a story. |
| AN-015 | Analytics Empty State | Add reusable analytics empty/incomplete state. | High | AN-002, AN-006, AN-014 | Empty states explain missing filter, no data, incomplete data, or unsupported grain. | Vue component, tests | Users understand why analysis is unavailable. |
| AN-016 | Analytics Route / Page Shell | Mount the first visible Analytics Workbench route and shell without data wiring. | High | AN-014, AN-015 | `/analytics` renders an authenticated Inertia shell, appears in operator navigation, explains readiness, and does not replace Reports. | controller, route, Vue page, feature tests | Users can see where Analytics will live before contract data is connected. |
| AN-017 | Wire Analytics Data Contract to Page | Connect approved analytics contracts to the mounted Analytics shell. | High | AN-016 | Page receives real contract-backed summary, consumption, demand, building comparison, and load-profile props without fake Vue values. | controller/action wiring, Vue page, feature tests | Analysts can evaluate real analytics data from `analytics-demo`. |
| AN-018 | First Visible Analytics Workspace | Compose the first useful analytics workspace using existing analytics components. | High | AN-017 | Summary cards, trend, demand curve, building comparison, load profile, and empty states render coherently with demo data. | Vue page/component integration, tests | Analysts can inspect historical patterns in the app. |
| AN-019 | Export Panel | Add analytics evidence export panel without replacing Reports. | Medium | AN-018 | Export path is explicit and does not alter report semantics. | Vue component, tests | Users can share analytical evidence. |
| AN-020 | Energy Manager Journey | Add smoke journey for abnormal consumption investigation. | High | AN-018 | Journey moves from trigger to evidence to share/export outcome. | feature/browser tests | Confirms analytics supports real investigation. |
| AN-021 | Executive Review Journey | Add smoke journey for monthly executive review. | Medium | AN-018 | Executive can review trend, top movers, and confidence summary. | feature/browser tests | Confirms concise review workflow. |

## Sizing Rule

If a task requires more than one new analytics data contract or one new major visualization, split it.

## Acceptance Gate

Each completed analytics task should report:

- files changed,
- data contracts used,
- analytics semantics preserved,
- report semantics preserved,
- components created/reused,
- tests or checks run,
- unresolved analytical risks.
