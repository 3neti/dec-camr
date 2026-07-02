# Analytics Data Inventory

## Purpose

This document completes `AN-001 — Analytics Data Inventory`.

It answers:

> What data is actually available for analytics today?

This is documentation only. It does not create analytics services, Vue pages, routes, charts, migrations, report changes, dashboard changes, seed changes, or simulator changes.

## 1. Canonical Tables

The current Laravel 13 database contains these CAMR-relevant tables:

- `meter_data`
- `meter_details`
- `meter_rtu`
- `meter_site`
- `meter_building_table`
- `meter_location_table`
- `meter_company_table`
- `meter_division_table`
- `meter_configuration_file`
- `user_access_group`
- `users`

There is no separate `meter_gateway` table in the current Laravel 13 schema. Gateway data is stored in `meter_rtu`.

### `meter_data`

Purpose:

- Raw interval telemetry and electrical readings.

Primary key:

- `id`

Identifier columns:

- `meter_id`: string identifier used by reports and telemetry.
- `location`: string context used by reports. Current seeded/simulated report-compatible telemetry uses building code. RTU ingestion accepts the posted `location` value.
- `mac_addr`: gateway MAC address copied from telemetry payload or simulator.

Timestamp columns:

- `datetime`: primary telemetry timestamp used by reports, dashboard, and analytics.
- `dt`: nullable timestamp, legacy-compatible secondary timestamp.
- `created_at`, `updated_at`: database insert/update timestamps.

Measurement columns:

- Voltage: `vrms_a`, `vrms_b`, `vrms_c`
- Current: `irms_a`, `irms_b`, `irms_c`
- Frequency: `freq`
- Power factor: `pf`
- Real/apparent/reactive power: `watt`, `va`, `var`
- Energy counters: `wh_del`, `wh_rec`, `wh_net`, `wh_total`
- Reactive/apparent energy: `varh_neg`, `varh_pos`, `varh_net`, `varh_total`, `vah_total`
- Demand markers: `max_rec_kw_dmd`, `max_rec_kw_dmd_time`, `max_del_kw_dmd`, `max_del_kw_dmd_time`, `max_pos_kvar_dmd`, `max_pos_kvar_dmd_time`, `max_neg_kvar_dmd`, `max_neg_kvar_dmd_time`
- Phase angles: `v_ph_angle_a`, `v_ph_angle_b`, `v_ph_angle_c`, `i_ph_angle_a`, `i_ph_angle_b`, `i_ph_angle_c`
- Device state: `soft_rev`, `relay_status`, `genset_status`

Nullability/default notes:

- Most numeric telemetry fields default to `0`.
- Demand timestamp fields are nullable.
- `mac_addr`, `soft_rev`, `dt`, and `genset_status` are nullable.
- `location` defaults to `Home`.

Indexes:

- `meter_data_index` on `meter_id`, `datetime`, `location`.

Current usage:

- Reports query `meter_data` by `meter_id`, `location`, and `datetime`.
- Dashboard and Live Operations read recent telemetry and timeline events from `meter_data`.
- RTU telemetry ingestion inserts into `meter_data` when `save_to_meter_data = 1`.
- Seed profiles and telemetry simulator insert deterministic demonstration telemetry into `meter_data`.

Analytics relevance:

- This is the primary source for consumption, demand, load profile, voltage/current/frequency/power factor, and data availability.

### `meter_details`

Purpose:

- Meter identity and context.

Primary key:

- `meter_id`

Relationship columns:

- `site_idx`
- `rtu_idx`
- `location_idx`
- `building_idx`
- `config_idx`

Identifier/context columns:

- `site_code`
- `meter_name`
- `meter_default_name`
- `meter_name_addressable`
- `customer_name`
- `meter_type`
- `meter_brand`
- `meter_role`
- `meter_remarks`

Operational columns:

- `meter_load_profile`
- `meter_status`
- `meter_multiplier`
- `last_log_update`
- `soft_rev`

Timestamp/audit columns:

- `created_at`
- `updated_at`
- `created_by_user_idx`
- `modified_by_user_idx`

Nullability/default notes:

- `building_idx` is nullable and defaults to `0`.
- `meter_type`, `meter_brand`, `meter_remarks`, and `customer_name` are nullable.
- `last_log_update` is a string with legacy default `0000-00-00 00:00:00`.
- `soft_rev` is nullable and defaults to `0`.

Current usage:

- Reports select active meters by `site_idx`, `meter_name`, and `meter_status`.
- Dashboard uses active meter counts and meter health from `last_log_update`.
- Gateway and meter maintenance use the relationship columns for list and mutation workflows.

Analytics relevance:

- Required for meter identity, meter multiplier, site/building/gateway context, customer context, and status filtering.

### `meter_rtu`

Purpose:

- Gateway identity, communication state, update flags, and firmware context.

Primary key:

- `rtu_id`

Relationship columns:

- `site_idx`
- `location_idx`

Identifier/context columns:

- `site_code`
- `gateway_sn`
- `gateway_mac`
- `gateway_ip`
- `connection_type`
- `gateway_description`
- `idf_number`
- `switch_name`
- `idf_port`

Network columns:

- `ip_netmask`
- `ip_gateway`
- `rtu_server_ip`

Operational/update columns:

- `update_rtu`
- `update_rtu_location`
- `update_rtu_ssh`
- `update_rtu_force_lp`
- `last_log_update`
- `soft_rev`

Timestamp/audit columns:

- `created_at`
- `updated_at`
- `created_by_user_idx`
- `modified_by_user_idx`

Nullability/default notes:

- Several network and location fields are nullable.
- Update flags default to `0`.
- `last_log_update` is nullable string with legacy default `0000-00-00 00:00:00`.
- `soft_rev` is nullable and defaults to `0`.

Current usage:

- Dashboard and Live Operations compute online/stale/offline gateway health.
- RTU protocol endpoints inspect and reset update flags.
- Telemetry ingestion updates `last_log_update` and `soft_rev`.

Analytics relevance:

- Supports communication rate, freshness, availability, firmware/version context, pending update analysis, and outage investigation.

### `meter_site`

Purpose:

- Site context and high-level communication freshness.

Primary key:

- `site_id`

Relationship columns:

- `division_idx`
- `company_idx`
- `building_idx`

Identifier/context columns:

- `site_code`
- `building_description`

Operational columns:

- `last_log_update`
- `deleted_at`

Timestamp/audit columns:

- `created_at`
- `updated_at`
- `created_by_user_idx`
- `modified_by_user_idx`

Nullability/default notes:

- `building_idx` is nullable and defaults to `0`.
- `site_code`, `building_description`, `last_log_update`, and `deleted_at` are nullable.

Current usage:

- Reports resolve site metadata and building code through `meter_building_table`.
- Dashboard uses site telemetry readiness.
- Scoped authorization uses `user_access_group` against `site_id`.

Analytics relevance:

- Required for site grouping, authorization scope, portfolio analysis, and site-level freshness.

### `meter_building_table`

Purpose:

- Building identity, site relationship, and network context.

Primary key:

- `building_id`

Relationship columns:

- `site_idx`
- `meter_site_id`

Identifier/context columns:

- `building_code`
- `building_description`

Operational/network columns:

- `cut_off`
- `device_ip_range`
- `ip_network`
- `ip_netmask`
- `ip_gateway`

Timestamp/audit columns:

- `created_at`
- `updated_at`
- `created_by_user_idx`
- `modified_by_user_idx`

Current usage:

- Reports use `building_code` as the `meter_data.location` lookup value.
- Site/building management uses building code and description.

Analytics relevance:

- Critical for building-level comparison, report-compatible telemetry lookup, and executive/manager summaries.

### `meter_location_table`

Purpose:

- Physical meter/gateway location context.

Primary key:

- `location_id`

Relationship columns:

- `site_idx`
- `building_id`

Identifier/context columns:

- `location_code`
- `location_description`

Timestamp/audit columns:

- `created_at`
- `updated_at`
- `created_by_user_idx`
- `modified_by_user_idx`

Current usage:

- Gateway and meter maintenance lists.
- Site/gateway/meter context.

Analytics relevance:

- Useful for physical grouping and maintenance context, but not currently used by report formulas.

### `meter_company_table`

Purpose:

- Company identity.

Primary key:

- `company_id`

Columns:

- `company_code`
- `company_name`
- `created_by_user_idx`
- `created_at`
- `modified_by_user_idx`
- `updated_at`

Current usage:

- Site relationship through `meter_site.company_idx`.
- Maintenance pages and seeded hierarchy.

Analytics relevance:

- Useful for portfolio rollups and company-level comparisons.

### `meter_division_table`

Purpose:

- Division identity.

Primary key:

- `division_id`

Columns:

- `division_code`
- `division_name`
- `created_by_user_idx`
- `created_at`
- `modified_by_user_idx`
- `updated_at`

Current usage:

- Site relationship through `meter_site.division_idx`.
- Maintenance pages and seeded hierarchy.

Analytics relevance:

- Useful for organizational rollups.

### `meter_configuration_file`

Purpose:

- Meter model/configuration file reference.

Primary key:

- `config_id`

Columns:

- `meter_model`
- `config_file`
- `created_by_user_idx`
- `created_at`
- `modified_by_user_idx`
- `updated_at`

Current usage:

- Meter configuration context.

Analytics relevance:

- Possible future grouping by model/configuration, but not an initial analytics source.

### `user_access_group`

Purpose:

- User-to-site scope mapping.

Current usage:

- Authorization and scoped site visibility.

Analytics relevance:

- Analytics contracts must respect the same site scoping as Reports, Dashboard, and maintenance workflows.

## 2. Column Semantics Summary

Analytics-relevant identifiers:

- Meter database key: `meter_details.meter_id`
- Legacy/report meter identifier: `meter_details.meter_name`
- Telemetry meter identifier: `meter_data.meter_id`
- Gateway database key: `meter_rtu.rtu_id`
- Gateway external identifier: `meter_rtu.gateway_mac`
- Gateway display identifier: `meter_rtu.gateway_sn`
- Site database key: `meter_site.site_id`
- Site legacy code: `meter_site.site_code`
- Building database key: `meter_building_table.building_id`
- Building report code: `meter_building_table.building_code`

Important compatibility note:

- Current Reports expect `meter_data.meter_id = meter_details.meter_name` and `meter_data.location = meter_building_table.building_code`.
- Dashboard currently accepts both `meter_data.meter_id = meter_details.meter_id` and `meter_data.meter_id = meter_details.meter_name` for transition compatibility.
- RTU ingestion accepts posted `location` and `meter_id` values directly, then updates related records by `site_code = location` and `meter_name = meter_id`.

Analytics must make this identifier policy explicit before implementation.

## 3. Measurement Availability

Available measured or telemetry-sourced fields:

- kWh / consumption counters: `wh_del`, `wh_rec`, `wh_net`, `wh_total`
- kW / demand markers: `max_rec_kw_dmd`, `max_del_kw_dmd`
- kVAR demand markers: `max_pos_kvar_dmd`, `max_neg_kvar_dmd`
- Voltage: `vrms_a`, `vrms_b`, `vrms_c`
- Current: `irms_a`, `irms_b`, `irms_c`
- Frequency: `freq`
- Power factor: `pf`
- Real power: `watt`
- Apparent power: `va`
- Reactive power: `var`
- Reactive energy: `varh_neg`, `varh_pos`, `varh_net`, `varh_total`
- Apparent energy: `vah_total`
- Phase angles: `v_ph_angle_a`, `v_ph_angle_b`, `v_ph_angle_c`, `i_ph_angle_a`, `i_ph_angle_b`, `i_ph_angle_c`
- Relay/genset state: `relay_status`, `genset_status`
- Firmware/revision: `soft_rev`
- Telemetry timestamp: `datetime`
- Gateway MAC: `mac_addr`

Derived metrics currently possible:

- Consumption from `wh_total` deltas.
- Demand from `wh_total` delta over elapsed minutes.
- Freshness from `last_log_update` and latest `meter_data.datetime`.
- Communication availability from expected versus present intervals.
- Load factor from average load and peak load, after demand contract is defined.
- Power factor from `pf`, if field semantics are accepted as directly measured.

Metrics not currently proven:

- Weather-normalized consumption.
- Occupancy-normalized consumption.
- Forecast values.
- Formal benchmark values.
- Cost/tariff calculations.

## 4. Grain And Time

Raw telemetry grain:

- `meter_data` stores event/interval rows with no schema-enforced grain.
- The table supports arbitrary `datetime` values.

Seed profile grain:

- Minimal profile seeds telemetry with 18 points per meter at 10-minute intervals.
- Demo profile seeds telemetry with 24 points per meter at 12-minute intervals.
- Heavy profile seeds telemetry with 12 points per meter at 8-minute intervals.

Simulator grain:

- `slow`: 30-minute steps.
- `real`: 15-minute steps.
- `fast`: 5-minute steps.
- Deterministic default anchor: `2026-07-01 08:00:00`.
- Supported scenarios: `normal`, `offline-recovery`, `report-window`.

Report aggregation grain:

- Raw report: raw rows in requested range.
- Consumption report: hourly and daily.
- Demand report: hourly and 15-minute.
- SAP report: monthly-style cutoff using current, previous, and two-month prior readings.
- Site/building report: selected range start/end readings.

Timestamp fields:

- `meter_data.datetime`: primary analytical timestamp.
- `meter_data.dt`: secondary nullable timestamp.
- `meter_data.created_at` / `updated_at`: persistence timestamps.
- `meter_details.last_log_update`: string freshness timestamp.
- `meter_rtu.last_log_update`: string freshness timestamp.
- `meter_site.last_log_update`: datetime freshness timestamp.

Time risks:

- No explicit timezone column exists.
- No gateway-time versus server-time model is persisted.
- RTU ingestion returns server time but stores posted telemetry `datetime`.
- `last_log_update` is mixed string/datetime across tables.
- Legacy default `0000-00-00 00:00:00` exists in meter/gateway freshness fields and must not be treated as a valid timestamp.
- DST and clock drift are not modeled.

## 5. Existing Aggregations

### Raw Report

Location:

- `ReportController::generateRawReport`

Aggregation:

- No aggregation. Queries `meter_data` by meter name, building code, and requested datetime range.

Implementation level:

- Controller-level query helper plus collection pagination.

Reuse recommendation:

- Useful as a raw telemetry source reference.
- Do not expose controller methods directly as analytics services.

### SAP Report

Location:

- `ReportController::generateSapRows`

Aggregation:

- Reads `wh_total` at or before monthly cutoffs.
- Applies meter multiplier.
- Calculates current and previous consumption, plus percentage difference.

Implementation level:

- Controller-level collection logic.

Reuse recommendation:

- Semantics are valuable, but should be extracted into an analytics/report calculation service only after tests protect parity.

### Site/Building Report

Location:

- `ReportController::siteReportRows`

Aggregation:

- Reads starting and ending `wh_total` values in a selected window.
- Applies meter multiplier.
- Calculates current consumption.

Implementation level:

- Controller-level query and collection logic.

Reuse recommendation:

- Good reference for building consumption summary.
- Needs explicit confidence handling for missing start/end readings.

### Consumption Report

Location:

- `ReportController::consumptionRows`

Aggregation:

- Hourly: uses 60-minute windows.
- Daily: uses 1440-minute windows.
- Finds start reading near boundary and end reading near window end.
- Calculates `kwh_total` from `wh_total` delta multiplied by meter multiplier.

Implementation level:

- Controller-level loop and helper methods.

Reuse recommendation:

- Strong candidate for first analytics data contract, but not reusable directly until moved behind a tested calculation boundary.

### Demand Report

Location:

- `ReportController::demandRows`

Aggregation:

- Hourly and 15-minute windows.
- Uses `wh_total` difference and elapsed minutes to calculate `kw_demand`.
- Applies meter multiplier.

Implementation level:

- Controller-level loop and helper methods.

Reuse recommendation:

- Strong candidate for demand analytics contract.
- Must preserve report semantics if extracted.

### Dashboard / Live Operations

Location:

- `BuildDashboardDataContractAction`

Aggregation:

- Gateway health counts by freshness thresholds.
- Meter health counts by freshness thresholds.
- Recent telemetry counts and active meter counts.
- Recent telemetry list.
- Report readiness counts from recent telemetry.
- Timeline events derived from telemetry and gateway state.

Implementation level:

- Action-level query aggregation and mapping.

Reuse recommendation:

- Useful for operational freshness and availability concepts.
- Should not become the Analytics data layer because it is optimized for current state, not historical investigation.

## 6. Data Quality / Trust

Measured:

- RTU-posted `meter_data` rows when `save_to_meter_data = 1`.
- Telemetry fields posted by devices: voltage, current, frequency, power factor, power, energy counters, phase angles, demand markers, relay/genset fields.

Calculated:

- Report consumption deltas from `wh_total`.
- Report demand values from `wh_total` differences over elapsed minutes.
- Dashboard health state from `last_log_update` thresholds.
- Report readiness counts from recent telemetry.

Estimated:

- No approved estimated analytics data exists today.
- Simulator-generated telemetry is synthetic, not estimated production data.

Incomplete:

- Rows missing matching meter/building context.
- Windows without start or end readings.
- Gaps in expected interval telemetry.
- Legacy `0000-00-00 00:00:00` freshness values.

Unknown:

- Whether a zero value means measured zero or missing/unposted field defaulted to zero.
- Timezone and clock drift behavior.
- Exact semantics of some power quality fields beyond field names and report tests.

Simulated:

- Seed profile telemetry and `camr:simulate` output.
- Useful for UI and analytics development, but must be marked as demo/development data in any analytics journey.

## 7. Gaps

Identifier gaps:

- `meter_data.meter_id` can represent numeric meter ID in older/test/dashboard-compatible paths or meter name in report-compatible paths.
- `meter_data.location` can represent site code, building code, location ID/code, or posted device location depending on source.
- There is no canonical telemetry foreign key to `meter_details.meter_id`.

Time gaps:

- No explicit timezone model.
- No explicit gateway-time/server-time distinction.
- No clock drift tracking.
- Mixed string and datetime freshness fields.

Completeness gaps:

- No expected-interval table or persistent availability rollup.
- Missing intervals must be inferred from actual rows and expected grain.
- No persistent event log for operational command results or telemetry lifecycle events.

Aggregation gaps:

- Report logic is currently controller-level.
- Report formulas are tested, but not packaged as reusable analytics services.
- No precomputed rollups for large analytical windows.

Trust gaps:

- No confidence model exists in persistence or response contracts.
- Zero default numeric fields can hide missing posted values.
- Simulated data and measured data are stored in the same table without an explicit source marker.

Power quality gaps:

- Fields exist for power factor, voltage, current, frequency, phase angles, apparent/reactive power, and demand markers.
- Business semantics and acceptable thresholds are not yet defined.

Scope gaps:

- Analytics must enforce existing site-access semantics.
- No analytics-specific scope contract exists yet.

## 8. Recommended Data Contracts

Recommended first contracts for AN-002 and follow-up work:

### `AnalyticsTelemetryPoint`

Purpose:

- Normalized point for raw telemetry display and downstream series.

Fields:

- meter key,
- meter name,
- building code,
- site ID/code,
- gateway serial/MAC,
- timestamp,
- source timestamp type,
- measurements,
- confidence,
- source lineage.

### `ConsumptionSeriesPoint`

Purpose:

- Consumption over a selected grain.

Fields:

- period start,
- period end,
- grain,
- meter/site/building context,
- kWh value,
- start reading,
- end reading,
- multiplier,
- missing interval count,
- confidence.

### `DemandSeriesPoint`

Purpose:

- Demand over hourly or 15-minute windows.

Fields:

- period start,
- period end,
- grain,
- kW demand,
- min reading,
- max reading,
- elapsed minutes,
- multiplier,
- peak marker,
- confidence.

### `BuildingConsumptionSummary`

Purpose:

- Compare buildings over a period.

Fields:

- building ID/code/name,
- site context,
- total kWh,
- peak demand if available,
- meter count,
- missing data summary,
- comparison value,
- confidence.

### `MeterProfilePoint`

Purpose:

- Load profile and meter-level investigation.

Fields:

- meter ID/name,
- timestamp,
- consumption or demand value,
- voltage/current/power factor fields where selected,
- confidence,
- missing-data flag.

### `DataTrustIndicator`

Purpose:

- Reusable trust object across analytics responses.

Fields:

- level: `Measured`, `Calculated`, `Estimated`, `Incomplete`, or `Unknown`,
- reason,
- missing interval count,
- source,
- warning message.

## 9. Recommended Next Work Item

Proceed to:

```text
AN-002 — Analytics Data Contract
```

No additional discovery step is required before AN-002, but AN-002 should stay narrow:

- define the first analytics contract around consumption telemetry,
- normalize meter/building/site identifiers,
- include confidence and missing-data summary,
- reuse report semantics without moving or changing report formulas yet.

