# Security exposure & remediation — FamilyFund / dstrader

**Date:** 2026-05-21 · **Status:** FamilyFund made private (mitigated, history
**not yet cleaned**); FamilyFund secret rotation + password resets **pending (user
action)**. dstrader history **PURGED + force-pushed + verified clean** (still
private; safe to publish when ready).

> Triggered while planning public project websites. Before going public, both
> repos were audited. FamilyFund was already public and is the live incident.

---

## 1. Severity summary

| Repo | Was public? | Exposure | Severity |
|------|-------------|----------|----------|
| **FamilyFund** | **YES** (now private) | `.env.dev` secrets + SQL dumps with real user PII, emails, **bcrypt password hashes** | **High** |
| **dstrader** | No (still private) | Was: committed logs (**1,102 IB account-id refs + 19 emails**) + account IDs in source. **Now PURGED + verified clean.** | Resolved |

Exposure window: unknown. Last push to public FamilyFund was 2026-05-22T00:30Z; how
long it had been *public* before that can't be determined from here. **Assume the
exposed content may already be cloned, cached, or indexed.**

---

## 2. FamilyFund — what was exposed

### 2a. Currently tracked (was public, still in history)
- **`app1/family-fund-app/.env.dev`** — real dotenv. Keys present: `APP_KEY`,
  `DB_PASSWORD`, `REDIS_PASSWORD`, `MAIL_PASSWORD`, `AWS_ACCESS_KEY_ID`,
  `AWS_SECRET_ACCESS_KEY`, `PUSHER_APP_SECRET`, plus host/port/mail config.
  ~2 secret-bearing keys hold real (non-placeholder) values — one is almost
  certainly `APP_KEY`. **Verify each and rotate every non-placeholder value.**
- **`database/dev/familyfund_dev_data.sql`** (3,516 lines) — real app data:
  INSERTs into `users, accounts, account_balances, funds, transactions,
  portfolios, portfolio_assets, asset_prices, matching_rules, …`.
  **9 emails + 9 bcrypt password hashes.**
  → **Untracked from HEAD 2026-05-24** (`git rm --cached`; nothing loaded it —
  the dev-load flow is `prod-to-dev.sh`). Still present in git **history** until
  the §5 history purge runs.
- **`app1/family-fund-app/database/test/test-baseline.sql.gz`** — full baseline:
  `persons, users, login_activities, credit_line_*, goals, fund_reports, …` —
  **62 emails.** → **Removed from HEAD 2026-05-25 (#78)**; CI + `testpool.sh` now
  build the test DB from the synthetic `Database\Seeders\TestBaselineSeeder`. Still
  in git **history** until the §5 history purge runs.
- `database/prod_to_dev.sql` — anonymization script (7 anon/mask refs). Logic, not
  bulk data — lower risk; review then decide whether to purge.
- `app1/family-fund-app/database/migrations/data/restore_dev_funds.sql`,
  `database/familyfund_ddl*.sql`, `database/*.sql` (drop/truncate/delete) — schema
  + ops scripts; low data risk, verify.

**Gitignore gap that caused this:** `.gitignore` excludes `.env`, `.env.prod`,
`.env.stage`, `.env.test*`, `.env.pool*` and the **dated** dumps
(`familyfund_dev_data_YYYYMMDD.sql`) — but NOT `.env.dev` nor the plain-named
`familyfund_dev_data.sql`. Both slipped through.

### 2b. In history only (deleted from HEAD, but exposed while public)
- `app1/family-fund-app/.env.local`, `app1/family-fund-app/.env.test0` — real envs.
- `database/csv/familyfund_password_resets.csv` — **password-reset tokens.**
- `app1/datadir_acl/familyfund_acl/password_resets.frm` + `.ibd` — **raw InnoDB
  table files** for `password_resets` committed to git.
- `php_app/app/.env.example` — example only (safe).
- ⚠️ Verify whether *more* of `app1/datadir_acl/` (a raw MySQL datadir) was ever
  committed — could include other tables' `.ibd` files.

---

## 3. dstrader — PURGED & verified clean (still private)

What was there (now removed/redacted across all 118 commits):
- Committed logs `daily_run.log.3` / `scheduler.log.4` / `demoApplication.log` —
  **1,102 IB account-id refs + 19 emails** + token mentions. **Removed entirely.**
- **5 real IB account IDs** hardcoded in source/tests/docs/portfolios — **redacted
  to same-shape placeholders.**
- **3 real emails** in source — redacted; **author/committer identities rewritten**
  to `…@users.noreply.github.com` (also cleared a GH007 push block).

Done by a parallel session 2026-05-21 21:57–21:58, then **force-pushed**.
**Independently verified against the pre-rewrite backup:** 0 real account-id and 0
real email occurrences remain across all commits; local `main` == `origin/main`.
Real values preserved off-repo in
`_repo-backups/2026-05-21-pre-purge/dstrader-redaction-map.SECRET.txt` (perms 600).
`familyfund.properties` keeps only internal API URLs (no raw IP); `mail.properties`
was never committed (template only). **Safe to make public when ready.**

---

## 4. Immediate actions

### Done
- [x] **FamilyFund set to private** (via API; unauthenticated fetch now 404).

### You must do (only you can — these are credentials/your account)
- [ ] **Rotate every real secret in `.env.dev`** — at minimum `APP_KEY`
  (⚠️ if prod shares the key, rotating re-keys encrypted columns — plan for it),
  and any non-placeholder `DB_PASSWORD` / `REDIS_PASSWORD` / `MAIL_PASSWORD` /
  `AWS_*` / `PUSHER_APP_SECRET`. Also rotate anything that was in the historical
  `.env.local` / `.env.test0`.
- [ ] **Force a password reset for all affected users** (the 9–62 accounts in the
  dumps). Bcrypt isn't plaintext, but treat hashes as compromised.
- [ ] **Invalidate outstanding password-reset tokens** (the CSV/`.ibd` exposure).
- [ ] Affected people are family — a quick heads-up is the decent thing to do.

---

## 5. History purge — dstrader DONE; FamilyFund still pending

dstrader (§3) is complete. **FamilyFund's history rewrite is deferred** by choice
(24 branches + 8+ locked worktrees + pool/test envs make the force-push disruptive
— coordinate first). Removing files from HEAD is not enough; they remain in history.
Plan for FamilyFund:

1. **Install tool** (none present; pip works):
   `pip3 install --user git-filter-repo`
2. **Fix `.gitignore`** (both repos) so this can't recur:
   - app: ignore `.env*` then un-ignore `!.env.example`
   - ignore `database/dev/*.sql`, `database/*.sql` data dumps, `*.sql.gz`,
     `*.ibd`, `*.frm`, `database/csv/*password*`, `**/*.log`, `**/*.log.[0-9]*`
3. **Purge paths from FamilyFund history** (`git filter-repo --invert-paths` for
   each): `.env.dev`, `.env.local`, `.env.test0`, `familyfund_dev_data.sql`,
   `test-baseline.sql.gz`, `familyfund_password_resets.csv`,
   `app1/datadir_acl/**` (+ any other raw datadir files found), and optionally
   `prod_to_dev.sql` / `restore_dev_funds.sql` after review.
4. ~~Purge dstrader history~~ — **DONE** (see §3).
5. **Re-add origin** (filter-repo drops it) and **force-push all branches/tags.**

> ⚠️ **Force-push caveat:** this rewrites SHAs. Every existing clone, worktree, and
> the FamilyFund pool/test environments will diverge and must re-clone or hard-reset.
> Coordinate before running — concurrent Claude sessions use those envs.

---

## 6. Pre-publish checklist (before EITHER repo goes public)
- [ ] Secrets rotated; affected users reset.
- [ ] History purged of all items in §5; verify with a fresh scan
  (`git log --all` + a `git grep` over `$(git rev-list --all)` for emails / `U\d{6,}` / `APP_KEY`).
- [ ] `.gitignore` hardened and committed.
- [ ] Add a synthetic-only seed/demo dataset; remove all real-data fixtures.
- [ ] Screenshots/PDFs use synthetic data only.
- [ ] Confirm no internal IPs/hostnames in tracked files.
- [ ] Consider a secret-scanning pre-commit hook (gitleaks) going forward.

---

## 7. Open items to verify
- Full extent of `app1/datadir_acl/` in history (other tables' raw files?).
- Whether `.env.dev`'s 2 live values include `DB_PASSWORD` (→ DB cred rotation).
- Whether dev-DB creds are shared with prod.
- Contents of `restore_dev_funds.sql` (INSERT scan returned nothing — verify).
