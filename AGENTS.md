# AGENTS.md

Canonical guidance for AI agents (Claude Code, Codex, etc.) and humans working
in this repository. This is the **memory bank index** — start here, then follow
the doc links below for depth on any specific area.

> **CLAUDE.md** is a thin pointer to this file. If you edit conventions, edit
> here.

## Project overview

**Family Fund** is a Laravel 11 financial fund management system for tracking
fund shares, portfolios, beneficiary accounts, and transactions. Uses the
repository pattern with `*Ext` model variants for business logic and extensive
test coverage (~1791 passing tests). Code lives in `app1/family-fund-app/`.

## Tech stack

- **Backend**: Laravel 11, PHP 8.2+
- **Frontend**: Blade + Livewire 3 + Volt + Bootstrap 5 + Tailwind CSS
- **Database**: MariaDB
- **Build**: Vite
- **PDF**: wkhtmltopdf via `knplabs/knp-snappy`
- **Auth/ACL**: Laravel Sanctum (API) + Spatie laravel-permission (roles/perms)
- **Secrets**: SOPS + age. Encrypted env files now live in the private
  `familyfund-secrets` repo ([ED-0017](docs/ENGINEERING_DECISIONS.md));
  in-repo `*.sops` files remain only as a transitional fallback. Original
  in-repo design: [ED-0002](docs/ENGINEERING_DECISIONS.md).

## Doc index (memory bank)

Onboarding / operations:

- [SETUP.md](SETUP.md) — New-machine setup (Mac). The canonical starting
  point. **Intentionally kept at repo root** (alongside `README.md`) as the
  one-click onboarding doc.
- [README.md](README.md) — High-level overview + Docker / DB-restore
  reference. **Intentionally kept at repo root**.
- [docs/SETUP.md](docs/SETUP.md) — Older partial copy of the setup doc; root
  `SETUP.md` is the maintained version. TODO(scope-clarify): consolidate or
  delete in a follow-up (out of scope for this ticket).

Architecture & decisions:

- [docs/DECISIONS.md](docs/DECISIONS.md) — Lightweight ADR index
  (`ED-NNNN`), one file per decision under [`docs/decisions/`](docs/decisions/).
  Read before re-litigating a non-obvious choice. (`docs/ENGINEERING_DECISIONS.md`
  remains as a thin pointer so old links keep resolving.)
- [docs/CONTROLLERS_REVIEW.md](docs/CONTROLLERS_REVIEW.md) — Audit of
  `app/Http/Controllers/`; what's auto-generated vs hand-rolled.
- [docs/ACL_IMPLEMENTATION.md](docs/ACL_IMPLEMENTATION.md) — Roles, permissions,
  policies, and authorization checks (Spatie + `fund.full` gating).
- [docs/API_VS_WEB_COMPARISON.md](docs/API_VS_WEB_COMPARISON.md) +
  [docs/API_VS_WEB_LOGIC_ANALYSIS.md](docs/API_VS_WEB_LOGIC_ANALYSIS.md) —
  Whether REST API and Web UI share business logic; DB-state impact.
- [docs/Transactions.md](docs/Transactions.md) — Transaction types and their
  effects on fund + beneficiary balances.
- [docs/MONEY_SUBSYSTEM.md](docs/MONEY_SUBSYSTEM.md) — Memory-bank index for
  the money subsystem: ledger + credit-lines + inbound deposits + the ACL
  enforcement points that gate them.
- [docs/INTEGRATIONS.md](docs/INTEGRATIONS.md) — Memory-bank index for
  cross-system integrations: IBKR cash-deposit pipeline, dstrader Sanctum
  service tokens, internal HTTP sidecars (trading-calendar, quickchart),
  outbound email, the SOPS+age secrets pipeline, and the planned BRL↔USD
  money-flow (not yet built).
- [docs/FUND_SETUP_GUIDE.md](docs/FUND_SETUP_GUIDE.md) — Operator guide for
  creating a new fund.
- [docs/ManualMaintenance.md](docs/ManualMaintenance.md) — Manual fund
  maintenance checklist (investments, beneficiary accounts).

Sub-project plans (drafts — not yet implemented):

- [docs/credit_lines/credit_lines_plan.md](docs/credit_lines/credit_lines_plan.md)
  — Borrow-shares-against-balance feature; UC-* tracker.
- [docs/credit_lines/fund_cashflow.md](docs/credit_lines/fund_cashflow.md) —
  Receivable-as-asset NAV accounting (Phase 0 decision).
- [docs/credit_lines/matching_on_repayment.md](docs/credit_lines/matching_on_repayment.md)
  — Should `MatchingRule`/`TransactionMatching` route credit-line repayments?
- [docs/money_flow_plan.md](docs/money_flow_plan.md) — Brazil ↔ US money flow,
  detection, recipient registry; MF-* tracker.
- [docs/runbooks/money_flow_runbook.md](docs/runbooks/money_flow_runbook.md) —
  Operator runbook for the money-flow subsystem.
- [docs/testing_plan.md](docs/testing_plan.md) — Test strategy for credit
  lines + money flow.

Security & runbooks:

- [docs/security/SECURITY-EXPOSURE.md](docs/security/SECURITY-EXPOSURE.md) —
  History exposure + remediation (private flip, history rewrite).
- [docs/security/HISTORY-AUDIT-66.md](docs/security/HISTORY-AUDIT-66.md) —
  Full git-history secret/PII purge audit (issue #66).
- [docs/SECURITY_NEXT_STEPS.md](docs/SECURITY_NEXT_STEPS.md) — Outstanding
  security-automation work.
- [docs/runbooks/ff-service-token-rotation.md](docs/runbooks/ff-service-token-rotation.md)
  — dstrader ⇄ FamilyFund Sanctum-token rotation procedure.

Process:

- [docs/GITHUB_PROJECTS.md](docs/GITHUB_PROJECTS.md) — GitHub Projects setup
  (boards over Jira); the **Trading & Fund** board (project #1) is the source
  of truth for FamilyFund work.

App-internal docs (lower-level reference):

- [app1/family-fund-app/docs/FAMILYFUND_TRANSACTION_SYSTEM.md](app1/family-fund-app/docs/FAMILYFUND_TRANSACTION_SYSTEM.md)
  — Transaction types, flags, and cash-position math.
- [app1/family-fund-app/docs/PDF_CHART_IMPROVEMENTS.md](app1/family-fund-app/docs/PDF_CHART_IMPROVEMENTS.md)
- [specs/V1.spec.md](specs/V1.spec.md), [specs/V3.spec.md](specs/V3.spec.md),
  [specs/V99.spec.md](specs/V99.spec.md) — Original specs.

## New-machine setup

See [SETUP.md](SETUP.md). Env secrets are committed **encrypted** (SOPS+age,
`*.sops`); decrypt with `app1/family-fund-app/bin/secrets.sh decrypt` once the
age key is in place ([ED-0002](docs/ENGINEERING_DECISIONS.md)). DB dumps + the
legacy GPG bundle still live on melnick at `~/familyfund_db_backups/` — pull
with `scp -O` (Synology drops SFTP).

## Commands

`docker compose` commands run from `app1/`. The `bin/*.sh` wrappers and
artisan commands run either from `app1/family-fund-app/` (the Laravel
project root) or from anywhere when invoked through `docker exec familyfund …`.

```bash
# --- Deployment (from your local checkout) ---
../dstrader-docker/local/deploy_ff.sh   # Deploy FamilyFund to prod

# --- Production container management (on the prod host, via ssh dstrader) ---
# IMPORTANT: always use these scripts, never run `docker compose` directly.
# FamilyFund:
ssh dstrader "cd ~/dev/dstrader-docker && ./server/dev/run_familyfund.sh prod"
# Wake prod server (if sleeping): ../dstrader-docker/local/wake_dstrader.sh
# DStrader (auto-runs weekdays 12:33 PM via cron, or manually):
ssh dstrader "cd ~/dev/dstrader-docker/dstrader/runtime && \
  ./start_dstrader.sh restart prod -d"

# --- Development (run from app1/) ---
cd app1
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
docker exec familyfund composer install
(cd family-fund-app && npm install && npm run dev)   # vite from app1/family-fund-app/

# --- Testing (must run inside the container — DB not reachable from host) ---
docker exec familyfund php artisan test
# Or the wrapper that rebuilds Vite assets + autodetects container name
# (run from app1/family-fund-app/):
cd app1/family-fund-app
bin/test.sh
bin/test.sh --filter=TransactionTest

# --- Database (artisan commands run inside the container) ---
docker exec familyfund php artisan migrate
docker exec familyfund php artisan migrate:fresh
docker exec familyfund php artisan db:seed
docker exec -it familyfund php artisan tinker

# --- Queue (for email/reports) ---
docker exec familyfund php artisan queue:listen
```

## Architecture (quick reference)

Directory structure inside `app1/family-fund-app/`:

- `app/Models/` — Eloquent models. Many have `*Ext` variants holding business
  logic (e.g., `AccountExt::sharesAsOf()`).
- `app/Repositories/` — Repository pattern (`*Repository` extends
  `BaseRepository`); all data access goes through these.
- `app/Http/Controllers/` — CRUD controllers.
- `app/Http/Controllers/API/` — REST API endpoints.
- `app/Http/Controllers/WebV1/` — Extended controllers (trade portfolios,
  rebalancing, reports).
- `app/Http/Requests/` — Form-request validation.
- `app/Livewire/` — Volt single-file components.
- `resources/views/` — Blade templates organized by entity.

Key domain models: **Fund** (NAV + share pricing), **Account/AccountExt**
(beneficiary balances; OWN vs BOR), **Portfolio/TradePortfolio** (asset
collections), **Asset** (stocks/crypto/real estate + price history),
**Transaction** (deposits, withdrawals, rebalancing trades),
**Goal/AccountGoal**.

Conventions:

- Repository pattern for all data access.
- Controllers inject repositories and use form-request validation.
- `*Ext` model variants hold the extended business logic.
- Historical views via the `as_of` parameter throughout.
- API versioning: `/api/`, `/api/v1/`.

Deeper rationale: [docs/DECISIONS.md](docs/DECISIONS.md)
(ED-0008, ED-0009, etc.).

## Testing

**Tests must run in Docker** (`docker exec familyfund php artisan test`) — the
DB is not reachable from the host.

The suite has thousands of passing tests (current count tracked by CI, not
duplicated here). The default run **excludes** slow `@group nightly` tests via
`phpunit.xml`. See [ED-0006](docs/ENGINEERING_DECISIONS.md) for rationale and
[ED-0007](docs/ENGINEERING_DECISIONS.md) for the isolated `testpool` slot
design.

Prefer the wrapper `app1/family-fund-app/bin/test.sh` — rebuilds Vite assets if
stale, autodetects the container name (`$FF_CONTAINER` →
`ffacl-familyfund-1` → `familyfund`), then runs `php artisan test`.
Pass-through args work (e.g. `bin/test.sh --filter=Foo`).

### Nightly (long-running) tests

`@group nightly` is excluded from the default run, so a normal cycle is a few
minutes instead of the full ~quarter-hour sweep. Tagged classes/methods:
`PDFTest`, `SmokeTest`, `OperationsControllerTest`, and
`AclMatrixTest::test_acl_matrix_matches_golden` (the exhaustive
full-route × all-roles sweep). `AclMatrixTest` still keeps a fast
critical-routes subset (`test_acl_matrix_critical_routes_match_golden`) that
runs every time over the security-sensitive surface (admin, credit lines,
PII, money, user management). Run the slow group with `bin/test-nightly.sh` (`--group=nightly`
overrides the config exclude) — intended for a nightly job. To rank slow tests
for re-tagging: `./vendor/bin/phpunit --log-junit junit.xml`, then sort
`<testcase time=...>` by class.

### Test layout (`app1/family-fund-app/tests/`)

- `Feature/` — Full HTTP request tests.
- `Feature/AclMatrixTest.php` — Discoverable ACL matrix: walks every GET route ×
  every named role, diffs against `tests/golden/acl_matrix.json`. Refresh with
  `ACL_MATRIX_UPDATE=1`. Full sweep is `@group nightly`; critical-routes subset
  always runs (see `CRITICAL_ROUTES`).
- `Feature/AuthorizationTest.php` — Policy/service unit tests.
- `APIs/` — API endpoint tests (28+ suites).
- `Repositories/` — Repository pattern tests (30+ suites).
- `GoldenData/` — Integration tests with versioned datasets.
- `DataFactory.php` — Test-data generation.
- `Fixtures/TestFixtures.php` — Higher-level fixtures including
  `aclUsers(Fund $fund)` (one user per role).

```bash
docker exec familyfund php artisan test
docker exec familyfund php artisan test --filter=TransactionTest

# Coverage
docker exec familyfund ./vendor/bin/phpunit --coverage-text 2>&1 \
  | grep -E "^  (Lines|Methods|Classes):"
```

### Isolated-DB test env (testpool) — for coverage, RefreshDatabase, parallel runs

Tests in the main `familyfund` container reset the shared `familyfund_dev` DB
(RefreshDatabase wipes it), which clobbers any other session using dev. For
coverage / Dusk / migration trials / parallel test runs, lease an isolated
slot from `~/.familyfund-pool/testpool.sh` instead — each slot has its own
`familyfund_testN` DB built fresh on every claim by `migrate:fresh` + the
synthetic `Database\Seeders\TestBaselineSeeder`. (No real-data dump lives in
the repo — see issue #78.) `account 7` / `user1@dev.familyfund.local` / the
first fund come from that seeder.

```bash
~/.familyfund-pool/testpool.sh list                               # 10 slots: test0..test9
~/.familyfund-pool/testpool.sh claim "<label>"                    # lease + seed + bring up stack
~/.familyfund-pool/testpool.sh run        -- --filter=Foo         # php artisan test in this slot
~/.familyfund-pool/testpool.sh tour       -- --filter=FooDuskTest # Dusk + Selenium sidecar
~/.familyfund-pool/testpool.sh reseed test2                       # re-seed mid-lease
~/.familyfund-pool/testpool.sh release                            # free the slot
```

Coverage in a slot (pcov is in `app1/Dockerfile`):

```bash
docker exec familyfund-<slot> sh -c "cd /app && \
  ./vendor/bin/phpunit --coverage-text" \
  2>&1 | grep -E '^  (Lines|Methods|Classes):'
```

**Do not** use `pool.sh` (the preview pool, `pool0..pool5` at ports
3020–3025) for tests — every preview slot hardcodes `DB_DATABASE=familyfund_dev`,
so RefreshDatabase will wipe shared dev data.

Remaining test-fix work tracked on the **Trading & Fund** board (project #1,
issues prefixed `test:`).

## Docker services

> Custom compose at `app1/docker-compose.yml` — **not** Laravel Sail
> (`family-fund-app/docker-compose.yml`).

Run from `app1/`:

```bash
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

Services:

- **familyfund** — Laravel app (custom Dockerfile with wkhtmltopdf).
- **mariadb** (container: `db`) — Database (`famfun_dev`/`1234`, db
  `familyfund_dev`).
- **mailhog** — Email testing via Mailpit (arm64-native, drop-in MailHog
  replacement; UI http://localhost:8025). Compose service key stays `mailhog`;
  `container_name` varies per env (`mail-dev`, `mail-acl`, …).
- **quickchart** — Chart-image generation (http://localhost:3400).

Services reach each other by **compose service name**, not `container_name`.
`.env` uses `MAIL_HOST=mailhog`. On a dev machine `.env` is a symlink to
`.env.dev`, which routes mail to `mailhog:1025`.

## Database backup

Run from `app1/family-fund-app/generators/`. Scripts need connection params
since DB runs in Docker.

```bash
./dump_data.sh dev --host=127.0.0.1 --port=3306 --user=famfun_dev -p1234
./dump_ddl.sh  dev --host=127.0.0.1 --port=3306 --user=famfun_dev -p1234
```

Backups land in:

- DDL: `database/familyfund_ddl_YYYYMMDD.sql`
- Data: `database/dev/familyfund_dev_data_YYYYMMDD.sql`

Load prod to dev + anonymize (canonical entry point). Paths below are
repo-rooted — run from the repo root:

```bash
mysql -h 127.0.0.1 -u famfun_dev -p1234 familyfund_dev \
  < database/prod/familyfund_prod_data_*.sql
./app1/family-fund-app/bin/prod-to-dev.sh
```

`bin/prod-to-dev.sh` runs `database/prod_to_dev.sql`,
`php artisan migrate --force`, and the `RolesAndPermissionsSeeder` +
`QaTestUsersSeeder` seeders. Skipping it leaves the credit-line allocation
tables un-migrated and the spatie permissions table empty.

## Code generators

Boilerplate generators using InfyOm Laravel Generator in
`app1/family-fund-app/generators/`:

- `models_from_table.sh` — Model, controller, repository, views from existing DB table.
- `api_from_json.sh` — API endpoints from JSON schema.
- `models_from_json.sh` — Models from JSON schema.
- `dump_ddl.sh` / `dump_data.sh` — DB export utilities.
- `form_to_blade_converter.py` — Convert forms to Blade templates.

These create: Model, Repository, Controller, Request classes, Views (index,
create, edit, show, fields, table).

## Code style & security

- Repository pattern — never query Eloquent directly from controllers.
- Validate everything via `app/Http/Requests/` form requests.
- Authorize at the **controller** layer (policies + Spatie permissions) AND at
  the **API model** layer (`AuthorizesApiAccess` trait, [ED-0004](docs/ENGINEERING_DECISIONS.md))
  — defense in depth.
- Deny-by-default ACL: see [docs/ACL_IMPLEMENTATION.md](docs/ACL_IMPLEMENTATION.md)
  and [ED-0003](docs/ENGINEERING_DECISIONS.md).
- Secrets stay out of git ([ED-0001](docs/ENGINEERING_DECISIONS.md)); committed
  secrets are SOPS+age-encrypted ([ED-0002](docs/ENGINEERING_DECISIONS.md)).
- Pre-commit gitleaks is wired up (`.gitleaks.toml`, `.pre-commit-config.yaml`).
- Required CI gate: Security Scan ([ED-0005](docs/ENGINEERING_DECISIONS.md)).

## Things to avoid

- **NEVER edit code directly on the production server** — always edit locally
  and deploy.
- **NEVER run `docker compose` directly on prod** — use the wrapper scripts.
- Don't bypass the wrapper scripts — they enforce env vars
  (`DSTRADER_DONT_EXECUTE_ORDERS=1`, etc. for dstrader).
- Don't use the preview `pool.sh` for tests with `RefreshDatabase` — use
  `testpool.sh` instead.
- Don't commit unencrypted secrets — the pre-commit hook will catch them, but
  treat the hook as a backstop, not a primary control.
- Don't run tests against `localhost:3306` from the host — use
  `docker exec familyfund …`.
- Don't supersede an `ED-NNNN` decision in place — add a new entry and flip
  the old one's `Status` to `Superseded by ED-NNNN`.

## Development notes

- Use http://localhost:3001 for local testing (the `docker-compose.dev.yml`
  default), not the production URL. The env-compose path
  (`docker-compose.env.yml`) uses `${FF_APP_PORT:-3000}`.
- Share prices are calculated from the previous day's NAV.
- Quarterly reports run via queue jobs.
- PDF reports use wkhtmltopdf (installed in the container).
- Email goes through Mailpit in development (compose service `mailhog`,
  `MAIL_HOST=mailhog`).
- **Frontend assets**: run `npm install && npm run build` from
  `app1/family-fund-app/` after changing Blade templates with new Tailwind
  classes (Tailwind purges unused classes).

## Test users (dev-only)

A dedicated test user exists for automated testing (created by
`prod_to_dev.sql`):

- **Email:** `claude@test.local`
- **Password:** `claude-test-2024`

Dev-only auto-login route (local environment only):

```
GET /dev-login/{redirect?}              # logs in as claude@test.local
GET /dev-login/{redirect?}?as=<email>   # logs in as that user
GET /dev-login/{redirect?}?as=<alias>   # alias: admin | system-admin | fund-admin
                                        #        financial-manager | beneficiary
```

Examples:

```
curl -L "http://localhost:3001/dev-login/accounts/8"                  # claude@test.local
curl -L "http://localhost:3001/dev-login/dashboard?as=fund-admin"     # qa-fund-admin@test.local
curl -L "http://localhost:3001/dev-login/funds/2/overview?as=admin"   # resolves to ADMIN_EMAILS[0]
```

`fund-admin`/`financial-manager`/`beneficiary` aliases resolve to
`qa-*@test.local` users seeded by `QaTestUsersSeeder` (password=`password`,
scoped to the first fund):

```bash
docker exec familyfund php artisan db:seed --class=RolesAndPermissionsSeeder --force
docker exec familyfund php artisan db:seed --class=QaTestUsersSeeder --force
```

## Misc

- FamilyFund `.env` should be a symlink to `.env.<ENV>`.

## Project tracking

Work for this repo is tracked on the **Trading & Fund** GitHub Project board
(user-level project **#1**, shared with `dstrader`, `dstrader-aws`,
`dstrader_python`, `DSTraderAnalysis`).

- Board: <https://github.com/users/jdtogni78/projects/1>
- Add an issue to the board:
  ```bash
  gh issue create --repo jdtogni78/FamilyFund --title "..." --body "..."
  gh project item-add 1 --owner jdtogni78 --url <ISSUE_URL>
  ```
- Quick note without an issue: `gh project item-create 1 --owner jdtogni78 --title "..."`
- Open the board: `gh project view 1 --owner jdtogni78 --web`

Full setup notes: [docs/GITHUB_PROJECTS.md](docs/GITHUB_PROJECTS.md). Managing
boards via CLI needs the `project` token scope
(`gh auth refresh -s project --hostname github.com`).
