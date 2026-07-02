# Analytics Journeys

## Purpose

Analytics journeys are investigations, not pages. Each journey starts with a question and ends with evidence, a decision, and optionally an export or shared summary.

## Journey 1: Abnormal Consumption

Trigger:

- A building consumed 18% more yesterday than its recent baseline.

Question:

- What caused the increase?

Drill-down:

- Compare yesterday against previous comparable days.
- Inspect hourly load profile.
- Identify meters or time windows driving variance.
- Check missing data confidence.

Evidence:

- consumption delta,
- meter contribution,
- time-of-day pattern,
- confidence level.

Decision:

- accept as operationally expected,
- escalate to maintenance,
- adjust schedule,
- monitor next period.

Export/share outcome:

- share investigation summary with building administrator or plant manager.

## Journey 2: Building Comparison

Trigger:

- Energy Manager wants to compare buildings for the same period.

Question:

- Which building is consuming more, and is the comparison fair?

Drill-down:

- Select buildings.
- Choose time range and grain.
- Compare consumption, demand, and load factor.
- Review missing-data confidence.

Evidence:

- ranked comparison,
- normalized metric if approved,
- peak periods,
- completeness indicator.

Decision:

- identify priority building,
- request engineering review,
- defer if comparison confidence is low.

Export/share outcome:

- export comparison grid or summary.

## Journey 3: Tariff Investigation

Trigger:

- Finance sees unexpected cost increase.

Question:

- Did demand or consumption drive the increase?

Drill-down:

- Review peak demand period.
- Compare demand against previous billing periods.
- Inspect demand curve.
- Identify coincident meter behavior.

Evidence:

- peak demand,
- peak timestamp,
- consumption total,
- demand contribution.

Decision:

- explain billing movement,
- recommend tariff review,
- recommend peak management action.

Export/share outcome:

- finance-ready explanation with supporting data.

## Journey 4: Load Profile Inspection

Trigger:

- Engineering suspects abnormal equipment behavior.

Question:

- Does the load shape indicate abnormal cycling or sustained load?

Drill-down:

- Select meter/building.
- Inspect 15-minute or hourly profile.
- Overlay previous comparable period.
- Mark peaks, valleys, and missing intervals.

Evidence:

- profile shape,
- peak duration,
- repeated pattern,
- confidence markers.

Decision:

- investigate equipment,
- validate operating schedule,
- accept as normal behavior.

Export/share outcome:

- image/export of profile with notes.

## Journey 5: Outage Analysis

Trigger:

- Telemetry gap or outage is detected.

Question:

- Was this an energy event, communication failure, or missing data?

Drill-down:

- Review gateway communication.
- Inspect meter readings around outage.
- Compare affected and unaffected meters.
- Check confidence classification.

Evidence:

- missing intervals,
- gateway freshness,
- meter availability,
- surrounding readings.

Decision:

- classify as communication issue,
- classify as likely operational outage,
- request field validation.

Export/share outcome:

- incident evidence package.

## Journey 6: Missing Telemetry Review

Trigger:

- Analyst notices incomplete report or low confidence.

Question:

- Which data is missing, and does it change the conclusion?

Drill-down:

- Inspect completeness by meter and interval.
- Identify affected metrics.
- Compare measured versus incomplete periods.

Evidence:

- missing interval count,
- affected meters,
- impacted calculations.

Decision:

- continue with caveat,
- rerun after data recovery,
- escalate device issue.

Export/share outcome:

- data quality note for report or audit.

## Journey 7: Executive Review

Trigger:

- Monthly executive review.

Question:

- Are energy use, demand, and efficiency improving?

Drill-down:

- Review headline trend.
- Inspect top movers.
- View high-confidence opportunities.
- Avoid operational noise unless it changes the conclusion.

Evidence:

- month-over-month trend,
- top buildings,
- peak movement,
- confidence summary.

Decision:

- approve action plan,
- request deeper investigation,
- accept current posture.

Export/share outcome:

- executive summary snapshot.

