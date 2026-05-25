# Engineering Decisions

The canonical, living log of significant engineering decisions for FamilyFund
(lightweight ADRs). One entry per decision: **context → decision → consequences**,
with a date and a source you can verify (commit, PR/issue, or doc).

**How to use this file**
- Add a new `ED-NNNN` section when a decision is non-obvious, hard to reverse, or
  future-you would otherwise re-litigate. Don't duplicate what the code/CLAUDE.md
  already makes obvious.
- Never supersede in place — add a new entry and flip the old one's **Status** to
  `Superseded by ED-NNNN`. History stays readable.
- Keep secret VALUES out of this file (decisions only).

## Index

| ID | Date | Status | Decision |
|----|------|--------|----------|
| [ED-0001](#ed-0001--secrets-stay-out-of-git-rotate--scan-not-a-secrets-server) | 2026-05-22 | Accepted | Secrets stay out of git (git-ignored `.env` + rotation + scanning), not a secrets server |
| [ED-0002](#ed-0002--sopsage-for-in-repo-encrypted-env-secrets) | 2026-05-24 | Accepted | SOPS + age for in-repo, committed, encrypted env secrets |
| [ED-0003](#ed-0003--deny-by-default-authorization) | 2026-05-24 | Accepted | Deny-by-default authorization across scopes + `fund.full` ACL gating |
| [ED-0004](#ed-0004--object-level-api-authorization-idor-remediation) | 2026-05-24 | Accepted | Object-level API authorization via `AuthorizesApiAccess` trait |
| [ED-0005](#ed-0005--security-scan-ci-as-the-gate-codeql-dropped) | 2026-05-24 | Accepted | Security Scan CI is the gate; CodeQL dropped for PHP/private-repo reasons |
| [ED-0006](#ed-0006--default-test-run-excludes-nightly-group) | 2026-05-24 | Accepted | Slow tests tagged `@group nightly`, excluded from the default run |
| [ED-0007](#ed-0007--isolated-testpool-dbs-separate-from-the-shared-dev-db) | 2026-05-24 | Accepted | Isolated `testpool` DBs for destructive/coverage runs, separate from the shared dev DB |
| [ED-0008](#ed-0008--repository-pattern--ext-model-variants) | (pre-existing) | Accepted | Repository pattern for data access; `*Ext` model variants for business logic |
| [ED-0009](#ed-0009--goal-current-is-net-shares) | 2026-05 | Accepted | Goal "Current" uses net shares (OWN − BOR), not gross |
| [ED-0010](#ed-0010--loan-share-is-a-label-only-rename-of-credit-line) | 2026-05 | Accepted | "Loan Share" is a UI label-only rename of credit_line |

---

## ED-0001 — Secrets stay out of git, rotate & scan (not a secrets server)

- **Date:** 2026-05-22 · **Status:** Accepted
- **Context:** A public `.env.dev` leak exposed dev/stage `APP_KEY` + DB password
  (prod creds live separately on spirit and were not exposed). We needed a
  secret-management posture proportional to a localhost/LAN docker setup.
- **Decision:** Keep all real secrets in **git-ignored** files (`.env`, `.env.dev`,
  `.env.stage`, `app1/.env`); the only tracked env file is `.env.example`
  (placeholders). De-hardcode DB creds from compose → `${FF_DB_*:-changeme}` read
  from git-ignored `app1/.env`. Add a repeatable **rotation runbook** (quarterly +
  on leak), `bin/rotate-dev-secrets.sh` (APP_KEY stage), and **secret scanning**
  (gitleaks locally + in CI). Chose this over standing up a secrets server (Vault),
  which was judged overkill for the footprint.
- **Consequences:** A fresh clone/worktree must recreate `app1/.env` or compose
  uses `changeme` and the DB fails. Encrypted DB columns (`two_factor_*`) are
  APP_KEY-bound, so APP_KEY rotation has a pre-flight guard. Prod creds remain
  hardcoded/weak in dstrader-aws (analyze-only; out of this repo's scope).
- **Source:** `docs/security/SECRET-ROTATION-PLAN.md`,
  `docs/security/SECRET-ROTATION-RUNBOOK.md`, `docs/security/SECURITY-EXPOSURE.md`.

## ED-0002 — SOPS + age for in-repo encrypted env secrets

- **Date:** 2026-05-24 · **Status:** Accepted · **Builds on:** ED-0001
- **Context:** ED-0001 kept secrets out of git, but distribution still relied on a
  shared-passphrase GPG tarball on melnick (`familyfund_secrets.tar.gz.gpg`):
  not versioned, all-or-nothing, and a single shared passphrase. We wanted
  versioned, reviewable, recipient-based secrets that can live *in* the repo.
- **Decision:** Adopt **SOPS + age**. Encrypted twins (`<name>.sops`, e.g.
  `app1/family-fund-app/.env.dev.sops`, `.env.stage.sops`, `app1/.env.sops`) are
  **committed**; plaintext stays git-ignored. Recipients live in repo-root
  `.sops.yaml` (public age key only); the **private** key stays off-repo at
  `~/.config/sops/age/keys.txt` and is transported out-of-band. Manage pairs with
  `app1/family-fund-app/bin/secrets.sh` (`status|decrypt|encrypt|edit|rekey|recipient`).
  Use **dotenv** in/out mode so diffs are line-by-line and keys stay readable.
- **Consequences:** New-machine setup needs only the age key, then
  `bin/secrets.sh decrypt` — replaces the melnick `scp` (now a documented
  fallback). SOPS's dotenv store strips **blank lines** on round-trip (cosmetic);
  all `KEY=VALUE` assignments and comments are preserved byte-for-byte (verified).
  `.gitignore` re-includes `*.sops` after the broad `**/.env.*` ignore. Adding a
  teammate/machine = append their `age1…` key to `.sops.yaml` + `bin/secrets.sh rekey`.
  Committing encrypted secrets is safe because secrets were rotated post-leak
  (ED-0001) and only age-key holders can decrypt — so this is safe even when the
  repo goes public (board #1).
- **Source:** `.sops.yaml`, `app1/family-fund-app/bin/secrets.sh`, `SETUP.md` §2.

## ED-0003 — Deny-by-default authorization

- **Date:** 2026-05-24 · **Status:** Accepted
- **Context:** Legacy `AuthorizationService` scopes (accounts/transactions/
  credit-lines) returned **all** rows for a user with no access (empty-closure
  leak; e.g. `GET /api/accounts` leaked everything). ACL gating on `fund.full`
  was also incomplete on admin/deposit/PII surfaces.
- **Decision:** Make authorization **deny-by-default**: no matching scope ⇒ no
  rows. Extend `fund.full` gating to admin/deposit/PII and to write operations.
  Enforce with an ACL matrix golden test (every GET route × every role) plus a
  fast critical-routes subset that runs every time.
- **Consequences:** Adding/removing a GET web route changes the AclMatrix golden —
  prefer surgical key edits over a full regen. New endpoints must opt in to a
  scope explicitly or they return nothing.
- **Source:** #74 (merge `884053eb`), #44 (`66318fff`), `tests/Feature/AclMatrixTest.php`,
  `tests/golden/acl_matrix.json`.

## ED-0004 — Object-level API authorization (IDOR remediation)

- **Date:** 2026-05-24 · **Status:** Accepted
- **Context:** API controllers allowed object access by ID without verifying the
  caller owned/could-see the object (IDOR).
- **Decision:** Introduce an `AuthorizesApiAccess` trait and apply object-level
  scoping across all API controllers; gate PII and system-admin operations; fix
  Sanctum `expires_at`; add authenticated ZAP tooling.
- **Consequences:** All API object access now runs an ownership/visibility check.
  Authenticated ZAP scans run in the scheduled CI security job.
- **Source:** #13 / #49 (merge `767600cd`).

## ED-0005 — Security Scan CI as the gate; CodeQL dropped

- **Date:** 2026-05-24 · **Status:** Accepted
- **Context:** We wanted automated security gating on PRs/push, but the repo is a
  private personal-account repo without GitHub Advanced Security, and CodeQL has
  no PHP support there.
- **Decision:** Run a **Security Scan** workflow as the gate: composer audit, npm
  audit (with documented `.npm-audit-ignore`), gitleaks (working-tree), semgrep
  (`p/php` + `p/owasp-top-ten` + `p/security-audit`), trivy config; plus scheduled
  trivy fs/image, OSV, SBOM, and ZAP. **Drop CodeQL.** Because SARIF upload to the
  Security tab needs GHAS, publish SARIF as **downloadable artifacts** and gate via
  explicit "fail on findings" steps. Mirror the exact gate locally in
  `bin/security-scan.sh`. Restore SARIF upload + `security-events:write` when the
  repo goes public (board #1).
- **Consequences:** Findings are reviewed via artifacts, not the Security tab, until
  the repo is public. Local and CI gates must be kept in sync.
- **Source:** `.github/workflows/security-scan.yml`,
  `app1/family-fund-app/bin/security-scan.sh`, `.gitleaks.toml`.

## ED-0006 — Default test run excludes the `nightly` group

- **Date:** 2026-05-24 · **Status:** Accepted
- **Context:** The full suite was ~17 min; a handful of classes dominated.
- **Decision:** Tag the slowest tests `@group nightly` and exclude them from the
  default run via `phpunit.xml` (~6 min). `AclMatrixTest` keeps a fast
  critical-routes subset that runs every time; the exhaustive sweep is nightly.
  Run the slow group with `bin/test-nightly.sh`.
- **Consequences:** The exhaustive ACL sweep only runs nightly/on demand —
  security-sensitive routes are still covered every run by the critical subset.
- **Source:** `phpunit.xml` (`8b82fc8e`), `tests/Feature/AclMatrixTest.php`.

## ED-0007 — Isolated `testpool` DBs, separate from the shared dev DB

- **Date:** 2026-05-24 · **Status:** Accepted
- **Context:** The default suite runs against the shared `familyfund_dev` DB; any
  `RefreshDatabase`/coverage run would clobber other sessions using dev.
- **Decision:** Provide isolated test slots (`~/.familyfund-pool/testpool.sh`,
  `test0..test2`) each with a slot-private `familyfund_testN` DB restored from a
  committed baseline. Use these (not the preview `pool.sh`, which shares dev) for
  any destructive/coverage/parallel test run.
- **Consequences:** A fresh worktree needs its own `.env` for the slot to mount;
  test runs must go through a leased slot, not `docker exec` on the dev container.
- **Source:** CLAUDE.md (Testing → testpool), `~/.familyfund-pool/testpool.sh`.

## ED-0008 — Repository pattern + `*Ext` model variants

- **Date:** (pre-existing convention) · **Status:** Accepted
- **Context:** Consistent data access and a home for business logic distinct from
  Eloquent models.
- **Decision:** All data access goes through `*Repository` classes (extending
  `BaseRepository`); controllers inject repositories and validate via form
  requests. Extended business logic lives in `*Ext` model variants. Historical
  views use an `as_of` parameter throughout; APIs are versioned (`/api`, `/api/v1`).
- **Consequences:** New entities follow the generator-produced
  model/repository/controller/request/views shape. Business logic added to `*Ext`,
  not the base model (e.g. the transaction observer moved to `TransactionExt`).
- **Source:** CLAUDE.md (Architecture / Patterns).

## ED-0009 — Goal "Current" is net shares

- **Date:** 2026-05 · **Status:** Accepted
- **Context:** Borrowed shares aren't actually held by the account.
- **Decision:** A goal's "Current" figure uses **net** shares (OWN − BOR), not
  gross — net is the only truthful "what you hold" number.
- **Consequences:** Goal progress reflects net position; borrowed shares don't
  inflate it.
- **Source:** Goal/AccountGoal logic; project memory.

## ED-0010 — "Loan Share" is a label-only rename of credit_line

- **Date:** 2026-05 · **Status:** Accepted
- **Context:** The credit-line feature needed clearer user-facing wording.
- **Decision:** Rename to **"Loan Share" / "Loan Shares"** in the **UI labels
  only**. Classes, tables, and routes keep the `credit_line` name to avoid a
  churny rename.
- **Consequences:** Code/DB/route searches still use `credit_line`; only display
  strings say "Loan Share". Don't assume a label change implies a schema change.
- **Source:** #62 (`7290f706`); project memory.

## ED-0011 — Test baseline is a synthetic seeder, not a committed data dump

- **Date:** 2026-05 · **Status:** Accepted
- **Context:** The suite uses `DatabaseTransactions`, so a baseline dataset must
  be pre-loaded (not just an empty migrate). That baseline was
  `database/test/test-baseline.sql.gz` — a real dev-DB dump carrying ~62 emails
  incl. real Gmail/Hotmail PII + real financials (SECURITY-EXPOSURE §2a). Both CI
  (`tests.yml`) and `testpool.sh` restored it on every run.
- **Decision:** Replace the dump with `Database\Seeders\TestBaselineSeeder` — a
  deterministic, **synthetic** seeder built from the existing factories +
  `Tests\DataFactory`. CI and `testpool.sh` now build the DB with
  `migrate:fresh --seed --seeder=…TestBaselineSeeder`; the dump is removed from
  HEAD. ~93% of tests self-seed via `DataFactory` inside the transaction; the
  seeder only has to satisfy the few fixtures that read pre-existing rows.
- **Consequences:**
  - The seeder reproduces the *load-bearing shape*, not the old data: roles/perms,
    CASH/SPY reference assets **with a wide/dense price history** (a fund's
    NAV/share-price 500s without CASH price; the scheduled-report data check does a
    global `AssetPrice::whereBetween('start_dt',…)` so SPY gets an every-3-days
    series), `user1@dev.familyfund.local` (id 1), the first fund, **account 7**
    (Dusk hard-codes it) with a dated 2021–2022 transaction history, and one of
    every entity the AclMatrix `{param}` resolvers read.
  - The dev-funds fixture migration (`restore_dev_funds.sql`, real dev data) is
    skipped during the build via `FF_SYNTHETIC_BASELINE=1`.
  - The ACL golden (`tests/golden/acl_matrix.json`) was **regenerated** against the
    synthetic baseline and diff-reviewed: no low-privilege escalation; changed
    cells are data-driven (entity now resolves at `fund->id`). Two manager-only
    nightly routes (`accounts/{id}/pdf_as_of`, `transactions/preview_pending`) now
    record `500` — sparse-data artifacts of AclMatrix using `fund->id` as a
    cross-entity id, not auth regressions.
  - One golden-data test (`AccountApiGoldenDataTest::test_account_transactions_as_of`)
    was decoupled from the old dump's hard-coded transaction ids (now derives the
    expectation from account 7's actual synthetic history).
  - Editing `testpool.sh` (in the **ai-harness** repo) is the companion change;
    branches that still ship the dump keep using the restore path.
- **Source:** #78; SECRET-ROTATION-PLAN §5.
