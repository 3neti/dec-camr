# CAMR Lifecycle Scenario Runner

## Purpose

The lifecycle scenario runner provides a small orchestration entrypoint for repeating operator-environment setups.

It coordinates existing profile seeds and telemetry simulation without introducing new domain behavior.

## Command

Use:

```bash
php artisan camr:scenario {scenario-key}
```

Options:

- `--list` list all registered scenarios
- `--dry-run` validate and print execution plan without mutating the database
- `--no-seed` skip the seed step
- `--no-simulate` skip the simulator step
- `--anchor="YYYY-MM-DD HH:MM:SS"` pass deterministic anchor to simulator
- `--allow-production` opt into running destructive operations in production

Examples:

```bash
php artisan camr:scenario --list
php artisan camr:scenario operations-gateway-recovery --dry-run
php artisan camr:scenario analyst-report-export --dry-run --anchor="2026-07-01 08:00:00"
php artisan camr:scenario fresh-install-smoke --no-simulate
php artisan camr:scenario bogus --dry-run
```

## Scenario Catalog

- `admin-provisioning`
- `operations-gateway-recovery`
- `maintenance-meter-update`
- `analyst-report-export`
- `fresh-install-smoke`
- `heavy-data-readiness`

## Safety

- By default scenario execution can use seed and simulator steps.
- `--dry-run` skips mutating seed/simulate steps.
- Production is blocked unless `--allow-production` is provided.
- Invalid scenario keys fail fast with supported list.
- Invalid anchors fail fast with strict format validation.

## Deterministic behavior

- Default deterministic behavior is provided by the simulator configuration.
- Scenarios may define `deterministic_anchor`.
- Explicit `--anchor` is always respected.

## Relationship to Operator Journeys

Use this command as the canonical setup for future journey execution.
Typical sequence:

1. `php artisan camr:scenario --list`
2. `php artisan camr:scenario <scenario>`
3. run the matching journey or demo script

This keeps operator scenarios repeatable and easier to review.

