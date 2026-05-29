# DEVELOPMENT

Local-development reference for FamilyFund: prerequisites, setup pointers, how
to run the app + queue + tests, and the lint/static-analysis gates. Structured
per the [AGENTS.md spec](https://agents.md/) (project overview, setup, run,
test, style, security notes, things to avoid). Fact-sourced from
`app1/family-fund-app/composer.json`, `app1/Dockerfile`,
`app1/docker-compose*.yml`, `phpunit.xml`, `bin/*.sh`, and `.github/workflows/*`.

See also: [SETUP.md](../SETUP.md) (new-machine bootstrap, secrets, DB dump),
[ENGINEERING_DECISIONS.md](ENGINEERING_DECISIONS.md) (ED-0006 nightly tests,
ED-0007 testpool, ED-0011 vite pin, ED-0013 phpstan, ED-0014 synthetic baseline).

Scope mapped to ticket #60: §1 prereqs, §2 setup pointer (SETUP.md fold-in),
§3 run app + queue worker, §4 test commands + coverage, §5 lint/format/static
analysis, §7 IDE notes (`_ide_helper.php`).

---

## 1. Prerequisites

All app processes run **inside Docker** — host tools are needed only for git
hygiene, frontend build, and secrets decryption.

| Tool | Version | Purpose |
|---|---|---|
| Docker Desktop + Compose v2 | current | runs every PHP process; DB; mail; charts |
| git | any modern | clone, hooks |
| Node | 18+ (CI uses 20) | Vite build on the host (volume-mounted into the container) |
| npm | bundled with Node | `npm ci` + `npm run build` from `app1/family-fund-app/` |
| sops + age | latest | decrypt env secrets from the separate `familyfund-secrets` repo (ED-0002, ED-0017) |
| gpg | system | legacy fallback only — the melnick GPG bundle predates SOPS |
| pre-commit or `bin/install-git-hooks.sh` | either | gitleaks pre-commit hook (pick **one** install path; see [SETUP.md](../SETUP.md) §1) |

**In-container runtime** (baked by [`app1/Dockerfile`](../app1/Dockerfile)):
PHP 8.4 CLI on Debian bookworm + extensions `pdo_mysql mbstring exif pcntl
bcmath gd zip opcache pcov`, plus `wkhtmltopdf` (PDF rendering), `mhsendmail`
(MailHog SMTP shim), and `composer`. `composer.json` requires `php ^8.2`, so PHP
8.2/8.3 also works if you build a custom image; CI and the shipped Dockerfile
both use 8.4.

**Database:** MariaDB LTS (`mariadb:lts` image,
[`app1/docker-compose.yml:3`](../app1/docker-compose.yml)). Schema/data
materialized by the dev SQL dump + post-April migrations — see SETUP.md §7–8.

---

## 2. Setup (one-liner pointer)

The full bootstrap (SSH to melnick, SOPS+age key, secrets repo, DB dump load,
post-April migrations, role seeders) lives in **[SETUP.md](../SETUP.md)** —
this doc deliberately does not duplicate it. Quick recap of the steps once
prereqs are installed:

```bash
git clone git@github.com:jdtogni78/FamilyFund.git ~/dev/FamilyFund
cd ~/dev/FamilyFund
# 1. install the gitleaks hook (pick ONE: pre-commit OR bin/install-git-hooks.sh)
# 2. clone the secrets repo + put the age key at ~/.config/sops/age/keys.txt
app1/family-fund-app/bin/secrets.sh decrypt          # materializes .env*
# 3. pull DB dumps from melnick: see SETUP.md §3
# 4. bring up the stack:
cd app1 && docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
docker exec familyfund composer install
# 5. frontend assets (host-side, mounted into container):
cd family-fund-app && npm install --legacy-peer-deps && npm run build && cd ..
# 6. load dev DB + run post-April migrations + RolesAndPermissionsSeeder
#    (see SETUP.md §7–8 for the exact commands and dbuser/dbpass per stack)
```

`app1/family-fund-app/.env` is a **symlink** to `.env.dev` (managed by
`bin/secrets.sh decrypt`). Never edit the symlink target by hand for secret
changes — use `bin/secrets.sh edit <name>` so the encrypted `.sops` twin in the
`familyfund-secrets` repo stays in sync (ED-0002).

---

## 3. Running the app

All compose commands run from **`app1/`** (NOT `app1/family-fund-app/`):

```bash
# Dev stack (Laravel app, mariadb, mailpit-as-mailhog, quickchart)
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
docker ps     # confirm familyfund + mariadb + mailhog + quickchart are Up
```

Service map ([`app1/docker-compose.yml`](../app1/docker-compose.yml),
[`docker-compose.dev.yml`](../app1/docker-compose.dev.yml)):

| Service (compose key) | Host port | Purpose |
|---|---|---|
| `familyfund` | `3001 -> 8000` | Laravel app (`php artisan serve` is `Dockerfile` CMD) |
| `mariadb` | `3306 -> 3306` | DB `familyfund_dev`. The app container connects **as root** in dev compose (`DB_USERNAME=root` + `DB_PASSWORD=${FF_DB_PASSWORD}`, see [`docker-compose.dev.yml`](../app1/docker-compose.dev.yml)). A second user `famfun_dev` / `${FF_DB_USER_PASSWORD}` is also seeded (used by the dumper scripts on the host). |
| `mailhog` | `8025` UI, `1025` SMTP | Mailpit (arm64-native, drop-in MailHog) |
| `quickchart` | `3400` | Chart image generation, called via `quickchart:3400` from inside the stack |

**Important:** containers reach each other by **compose service name** (e.g.
`MAIL_HOST=mailhog`), not by `container_name`. The dev `.env` already routes
mail to `mailhog:1025`. `container_name` varies per overlay (e.g. `mail-dev`,
`mail-acl`) — see CLAUDE.md.

Parallel overlays exist for an isolated ACL test stack (`docker-compose.ffacl.yml`,
ports 3401/3406/...) and an env-driven stack (`docker-compose.env.yml`); the
preview pool (`pool0..pool5`) and test pool (`test0..test9`) layer on top.

Hit the app:

```bash
open http://localhost:3001                       # dev stack (port 3001 per docker-compose.dev.yml)
open http://localhost:3001/dev-login/dashboard   # auto-login as claude@test.local
```

> Port note: root `AGENTS.md`/`CLAUDE.md` still reference `localhost:3000` in
> a few places — that is stale ahead of the AGENTS.md refresh in #56. The
> compose file [`app1/docker-compose.dev.yml`](../app1/docker-compose.dev.yml)
> maps `3001 -> 8000`, so **3001 is correct** for the dev stack.

> Test-user note: the seeded automated-test user is
> `claude@test.local` / `claude-test-2024` (created by `prod_to_dev.sql`).
> Root `AGENTS.md` currently shows `Codex@test.local` — that is also stale and
> tracked by #56.

`/dev-login` is gated to `APP_ENV` ∈ `{dev, local}` — a reused stage container
returns 404 there; log in normally at `/login`.

### 3.1 Queue worker

`config/queue.php:16` → `QUEUE_CONNECTION` defaults to **`database`**
(`jobs` table). Used for emails, PDF reports, and quarterly report generation.
`phpunit.xml:47` overrides this to `sync` during tests so jobs run inline.

**Local dev — one-shot in foreground (logs visible):**

```bash
docker exec familyfund php artisan queue:listen --tries=1
# or, code-reload friendlier in long sessions:
docker exec familyfund php artisan queue:work --sleep=3 --tries=3
```

**Composer `dev` script — all four processes in parallel via concurrently**
([`composer.json:71-74`](../app1/family-fund-app/composer.json)):

```bash
docker exec familyfund composer dev
# runs: php artisan serve + queue:listen + pail (logs) + npm run dev
```

**Production parity** is via supervisord
([`app1/supervisor.conf:3`](../app1/supervisor.conf)): 2 worker processes,
`queue:work --sleep=3 --tries=3`, autorestart on exit. Not auto-started by the
dev compose `CMD`; OperationsController has an in-app "start the worker"
shortcut (`WebV1/OperationsController.php`) that spawns a daemonized
`queue:work`. Restart the worker after deploying job code so workers pick up
new class definitions.

---

## 4. Testing

**Tests must run in Docker** — the default suite does not use `RefreshDatabase`
and `phpunit.xml` only sets `APP_ENV=testing` (no separate test connection), so
the suite runs against whatever DB the container's `.env` points at, and
`quickchart:3400` + `wkhtmltopdf` are only reachable from inside the stack.
Running it in the dev container therefore writes test rows into
`familyfund_dev`; any test that wants to use `RefreshDatabase` (or otherwise
reset the schema) must lease an isolated `testpool` slot — see §4.1.

```bash
# Preferred wrapper — rebuilds Vite if stale + autodetects container name
# ($FF_CONTAINER -> ffacl-familyfund-1 -> familyfund)
app1/family-fund-app/bin/test.sh
app1/family-fund-app/bin/test.sh --filter=AuthorizationTest

# Raw equivalent
docker exec familyfund php artisan test
docker exec familyfund php artisan test --filter=TransactionTest
```

Test suites are split in [`phpunit.xml`](../app1/family-fund-app/phpunit.xml):
`Unit`, `Feature`, `APIs`, `Repositories`, `GoldenData`. The default run
**excludes `@group nightly`** (ED-0006) for fast feedback (~6 min vs ~17 min).
Run the slow group nightly:

```bash
app1/family-fund-app/bin/test-nightly.sh        # passes --group=nightly
```

Currently nightly-tagged: `PDFTest`, `SmokeTest`, `OperationsControllerTest`,
and `AclMatrixTest::test_acl_matrix_matches_golden`. `AclMatrixTest` keeps a
fast `test_acl_matrix_critical_routes_match_golden` (~6s) in the default run
covering the security-sensitive subset (admin, credit lines, PII, money, user
management).

### 4.1 Coverage

`pcov` is baked into the image
([`app1/Dockerfile:49`](../app1/Dockerfile)). The fast path:

```bash
docker exec familyfund ./vendor/bin/phpunit --coverage-text 2>&1 \
  | grep -E "^  (Lines|Methods|Classes):"
```

For coverage that needs `RefreshDatabase` or parallel runs against an isolated
DB, lease a `testpool` slot (ED-0007) — each slot owns its own `familyfund_testN`
DB, seeded from `Database\Seeders\TestBaselineSeeder` (ED-0014, no real-data
dump lives in the repo):

```bash
~/.familyfund-pool/testpool.sh list           # 10 slots: test0..test9
~/.familyfund-pool/testpool.sh claim "<label>"
~/.familyfund-pool/testpool.sh run    -- --filter=Foo
~/.familyfund-pool/testpool.sh tour   -- --filter=FooDuskTest   # Dusk + Selenium sidecar
~/.familyfund-pool/testpool.sh reseed test2
~/.familyfund-pool/testpool.sh release
```

> **Do not** use the preview pool (`pool.sh`, `pool0..pool5`) for tests —
> every preview slot hardcodes `DB_DATABASE=familyfund_dev`, so RefreshDatabase
> would wipe shared dev data.

### 4.2 CI parity

`.github/workflows/tests.yml` reproduces the exact local setup: builds Vite
on the runner, brings up `docker-compose.yml + docker-compose.dev.yml`,
runs `composer install`, builds the baseline with
`migrate:fresh --seed --seeder='Database\Seeders\TestBaselineSeeder'` and
`FF_SYNTHETIC_BASELINE=1`. The **PR / push-to-main** job runs only the fast
tier (`php artisan test --exclude-group=nightly`); a separate
`--group=nightly` step runs **only** on the `schedule:` cron and
`workflow_dispatch` (see [`tests.yml:99-122`](../.github/workflows/tests.yml)).

---

## 5. Lint / format / static analysis

`composer.json` ships **Pint** (`laravel/pint`) and **Larastan/PHPStan** as dev
deps; both run in-container:

```bash
# Pint — Laravel code style (PSR-12 + Laravel preset by default; no pint.json
# means defaults are in effect)
docker exec familyfund ./vendor/bin/pint
docker exec familyfund ./vendor/bin/pint --test           # check-only, exit 1 on diff

# Larastan / PHPStan — level 5, baseline-gated (ED-0013, #77)
docker exec familyfund ./vendor/bin/phpstan analyse --no-progress --memory-limit=1G
```

PHPStan config: [`phpstan.neon`](../app1/family-fund-app/phpstan.neon) (level
5, paths = `app/`, baseline at `phpstan-baseline.neon`). The CI step is
**non-blocking by design** (`continue-on-error: true` in
`security-scan.yml`) until the signal/noise settles — raise the level or drop
the continue-on-error once the baseline shrinks.

`composer audit --locked` and `npm audit` also run in CI
(`.github/workflows/security-scan.yml`) — `app1/family-fund-app/bin/security-scan.sh`
runs all three locally (plus gitleaks, semgrep, and trivy). ZAP is **not** in
that local wrapper; it runs only as separate jobs in CI
(`zap-baseline.yml`-style steps in `security-scan.yml`) and via the standalone
`bin/zap-baseline.sh` / `bin/zap-authenticated.sh` scripts.

TODO(verify): a project-wide `pint.json` would let us pin a non-default preset;
none exists today, so Pint applies Laravel's built-in preset.

---

## 6. Logs, debugging, tinker

```bash
# Tail Laravel logs (Pail — real-time, structured)
docker exec familyfund php artisan pail --timeout=0

# Raw log files (mounted via volume from host)
tail -f app1/family-fund-app/storage/logs/laravel.log

# REPL into the running app context
docker exec -it familyfund php artisan tinker
```

Email in dev: with `.env.dev`'s `MAIL_MAILER=smtp` + `MAIL_HOST=mailhog`,
outgoing mail lands in Mailpit at <http://localhost:8025>. (The image's
`sendmail_path` is set to `mhsendmail --smtp-addr=mail:1025` —
[`app1/Dockerfile:52`](../app1/Dockerfile) — which uses the legacy `mail`
hostname; it only fires for code paths that bypass the SMTP mailer, which is
not the default.) Telescope is disabled (`phpunit.xml:49`).

---

## 7. IDE notes

A root `_ide_helper.php` (Laravel IDE Helper output) is checked in to give
PhpStorm / VS Code Intelephense facade and model autocompletion. It's **stale**
(the header says "Generated for Laravel 5.5.13 on 2017-09-28") and predates the
current `laravel/framework: ^12.0` (per
[`composer.json:18`](../app1/family-fund-app/composer.json)) on PHP 8.2+, so
it's a partial aid only — verify suggestions against actual framework version.
Root `AGENTS.md` / `CLAUDE.md` still describe the stack as "Laravel 11"; the
shipped `composer.json` requires `^12.0`, so this doc treats Laravel 12 as the
source of truth and #56 will refresh the index docs.

`FamilyFund.iml` is a PhpStorm module file; the `.idea/` directory is
git-ignored per `.gitignore`.

TODO(verify): regenerate via `php artisan ide-helper:generate` once
`barryvdh/laravel-ide-helper` is reinstated (it is **not** in `composer.json`
today, so generation requires `composer require --dev barryvdh/laravel-ide-helper`
first). Whether to refresh `_ide_helper.php` is out of scope for this doc;
revisit when issue #57 lands `docs/ARCHITECTURE.md`.

---

## 8. AGENTS.md convention

This doc is part of the memory-bank set under `docs/` (per the
[AGENTS.md spec](https://agents.md/) — root `AGENTS.md` is meant to act as the
index). The current root `AGENTS.md` does **not yet** link
`docs/DEVELOPMENT.md`; the index refresh is owned by ticket #56 (scaffold).
Until that lands, this doc is the canonical "copy-paste this exact command"
source for development; root `AGENTS.md` / `CLAUDE.md` summarize at a higher
level. There is no `CONTRIBUTING.md` yet — ticket #65 is the hygiene ticket
that will add it alongside a PR-template doc-sync checkbox.
