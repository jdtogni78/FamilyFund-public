# FamilyFund — secret-rotation RUNBOOK (repeatable)

A standing, repeatable procedure for rotating FamilyFund's local **dev/stage**
secrets. Distinct from `SECRET-ROTATION-PLAN.md` (the one-off 2026-05-22 leak
incident). Run this on a cadence and after any suspected exposure.

> **No secret values in this file.** New secrets go ONLY into git-ignored files
> (`app1/.env`, `app1/family-fund-app/.env.dev` / `.env.stage`) — never a tracked file.

## Cadence
- **Dev/stage:** rotate **quarterly** (and immediately after any leak/role change).
- **Prod (on spirit, REDACTED_PROD_HOST):** rotate on its own change-managed window —
  **analyze-only from here**, never auto-rotated. See "Prod" below.
- Cheap to repeat once automated (see Automation roadmap), so prefer more often.

## What gets rotated (dev/stage)
| Secret | Lives in | Rotate how |
|---|---|---|
| `APP_KEY` | git-ignored `.env.dev` (via `.env` symlink) + per-pool `.env.<slot>` | `php artisan key:generate` in the app container |
| DB root pw (`root@familyfund_dev`) | git-ignored `app1/.env` (`FF_DB_ROOT_PASSWORD`/`FF_DB_PASSWORD`); `ALTER`'d on the live DB | `ALTER USER` + recreate consumers |
| `famfun_dev` pw | `app1/.env` (`FF_DB_USER_PASSWORD`) | `ALTER USER` (unused by app; low priority) |
| `REDIS_PASSWORD`, `MAIL_PASSWORD` | `.env.dev` | rewrite value (no live consumer in dev: cache=file, mail=Mailpit) |

Skip (placeholders/unused in dev): `AWS_*`, `PUSHER_APP_SECRET`, `MIX_PUSHER_*`.

## Pre-flight (do this FIRST — coordination is the #1 footgun)
1. **`docker` isn't on the sandbox PATH** — use `/usr/local/bin/docker`, and when
   invoking project scripts prepend `PATH=/usr/local/bin:$PATH`.
2. **Find every live consumer of the shared dev DB** (`app1-mariadb-1`, publishes
   host `:3306`). Apps connect as `root`:
   - `app1-familyfund-1` (mounts the **main repo**; serves host **:3001**, not 3000).
   - Preview pools: `~/.familyfund-pool/pool.sh list` → any `leased` slot
     (`familyfund-poolN`) reaches the DB via `host.docker.internal:3306`.
   - These may be **other people's live sessions** — coordinate before breaking them.
3. **Testpool is SEPARATE** (`db-testpool`, host `:3315`, DBs `familyfund_testN`).
   Check `~/.familyfund-pool/testpool.sh list`. **Do NOT rotate the testpool DB while
   any `testN` slot is leased** — it breaks the active lease (learned the hard way).
4. **Encrypted-column safety for APP_KEY:** rotating `APP_KEY` orphans `encrypted`
   casts. Check the dev DB:
   `SELECT COUNT(two_factor_secret) FROM users;` and whether `MAIL_PASSWORD_ENCRYPTED`
   is set. If both are empty/0 → APP_KEY rotation is clean. Otherwise null the 2FA
   columns (forces re-enroll) and decrypt-then-re-encrypt the mail password.

## Procedure (dev) — run in the MAIN repo `/Users/claudio1/dev/FamilyFund`
> The app container mounts the **main repo**, so the rotation must run there
> (a branch off `main`), not in a worktree.

```sh
D=/usr/local/bin/docker
cd /Users/claudio1/dev/FamilyFund
git switch -c secret-rotation-$(date +%Y%m%d)
```

### A. APP_KEY + repo hygiene (no DB downtime)
1. Back up the OLD secrets off-repo (perms 600), e.g. `~/familyfund-secret-rotation-backup/<date>/`.
2. If `.env.dev` is tracked: `git rm --cached app1/family-fund-app/.env.dev` (keep on
   disk — it's the git-ignored dev/pool/test template). Ensure a tracked secret-free
   `app1/family-fund-app/.env.example` exists (the only un-ignored env file).
3. `D exec app1-familyfund-1 php artisan key:generate --force` (writes via the
   `.env -> .env.dev` symlink into the now git-ignored file).
4. Rewrite `REDIS_PASSWORD`/`MAIL_PASSWORD` in `.env.dev` (random; no consumer).
5. `D exec app1-mariadb-1 ... TRUNCATE sessions; TRUNCATE password_resets;`
6. `D exec app1-familyfund-1 php artisan optimize:clear && D restart app1-familyfund-1`
7. Verify: `curl -s -o /dev/null -w '%{http_code}' http://localhost:3001/login` → 200.

### B. DB password (HAS downtime for app1 + pools — keep value out of git)
1. Generate a strong value; put it ONLY in git-ignored `app1/.env`:
   `FF_DB_ROOT_PASSWORD=…`, `FF_DB_PASSWORD=…` (apps connect as root, so this == root pw),
   `FF_DB_USER_PASSWORD=…`. docker-compose auto-reads `app1/.env` for `${VAR}`.
2. **De-hardcode** the literal from the 4 tracked compose files (one-time; already done):
   `docker-compose.{env,acl,dev,dev2}.yml` use `${FF_DB_*:-changeme}`. Verify none
   re-introduce a literal: `git grep -nE '=(123456|1234)$' -- ':!docs/'` must be empty.
3. Mirror the value into git-ignored `.env.dev`/`.env.stage` `DB_PASSWORD` and into the
   off-repo tooling literals: `~/.familyfund-pool/pool.sh` (the `DB_PASSWORD=` echo).
   **Leave `testpool.sh` for the testpool-specific rotation** (see below).
4. Pre-edit each LIVE pool's generated override `DB_PASSWORD=` →
   `<worktree>/app1/docker-compose.pool<N>.local.yml`.
5. **Switch (minimize the window):**
   - `D exec app1-mariadb-1 mariadb -uroot -p"$OLD" -e "ALTER USER IF EXISTS 'root'@'%' IDENTIFIED BY '$NEW'; ALTER USER IF EXISTS 'root'@'localhost' IDENTIFIED BY '$NEW'; FLUSH PRIVILEGES;"`
     (the compose `MARIADB_ROOT_PASSWORD` is ignored on an existing datadir — `ALTER` is
     what changes the live grant). Rotate `famfun_dev` likewise.
   - Recreate **only the app**: `D compose -f docker-compose.yml -f docker-compose.dev.yml up -d --no-deps --force-recreate familyfund`.
   - Recreate each pool **with its own launcher** (NOT bare `docker compose`, which
     resolves `container_name` to `familyfund-dev` and conflicts):
     `cd <worktree>/app1 && PATH=/usr/local/bin:$PATH ./launch_docker.sh pool<N> -f docker-compose.pool<N>.local.yml up -d --no-deps familyfund`
6. Verify each stack: container `DB_PASSWORD` sha matches; `php artisan tinker --execute='DB::select("select 1")'` → ok; `/login` 200 on :3001 / :3020 / :3021.
7. Confirm the old pw is rejected: `mariadb -uroot -p"$OLD"` → Access denied.

### C. Testpool DB (only when ALL `testN` slots are free)
`ALTER` root on `db-testpool` to the new value AND update `~/.familyfund-pool/testpool.sh`
`DB_PASS=` together. Skipping the lease check breaks any active test slot.

### D. Land it
`git commit` (pre-commit guard blocks env files / `APP_KEY=base64:` / dumps), then
`git switch main && git merge --ff-only secret-rotation-<date> && git push origin main`.
Update the off-repo backup with the NEW values. Tick this run in a dated log.

## Verification checklist
- [ ] `/login` 200 on every live stack (:3001, leased pools, leased test slots).
- [ ] Old DB pw rejected; new pw authenticates.
- [ ] `git grep -nE 'APP_KEY=base64:[A-Za-z0-9+/=]{20}|=(123456|1234)$' -- ':!docs/'` empty.
- [ ] `.env.dev` not tracked; `app1/.env` git-ignored and present.

## Gotchas (learned 2026-05-22)
- **`app1/.env` is now required** for `docker compose up` in the main repo — without it
  compose substitutes `changeme` and the DB connection fails. A fresh clone/worktree
  must recreate it from the off-repo backup.
- Running containers read `DB_PASSWORD` from **compose-injected env**, not `.env` — so
  you must recreate them, not just edit files.
- Pools recreate ONLY via `./launch_docker.sh <slot>` (sets `FF_NICKNAME`+ports).
- zsh: `status` is read-only — don't use it as a loop var in scripts.

## Rollback
Old values are in `~/familyfund-secret-rotation-backup/<date>/*.SECRET.txt` (600).
Re-`ALTER` the DB back, restore `app1/.env`, `git revert` the commits if needed.

## Prod (analyze-only from here)
Never rotate prod from a dev session. The only standing prod task is the read-only
check that prod's `APP_KEY` ≠ a leaked dev key (needs trusted SSH to spirit):
`ssh jdtogni@REDACTED_PROD_HOST "grep '^APP_KEY=' ~/dev/FamilyFund/app1/family-fund-app/.env"`
then compare. If it ever matches, schedule a real prod rotation window.

## Automation roadmap (eventual)
Goal: a single `app1/family-fund-app/bin/rotate-dev-secrets.sh` that runs A–C
non-interactively with the safety rails above. Build it incrementally:
1. **Pre-flight gate** — refuse to run if a `poolN`/`testN` slot is leased by another
   host/session (parse `pool.sh list` / `testpool.sh list`); print who holds what.
2. **APP_KEY step** — fully scriptable today (steps A1–A7); lowest risk → ship first.
3. **DB step** — generate value → write `app1/.env` → `ALTER` → recreate app + iterate
   leased pools via their launchers → verify each. Driven off the live lease table so it
   only touches stacks it can safely cycle.
4. **Verification harness** — assert `/login` 200 + old-pw-rejected + `git grep` clean;
   non-zero exit on any failure.
5. **CI guardrails** (already partly in place): keep the `.git/hooks/pre-commit` secret
   guard; add a CI `gitleaks` scan so a re-hardcoded secret fails the build.
6. **Scheduling** — once the script is trusted, drive it on the quarterly cadence (e.g.
   a `/schedule` routine or cron) that opens a branch, rotates dev, and reports.
What stays manual: prod rotation, git **history purge**, and SSH host-key trust.
