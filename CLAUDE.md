# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Family Fund is a Laravel 11 financial fund management system for tracking fund shares, portfolios, beneficiary accounts, and transactions. Uses repository pattern with extensive test coverage.

## Engineering decisions

Significant, non-obvious decisions are logged in
[docs/ENGINEERING_DECISIONS.md](docs/ENGINEERING_DECISIONS.md) (lightweight ADRs).
Read it before re-litigating an architectural/security choice; add an `ED-NNNN`
entry when you make a new one.

## New-machine setup

See [SETUP.md](SETUP.md). Env secrets are committed **encrypted** (SOPS+age,
`*.sops`); decrypt with `app1/family-fund-app/bin/secrets.sh decrypt` once the age
key is in place (see ED-0002). DB dumps + the legacy GPG bundle still live on
melnick at `~/familyfund_db_backups/` — pull with `scp -O` (Synology drops SFTP).

## Tech Stack

- **Backend**: Laravel 11, PHP 8.2+
- **Frontend**: Blade + Livewire 3 + Volt + Bootstrap 5 + Tailwind CSS
- **Database**: MariaDB
- **Build**: Vite
- **PDF**: wkhtmltopdf via knplabs/knp-snappy

## Commands

All commands run from `app1/` (NOT `app1/family-fund-app/`):

```bash
# Deployment
/Users/dtogni/dev/dstrader-docker/local/deploy_ff.sh  # Deploy FamilyFund to prod (REDACTED_PROD_HOST)

# Production Container Management (on dstrader server REDACTED_PROD_HOST)
# IMPORTANT: Always use these scripts, never run docker compose directly!
# FamilyFund:
ssh dstrader "cd ~/dev/dstrader-docker && ./server/dev/run_familyfund.sh prod"
# Wake prod server (if sleeping): /Users/dtogni/dev/dstrader-docker/local/wake_spirit.sh
# DStrader (auto-runs weekdays 12:33 PM via cron, or manually):
ssh dstrader "cd ~/dev/dstrader-docker/dstrader/runtime && ./start_dstrader.sh restart prod -d"
# Env vars: DSTRADER_DONT_EXECUTE_ORDERS=1, DSTRADER_DONT_RUN_STRATEGY=1, DSTRADER_DONT_VALIDATE_TRADING_HOURS=1, DSTRADER_KEEP_RUNNING=1

# DStrader Prod Logs:
# ~/dev/dstrader-docker/dstrader/prod/logs/dstrader*.log  - Main dstrader logs
# ~/dev/dstrader-docker/dstrader/prod/logs/tws*.log       - TWS/IB Gateway logs
# ~/dev/dstrader-docker/dstrader/prod/logs/run_report*.log - Report generation logs
ssh dstrader "tail -100 ~/dev/dstrader-docker/dstrader/prod/logs/dstrader.log"  # View recent logs

# Development (run from app1/)
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up
docker exec familyfund composer install
npm install && npm run dev   # run from app1/family-fund-app/

# Testing (IMPORTANT: must run in Docker - database not accessible from host)
docker exec familyfund php artisan test  # See Testing section below for more options

# Database
php artisan migrate
php artisan migrate:fresh
php artisan db:seed
php artisan tinker

# Queue (for email/reports)
php artisan queue:listen
```

## Architecture

### Directory Structure (app1/family-fund-app/)

- `app/Models/` - Eloquent models (many have `*Ext` variants with business logic)
- `app/Repositories/` - Repository pattern for data access (extends BaseRepository)
- `app/Http/Controllers/` - Standard CRUD controllers
- `app/Http/Controllers/API/` - REST API endpoints
- `app/Http/Controllers/WebV1/` - Extended controllers (trade portfolios, rebalancing, reports)
- `app/Http/Requests/` - Form validation
- `app/Livewire/` - Volt single-file components
- `resources/views/` - Blade templates organized by entity

### Key Domain Models

- **Fund** - Investment fund with NAV and share pricing
- **Account/AccountExt** - Beneficiary accounts tracking fund shares
- **Portfolio/TradePortfolio** - Asset collections with allocations
- **Asset** - Stocks, crypto, real estate with price history
- **Transaction** - Deposits, withdrawals, rebalancing trades
- **Goal/AccountGoal** - Beneficiary goal tracking

### Patterns

- Repository pattern: all data access via `*Repository` classes
- Controllers inject repositories and use form request validation
- `*Ext` model variants contain extended business logic
- Historical views via `as_of` parameter throughout
- API versioning: `/api/`, `/api/v1/`

## Testing

**IMPORTANT:** Tests must run in Docker (`docker exec familyfund php artisan test`) - database is not accessible from host.

**Current Status (2026-05-14):** 1791 passing. No tests are currently tagged with `incomplete` or `needs-data-refactor` groups — the `<exclude><group>incomplete</group></exclude>` block in `phpunit.xml` is a no-op kept as scaffolding for future use.

Prefer the wrapper `app1/family-fund-app/bin/test.sh`: rebuilds Vite assets if stale, autodetects the container name (`$FF_CONTAINER` → `ffacl-familyfund-1` → `familyfund`), then runs `php artisan test`. Pass-through args work (e.g. `bin/test.sh --filter=Foo`).

**Nightly (long-running) tests.** The slowest tests are tagged `@group nightly` and **excluded from the default run** via `phpunit.xml`, so `bin/test.sh` / `php artisan test` is ~6min instead of ~17min. Tagged: `PDFTest`, `SmokeTest`, `OperationsControllerTest` (whole classes, each ≥50s) and `AclMatrixTest::test_acl_matrix_matches_golden` (the exhaustive 151-route × 6-role sweep, ~7min — tagged at the method level). `AclMatrixTest` still keeps a fast critical-routes subset (`test_acl_matrix_critical_routes_match_golden`, ~6s) that runs **every time** over the security-sensitive surface (admin, credit lines, PII, money, user management). Run the slow group with `bin/test-nightly.sh` (passes `--group=nightly`, which overrides the config exclude) — intended for a nightly job. To rank slow tests for re-tagging: `./vendor/bin/phpunit --log-junit junit.xml` then sort `<testcase time=...>` by class.

Tests organized in `tests/`:
- `Feature/` - Full HTTP request tests
- `Feature/AclMatrixTest.php` - Discoverable ACL matrix: walks every GET route × every named role, diffs against `tests/golden/acl_matrix.json`. Refresh with `ACL_MATRIX_UPDATE=1`. Full sweep is `@group nightly`; a curated critical-routes subset runs every time (see `CRITICAL_ROUTES`).
- `Feature/AuthorizationTest.php` - Policy/service unit tests
- `APIs/` - API endpoint tests (28+ suites)
- `Repositories/` - Repository pattern tests (30+ suites)
- `GoldenData/` - Integration tests with versioned datasets
- `DataFactory.php` - Test data generation
- `Fixtures/TestFixtures.php` - Higher-level reusable fixtures including `aclUsers(Fund $fund)` (one user per role)

```bash
# Run tests in Docker (REQUIRED - do not run locally)
docker exec familyfund php artisan test
docker exec familyfund php artisan test --filter=TransactionTest    # Single test

# Coverage report
docker exec familyfund ./vendor/bin/phpunit --coverage-text 2>&1 | grep -E "^  (Lines|Methods|Classes):"
```

### Isolated-DB test env (testpool) — for coverage, RefreshDatabase, parallel runs

Tests in the main `familyfund` container reset the shared `familyfund_dev` DB
(RefreshDatabase wipes it), which clobbers any other session using dev. For
coverage / Dusk / migration trials / parallel-session test runs, lease an
isolated slot from `~/.familyfund-pool/testpool.sh` instead — each slot has
its own `familyfund_testN` DB built fresh on every claim by `migrate:fresh` +
the synthetic `Database\Seeders\TestBaselineSeeder` (no real-data dump lives in
the repo — see #78). `account 7` / `user1@dev.familyfund.local` / the first fund
come from that seeder.

```bash
# From this worktree's app1/
~/.familyfund-pool/testpool.sh list                                # 5 slots: test0..test4
~/.familyfund-pool/testpool.sh claim "<label>"                     # lease + seed DB + bring up stack
~/.familyfund-pool/testpool.sh run        -- --filter=Foo          # `php artisan test` in this worktree's slot
~/.familyfund-pool/testpool.sh tour       -- --filter=FooDuskTest  # Dusk w/ Selenium sidecar
~/.familyfund-pool/testpool.sh reseed test2                        # re-seed mid-lease
~/.familyfund-pool/testpool.sh release                             # free the slot
```

Coverage in a slot (pcov is in `app1/Dockerfile`):

```bash
docker exec familyfund-<slot> sh -c "cd /app && \
  ./vendor/bin/phpunit --coverage-text --exclude-group=incomplete,needs-data-refactor" \
  2>&1 | grep -E '^  (Lines|Methods|Classes):'
```

**Do not** use `pool.sh` (the preview pool, `pool0..pool5` at ports
3020–3025) for tests — every preview slot hardcodes `DB_DATABASE=familyfund_dev`,
so RefreshDatabase will wipe shared dev data.

See the **Trading & Fund** GitHub Project board (#1) for remaining test-fix work (issues prefixed `test:`).

## Docker Services

**Note:** Uses custom docker-compose at `app1/docker-compose.yml`, NOT Laravel Sail (`family-fund-app/docker-compose.yml`).

Run from `app1/`:
```bash
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

Services:
- **familyfund** - Laravel app (custom Dockerfile with wkhtmltopdf)
- **mariadb** (container: `db`) - Database (famfun_dev/1234, database: familyfund_dev)
- **mailhog** - Email testing via Mailpit (arm64-native, drop-in MailHog replacement; UI: http://localhost:8025). Compose service key kept as `mailhog`; `container_name` varies per env (`mail-dev`, `mail-acl`, …).
- **quickchart** - Chart image generation (http://localhost:3400)

Environment: services reach each other by **compose service name**, not `container_name`. `.env` uses `MAIL_HOST=mailhog` (the service key). On a dev machine `.env` is a symlink to `.env.dev`, which routes mail to `mailhog:1025`.

## Database Backup

Run from `app1/family-fund-app/generators/`. Scripts need connection params since DB runs in Docker.

```bash
# Dev backup (connects to container on localhost:3306)
./dump_data.sh dev --host=127.0.0.1 --port=3306 --user=famfun_dev -p1234

# Dev DDL backup
./dump_ddl.sh dev --host=127.0.0.1 --port=3306 --user=famfun_dev -p1234
```

Backups saved to:
- DDL: `database/familyfund_ddl_YYYYMMDD.sql`
- Data: `database/dev/familyfund_dev_data_YYYYMMDD.sql`

Load prod to dev + anonymize (see README.md for full docs):
```bash
mysql -h 127.0.0.1 -u famfun_dev -p1234 familyfund_dev < database/prod/familyfund_prod_data_*.sql
# Anonymize + apply pending migrations + seed permissions/QA users in one step.
app1/family-fund-app/bin/prod-to-dev.sh
```

`bin/prod-to-dev.sh` is the canonical entry point — it runs `database/prod_to_dev.sql`,
`php artisan migrate --force`, and the `RolesAndPermissionsSeeder` + `QaTestUsersSeeder`
seeders. Skipping it leaves the credit-line allocation tables un-migrated and the
spatie permissions table empty.

## Code Generators

Boilerplate code generators using InfyOm Laravel Generator in `app1/family-fund-app/generators/`:

- **`models_from_table.sh`** - Generate model, controller, repository, views from existing DB table
  ```bash
  php artisan infyom:scaffold ModelName --fromTable --tableName table_name
  ```
- **`api_from_json.sh`** - Generate API endpoints from JSON schema
  ```bash
  php artisan infyom:api ModelName --fieldsFile resources/api_schemas/ModelName.json
  ```
- **`models_from_json.sh`** - Generate models from JSON schema
- **`dump_ddl.sh`** / **`dump_data.sh`** - Database export utilities
- **`form_to_blade_converter.py`** - Convert forms to Blade templates

The generators create: Model, Repository, Controller, Request classes, Views (index, create, edit, show, fields, table).

## Development Notes

- **NEVER edit code directly on the production server (REDACTED_PROD_HOST)** - always edit locally and deploy
- Use http://localhost:3000 for testing, not the production URL
- Share prices calculated from previous day's NAV
- Quarterly reports generated via queue jobs
- PDF reports use wkhtmltopdf (installed in container)
- Email testing via Mailpit in development (compose service `mailhog`, `MAIL_HOST=mailhog`)
- **Frontend assets**: Run `npm install && npm run build` from `app1/family-fund-app/` after changing Blade templates with new Tailwind classes (Tailwind purges unused classes)

## Claude Testing

A dedicated test user exists for automated testing (created by `prod_to_dev.sql`):
- **Email**: claude@test.local
- **Password**: claude-test-2024

Dev-only auto-login route (local environment only):
```
GET /dev-login/{redirect?}              # logs in as claude@test.local
GET /dev-login/{redirect?}?as=<email>   # logs in as that user
GET /dev-login/{redirect?}?as=<alias>   # alias: admin | system-admin | fund-admin
                                        #        financial-manager | beneficiary
```
Examples:
```
curl -L "http://localhost:3000/dev-login/accounts/8"                       # claude@test.local
curl -L "http://localhost:3000/dev-login/dashboard?as=fund-admin"          # qa-fund-admin@test.local
curl -L "http://localhost:3000/dev-login/funds/2/overview?as=admin"        # admin@dev.familyfund.local
```

`fund-admin`/`financial-manager`/`beneficiary` aliases resolve to `qa-*@test.local`
users seeded by `QaTestUsersSeeder` (password=`password`, scoped to the first fund):
```bash
docker exec familyfund php artisan db:seed --class=RolesAndPermissionsSeeder --force
docker exec familyfund php artisan db:seed --class=QaTestUsersSeeder --force
```

# Misc

* FamilyFund .env should be a link to .env.<ENV>

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

Full setup notes: `~/dev/GITHUB_PROJECTS.md`. Managing boards via CLI needs the
`project` token scope (`gh auth refresh -s project --hostname github.com`).
