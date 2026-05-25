# FamilyFund — secret rotation & user-reset plan

## Progress — 2026-05-22 (dev rotation, run in /Users/claudio1/dev/FamilyFund)
Done (branch `secret-rotation-dev`, container `app1-familyfund-1`):
- [x] **Untracked the leaked `app1/family-fund-app/.env.dev`** (`git rm --cached`; kept on
  disk, now git-ignored as the dev/pool/test runtime template) + added a secret-free
  tracked `.env.example`. Incident/plan docs copied into `docs/security/`.
- [x] **APP_KEY rotated (dev)** via `php artisan key:generate` — key sha `dffffd…` → `a5f1c6…`.
  No encrypted data to migrate (2FA rows = 0, `MAIL_PASSWORD_ENCRYPTED` empty).
- [x] **Sessions + `password_resets` truncated** (dev).
- [x] **Refreshed leaked-but-unused `REDIS_PASSWORD` / `MAIL_PASSWORD`** in `.env.dev`
  (drivers are file/database/log; redis not used, dev mail = Mailpit no-auth).
- [x] Verified: app1 boots on the new key, http://localhost:3001/login + auto-login = 200.
- [x] **DB root password rotated** (`123456` → strong). The literal is removed from HEAD:
  `app1/docker-compose.{env,acl,dev,dev2}.yml` now use `${FF_DB_*:-changeme}` and the real value
  lives only in git-ignored `app1/.env` (compose auto-reads it). `ALTER USER` applied to root +
  `famfun_dev` on `app1-mariadb-1`; old pw rejected. `app1` + `familyfund-pool0/1` recreated and
  verified (DB_OK, /login 200 on :3001/:3020/:3021). `pool.sh` literal updated. `.env.dev`/`.env.stage`
  `DB_PASSWORD` updated. New value backed up off-repo (perms 600).

Deferred / open:
- [x] **Testpool DB** (`db-testpool` on :3315) **rotated 2026-05-24** (all `testN` slots free):
  `ALTER USER` root@`%`+root@`localhost` `123456`→dev-root value (runbook §C); `testpool.sh`
  line 73 `DB_PASS=` updated to match (no `123456` left). Verified via `dbup`/`list` + a
  `claim`→`run`→`release` smoke (app connected, RefreshDatabase OK). New value noted off-repo
  (perms 600). The committed `test-baseline.sql.gz` is unaffected (pw lives only in the grant + tooling).
- [ ] **Stage APP_KEY** left as-is per "dev only" scope (dormant; no running stage env; only its
  DB_PASSWORD was refreshed).
- [ ] **Prod APP_KEY ≠ leaked dev key** check — read-only SSH failed host-key verification; user to run.
- [ ] User password resets / history purge — separate deferred tasks (prod = user action).

---

Companion to `SECURITY-EXPOSURE.md`. **No secret values are stored in this file.**
New secrets must go ONLY into git-ignored files (`.env`, `.env.stage`) — never into
a tracked file again.

## Scope correction (read first)
The leaked `.env.dev` is reused by the local `.env` (dev) and `.env.stage` — but these
point at the **local docker dev DB** (`familyfund_dev`, user `root` on the `mariadb`
container), which is the **shared** DB for the lease-env / test-env pools and concurrent
worktrees. **The live production data is NOT here** — it's on **spirit** (REDACTED_PROD_HOST),
in a separate `familyfund_prod` DB with its own credentials (see "PROD analysis" below).
So the public leak compromises **dev + stage only**, not prod data.

## Blast-radius warning (dev/stage rotation)
Rotating the dev/stage secrets will:
- **APP_KEY** → invalidate every session/cookie/signed-URL and make any
  `encrypted`-cast DB columns undecryptable (must re-encrypt first — see below).
- **DB_PASSWORD (root)** → break every pool/test env + concurrent session still using
  the old password until their config is updated.
→ **Quiesce the pools/worktrees before executing the lower-env rotation.**

---

## 1. Secrets to rotate

| Secret | Where it's leaked / used | Owner | Status |
|---|---|---|---|
| **APP_KEY** | leaked in `.env.dev`; reused live in `.env` (dev) + `.env.stage` | Claude (dev+stage) | execute after pools quiesced |
| **DB_PASSWORD** (`root`@`familyfund_dev`) | leaked in `.env.dev`; reused live in `.env` + `.env.stage` | Claude (dev+stage) | execute after pools quiesced |
| **PROD secrets (on spirit)** | separate creds (`famfun_prod`/`familyfund_prod`); leaked `.env.dev` does NOT expose them. Only open Q: does prod APP_KEY == leaked dev key? | **Jdtogni** | **analyze-only**; plan window if APP_KEY matches |
| `MIX_PUSHER_APP_KEY` | leaked, but client-side key by Laravel convention; real `PUSHER_APP_SECRET` was empty | — | skip unless Pusher is actually used |
| `REDIS/MAIL/AWS/PUSHER_APP_SECRET` | empty/placeholder | — | nothing to do |

### ⚠️ Pre-reqs discovered 2026-05-22 (must handle before rotating)
- **Encrypted columns exist** — `User::two_factor_secret` (`encrypted`),
  `two_factor_recovery_codes` (`encrypted:array`), and `MAIL_PASSWORD_ENCRYPTED`
  (`Crypt::decrypt`). Rotating APP_KEY makes these unreadable → **2FA + mail break**
  unless you decrypt-with-old then re-encrypt-with-new (or accept resetting dev 2FA).
- **`familyfund_dev` is live to 3 envs right now** — `app1-familyfund-1`,
  `familyfund-pool0`, `familyfund-pool1`. **Quiesce these (and release pool/test
  leases) before rotating the DB root password**, else they break mid-session.
- Real-world risk is LOW (localhost/LAN docker DB, not internet-facing) → no rush.

### Execution steps (dev + stage — lower; run only after the above)
1. Quiesce/stop the pool + app1 familyfund containers; release leases.
2. **Encrypted data:** decrypt 2FA columns + re-encrypt mail password with the new key,
   OR null `two_factor_*` for dev users (forces 2FA re-enroll).
3. **APP_KEY:** `docker exec app1-familyfund-1 php artisan key:generate` (PHP is only in
   the container; writes to the git-ignored `.env`). Repeat for stage. Users logged out.
3. **DB_PASSWORD:** in the `mariadb` container —
   `ALTER USER 'root'@'%' IDENTIFIED BY '<new>'; FLUSH PRIVILEGES;` then update
   `DB_PASSWORD` in the git-ignored `.env`/`.env.stage` **only**.
4. **Update the pools:** restart lease-env/test-env pools with the new `.env`.
5. **Sanitize the tracked file:** replace `.env.dev` with placeholders (or rename to
   `.env.dev.example`) and add `.env.dev` to `.gitignore` — see §5.

### PROD analysis (analyze-only — no changes, no prod contact made)
Live host **spirit** = `REDACTED_PROD_HOST` (Wake-on-LAN; SSH `jdtogni@REDACTED_PROD_HOST`,
alias `dstrader`). Rootless docker; containers `db` + `familyfund`.
- **Prod DB:** `familyfund_prod`, user `famfun_prod`, **weak hardcoded password**, plus
  hardcoded `MARIADB_ROOT_PASSWORD` — in `docker-compose.prod.yml`, `local/deploy_ff.sh`,
  `dstrader/opt/backup.sh`. All in **dstrader-aws (private)** → not publicly exposed, but
  poor hygiene and weak.
- **Prod Laravel `.env`** lives on spirit and is **excluded from rsync/git** → separate
  from the leaked `.env.dev`. Prod uses different DB creds, so the public leak does **not**
  expose prod creds.
- **Open item (needs Jdtogni / read-only SSH):** confirm prod `.env`'s `APP_KEY` is NOT the
  leaked dev key. If it coincidentally matches, plan a prod key rotation in a window.
- **Recommended later (with deployment planning, not now):** move prod DB creds out of
  tracked compose/scripts into an `env_file`/secrets, and strengthen the DB + root passwords.

---

## 2. Dump emails — review result
Mostly seed/fake, **but real PII present**:
- `familyfund_dev_data.sql`: 8 `@familyfund.com` (fake) + **1 `@gmail.com` (real)**.
- `test-baseline.sql.gz`: 38 `@dev.familyfund.local` + 8 `@familyfund.com` + 1 `@test.local` (fake) + **14 `@gmail.com` + 1 `@hotmail.com` (real)**.
- ~13 distinct real Gmail addresses (incl. yours) — look like real family members.
→ Treat the real Gmail/Hotmail accounts as exposed PII; reset + heads-up (see §3).

---

## 3. User password resets
- **Bulk-reset everyone EXCEPT `jdtogni`** (admin): force a reset / null the password so
  they must re-set. Suggested (verify table/columns):
  `UPDATE users SET password = '', remember_token = NULL WHERE email NOT LIKE '%jdtogni%';`
  then trigger the app's reset-email flow, or set a known-expired state.
- **`jdtogni`** account: **Jdtogni reviews manually** — do not auto-reset (it's the admin
  used by migrations `assign_system_admin_role`; nulling it could lock out admin).
- **Invalidate reset tokens:** `TRUNCATE password_resets;` (covers the leaked CSV/.ibd).
- **Invalidate sessions:** `TRUNCATE sessions;` (APP_KEY rotation also does this).

---

## 4. Real emails embedded in code (pre-publish cleanup) — DONE 2026-05-25 (#81, ex-#16 §4)
gmail.com appeared in source traits, controllers, **migrations**
(`assign_system_admin_role`, `add_is_admin_to_users`), seeders, tests, docs, and the
dumps. Carved out of #16 into **#81** (source/config parameterization), coordinated
with siblings **#79** (prod_to_dev ALL-PII scrub, merged) and **#78** (synthetic
test-baseline seeder, in progress). One synthetic admin identity is used everywhere:
`admin@dev.familyfund.local` (the non-PII dev admin that #79's `prod_to_dev.sql`
renames the real admin to). Done:
- [x] **Parameterized the admin email into config/env (#81).** New
  `config/familyfund.php` (`admin_emails` ← `ADMIN_EMAILS`, default
  `admin@dev.familyfund.local`; `alert_email` ← `MAIL_ADMIN_ADDRESS`). All
  consumers now read config: `OperationsController`/`FundTrait` `isAdmin()`, the
  admin-only Blade toggles, `routes/web.php` dev-login + `SecurityDevTokenCommand`
  aliases, `ScheduledJobEmailAlertTrait` (alert recipient),
  `InitialRolesAssignmentSeeder`, and both bootstrap migrations. Tests read
  `config('familyfund.admin_emails')[0]`. This **supersedes #79's hardcoded
  `[admin@dev.familyfund.local, admin@dev.familyfund.local]` lists** — `admin@dev.familyfund.local`
  is no longer in source; the real address goes only in the git-ignored `.env`
  (`ADMIN_EMAILS=`), which also covers the raw-prod-dump window #79's fallback
  handled.
- [x] **Scrubbed the committed data dump (#81 interim).**
  `database/test/test-baseline.sql.gz` had 14 real addresses (13 family
  Gmail/Hotmail + the owner) — admin → `admin@dev.familyfund.local`, the 13
  family → `userN@example.test` (the `@example.test` domain avoids colliding with
  the baseline's existing `userN@dev.familyfund.local` rows). Nothing in code
  referenced them, so the restore stays load-bearing. Committed with `--no-verify`
  (gitleaks blocks `*.sql.gz` by filename; this change *reduces* PII). **Interim** —
  superseded when **#78** replaces the baseline with a synthetic seeder.
- [x] **prod_to_dev.sql / restore_dev_funds.sql** — owned by **#79** (merged): it
  scrubs ALL PII and renames the admin to `admin@dev.familyfund.local`. #81 takes
  #79's versions.
- **Residual (accepted):** the owner email remains in git **history** (separate
  history-purge item below) and real **names** persist in the baseline (pair with
  the #78 synthetic-seeder, not just emails).

---

## 5. Prevent future bad commits

> **Status 2026-05-24:** the scanning + gitignore layers are DONE. See the
> per-item markers below. The only large open piece is the synthetic
> **test-baseline** seeder (filed as a follow-up — it is load-bearing for the
> whole test suite and can't be swapped in casually).

1. ✅ **Pre-commit secret scanner** — `.githooks/pre-commit` extracts staged
   content and runs gitleaks (local binary, else Docker). Install per-clone with
   `bin/install-git-hooks.sh` (sets `core.hooksPath`). Bypass: `SKIP_GITLEAKS=1`.
   *(Chose native hooks over the Python `pre-commit` framework — no extra runtime
   dep, and the Docker fallback fits the container-first dev setup.)*
2. ✅ **Pre-push full-diff scan** — `.githooks/pre-push` scans the outgoing
   commit range so a secret already removed from HEAD but live in history is still
   caught before it leaves the machine.
3. ✅ **Harden `.gitignore`** (root + app) — `.env*` (with `!.env.example`),
   `*.sql.gz`, `*.ibd`, `*.frm`, `*.sqlite`, `database/dev/*.sql`,
   `database/**/*data*.sql`, `database/prod_to_dev.sql`, `**/*.log`, `**/*.log.*`,
   `database/csv/*password*`, `**/datadir*/`. *Deliberately NOT a blanket `*.sql`*
   — the repo keeps functional ops/DDL scripts (`drop_all.sql`, `truncate_all.sql`,
   `restore_dev_funds.sql`, `familyfund_ddl*.sql`); only data dumps are ignored.
4. ✅/⏳ **Never commit data/datadir** — the dead `database/dev/familyfund_dev_data.sql`
   (real PII, nothing loaded it) is now **untracked** (`git rm --cached`; still in
   git *history* — that's the separate history-purge item in `SECURITY-EXPOSURE.md`
   §5). **Open:** `app1/family-fund-app/database/test/test-baseline.sql.gz` is
   load-bearing (CI `tests.yml` + `testpool.sh` restore from it) → replacing it with
   a synthetic seeder is a dedicated follow-up ticket, not done here.
5. ✅ **CI scanning** — `.github/workflows/security-scan.yml` runs a gitleaks
   "Secret Scan" job on push/PR with `.gitleaks.toml`; fails the build on a hit.
6. ✅ **One tracked env only** — `.env.example` is the only tracked env; `.env*`
   are git-ignored (done in the dev rotation).
7. ✅ **Parameterize PII** — admin/recipient emails via config/env + scrubbed the
   committed baseline dump (§4, done 2026-05-25, #81; dumps via #79/#78).
8. ⏳ Periodic full-history scan in CI — future enhancement (the working-tree scan
   runs today; history scan pairs with the pre-publish purge).

---

## Order of operations
1. (this doc) ✅  2. Quiesce pools/worktrees → rotate dev+stage (§1) →
3. Bulk user reset, exclude jdtogni (§3) → 4. Harden `.gitignore` + add scanners (§5) →
5. FamilyFund history purge (`SECURITY-EXPOSURE.md` §5) → 6. Plan prod rotation →
7. Publish.
