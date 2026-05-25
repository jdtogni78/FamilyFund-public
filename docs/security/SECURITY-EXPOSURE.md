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

## 5. History purge — dstrader DONE; **FamilyFund DONE** (#66)

dstrader (§3) is complete. **FamilyFund's history rewrite is now also complete
and force-pushed (#66, 2026-05-25).** `.gitignore` was already hardened (the
post-leak block ignoring `**/.env.*`, `*.ibd`, `*.frm`, `**/datadir*/`,
`database/**/*data*.sql`, `database/csv/*password*`, `*.sql.gz`, `**/*.log*`), so
the leaked classes cannot recur on HEAD.

> **2026-05-25 (#66): EXECUTED.** Full-history rewrite with
> `git-filter-repo` on a fresh mirror clone, then force-pushed all branches.
> See `HISTORY-AUDIT-66.md` (full report) and `history-purge-paths.txt` (the exact
> `--paths-from-file` spec used).

**What was run** (the validated two-part recipe):

1. **Path-purge** (`--invert-paths --paths-from-file history-purge-paths.txt`,
   18 specs): the whole `app1/datadir_acl/` raw InnoDB datadir, every SQL data
   dump, the leaked `.env.dev`/`.env.local`/`.env.test0`,
   `database/csv/familyfund_users.csv` + `…password_resets.csv`,
   `test-baseline.sql.gz`, plus the four dead legacy dirs (`php_app/`,
   `php_app2/`, `app1/coreui-generator/`, `leadconcept_app/`) for bloat/author-email
   removal. (All were already absent from HEAD — purging only scrubbed history.)
2. **`--replace-text`** of the owner's email baked into source/tests/docs *history*
   (rule: `regex:jd?togni(78)?@gmail\.com==>admin@dev.familyfund.local` — the
   `jd?` form also catches the misspelled `jtogni@…` test-CC fixture). HEAD was
   already parameterized via #81; this scrubbed the literal from the deleted/old
   blobs as well.

**Result (audited over the publishable `refs/heads`+`refs/tags` after the rewrite):**

| metric | before | after |
|---|---:|---:|
| `app1/datadir_acl/` blob objects | 248 | **0** |
| leaked APP_KEYs (distinct) | 2 (8 occ) | **0** |
| real family-member PII emails | ~97 | **0** |
| real user password hashes | bulk (550 distinct) | **0** (5 left are synthetic `prod_to_dev.sql` dev-password targets) |
| owner email occurrences | ~291 | **0** in file content* |
| `.git` size | 56 MB | 21 MB |
| commits on `main` | 1026 | 957 (legacy-only/empty dropped) |

\* The owner email remains in **commit author/committer metadata** (filter-repo
`--replace-text` only rewrites blob *content*, not identities — by design here).
Residual greppable emails are 32 open-source **package-author** addresses in
`composer.lock`/`_ide_helper.php` (public, not PII).

> ⚠️ **Force-push aftermath:** every clone, worktree, and the pool/test
> environments diverged and must re-clone or hard-reset. **GitHub may retain the
> pre-rewrite objects by SHA** (incl. `refs/pull/*`) until GC; since the leaked dev
> `APP_KEY`/`DB_PASSWORD` are already **rotated (dead)** and the bulk PII is now
> purged from all branches, the residual is treat-as-cached PII — optionally ask
> GitHub Support to run GC before publishing.

---

## 6. Pre-publish checklist (before EITHER repo goes public)
- [ ] Secrets rotated; affected users reset.
- [x] History purged of all items in §5 (#66, 2026-05-25) — verified by a fresh
  full-history scan: real PII / password hashes / leaked APP_KEYs = 0 across all
  branches. (GitHub-side GC of unreachable pre-rewrite objects still pending.)
- [x] `.gitignore` hardened and committed.
- [ ] Add a synthetic-only seed/demo dataset; remove all real-data fixtures.
- [ ] Screenshots/PDFs use synthetic data only.
- [ ] Confirm no internal IPs/hostnames in tracked files.
- [ ] Consider a secret-scanning pre-commit hook (gitleaks) going forward.

---

## 7. Open items to verify
- ~~Full extent of `app1/datadir_acl/` in history~~ — **RESOLVED (#66):** 253
  objects (`*.ibd`/`*.frm` InnoDB tables, `ibdata1`, `ib_logfile0`, mysql/sys
  system tables) — all purged.
- ~~Whether `.env.dev`'s 2 live values include `DB_PASSWORD`~~ — yes; dev `APP_KEY`
  + `DB_PASSWORD` both leaked and both **already rotated** (and now purged).
- Whether dev-DB creds are shared with prod — prod creds are separate (on spirit),
  not in this repo's history. Prod `APP_KEY` check is owner-only (#16).
- ~~Contents of `restore_dev_funds.sql`~~ — **RESOLVED (#66):** 0 hashes / 0 real
  emails on HEAD (#79 scrub clean).
