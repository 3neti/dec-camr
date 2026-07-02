# Analytics Grammar

## Purpose

This document defines relationships between analytics concepts. It is not a flat glossary. The goal is to establish how analytical ideas connect before designing charts or screens.

## Core Chain

```text
Meter Reading
↓
Consumption
↓
Load Profile
↓
Demand
↓
Tariff
↓
Cost
↓
Benchmark
↓
Forecast
```

Each step depends on the trustworthiness and grain of the previous step.

## Consumption

Consumption is energy used over a period. It is usually derived from interval readings or counter differences.

Consumption depends on:

- meter identity,
- time window,
- aggregation grain,
- meter multiplier,
- missing interval handling,
- boundary readings.

Consumption leads to:

- usage trends,
- building comparison,
- baseline comparison,
- cost context.

## Load Profile

Load Profile describes the shape of consumption or demand over time. It helps users understand behavior, not only totals.

Load Profile depends on:

- interval grain,
- reliable timestamps,
- complete enough data,
- chosen aggregation.

Load Profile leads to:

- peak detection,
- valley detection,
- schedule review,
- equipment behavior inference.

## Demand

Demand represents the power requirement over an interval or the maximum value within a window. It explains stress and tariff impact more directly than total consumption.

Demand depends on:

- interval duration,
- peak calculation rule,
- demand window alignment,
- meter and site context.

Demand leads to:

- peak analysis,
- tariff investigation,
- capacity review,
- demand management.

## Peak And Valley

Peak is the highest point in a selected context. Valley is the lowest meaningful point.

Peak and valley depend on:

- selected metric,
- aggregation grain,
- time window,
- whether missing data exists.

Peak and valley lead to:

- abnormal behavior review,
- operational scheduling decisions,
- demand charge explanation.

## Baseline

Baseline is a reference pattern used to compare current behavior against expected behavior.

Baseline depends on:

- chosen historical period,
- normalization rules,
- exclusions,
- data confidence.

Baseline leads to:

- variance,
- savings estimates,
- performance tracking.

Baseline is a future concept until approved implementation defines calculation rules.

## Power Factor

Power Factor describes the relationship between real power and apparent power. It is an engineering and cost signal.

Power Factor depends on:

- available telemetry fields,
- aggregation rules,
- data quality,
- meter capability.

Power Factor leads to:

- engineering investigation,
- equipment review,
- penalty or tariff analysis.

## Load Factor

Load Factor compares average load to peak load over a period.

Load Factor depends on:

- consumption or average demand,
- peak demand,
- consistent time window,
- complete enough data.

Load Factor leads to:

- efficiency review,
- capacity utilization,
- schedule optimization.

## Aggregation

Aggregation changes raw or interval data into a higher-level view.

Common aggregations:

- sum for consumption,
- average for representative operating values,
- maximum for peaks,
- minimum for valleys,
- count for availability or communication,
- ratio for factors.

Aggregation must never hide missing data without indicating confidence.

## Normalization

Normalization adjusts data so comparisons become fairer.

Potential normalizers:

- operating hours,
- floor area,
- occupancy,
- production volume,
- weather.

Normalization is a future concept until source data and rules are approved.

## Variance

Variance is the difference between actual and comparison reference.

Comparison references:

- previous day,
- previous week,
- same weekday,
- baseline,
- forecast,
- peer building.

Variance leads to investigation only when the confidence level supports it.

## Benchmark

Benchmark compares one entity against another or against a standard.

Benchmark depends on:

- comparable entities,
- normalized context,
- consistent data grain,
- clear confidence.

Benchmark is a future concept until approved comparison rules exist.

## Forecast

Forecast projects future behavior from historical patterns.

Forecast depends on:

- sufficient history,
- stable patterns,
- approved model assumptions,
- visible uncertainty.

Forecast is a future concept and must not appear as authoritative prediction until accepted.

