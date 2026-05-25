# FamilyFund — full git-history secret/PII audit + purge (issue #66)

**Date:** 2026-05-25 · **Status:** ✅ **EXECUTED** — history rewritten with
`git-filter-repo` and force-pushed to `origin`. Post-rewrite full-history audit:
real PII / password hashes / leaked APP_KEYs = **0**.

This was the pre-publish gate from `SECURITY-EXPOSURE.md` §5. The routine CI
gitleaks job scans the **working tree only** (`--no-git`); this audit/purge covered
the **entire commit history across every ref** — what would become public the
instant the repo flips to public.

> **No secret values are stored in this file** (all evidence is counts / masked).

---

## 1. Scope

- Repo: `https://github.com/jdtogni78/FamilyFund` (private).
- **All publishable refs** (`refs/heads` + `refs/tags`) — 29 branches, ~1026
  commits pre-rewrite. The server-managed `refs/pull/*` were dropped from the
  rewrite source (they are never force-pushed; GitHub retains them until GC).
- Tools: `git-filter-repo`; audit by a Python blob-sweep over every reachable blob
  (bcrypt `$2[aby]$…`, Laravel `APP_KEY` `base64:…`, real-provider emails,
  purged-path objects) cross-checked against the project history.

## 2. What was in history (the exposure)

| Item | Path(s) — all already gone from HEAD | Sensitivity |
|---|---|---|
| Raw MariaDB datadir (253 objects) | `app1/datadir_acl/**` (`*.ibd`/`*.frm` InnoDB tables, `ibdata1`, `ib_logfile0`, mysql/sys) | **Highest** — raw rows: hashes + emails |
| SQL data dumps | `database/familyfund_dump.sql`, `database/familyfund_data.sql`, `database/familyfund_only_dump.sql`, `database/dev/familyfund_data.sql`, `database/dev/familyfund_dev_data.sql`, `app1/familyfund_dump.sql`, `app1/core_dump.sql` | High — bcrypt + emails |
| Compressed baseline | `app1/family-fund-app/database/test/test-baseline.sql.gz` | High |
| Leaked dotenvs | `app1/family-fund-app/.env.dev`, `.env.local`, `.env.test0` | High — leaked dev `APP_KEY` + `DB_PASSWORD` (**already rotated → dead**) |
| Table exports | `database/csv/familyfund_users.csv`, `database/csv/familyfund_password_resets.csv` | Med/Low |
| Dead legacy dirs | `php_app/`, `php_app2/`, `app1/coreui-generator/`, `leadconcept_app/` | Bloat + ~90 open-source author emails |
| Owner email in source/tests/docs *history* | many files (HEAD already parameterized via #81) | Low — owner's own address |

## 3. The recipe EXECUTED (two parts)

1. **Path-purge** — `git-filter-repo --invert-paths --paths-from-file
   docs/security/history-purge-paths.txt` (14 data paths + 4 dead legacy dirs).
2. **`--replace-text`** — `regex:jd?togni(78)?@gmail\.com==>admin@dev.familyfund.local`.
   (`jd?` also catches the misspelled `jtogni@…` test-CC fixture in
   `FundReportTest.php`.)

Per-file decision: `database/prod_to_dev.sql` (the dev anonymizer) had the owner
email in functional `WHERE email …` clauses. **Decision: accept the substitution**
(it becomes `admin@dev.familyfund.local` throughout). Operators re-pointing a real
prod dump must account for the prod admin's actual address separately.

Note: `--replace-text` rewrites blob **content** only — the owner's email remains
in **commit author/committer metadata** by design (identities were not rewritten).

## 4. Result (audited over `refs/heads` + `refs/tags` after the rewrite)

| metric | before | after |
|---|---:|---:|
| `app1/datadir_acl/` blob objects | 248 | **0** |
| leaked APP_KEYs (distinct) | 2 (8 occ) | **0** |
| real family-member PII emails | ~97 | **0** |
| real user password hashes (distinct) | 550 | **0** real (5 left = synthetic `prod_to_dev.sql` dev-password targets) |
| owner email occurrences (content) | ~291 | **0** |
| `.git` size | 56 MB | 21 MB |
| commits on `main` | 1026 | 957 |

Residual greppable real-provider emails = **32 open-source package-author**
addresses in `composer.lock` / `_ide_helper.php` (public, inherent to the lockfile;
not PII).

## 5. Aftermath / caveats

- The rewrite changed **every commit SHA**. All clones, worktrees, and pool/test
  environments diverged and were re-cloned / hard-reset.
- **GitHub may retain pre-rewrite objects by SHA** (including `refs/pull/*`) until
  garbage-collection. The leaked dev `APP_KEY`/`DB_PASSWORD` are already **rotated
  (dead)**; the bulk PII is now purged from all branches. Treat any cached
  pre-rewrite content as the residual concern; optionally ask GitHub Support to run
  GC before making the repo public.

## 6. #66 checklist — final

- [x] Full-history scan (all refs) — done.
- [x] Triage real vs test/placeholder — done (§2).
- [x] Confirm `.env*` / DB dumps / raw datadir in history — **found** and purged.
- [x] Validated removal recipe (dry-run on a mirror clone → 0).
- [x] Rotate any still-live secret — dev `APP_KEY`/`DB_pw` already rotated (dead).
- [x] **Purge from history + force-push** — DONE 2026-05-25.
- [x] Re-run full-history scan against rewritten `origin` → real PII/hashes/APP_KEYs = 0.
- [x] Sign-off recorded here + in `SECURITY-EXPOSURE.md` §5.
