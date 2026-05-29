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
- **Cross-repo** decisions (the worktree workflow, multi-agent ticket
  coordination, the env/test pools, secrets posture, etc.) live in the
  ai-harness `DECISIONS.md` (`GD-NNNN`) — this file is FamilyFund-only.

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
| [ED-0011](#ed-0011--frontend-build-pinned-to-vite-6-not-5-not-latest) | 2026-05 | Accepted | Frontend build pinned to Vite 6 (lowest major with patched esbuild + current plugin's peer range) |
| [ED-0012](#ed-0012--no-intentionally-public-api-endpoints) | 2026-05-24 | Accepted | No intentionally-public API endpoints; overview-data + exchange holidays are auth-locked |
| [ED-0013](#ed-0013--larastanphpstan-static-analysis-behind-a-baseline-non-blocking) | 2026-05-24 | Accepted | Larastan/PHPStan static analysis at level 5 behind a baseline; non-blocking gate to start |
| [ED-0014](#ed-0014--test-baseline-is-a-synthetic-seeder-not-a-committed-data-dump) | 2026-05 | Accepted | Test baseline is a synthetic seeder (`TestBaselineSeeder`), not a committed real-data dump |
| [ED-0015](#ed-0015--management-index-pages-get-controller-level-scoping-behind-fundfull) | 2026-05 | Accepted | Management index pages get a second, in-controller scoping layer behind `fund.full` |
| [ED-0016](#ed-0016--reference-data-api-writes-are-system-admin-only) | 2026-05-25 | Accepted | Global reference-data API writes are system-admin-only (flag-gated); dstrader uses a system-admin service token |
| [ED-0017](#ed-0017--encrypted-secrets-move-to-a-separate-private-repo) | 2026-05-25 | Accepted | Encrypted env secrets move OUT of the (soon-public) app repo into a private `familyfund-secrets` repo |
| [ED-0018](#ed-0018--stage-app_key-is-independent-of-prod-encrypted-columns-are-nulled-on-restore) | 2026-05-28 | Accepted | Stage `APP_KEY` is independent of prod; encrypted 2FA columns are nulled on restore |
| [ED-0019](#ed-0019--familyfund-specific-semgrep-rules-alongside-the-registry-packs) | 2026-05-29 | Accepted | FamilyFund-specific Semgrep custom rules (`.semgrep/familyfund.yml`) run alongside the registry packs |

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
- **Source:** `docs/security/SECURITY-EXPOSURE.md` (in this repo); rotation
  plan + runbook live in the private `familyfund-secrets/docs/` repo
  (`SECRET-ROTATION-PLAN.md`, `SECRET-ROTATION-RUNBOOK.md`).

## ED-0002 — SOPS + age for in-repo encrypted env secrets

- **Date:** 2026-05-24 · **Status:** Accepted (location amended by ED-0017) · **Builds on:** ED-0001
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
  (ED-0001) and only age-key holders can decrypt. **Amended by ED-0017:** even
  though encrypted-in-public is *cryptographically* safe, the encrypted twins and
  `.sops.yaml` now live in a private sibling repo rather than in the public app
  repo (defense-in-depth: no secret material — not even ciphertext — ships
  publicly).
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

## ED-0011 — Frontend build pinned to Vite 6 (not 5, not latest)

- **Date:** 2026-05 · **Status:** Accepted
- **Context:** `vite@4` bundled `esbuild@0.18`, which carried a cluster of 5
  dev-server-only GHSA advisories parked in `.npm-audit-ignore`, and mismatched
  `laravel-vite-plugin@1.3.0`'s peer range (`^5||^6`), forcing
  `npm install --legacy-peer-deps`.
- **Decision:** Bump to **Vite 6** (`^6.0`), not 5 and not the newest (7/8).
  Vite 6 is the lowest major that pulls a *patched* `esbuild@^0.25` (vite 5 still
  ships `esbuild@^0.21`, leaving `GHSA-67mh-4wv8-2f99` unfixed); it satisfies the
  **existing** `laravel-vite-plugin@^1.0` peer range, so no plugin major bump is
  needed; and it keeps the broad node engine range (`^18||^20||>=22`) the CI
  node-20 jobs rely on (vite 7/8 require node ≥20.19 and `laravel-vite-plugin@2`+).
- **Consequences:** All 5 advisories cleared (`npm audit` → 0), `.npm-audit-ignore`
  emptied, `--legacy-peer-deps` no longer required (dropped from `bin/test.sh`).
  Revisit a 7/8 + plugin-major bump only if a future advisory needs it.
- **Source:** #34; `package.json`, `.npm-audit-ignore`, `bin/test.sh`.

## ED-0012 — No intentionally-public API endpoints

- **Date:** 2026-05-24 · **Status:** Accepted · **Builds on:** ED-0003, ED-0004
- **Context:** The 2026-05 route audit (#14 item C, `SECURITY_NEXT_STEPS.md`
  "Remaining" #3) asked whether any `api/`-prefixed endpoint is *intentionally*
  public. At audit time `api/funds/{id}/overview-data` and the exchange-holiday
  endpoints were the open questions; they needed an explicit classification, not
  silent ambiguity that the route-guardrail tests would keep flagging as drift.
- **Decision:** **No FamilyFund API endpoint is intentionally public.**
  Classification of the audited routes:
  - **`api/funds/{id}/overview-data`** (`FundControllerExt@overviewData`, name
    `api.funds.overview_data`) is the AJAX data backer of the auth-gated fund
    **overview page**. It deliberately uses the **web `auth`** guard (session),
    *not* `auth:sanctum`, because it is consumed first-party by that page; it
    also runs `authorize('view', $fund)` (FundPolicy), so it is tenant-authorized
    identically to the page. The `api/` prefix is a URL convention only — it is a
    web route in `routes/web.php`, inside the `Route::middleware('auth')` group.
  - **`api/exchange_holidays/{exchange}/{year}`, `/status`, `/sync`** are
    auth-locked under the `auth:sanctum` group in `routes/api.php`. Exchange
    holidays are shared reference data (no tenant/IDOR dimension), but reads and
    the sync write still require authentication. (Admin-only write-authz hardening
    of global reference data is a separate concern, out of this classification —
    tracked in #82.)
  - There is **no public market-data / quote endpoint**; `api/asset_prices*` are
    all auth-locked.
  - The only unauthenticated `api/` route is **`api/clear`** (cache/route clear),
    which is **env-gated to `local`/`dev`** and never registered in production.
- **Consequences:** The route-guardrail allowlists
  (`TEMPORARY_UNAUTHENTICATED_API_READ_ALLOWLIST` /
  `…_MUTATION_ALLOWLIST` in `SecurityRouteAutomationTest`) stay **empty** —
  deny-by-default: any new unauthenticated `api/` route fails CI. The audited
  endpoints are additionally pinned authenticated **by name**
  (`test_audited_api_endpoints_are_authenticated_not_public`), so neither a move
  out of their auth group nor a delete-and-readd-as-public regresses silently;
  overview-data's anonymous→login redirect and per-role authz are also pinned in
  the ACL matrix golden, and `api/clear`'s env gating in
  `test_api_clear_route_is_environment_gated_in_source`. Introducing a genuinely
  public API endpoint in future is a conscious act that must update this ED and
  the guardrail.
- **Source:** #51 (item C), #14; `routes/web.php`, `routes/api.php`,
  `tests/Feature/SecurityRouteAutomationTest.php`, `tests/golden/acl_matrix.json`,
  `SECURITY_NEXT_STEPS.md`.

## ED-0013 — Larastan/PHPStan static analysis behind a baseline, non-blocking

- **Date:** 2026-05-24 · **Status:** Accepted · **Builds on:** ED-0005
- **Context:** SECURITY_NEXT_STEPS Phase 4 / issue #77 wanted static analysis as a
  quality/security-adjacent check. A cold run of `larastan` (Laravel-aware
  PHPStan) over `app/` reports ~1,500 findings — mostly the base
  `Illuminate\…\Model` type degrading on dynamic Eloquent attributes/relations
  used by the `*Ext` models (ED-0008), plus some real dynamic-property smells.
  Levels 4 and 5 differ by only ~58 findings, so the level barely moves the count.
- **Decision:** Add `larastan/larastan` + `phpstan/phpstan` (dev) and adopt
  **level 5** (the issue's upper bound — stricter on *new* code) over `app/`.
  Freeze the existing findings in **`phpstan-baseline.neon`** so the gate is green
  from day one and fails only on NEW regressions — an exact, per-occurrence
  baseline beats broad `ignoreErrors` that would mask real bugs. Run it both
  locally (`bin/security-scan.sh`) and in CI, but keep the CI job
  **non-blocking** (`continue-on-error: true`, NOT a required check) until the
  signal/noise is understood. Do **not** also add Psalm as a second required gate.
- **Consequences:** `phpstan-baseline.neon` is large (~6.3k lines); shrinking it
  (and then raising the level / widening `paths`) is the ongoing goal — don't add
  to it casually. The committed `phpstan.neon` uses default parallelism (CI has
  pcntl + RAM); the local `bin/security-scan.sh` Docker fallback runs the stock
  `php:8.4-cli` image **single-process** (that image lacks `pcntl`, so parallel
  workers crash, and small Docker VMs OOM with N workers). Promote to a required
  check — and drop `continue-on-error` — once trusted.
- **Source:** #77; `app1/family-fund-app/phpstan.neon`, `phpstan-baseline.neon`,
  `bin/security-scan.sh` (`run_phpstan`), `.github/workflows/security-scan.yml`
  (`phpstan` job).

## ED-0014 — Test baseline is a synthetic seeder, not a committed data dump

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

## ED-0015 — Management index pages get controller-level scoping behind `fund.full`

- **Date:** 2026-05 · **Status:** Accepted
- **Context:** ~15 management index pages (goals, matchingRules, accountGoals,
  accountMatchingRules, portfolios, portfolioAssets, tradePortfolios,
  tradePortfolioItems, cashDeposits, assets, fundReports, accountReports,
  accountBalances, depositRequests, scheduledJobs) relied **solely** on the
  `fund.full` route middleware for tenant isolation; their `index()` methods did
  unscoped `->all()` / manual queries. A beneficiary 403s at the middleware
  today, but it was a single layer — and a full-access fund-admin already saw
  *other* funds' rows. Follow-up to the beneficiary-ACL hardening (ED context in
  commit 66937d01).
- **Decision:** Add a second, in-controller layer mirroring
  Account/Transaction/Fund:
  - **Rows with a fund/account to scope on** are filtered in `index()` via
    `AuthorizationService` — `scopeByAccountRelation` (account-owned),
    `scopeByFundColumn` (direct `fund_id`), `scopePortfoliosQuery` /
    `scopeByPortfolioColumn` / `scopeByPortfolioRelation` (portfolio subtree).
    A fund-admin of X now sees only X's rows; system-admin sees all
    (short-circuit); deny-by-default for no-access users.
  - **Global/template data with no fund or account column** (goals,
    matchingRules, assets, scheduledJobs) instead re-asserts the capability with
    `AppBaseController::requireFullFundAccessSurface()` (=
    `AuthorizationService::canAccessManagementSurface()`), which mirrors the
    `RequireFullFundAccess` middleware. If the route middleware is ever removed,
    the controller still denies non-privileged callers.
- **Consequences:**
  - Portfolios belong to a fund via the legacy `fund_id` column **and** the
    `fund_portfolio` pivot, so portfolio scoping must check both (mirrors
    `AuthorizesApiAccess::scopePortfolioQuery`); plain `scopeByFundColumn` is
    insufficient.
  - `TradePortfolioExt::portfolio()` is an **overridden, validating accessor**
    that throws inside `whereHas`. So portfolio-child resources with a direct
    `portfolio_id` are scoped by **column** (`scopeByPortfolioColumn`
    pre-resolves accessible portfolio ids) rather than by traversing that
    relation. Two-hop children with clean relations (trade_portfolio_items via
    `tradePortfolio.portfolio`) still use the relation form.
  - `WebControllerSmokeTest` runs `WithoutMiddleware` and unauthenticated, so the
    new `/assets` guard correctly 403'd it — proving the defense-in-depth. The
    smoke test now authenticates as a system-admin (these are admin surfaces).
  - No route changes, so the ACL golden is unaffected; status codes per role are
    unchanged (the guard 403s exactly where the middleware already did).
- **Source:** #85.

## ED-0016 — Reference-data API writes are system-admin-only

- **Date:** 2026-05-25 · **Status:** Accepted · **Builds on:** ED-0004, ED-0012
- **Context:** Every `/api/*` route is auth-locked (ED-0012) and tenant data is
  object-scoped (ED-0004), but create/update/delete on the **global shared/
  reference** resources (assets, asset_prices, matching_rules, schedules,
  change_logs, asset_change_logs, exchange_holidays) plus the
  `asset_prices_bulk_update` price feed were writable by **any** authenticated
  user. The only external writer is dstrader (the trading app + report
  scheduler), which historically sent **no auth at all** — already incompatible
  with the auth-locked API (#82).
- **Decision:** Reference-data writes are **system-admin-only**, enforced by
  `AuthorizesApiAccess::adminWriteMiddleware()` (controller middleware on
  store/update/destroy + bulkStore, so it rejects before FormRequest validation)
  and `requireAdminWrite()` (exchange_holidays/sync). The tightening is gated by
  `config('familyfund.enforce_admin_writes')` (env `FF_ENFORCE_ADMIN_WRITES`,
  default on) so a prod cutover can deploy with it **off**, confirm dstrader's
  token works, then flip it **on**. dstrader authenticates as a dedicated
  **system-admin service user** (`DstraderServiceUserSeeder`) using two named,
  expiring Sanctum tokens (`app`, `scheduler`) minted/rotated by
  `php artisan security:service-token`.
- **Consequences:**
  - The positions/balances bulk endpoints already gate via
    `requireFullAccessToAnyFund()` (fund-scoped data) — left as-is; system admins
    satisfy it, so they were **not** tightened to system-admin-only.
  - Enforcement is asserted by
    `SecurityApiAclMatrixTest::test_reference_data_writes_are_admin_only` (+ the
    flag-off path). Generated `tests/APIs/*` and the functional bulk tests use
    `WithoutMiddleware`, which bypasses the ctor gate, so they stay green and keep
    testing controller mechanics.
  - Tokens expire (default 90d); a daily `security:service-token --check-expiry`
    scheduler task warns before lapse and a weekly `--prune-expired` cleans up.
    Actual rotation/distribution runs as an external dstrader-docker cron (it must
    push the new token to dstrader). Runbook:
    `docs/runbooks/ff-service-token-rotation.md`.
  - **Cross-repo:** dstrader (Java `HTTPUtils` / `HolidayAPIClient`) and
    dstrader-docker (`familyfund_bashlib.sh`) must send
    `Authorization: Bearer <token>` and deploy together with this change.
- **Source:** #82 (split from #51); `routes/api.php`; `AuthorizesApiAccess`.

## ED-0017 — Encrypted secrets move to a separate private repo

- **Date:** 2026-05-25 · **Status:** Accepted · **Builds on / amends:** ED-0002
- **Context:** ED-0002 committed the SOPS+age-**encrypted** env twins (`*.sops`)
  and the SOPS recipe (`.sops.yaml`) directly into this repo, on the reasoning
  that ciphertext is safe to publish (only age-key holders can decrypt). As the
  repo is prepared to go **public** (#16), we want a stricter posture:
  **no secret material at all in the public repo — not even encrypted blobs.**
  Encrypted-in-public is cryptographically fine but is an unnecessary attack
  surface (offline brute-force of a future-broken cipher, recipient-set
  disclosure, "why are there secrets in a public repo" optics) and the encrypted
  blobs already in git history are a separate cleanup (#36).
- **Decision:** Move the encrypted twins + `.sops.yaml` OUT into a **new PRIVATE
  repo `jdtogni78/familyfund-secrets`**, cloned next to this one. The tooling
  stays here but is repointed: `bin/secrets.sh` resolves the secrets dir as
  `${FF_SECRETS_DIR:-~/dev/familyfund-secrets}` and reads/writes the `.sops`
  twins + `.sops.yaml` there, mirroring this repo's relative paths
  (`app1/.env.sops`, `app1/family-fund-app/.env.{dev,stage}.sops`). If the secrets
  clone is absent it **falls back to any in-repo `.sops` files** so transitional
  checkouts and CI keep working. Plaintext envs are still git-ignored and still
  materialized into their canonical in-app paths (the app reads them there). The
  age **private** key remains off-repo and is committed to **neither** repo.
- **Consequences:**
  - New-machine setup needs **two** out-of-band artifacts now: a clone of
    `familyfund-secrets` *and* the age private key (SETUP.md §2).
  - After rotating/editing a secret, re-encrypt with `bin/secrets.sh encrypt`
    then commit in the **secrets repo**, not here (`rotate-dev-secrets.sh` notes
    this).
  - This does **not** rewrite git history; the already-committed encrypted blobs
    are handled separately by the history-purge work (#36).
- **Source:** #16 (secrets-move publish-blocker); `app1/family-fund-app/bin/secrets.sh`,
  `SETUP.md` §2; repo `jdtogni78/familyfund-secrets` (private).

## ED-0018 — Stage `APP_KEY` is independent of prod; encrypted columns are nulled on restore

- **Date:** 2026-05-28 · **Status:** Accepted · **Builds on:** ED-0017
- **Context:** The go-public plan (#36) restores a prod DB dump into a new
  `stage` environment on spirit (#46). Any column on a model with an
  `'encrypted'` cast was written under prod's `APP_KEY`; restoring it under a
  different key makes it undecryptable. The ticket (#52) framed two options:
  (1) stage reuses prod's `APP_KEY`, or (2) stage uses a fresh key and the
  restore tool re-encrypts or nulls the affected columns. The audit
  (`docs/security/ENCRYPTED-CASTS-AUDIT-52.md`) found the entire encrypted
  surface is `User.two_factor_secret` (`encrypted`) + `User.two_factor_recovery_codes`
  (`encrypted:array`) — no business/PII columns are encrypted at the cast
  layer. The non-cast `Crypt::decrypt(env('MAIL_PASSWORD_ENCRYPTED'))` site in
  `AppServiceProvider` is env-side, not DB-side, and is already per-environment.
- **Decision:** Adopt **Option 2 — fresh stage `APP_KEY`, null the encrypted
  2FA columns on restore.** Stage and prod hold independent `APP_KEY`s, rotated
  independently. The prod→stage restore tool (#47) lifts the guarded
  `UPDATE users SET two_factor_secret = NULL, two_factor_recovery_codes = NULL,
  two_factor_confirmed_at = NULL` already used by `database/prod_to_dev.sql`
  (lines 163–171) so the same idempotent pattern covers prod→dev and
  prod→stage. `.env.stage.sops` (#48) carries its own `APP_KEY` from
  `php artisan key:generate` on the stage container.
- **Consequences:**
  - Stage compromise does not transfer to prod encrypted data — strict
    key separation across environments.
  - Cutover team members who enable 2FA on stage must re-enroll after any
    prod→stage rebuild. Acceptable (and a useful smoke test).
  - If a future ticket adds an `'encrypted'` cast to a column carrying
    *business* data (SSN, bank routing, customer address, etc.), this
    decision must be revisited per column — business data can't be nulled
    on restore the way 2FA secrets can.
- **Source:** #52 (audit + decision); `docs/security/ENCRYPTED-CASTS-AUDIT-52.md`;
  `app/Models/User.php` casts; `database/prod_to_dev.sql:163-171`;
  drives #47 (restore tooling) and #48 (`.env.stage.sops`).

## ED-0019 — FamilyFund-specific Semgrep rules alongside the registry packs

- **Date:** 2026-05-29 · **Status:** Accepted · **Builds on:** ED-0005, ED-0013
- **Context:** The security scan (ED-0005) already runs Semgrep with the
  registry packs `p/php`, `p/owasp-top-ten`, and `p/security-audit`. Those
  catch the usual PHP / OWASP / framework pitfalls but miss FamilyFund's
  *own* risk surfaces — most notably the `/dev-login` impersonation route,
  the `security:dev-token` admin-token primitive, ad-hoc raw SQL with
  request input, and unsafe file reads. #77 (ED-0013) Phase 4 called for
  repo-local rules to cover those gaps.
- **Decision:** Author a small, **high-precision** repo-local ruleset at
  `.semgrep/familyfund.yml` and wire it into both
  `app1/family-fund-app/bin/security-scan.sh` and the `Semgrep` job in
  `.github/workflows/security-scan.yml` alongside the registry packs (one
  pass, single SARIF). Initial rules:
  1. `ff-dev-login-route-outside-env-guard` (ERROR)
  2. `ff-dev-token-call-outside-allowed-context` (ERROR)
  3. `ff-raw-sql-interpolates-request-input` (ERROR)
  4. `ff-unsafe-file-read-from-request` (ERROR)
  Precision bar: every rule must be **quiet on the current clean tree**
  (zero findings) and must fire on the seeded positive cases in
  `.semgrep/familyfund.test.php`. A WebV1 missing-`authorize` heuristic was
  prototyped but deferred — its current baseline is 53 candidate findings
  (real IDOR risk mixed with global-resource actions where per-object authz
  doesn't apply) and needs per-controller triage before it can be a quiet
  gate.
- **Consequences:** Adds two CI checks worth of coverage with no extra
  scanner; the SARIF artifact still covers everything (Semgrep merges
  configs into one report). The CI step keeps its existing `continue-on-error`
  posture inherited from the registry-pack flow; a new finding fails the
  step but doesn't block the workflow until the gate is promoted. Rules are
  pinned to FamilyFund-specific risk surfaces — generic patterns belong in
  the registry packs, not here.
- **Source:** #17 (ticket + rationale); `.semgrep/README.md`;
  `.semgrep/familyfund.yml`; `.semgrep/familyfund.test.php`;
  `app1/family-fund-app/bin/security-scan.sh` (`run_semgrep_scan`);
  `.github/workflows/security-scan.yml` (`Semgrep` job).
