# Workflow: simplestats-cloud-experiment

**EXPERIMENT.** Fork of simplestats-io/app (git remote `upstream`; AGPL-3.0; Laravel 12, Inertia/Vue, Postgres, Horizon).
The question: *does SimpleStats, as released, deploy to Laravel Cloud?* If yes, the output is an install guide. If no,
the output is the **smallest diff** that makes it work, contributed back upstream as PRs, plus the guide. (Ed, 2026-10-14.)

Unlike the Campfire experiments, **minimal diff IS the goal.** We are not maintaining a fork. Every change must be
something the upstream maintainer would plausibly merge: small, general (good for every self-hoster, not Cloud-only
hacks where a general fix exists), opt-in or backwards compatible, matching upstream style.

## Branches & mode
- Our fork's `main` = upstream `main` plus this file. Never put fixes on `main`.
- Each fix: its own branch off **`upstream/main`** (so `.solo/` never leaks into an upstream PR), one concern per branch,
  pushed to `origin`. Open the PR on OUR fork for review; never on `simplestats-io/app`.
- **Opening a PR on upstream (simplestats-io/app) requires Ed's explicit OK** — it is outward-facing under our name.
- Deploys may run from an integration branch (e.g. `cloud/deploy`) that combines the release tag with the fix branches.
- Merge policy: nothing merges. Human review (Ed) decides what goes upstream.

## Hard gate
- Upstream's own CI: `.github/workflows/tests.yml` + `static-analysis.yml` (PHPStan/Larastan, Pint, Rector per composer
  `check`). Run `composer check` (or the equivalent upstream scripts) on every fix branch before handing it over.
- Behaviour that matters is LIVE behaviour on Cloud (standing order 5).

## Hard rules for this repo
- Never set a Cloud env var for a Cloud-provisioned resource (the `laravel-cloud-deploy` skill). App secrets and
  third-party keys may be set by hand. Secrets never go on disk, into git, or into brain.
- `gh` defaults to `artisan-build/simplestats-cloud-experiment` (set 2026-10-14). In a fresh clone run
  `gh repo set-default artisan-build/simplestats-cloud-experiment` first, or `gh pr create` lands on upstream.
- Do not add `.cloud/config.json` to any upstream-bound branch (it is ours, not theirs); it may live on the
  integration branch.
- Do not touch `scripts/deploy.sh` or `composer.saas.*` (the hosted SaaS's own files).
