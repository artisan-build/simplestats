# Workflow: artisan-build/simplestats (maintained fork)

Fork of **simplestats-io/app** (git remote `upstream`; AGPL-3.0; Laravel 12, Inertia/Vue, Postgres, Horizon).

**This fork's job: be the branch Scalpels one-click installs SimpleStats from.** `main` is the installable
branch. It must deploy on Laravel Cloud with Cloud's stock build command and nothing fork-specific in it.
(Ed, 2026-10-14. The repo was renamed from `simplestats-cloud-experiment` on the same day; the local folder
may still carry the old name.)

## The one rule everything else serves

**Zero diff from upstream outside `.solo/`.** That is the goal state, and we are measured against it:

```
git fetch upstream
git diff upstream/main main --stat -- ':!.solo'
```

The output of that command must equal **exactly** the union of the carried-patch diffs in the table below —
no more, no less. When the table is empty, that command must print nothing at all. `.solo/` is the only
directory this fork is allowed to own, because it never ships to a running app.

Prove it mechanically rather than by eye. Cherry-pick the carried branches onto `upstream/main` in a scratch
worktree and compare trees; identical trees outside `.solo/` is the proof, a plausible-looking `--stat` is not:

```
git worktree add -b verify/union <scratch> upstream/main
for b in <carried branches>; do git -C <scratch> cherry-pick "origin/$b"; done
git diff verify/union main -- ':!.solo'      # must be empty
```

## Carried patches

Patches live here **temporarily**, only while the identical fix is open upstream. Each is carried as its own
`--no-ff` merge of its own branch, so it stays one identifiable, droppable unit in `main`'s history.

| Branch | Upstream PR | What it fixes | Drop when |
|---|---|---|---|
| `fix/storage-skeleton` | simplestats-io/app#383 | Keeps `storage/` in the repo so a stock build command needs no `mkdir` | #383 merged upstream |
| `fix/cache-store-env` | simplestats-io/app#384 | Reads the cache store from `CACHE_STORE` as well as `CACHE_DRIVER` | #384 merged upstream |
| `fix/health-check-columns` | simplestats-io/app#385 | Adds the missing `check_name` / `check_label` health-history columns | #385 merged upstream |
| `fix/app-title-undefined-env` | simplestats-io/app#386 | Stops page titles rendering as `undefined SimpleStats` without `VITE_APP_ENV` | #386 merged upstream |
| `fix/import-disk-shared` | simplestats-io/app#387 | Hands data-import uploads to the queue worker on a shared disk | #387 merged upstream |
| `fix/horizon-health-check` | simplestats-io/app#388 | Registers the Horizon health check only when the queue runs on Horizon | #388 merged upstream |
| `fix/backups-opt-in-on-cloud` | simplestats-io/app#389 | Lets backups default to off where the platform already backs the database up | #389 merged upstream |
| `fix/first-user-admin` | simplestats-io/app#390 | Makes the first user created by `app:create-new-user` an admin | #390 merged upstream |

`#387` and `#389` both add an identical `app/Helpers/StorageHelper.php`. Identical content merges without a
conflict; do not "resolve" it by editing the file.

## Sync procedure

Run this whenever upstream moves. Never force-push `main`.

1. `git fetch upstream && git fetch origin`
2. Merge upstream into `main` with an ordinary merge commit: `git merge upstream/main`. No rebase, no
   force-push — `main` is what Scalpels installs from, so its history stays append-only.
3. For each row in the table, check whether upstream has merged it. The test is the diff, not the PR page:
   `git diff upstream/main main -- ':!.solo'` no longer contains that patch's changes once upstream carries
   them (the merge in step 2 collapses them away).
4. For every row that has dropped out of the diff: delete the row from the table, and say so in the commit.
   Do **not** delete the fix branch — standing orders forbid deleting branches, and the branch is the record.
5. Re-run the acceptance check at the top of this file. Diff outside `.solo/` == the remaining rows, exactly.
6. If a carried patch now **conflicts** with upstream (upstream fixed the same thing differently), stop and
   report to Ed. Upstream's version wins; the carried patch gets dropped, not reconciled by hand.

## Fork-only code is not allowed

No feature, config default, helper or workaround may exist on this fork that is not either (a) `.solo/`, or
(b) a patch open upstream and listed in the table above. If something seems to need a fork-only change,
**it goes to Ed** — it is a decision about what we maintain, not a coding problem. The fix for "Cloud needs X"
is a general upstream patch every self-hoster benefits from, or it is a Scalpels-side listing change, or it is
nothing.

Corollary: changes we want start as a branch off **`upstream/main`** (so `.solo/` never leaks into an upstream
diff), one concern per branch, pushed to `origin`.

## Hard rules

- **Nothing is opened, pushed or commented on `simplestats-io/app` without Ed's explicit OK.** It is
  outward-facing under our name. `gh` defaults to `artisan-build/simplestats` (set 2026-10-14); in a fresh
  clone run `gh repo set-default artisan-build/simplestats` or `gh pr create` lands on upstream.
- **Never force-push `main`**, and never delete a branch.
- **Never set a Cloud env var for a Cloud-provisioned resource** — that includes connection selectors
  (`DB_CONNECTION`, `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER`, …) and not just credentials. Cloud
  injects all of it into its own managed env file; anything you set by hand shadows the injected value and
  breaks the resource. See the `laravel-cloud-deploy` skill. App secrets and third-party keys may be set by hand.
- **Never ask an installer to set an environment variable.** Every value needs a working default (standing
  order 34). An env var the app cannot start without is a defect to fix upstream, not a line in an install guide.
- **Do not touch `scripts/deploy.sh` or `composer.saas.*`** — those are the hosted SaaS's own files.
- **Do not commit `.cloud/config.json`** or any Cloud/Scalpels-side config to this repo. It is ours, not
  upstream's, and it is not `.solo/`.
- Secrets never go on disk, into git, or into brain.

## Gate

- `composer check` (Pint, PHPStan, Rector, Pest Arch) plus the full suite: `composer test`.
- Upstream's own CI (`.github/workflows/tests.yml`, `static-analysis.yml`) must be green on `main`.
- Behaviour that matters is **live** behaviour on Cloud (standing order 5). A green suite proves the carried
  patches did not break upstream; it does not prove the install works.
