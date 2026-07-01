# CAMR Operator Personas

## Why Personas First

UI modernization should optimize operator outcomes, not visual novelty. These personas define the expected journeys and prioritize risk-sensitive UX requirements.

## Administrator

- Primary outcomes: configure governance, entities, users, and permissions.
- Most sensitive actions: user lifecycle, scoped access, destructive edits.

### Success Criteria

- Can complete company/division/site setup quickly.
- Can grant/revoke scoped access confidently.
- Can audit role-limited visibility without confusion.

### Risks

- Permission drift can silently hide critical operations.
- Unclear action consequences in maintenance screens.

## Operations Engineer

- Primary outcomes: monitor operational health and respond to device conditions.
- Most sensitive actions: gateway status review, updates, resets, and quick data retrieval.

### Success Criteria

- Can identify offline/stale entities in one pass.
- Can trigger operational actions without ambiguity.
- Can verify that actions had visible side effects.

### Risks

- Health signals hidden behind multiple clicks.
- Update actions that do not show effective status transitions.

## Maintenance Technician

- Primary outcomes: maintain meter/gateway/site/building metadata and configuration lifecycle.
- Most sensitive actions: updates, associations, and metadata corrections.

### Success Criteria

- Can navigate site → gateway → meter paths quickly.
- Can make safe edits with clear form intent.
- Can see immediate state feedback after mutation.

### Risks

- Form complexity without visible context,
- unclear validation meaning,
- broken list actions in high-density pages.

## Analyst

- Primary outcomes: generate and export reports reliably.
- Most sensitive actions: filter correctness, export flow, file semantics.

### Success Criteria

- Can run report families with predictable defaults and feedback.
- Can export XLSX and identify output file type and naming conventions.
- Can detect empty/no-data windows quickly.

### Risks

- Misleading filters,
- export confusion due to file naming/content ambiguity,
- hidden error states for empty result ranges.

## Shared UX Requirements

All personas require:
- workflow continuity,
- clear statuses,
- concise error messages,
- predictable navigation,
- minimal mode changes between tasks.

## Phase 0 Persona Coverage Baseline

- Minimal profile must expose each persona’s minimum operational environment.
- Demo profile should expose at least one representative path per persona.
- Heavy profile should validate scalability for all personas’ high-volume contexts.
