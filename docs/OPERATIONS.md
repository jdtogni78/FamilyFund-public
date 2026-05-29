# OPERATIONS

Operator runbook index for FamilyFund: how to deploy, run the periodic work,
recover from common incidents, and rotate the things that expire. Day-to-day
**manual fund maintenance** (investment rebalancing, grantor/beneficiary
support) lives in [ManualMaintenance.md](ManualMaintenance.md); the
money-flow subsystem has its own runbook at
[runbooks/money_flow_runbook.md](runbooks/money_flow_runbook.md); the dstrader ⇄ FF service-
token rotation runbook is at
[runbooks/ff-service-token-rotation.md](runbooks/ff-service-token-rotation.md).

This doc takes the **index** approach (issue #59 allows either consolidation or
an index — index keeps each subsystem runbook in one editable place). §6 is the
canonical pointer list.

> Style: each task is `trigger → command → verify → rollback`. Production host
> is `spirit` (the dstrader-docker box); the FamilyFund container runs inside
> the dstrader-docker stack as a **rootless** Docker daemon — every prod
> `docker` call uses `--context rootless`. Never edit code on the server;
> deploy from your dev machine via `dstrader-docker/local/deploy_ff.sh`.

## Map

| Index | What you'll find |
|---|---|
| §1 Topology | Where things run, who can reach what |
| §2 Standard tasks | Deploy, queue worker, replay failed job, reseed, run-due-jobs, test email, validate balances |
| §3 Periodic work | Daily / weekly / monthly / quarterly / yearly |
| §4 Incidents | Common failures + recovery |
| §5 Rotation | Tokens, secrets, env keys |
| §6 Subsystem runbooks | Pointers to deeper docs |

---

## 1. Topology

| Env | Where | URL | Notes |
|---|---|---|---|
| **dev** | dev machine, Docker | http://localhost:3000 (or :3001 with `docker-compose.dev.yml`) | `.env` -> `.env.dev`; `mariadb` container; mail via Mailpit (`http://localhost:8025`). |
| **stage** | dev machine, parallel compose project | varies (port offset, see `app1/launch_docker.sh`) | Reused dstrader image; `APP_ENV=stage`; `/dev-login` disabled (only `local`/`dev`/`testing` gate it — see `app1/family-fund-app/routes/web.php:21`). |
| **prod** | `spirit` | per `.env.prod` on host | Container `familyfund`, MariaDB container `db`. Managed by `~/dev/run_familyfund.sh prod` on the host. Rootless Docker → use `docker --context rootless ...`. |

**Adjacent systems**:

- **dstrader** (Java) — calls FF's `/api/*` as the `dstrader-app` Sanctum token.
- **dstrader bash scheduler** — calls FF's `/api/*` as the `dstrader-scheduler` token.
- **IBKR Flex Query** — pulled by the `FetchDeposits` job (CSV).
- **Banking vendor** (Relay/Mercury) and the money-flow webhook/UI surface
  (`CheckingDeposit`, `BrazilianRecipient`, `mf_audit_log`, operator dashboard,
  `/dev/fake-bank/*`) are **planned / v1-in-design** — referenced by
  `money_flow_plan.md` and `money_flow_runbook.md` but not yet present in the
  running app code. Future operators chasing those models in code should expect
  to land in the design docs, not in `app/Models/`.

## 2. Standard operator tasks

### 2.1 Deploy to prod

`spirit` is the prod host. The canonical deploy path is the wrapped script,
which does substantially more than a raw rsync:

```bash
~/dev/dstrader-docker/local/deploy_ff.sh
```

That script (see `dstrader-docker/local/deploy_ff.sh`) performs:

1. `mariadb-dump familyfund_prod` → timestamped predeploy backup on `spirit`.
2. `cp -r storage/app/private/*` → timestamped storage backup (queued emails / attachments).
3. `npm run build` locally (frontend assets baked in).
4. `rsync -avc` of `app1/` excluding `.git`, `.env`, `tests/`, `storage/app/{private,emails}`, `datadir*`, `logs`, `backups`, `out`, `local`.
5. `rsync` of `dstrader-docker/docker-compose*.yml` to the server.
6. Ownership flip (`jdtogni` for rsync → `dockeruser` for runtime).
7. `cd ~/dev && ./run_familyfund.sh prod` on `spirit` to restart.
8. `docker --context rootless exec familyfund php artisan migrate --force`.
9. `docker --context rootless exec familyfund php artisan optimize:clear`.

If `wake_spirit.sh` (`~/dev/dstrader-docker/local/wake_spirit.sh`) needs to fire
first because the host is asleep, the deploy script does **not** auto-wake —
run it explicitly.

**Verify** —

```bash
ssh spirit "docker --context rootless logs --tail=50 familyfund"
ssh spirit "docker --context rootless exec familyfund php artisan migrate:status | tail -10"
```

The app's HTTP root should return 2xx through whatever ingress prod uses
(check `dstrader-docker/server/dev/run_familyfund.sh prod` for the exposed
port).

**Rollback** — `git checkout <prev-sha>` locally and re-run the script. The
**predeploy DB backup** (step 1) is your safety net if a migration ate data —
restore from `~/dev/dstrader-docker/prod/backups/db-backup-predeploy-*.sql` on
`spirit`. See §4.2 for the restore procedure.

### 2.2 Restart the queue worker

Email and quarterly-report PDFs are queued (`QUEUE_CONNECTION=database`).
**Caveat:** `app1/Dockerfile` boots `php artisan serve` as PID 1 — it does
**not** automatically start `supervisord` even though `app1/supervisor.conf`
defines an `email-queue` program with 2 workers. Confirm on the host whether
`supervisord` is being launched in prod (e.g. via a compose `command:` override
not committed here) before assuming workers are auto-managed; otherwise the
worker has to be started explicitly via the Operations UI or CLI.

| Path | When | How |
|---|---|---|
| **Operations UI** | Any env; ad-hoc | Sign in as system-admin → `/operations` → Start/Stop Queue. Backed by `OperationsController::startQueue / stopQueue` (`app1/family-fund-app/app/Http/Controllers/WebV1/OperationsController.php:315`) — writes a PID file at `storage/app/queue_worker.pid` and sends `SIGTERM` to stop. |
| **CLI** | Inside the container | `php artisan queue:work --sleep=3 --tries=3` (daemonized) — same flags as supervisor.conf. |
| **Supervisor** | Only if it's actually running | `supervisorctl restart email-queue:*` inside the container. |

**Verify** — instead of `queue:work --once` (which consumes a real pending job),
do a no-side-effect check:

```bash
# Inside the container:
php artisan tinker --execute "echo DB::table('jobs')->count();"      # pending count
php artisan tinker --execute "echo DB::table('failed_jobs')->count();"
tail -f /var/log/supervisord.log   # if supervisor is in use
```

The Operations UI shows a "Queue worker running" indicator backed by
`isQueueWorkerRunning()` (`posix_kill($pid, 0)`).

### 2.3 Replay a failed job

Sign in as system-admin → `/operations` → Queue tab. Per-job **Retry**, **Retry
All**, and **Flush** buttons map to
`OperationsController::retryFailedJob/retryAllFailedJobs/flushFailedJobs`
(thin wrappers around `Artisan::call('queue:retry'|'queue:flush')`).

CLI equivalent (inside the container):

```bash
php artisan queue:failed                  # list
php artisan queue:retry <uuid>            # one
php artisan queue:retry all               # all
php artisan queue:flush                   # drop
```

Every action writes an `operation_logs` row (`OperationLog::log()`).

### 2.4 Run scheduled jobs on demand

Two parallel mechanisms — both exist on purpose:

| Mechanism | What it runs | Trigger |
|---|---|---|
| **Laravel scheduler** (`routes/console.php`) | Cross-cutting infra: `security:service-token --check-expiry --warn-days=14` (daily 06:30) + `--prune-expired` (Mon 06:35) | Requires `* * * * * php /app/artisan schedule:run` somewhere. **The repo does not commit a crontab definition for this** — confirm it's installed at the host/orchestrator level (check `crontab -l` on the prod host or any compose `command:` override). The service-token runbook calls this out as a safety net; if it's not wired, expiry warnings won't fire. |
| **App scheduled-jobs** (`scheduled_jobs` table) | Quarterly fund/account reports, holiday sync, etc. — domain jobs the operator can author from the UI | `/operations` → "Run Due Jobs" (uses `ScheduledJobTrait::scheduleDueJobs`); or `php artisan schedule_jobs:run [--as-of=YYYY-MM-DD]` (`RunScheduledJobs`) |

**Caveat:** `php artisan schedule_jobs:run` works by HTTP-POSTing to
`/api/schedule_jobs`, which is inside the `auth:sanctum` middleware group. If
the env doesn't make that route reachable with credentials, the command 401s.
The Operations UI path (admin-signed-in browser) is the reliable manual
trigger.

**Backfill** a missed run by passing a past date: `php artisan schedule_jobs:run
--as-of=<YYYY-MM-DD>` re-evaluates due-ness against that date.

### 2.5 Reseed dev (load prod → dev with anonymization)

```bash
cd ~/dev/FamilyFund/app1                  # run docker commands from app1/, NOT family-fund-app/
docker compose exec familyfund php artisan migrate:fresh
mysql -h 127.0.0.1 -u famfun_dev -p1234 familyfund_dev \
  < family-fund-app/database/prod/familyfund_prod_data_<YYYYMMDD>.sql
# Anonymize + run pending migrations + seed permissions + seed QA users.
FF_CONTAINER=familyfund family-fund-app/bin/prod-to-dev.sh
```

`bin/prod-to-dev.sh` is the **canonical** entry point — skipping it leaves the
credit-line tables un-migrated and the spatie permissions table empty (see
[../SETUP.md](../SETUP.md) §10 and the §13 troubleshooting "Tests get 403s in
`setUp`"). Default `FF_CONTAINER` in the script is `familyfund-dev`; override
to match your `docker ps` (commonly `familyfund` or `app1-familyfund-1`).

### 2.6 Reseed an isolated test slot

For coverage / Dusk / RefreshDatabase runs that must not touch shared dev data,
lease a `testN` slot from the test pool — see
[../SETUP.md](../SETUP.md)'s "Isolated-DB test env (testpool)" section. Each
slot freshly builds `familyfund_testN` from
`Database\Seeders\TestBaselineSeeder` on claim (ED-0014, ED-0007).

```bash
~/.familyfund-pool/testpool.sh claim "<label>"
~/.familyfund-pool/testpool.sh reseed test2     # mid-lease re-seed
~/.familyfund-pool/testpool.sh release
```

### 2.7 Send a test email (verify mail config)

`/operations` → "Send Test Email" (`OperationsController::sendTestEmail`) —
posts a `Mail::raw()` to the address you give it. On dev that lands in Mailpit
(`http://localhost:8025`); on prod it goes through the configured SMTP relay.
Result is logged as `OperationLog::OP_SEND_TEST_EMAIL`.

### 2.8 Validate portfolio balances

`/operations` → "Validate Portfolio Balances" (`validatePortfolioBalances`)
calls `PortfolioExt::validateAllBalances($asOf, $threshold)`. Mismatches >
threshold % surface in the UI and in an `operation_logs` row. Use this after
any rebalance or whenever you suspect drift on the trade-portfolio side. The
default threshold is 5% — tighten when investigating a known issue.

## 3. Periodic work

| Cadence | Task | Where |
|---|---|---|
| **Continuous** | `php artisan schedule:run` (must be installed as a 1-minute crontab somewhere — confirm; not committed in this repo) — drives `routes/console.php` schedules | Host or compose override |
| **Daily 06:30** | `security:service-token --check-expiry --warn-days=14` (warns by email if a dstrader service token nears expiry) | `routes/console.php` |
| **Daily** | Money-flow operator checks (reconciliation, flagged tx, pending-attribution, buffer signals) — **applies once money-flow ships** | [runbooks/money_flow_runbook.md](runbooks/money_flow_runbook.md) §1 |
| **Weekly Mon 06:35** | `security:service-token --prune-expired` | `routes/console.php` |
| **Weekly** | Stale DepositRequest cleanup, recipient verification queue, audit-log spot check | money_flow_runbook §2 |
| **Every 60 days** | Rotate dstrader service tokens (TTL 90d) | [runbooks/ff-service-token-rotation.md](runbooks/ff-service-token-rotation.md) (cron lives in dstrader-docker per #82; script may not yet exist at HEAD) |
| **Quarterly (months 3/6/9/12)** | Generate quarterly reports — runs as queued jobs from the `scheduled_jobs` table; preview first per money_flow_runbook §3 | `/operations` → Run Due Jobs, or the queue worker fires on its own |
| **Quarterly** | Rebalance the IBKR investment account to target percentages | manual; see [ManualMaintenance.md](ManualMaintenance.md) |
| **Yearly** | `holidays:sync <exchange>` (e.g. `nyse`) — refreshes the exchange-holiday table consumed by trade-portfolio scheduling | `SyncExchangeHolidays` command |
| **Yearly** | Investment-board strategy/percentages review | manual; [ManualMaintenance.md](ManualMaintenance.md) |

## 4. Incidents

Each playbook: **trigger → detect → recover → escalate**. Escalation contacts:
the developer + trustee (single-operator project; in a multi-person setup, fill
in the on-call list at the top of this section).

### 4.1 Container won't start after deploy

**Trigger:** `docker --context rootless ps` shows `familyfund` not running, or
`docker --context rootless logs familyfund` shows a fatal — typically a
syntax/config error, missing migration, or `.env` symlink broken (convention
is `.env -> .env.<APP_ENV>`).

**Steps:**
1. Confirm `.env` resolves:
   `ssh spirit "ls -la ~/dev/FamilyFund/app1/family-fund-app/.env"`.
2. If env is fine, check unrun migrations:
   `ssh spirit "docker --context rootless exec familyfund php artisan migrate:status"`.
3. Roll back the code (§2.1 rollback) if the failure correlates with this
   deploy.

**Escalate** if step 3 doesn't recover within 15min: contact the trustee
(prod outage), capture the full `docker logs` for post-incident.

### 4.2 Migration broke prod

**Trigger:** `migrate --force` errored partway through; container is up but the
schema is half-applied.

**Steps:**
1. **Stop the queue worker first** (it will hammer broken tables) — Operations
   UI → Stop, or kill the supervisor program.
2. Inspect: `docker --context rootless exec familyfund php artisan migrate:status`.
   Identify the last clean migration.
3. Restore from the **predeploy backup** that `deploy_ff.sh` created at
   `~/dev/dstrader-docker/prod/backups/db-backup-predeploy-<DATE>.sql` on
   `spirit`. If that's gone (deploy crashed before backup or you deployed
   manually), fall back to the most recent backup on `melnick`
   (`~/familyfund_db_backups/familyfund_prod_data_<DATE>.sql`).
   Restore procedure mirrors [../SETUP.md](../SETUP.md) §7 — adjusted for prod
   container `db` (user `famfun_prod`, password from `FAMFUN_PROD_PASSWORD`).
4. Re-apply migrations up to the last known-good one.
5. Fix the offending migration in a worktree, retest in dev + an isolated
   testpool slot, redeploy.

**Rollback:** restore-from-backup is the only safe rollback. Down-migrations
are not guaranteed (see ED-0010 for the no-down-migrate "label-only rename"
precedent).

### 4.3 Queue worker stuck / failed jobs piling up

**Trigger:** `failed_jobs` count rising
(`select count(*) from failed_jobs;`), emails delayed, or `/operations` shows
the worker as not running.

**Steps:**
1. `/operations` → Queue tab. Look at the failed-job exception class to
   classify:
   - **Transient** (SMTP timeout, mail-host hiccup) → Retry All.
   - **Bad payload** (deserialization error, missing model) → Flush after
     copying the payload for triage.
   - **Code bug** (recent deploy regression) → fix + redeploy first; replay
     after.
2. Restart the worker via the UI (or `supervisorctl restart email-queue:*`
   inside the container if supervisor is up). If the PID file is stale
   (`storage/app/queue_worker.pid` but no process), Stop will return silently;
   start again afterwards.

### 4.4 Scheduled reports overdue (no report email arrived)

**Trigger:** End of quarter passes and beneficiaries haven't received their
report.

**Steps:**
1. Sign in as system-admin → `/operations` → check the `scheduled_jobs` panel
   for due / past-due rows.
2. Trigger them: "Run Due Jobs" (UI) or `php artisan schedule_jobs:run`
   (subject to the §2.4 caveat about Sanctum).
3. If a job ran but the email didn't land, check `failed_jobs` (§4.3) and
   then the SMTP/Mailpit endpoint via §2.7.

### 4.5 PDF / chart generation failure

**Trigger:** Quarterly report job runs but the PDF is missing/blank, or chart
images don't render.

**Steps:**
1. PDFs are produced via `wkhtmltopdf` baked into `app1/Dockerfile`
   (line 19; `wkhtmltox_0.12.6.1-3.bookworm_<arch>.deb`). Run
   `docker --context rootless exec familyfund which wkhtmltopdf` and
   `wkhtmltopdf --version` to confirm the binary is present.
2. Charts are produced by the `quickchart` service (`ianw/quickchart`); confirm
   it's up (`docker --context rootless ps | grep quickchart`).
3. Re-queue the failed report job (§4.3).

### 4.6 IBKR / FetchDeposits failure

**Trigger:** `FetchDeposits` job fails or doesn't import expected deposits.

**Steps:**
1. Inspect the job in `failed_jobs` for the exception.
2. Check IBKR Flex Query availability (manual login at the IBKR portal).
3. Re-queue or run a manual import if the portal works.

### 4.7 DB unavailable

**Trigger:** App returns 500s with PDO connection errors; `db` container
restart-looping.

**Steps:**
1. `ssh spirit "docker --context rootless ps -a | grep db"` — is it running?
2. Inspect `docker --context rootless logs db` for the failure (commonly a
   corrupt InnoDB page or disk-full).
3. If the volume is intact but the container is wedged, restart via the
   wrapper: `ssh spirit "cd ~/dev && ./run_familyfund.sh prod"`.
4. If the volume is corrupted, restore from the latest predeploy backup
   (§4.2 step 3).

### 4.8 `dstrader` API calls 401/403 against FF

**Trigger:** dstrader app logs `401 Unauthenticated` or `403` on
`/api/asset_prices_bulk_update`, etc.

**Steps:** see [runbooks/ff-service-token-rotation.md](runbooks/ff-service-token-rotation.md).
Short version: a Sanctum token expired or was revoked; mint a replacement, push
to SOPS, reload the consumer. ED-0016 means reference-data writes are
system-admin-only — a non-admin token will 403 even with a fresh secret.

### 4.9 Money-flow incidents (reconciliation drift, webhook signature spike, vendor outage, ACH return, operator-role compromise)

These apply once money-flow v1 ships. Each has a playbook in
[runbooks/money_flow_runbook.md](runbooks/money_flow_runbook.md) §4. They're scoped to the
money-flow subsystem (Wise → checking → IBKR), not the core fund/portfolio
code.

### 4.10 Portfolio-balance mismatch

**Trigger:** Validation (§2.8) flags one or more portfolios.

**Steps:**
1. Note the `as_of` date and threshold from the validation run.
2. Open each flagged portfolio in the admin UI; compare set vs computed balance.
3. If the mismatch is a known input error (manual trade row), correct the
   transaction. If it's a calculation drift, escalate to the developer — do
   **not** edit the calculated columns directly.

### 4.11 Suspected secret leak

**Trigger:** Gitleaks alert (CI "Secret Scan" job), accidental push of a
plaintext `.env`, or a third party reports a credential.

**Steps:** ED-0001 + ED-0002 apply — rotate every value the leak touched
(DB password, `APP_KEY` (with caveats — §5), `MAIL_PASSWORD`, AWS, Pusher,
**Sanctum service tokens**), update the SOPS-encrypted twins in
`familyfund-secrets`, reload prod, and purge git history if needed. The full
incident-response template lives in
[security/SECURITY-EXPOSURE.md](security/SECURITY-EXPOSURE.md), which covers
the May 2026 exposure that drove ED-0001 / ED-0002 / ED-0017.

### 4.12 Lost / compromised `claude@test.local` (or any admin)

`/dev-login` is gated to `local` / `dev` / `testing` only
(`routes/web.php:21`) — it returns 404 on stage/prod. To rotate the admin
password:

```bash
docker exec <familyfund-container> php artisan tinker --execute "
\$u = \App\Models\User::where('email','claude@test.local')->first();
\$u->password = bcrypt('<NEW>'); \$u->save();
"
```

For prod admin loss, sign in with the address in `ADMIN_EMAILS` (env var) via
`/login`. If that's also lost, set its password via tinker on the prod
container with `docker --context rootless exec familyfund php artisan tinker
...`.

## 5. Rotation

| Asset | Cadence | How |
|---|---|---|
| **dstrader Sanctum tokens** (`dstrader-app`, `dstrader-scheduler`) | 60 days (TTL 90, 30d margin) | `php artisan security:service-token --name=<n> --rotate --ttl=90` mints the new token **and overlaps with prior valid tokens until you pass `--revoke-previous` next time**. Distribute the new token via SOPS, then the next `--rotate` call (with `--revoke-previous`) revokes the now-superseded old one. Cron lives in dstrader-docker; safety net = scheduler `--check-expiry`. Full procedure: [runbooks/ff-service-token-rotation.md](runbooks/ff-service-token-rotation.md). |
| **SOPS-encrypted `.env*`** (dev/stage/compose) | On change | `app1/family-fund-app/bin/secrets.sh edit <name>` (decrypt → `$EDITOR` → re-encrypt) → `bin/secrets.sh encrypt <name>` → commit in the private `familyfund-secrets` repo (ED-0002, ED-0017). |
| **Age key** (decrypts SOPS files) | On member change / breach | Generate new keypair, `app1/family-fund-app/bin/secrets.sh rekey` to re-wrap, distribute new private key out-of-band. Old key stays valid until rekey is committed. |
| **DB password (`famfun_prod`)** | On suspected exposure | Change in MariaDB → update `.env.prod` → re-encrypt in `familyfund-secrets` → redeploy. |
| **`APP_KEY`** | Never (rotation invalidates encrypted columns and sessions) | If you must (key compromise): see Laravel docs for `php artisan key:generate` plus re-encrypting every `encrypted:` cast column. **Do not do this lightly** — the test DB is cloned from dev specifically because reusing the key keeps cloned encrypted columns decryptable (SETUP.md note in "Running the suite isolated"). |

## 6. Subsystem runbooks

These cover scopes too big to fit here without losing precision; this index
does not duplicate them.

- **Money-flow** (Wise → checking → IBKR) — [runbooks/money_flow_runbook.md](runbooks/money_flow_runbook.md). Daily/weekly/monthly checklists, incident playbooks (drift, webhook, vendor outage, ACH return, operator compromise), roles cheat-sheet, glossary. Companion design doc: [money_flow_plan.md](money_flow_plan.md).
- **dstrader ⇄ FamilyFund service-token rotation** — [runbooks/ff-service-token-rotation.md](runbooks/ff-service-token-rotation.md). Mint/rotate/revoke commands, the dstrader-docker cron, emergency rotation, verification, rollback.
- **Manual fund maintenance** (investment cadence, grantor/beneficiary support, reports cadence) — [ManualMaintenance.md](ManualMaintenance.md).
- **Credit-lines** (design + recurring ops scenarios) — [credit_lines/credit_lines_plan.md](credit_lines/credit_lines_plan.md) and the per-flow notes in [credit_lines/](credit_lines/). No standalone runbook today; the operations surface is the same `/operations` page plus admin-only credit-line write actions per ED-0016.
- **Engineering decisions** (the "why" behind operational choices — secrets, testing, scheduling) — [ENGINEERING_DECISIONS.md](ENGINEERING_DECISIONS.md).
- **New-machine setup** (prereqs, env decrypt, DB load, deploy prereqs) — [../SETUP.md](../SETUP.md).
- **Security exposure / incident-response template** — [security/SECURITY-EXPOSURE.md](security/SECURITY-EXPOSURE.md).
