# INTEGRATIONS

How FamilyFund talks to the outside world. Today's surface is small and
deliberately so: one **inbound CSV pipeline** (IBKR cash deposits), one
**outbound service-token API** consumed by `dstrader`, two **internal HTTP
sidecars** (trading-calendar, quickchart), **outbound transactional email**,
and a **secrets-decryption** integration with the private
`familyfund-secrets` repo. A planned Brazil ↔ US money-flow integration
(Wise/Mercury/PIX) is design-only — see §8.

> **Memory-bank doc** — see root [`AGENTS.md`](../AGENTS.md) for the index.
> **As of:** 2026-05-29. `path:line` references drift; verify with `git grep`
> before quoting in code review. Paths in this doc are relative to
> `app1/family-fund-app/` unless prefixed with `docs/` or `app1/`.

Companion docs:
[`MONEY_SUBSYSTEM.md`](MONEY_SUBSYSTEM.md) (ledger + the IBKR pipeline from
the money side),
[`SECURITY.md`](SECURITY.md) (auth/ACL boundary),
[`runbooks/ff-service-token-rotation.md`](runbooks/ff-service-token-rotation.md)
(dstrader ⇄ FamilyFund Sanctum-token rotation),
[`money_flow_plan.md`](money_flow_plan.md) (planned BRL↔USD v1),
[`OPERATIONS.md`](OPERATIONS.md) (deploy / backfill).

---

## 1. Inventory

| Integration | Direction | Transport | Auth | Code |
|---|---|---|---|---|
| IBKR Flex Query (cash deposits) | inbound (pull) | HTTPS XML+CSV | `tws_token` per portfolio | `app/Jobs/FetchDeposits.php`, `Traits/IBFlexQueriesTrait.php`, `Traits/CashDepositTrait.php` |
| `dstrader` ⇄ FamilyFund reference-data API | inbound (push from dstrader) | HTTPS JSON | Sanctum bearer (system-admin) | `routes/api.php`, `Controllers/APIv1/*`, `Traits/AuthorizesApiAccess.php` |
| `dstrader_scheduler` ⇄ FamilyFund schedule jobs | inbound (push) | HTTPS JSON | Sanctum bearer (system-admin) | `routes/api.php`, `Controllers/APIv1/ScheduleJobAPIController` |
| `trading-calendar` sidecar (NYSE/NASDAQ/AMEX holidays) | outbound | HTTP JSON | none (private network) | `app/Services/HolidaySyncService.php` |
| `quickchart` sidecar (Chart.js → PNG) | outbound | HTTP JSON | none (private network) | `app/Services/QuickChartService.php` |
| Outbound email (Mailpit dev / SMTP prod) | outbound | SMTP | per-driver | `config/mail.php`, `app/Mail/*`, `resources/views/emails/*` |
| Local PDF render (wkhtmltopdf) | local binary | exec | n/a | `app/Services/SnappyPdfWrapper.php`, `config/snappy.php` |
| Secrets decryption (SOPS+age) | local + private repo | git + age key | age private key off-repo | `bin/secrets.sh`, `~/dev/familyfund-secrets/` |

Things that are **deliberately not integrated yet** (forward pointers, see
§8): direct bank webhooks (Wise/Mercury), PIX payout API, OFAC screening,
broker-quote APIs (Yahoo/Alpha Vantage/CoinGecko). Asset prices come from
`dstrader` over the inbound API in §3, not from a third-party quote feed
here.

---

## 2. IBKR Flex Query (inbound cash deposits)

The only fully end-to-end inbound pipeline that ships today. Operator-driven
ingestion of Interactive Brokers' Flex Query cash-transaction CSV, written
into the `cash_deposits` table, then reconciled to `DepositRequest` rows by
hand (see [`MONEY_SUBSYSTEM.md`](MONEY_SUBSYSTEM.md) §4.1 for the downstream
ledger side).

### 2.1 Wire

Two-step Flex Web Service handshake — `app/Http/Controllers/Traits/IBFlexQueriesTrait.php`:

1. `POST https://ndcdyn.interactivebrokers.com/AccountManagement/FlexWebService/SendRequest?t={token}&q={queryId}&v=3`
   returns XML with a one-shot `Url` + `ReferenceCode`.
2. `GET {Url}?q={ReferenceCode}&t={token}&v=3` returns the actual CSV body.

`tws_query_id` + `tws_token` live on `trade_portfolios`
(`database/migrations/2025_01_17_000000_add_tws_query_trade_portfolios.php`).
Each portfolio is fetched independently — `FetchDeposits.php` skips rows with
either column null.

### 2.2 CSV parse → `cash_deposits`

`CashDepositTrait::parseCashDeposit()` expects columns `ClientAccountID`,
`Description`, `Date/Time`, `SettleDate`, `Amount`, `Type`, `TransactionID`,
`ClientReference`. It joins `ClientAccountID` to `trade_portfolios.account_name`
to find the owning portfolio, then dedupes on
`(account_id, amount, date, description, status)` before inserting a
`CashDeposit` row in `STATUS_PENDING`.

CSVs are persisted under `storage/app/cash_deposits/` and auto-deleted after
three months.

### 2.3 Failure modes

- **Token expired (XML status `1012`) / invalid (`1015`)** —
  `IBFlexQueriesTrait::getIBFlexQuery()` throws; an admin needs to refresh
  the Flex Query token in IBKR Account Management and update
  `trade_portfolios.tws_token`. No retry queue.
- **HTTP non-2xx** — same throw path; surfaced as a failed job.
- **CSV header drift** — silent column miss is the highest-risk failure;
  there is no schema assertion beyond column-name lookup.
- **Duplicate detection collision** — the `(amount, date, description)`
  dedupe key will collapse two genuinely-distinct same-day same-amount
  deposits with identical descriptions. Real-world risk: low; observed: never.

### 2.4 Scheduling status

`FetchDeposits` is **not on the scheduler**. The Laravel-11 minimal
bootstrap (`bootstrap/app.php`) doesn't register the legacy
`Kernel::schedule()` entry — see [`ARCHITECTURE.md`](ARCHITECTURE.md) §6.3.
Operators dispatch it manually today.

---

## 3. dstrader ⇄ FamilyFund (outbound from dstrader, inbound to us)

The Java `dstrader` process and its bash `dstrader_scheduler` companion
write reference data (asset prices, exchange holidays, portfolio balances)
into FamilyFund over the REST API. From FamilyFund's perspective this is an
**inbound** integration even though dstrader is the active caller.

### 3.1 Auth — Sanctum service tokens

Two named tokens minted by `php artisan security:service-token` — see
`app/Console/Commands/SecurityServiceTokenCommand.php` and the runbook at
[`runbooks/ff-service-token-rotation.md`](runbooks/ff-service-token-rotation.md):

| Token name | Consumer | Holds env var | Hits |
|---|---|---|---|
| `dstrader-app` | the Java trader | `FF_API_TOKEN` | `asset_prices`, `portfolio_assets`, `portfolio_balances` writes; reads on funds/accounts |
| `dstrader-scheduler` | bash scheduler | `FF_SCHEDULER_API_TOKEN` | `schedule_jobs`, `fund_reports` |

Both belong to the `dstrader-service@familyfund.local` user (configurable
via `familyfund.service_user_email`) which is seeded as a global
`system-admin` by `database/seeders/DstraderServiceUserSeeder.php`. Tokens
have a 90-day TTL by default and rotation is owed every 60 days. Tokens are
SOPS-encrypted twins inside the private `familyfund-secrets` repo (§7).

### 3.2 Endpoints (the inbound surface)

`routes/api.php`, all behind `auth:sanctum`. Two access tiers:

- **Tenant-scoped reads** (any authenticated caller, scoped by
  `AuthorizationService` + `AuthorizesApiAccess` trait): funds, accounts,
  portfolios, transactions, account_balances, account_matching_rules,
  transaction_matchings, fund_reports, account_reports, trade_portfolios,
  trade_portfolio_items, portfolio_assets, portfolio_balances.
- **Reference-data writes** (system-admin only, gated by
  `requireAdminWrite()` / the `familyfund.enforce_admin_writes` flag —
  ED-0016): `asset_prices`, `asset_prices_bulk_update`,
  `portfolio_assets_bulk_update`, `portfolio_balances_bulk_update`,
  `exchange_holidays/sync`, `schedule_jobs`. PII reads (`users`, `people`,
  `phones`, `addresses`, `id_documents`) are also system-admin-only.

A non-admin token gets `403` from `AuthorizesApiAccess::adminWriteMiddleware`;
the per-object scoping below it returns deny-by-default (a no-access user
gets `whereRaw('1=0')`, not an empty filter — fixed in `#74`).

### 3.3 Token expiry housekeeping

Scheduled in `routes/console.php`:

- `security:service-token --check-expiry --warn-days=14` daily 06:30 — emails
  admins when a token has under 14 days left.
- `security:service-token --prune-expired` Mondays 06:35 — deletes already-expired
  rows from `personal_access_tokens`.

### 3.4 Failure modes

- **Token expired** — 401. Recovery: re-run `security:service-token --rotate`
  per the runbook; redeploy the consumer with the new env var.
- **Admin-write flag flipped off** — `FF_ENFORCE_ADMIN_WRITES=false` opens
  reference-data writes to any authenticated user. Intentional kill-switch
  for incident recovery, not a normal operating state (ED-0016).
- **Sanctum `expires_at` NULL** — pre-`#13` tokens have no expiry; security
  scan catches it (`AclMatrixTest` indirectly) and the prune job is a no-op
  on them. Re-mint with `--ttl` to fix.

---

## 4. Internal HTTP sidecars

Two services on the docker-compose network. They are **not** external
dependencies in the production sense — they share the FamilyFund deploy
unit — but they speak HTTP and have failure modes worth noting.

### 4.1 trading-calendar (NYSE/NASDAQ/AMEX holidays)

`app/Services/HolidaySyncService.php` calls
`GET {trading-calendar}/api/v1/markets/holidays?mic={MIC}&start={date}&end={date}`,
where `{trading-calendar}` is `config('services.trading_calendar.url')`
(`http://trading-calendar:80` by default). MIC mapping: NYSE→`XNYS`,
NASDAQ→`XNAS`, AMEX→`XASE`. 10-second client timeout.

Results land in `exchange_holidays` via
`ExchangeHolidayRepository::bulkUpsert()`. Non-2xx responses throw; an empty
payload returns `[]` and writes nothing. `CheckPriceStaleness` consumes the
holiday table to know which days were trading days when computing staleness
windows — so a stale `trading-calendar` sync silently widens the staleness
threshold.

### 4.2 quickchart (Chart.js → PNG)

`app/Services/QuickChartService.php` and
`app/Console/Commands/GenerateChartImage.php` post a Chart.js config to
`POST {quickchart}/chart` (default `http://quickchart:3400`). PNG output is
embedded in PDF reports — quarterly account / fund / trade-band PDFs. If
quickchart is down the PDF still renders, just without the chart panel.

---

## 5. Outbound email

The transactional-email surface — mailers live in `app/Mail/` and templates
in `resources/views/emails/`.

### 5.1 Transport

`config/mail.php` reads `MAIL_MAILER`, defaulting to `log` (silent for
non-configured envs). Dev uses the `mailhog` compose service (Mailpit
arm64-native) at `mailhog:1025` with the web UI on
`http://localhost:8025` — `.env.dev` symlink wires this. Prod uses SMTP or
one of the driver-specific options (`ses`, `postmark`, `resend`) — currently
configured but dormant.

### 5.2 Mailers

Roughly four families:

| Family | Mailers |
|---|---|
| Cash flow | `CashDepositMail`, `CashDepositErrorMail`, `DepositAllocationMail`, `TransactionEmail` |
| Periodic reports | `FundReportEmail`, `AccountQuarterlyReport`, `TradeBandReportEmail` |
| Matching + alerts | `AccountMatchingRuleEmail`, `AccountMatchingRuleRemovedEmail`, `MatchingExpirationReminderEmail`, `ScheduledJobFailureMail` |
| Credit lines | `app/Mail/CreditLine/*` — `StatusUpdateMail`, `TransactionDetectedMail`, `TransactionReceivedMail`, `DelayNotificationMail` |
| Admin | `TradePortfolioAnnouncementMail`, the price-staleness alert (template `resources/views/emails/price_staleness_alert.blade.php`, sent from `CheckPriceStaleness`) |

Recipients for alert-class mail (staleness, scheduled-job failure,
mismatch) come from `config('familyfund.admin_emails')` —
`ADMIN_EMAILS` env, default `admin@dev.familyfund.local` (tracking ticket
`#81`).

### 5.3 Failure modes

- **`MAIL_MAILER=log` in prod** — silent drop; the most common deploy
  misconfiguration. The mail goes to `storage/logs/laravel.log` and looks
  delivered to the app.
- **SMTP TLS failure** — `Symfony\Component\Mailer\Exception\TransportException`
  bubbles into the queue worker's failed-job log.
- **PDF render failure mid-report** — `SendFundReport` / `SendAccountReport`
  fail the job before the mail goes out; recipients see nothing rather than a
  broken PDF.

---

## 6. PDF generation (local binary, not a network call)

`config/snappy.php` configures `knplabs/knp-snappy` against
`WKHTML_PDF_BINARY` (default `/usr/local/bin/wkhtmltopdf`, installed in the
container `Dockerfile`). `SnappyPdfWrapper` (`app/Services/SnappyPdfWrapper.php`)
is the thin wrapper. Templates: `accounts/show_pdf.blade.php`,
`funds/show_pdf.blade.php`, `funds/show_trade_bands_pdf.blade.php`,
`portfolios/show_rebalance_pdf.blade.php`, all on
`layouts/pdf_modern.blade.php`.

Failure mode: binary missing or non-executable → `RuntimeException`. Chart
panels degrade gracefully if `quickchart` (§4.2) is down. Listed here as an
"integration" because the binary is a moving piece of the deploy and the
QuickChart→Snappy chain is the most common operator-visible failure.

---

## 7. Secrets pipeline (SOPS + age)

Encrypted env twins live in the private sibling repo
`~/dev/familyfund-secrets/` (`.sops` extension, dotenv format, age-encrypted
to the recipients in `.sops.yaml`). The age private key lives **off both
repos** at `~/.config/sops/age/keys.txt` (or `$SOPS_AGE_KEY_FILE`).
`bin/secrets.sh` is the materializer — see [ED-0002](decisions/ED-0002-sops-age-encrypted-secrets.md)
for the original in-repo decision and [ED-0017](decisions/ED-0017-secrets-in-private-repo.md)
for the move to the private repo.

Three managed env files:

| Name | Plaintext path |
|---|---|
| `dev` | `app1/family-fund-app/.env.dev` |
| `stage` | `app1/family-fund-app/.env.stage` |
| `compose` | `app1/.env` |

Commands: `status` (state without secrets), `decrypt [name|all]`,
`encrypt [name|all]`, `edit <name>` (open in `$EDITOR`, re-encrypt on save),
`rekey` (re-wrap to current recipients after rotating an age key).

**Failure modes worth surfacing:**

- **`MissingAppKeyException` on a fresh worktree** — a new preview slot will
  500 until `bin/secrets.sh decrypt` runs. This is the canonical "I checked
  out a fresh branch and the slot is broken" cause.
- **Missing age key on a new machine** — every `sops` call fails. Onboarding
  step is to copy the age key from a trusted device (manual, no automation
  by design).
- **`.sops.yaml` recipient list drift** — rotating a key without `rekey`
  leaves old `.sops` files un-decryptable by the new keyholder. Fix is
  `rekey` after editing the recipient list.

---

## 8. Money-flow (planned, not built)

[`docs/money_flow_plan.md`](money_flow_plan.md) describes a Brazil ↔ US
money-movement integration: US business-checking sidecar with a developer-API
bank (Mercury/Relay) as orchestration hub, **webhook ingestion** from Wise
USD ACH at the checking account, attribution at the webhook instant, a
Brazilian recipient registry, outbound USD → BRL → PIX, an isolated
`App\MoneyFlow` namespace with its own queue and audit log (`mf_audit_log`),
three-way reconciliation, OFAC screening, webhook signature validation, and
idempotency keys.

**None of those models exist in `app/Models/` yet.** No `/webhook` route
exists in `routes/web.php` or `routes/api.php`. The operator runbook at
[`runbooks/money_flow_runbook.md`](runbooks/money_flow_runbook.md) describes
the planned operator surface, not anything live. Already flagged in
[`OPERATIONS.md`](OPERATIONS.md) §1 and
[`MONEY_SUBSYSTEM.md`](MONEY_SUBSYSTEM.md) §4.2 — repeated here so an agent
landing in this doc isn't surprised.

---

## 9. CI external dependencies

Not "integrations" in the app sense, but the CI surface pulls from real
external services and an outage there will red the gate:

- **Docker Hub** — `mariadb:11`, `axllent/mailpit`, `aquasecurity/trivy-action`,
  `zaproxy/action-baseline`, `shivammathur/setup-php`.
- **GitHub Container Registry** — gitleaks image.
- **npm / PyPI** — `npm audit` baseline (custom gate in
  `bin/npm-audit-gate.cjs`), semgrep + ZAP tooling.
- **Semgrep packs** — `p/php`, `p/owasp-top-ten`, `p/security-audit` plus the
  in-repo `.semgrep/familyfund.yml` (see ED-0019).
- **Trivy DBs** — filesystem + Docker image scans, nightly only.
- **OSV scanner + cyclonedx SBOM** — nightly only.

CodeQL was dropped (no PHP support + private repo can't upload SARIF without
GHAS); ZAP runs both baseline and authenticated (beneficiary) scans using a
short-lived dev token minted by `security:dev-token --as=beneficiary` during
the workflow.

Workflows: `.github/workflows/tests.yml`,
`.github/workflows/security-scan.yml`.

---

## 10. Failure-modes cheat sheet

| Surface | Symptom | Most likely cause | First check |
|---|---|---|---|
| IBKR Flex Query | Empty CSV / job fails | Token expired (`1012`) or invalid (`1015`) | Refresh in IBKR AM, update `trade_portfolios.tws_token` |
| `dstrader-app` push | 401 on `/api/asset_prices_bulk_update` | Sanctum token expired | `security:service-token --rotate --name=app` |
| `dstrader-app` push | 403 on bulk-update | Admin-write flag off, or token holder not system-admin | `FF_ENFORCE_ADMIN_WRITES`; check `DstraderServiceUserSeeder` ran |
| `trading-calendar` | Staleness alert widens unexpectedly | Holiday sync silently empty | `exchange_holidays` row count for current year |
| `quickchart` | PDF reports missing chart panel | Sidecar down | `curl http://quickchart:3400/chart` from the app container |
| Mail | "Looks delivered" but nothing in Mailpit / inbox | `MAIL_MAILER=log` | `php artisan tinker` → `config('mail.default')` |
| Mail | TLS / auth failure in prod | Driver creds drifted | `storage/logs/laravel.log`, failed-job table |
| PDF render | `RuntimeException` from Snappy | `wkhtmltopdf` not in container | `docker exec familyfund which wkhtmltopdf` |
| Secrets | App 500s with `MissingAppKeyException` on fresh worktree | Plaintext env not materialized | `bin/secrets.sh decrypt` |
| Secrets | `sops` decrypt failure on a new machine | Age key not installed | `ls $SOPS_AGE_KEY_FILE` |

---

## 11. Out of scope for this doc

- Ledger / OWN-BOR math, the credit-line domain, fund-cash accounting →
  [`MONEY_SUBSYSTEM.md`](MONEY_SUBSYSTEM.md).
- Full role/permission matrix backing the admin-write flag →
  [`ACL_IMPLEMENTATION.md`](ACL_IMPLEMENTATION.md).
- Per-mailer template structure → the `app/Mail/*` classes themselves.
- BRL ↔ USD money-flow design + operator surface →
  [`money_flow_plan.md`](money_flow_plan.md) and
  [`runbooks/money_flow_runbook.md`](runbooks/money_flow_runbook.md).
- Sanctum token rotation procedure →
  [`runbooks/ff-service-token-rotation.md`](runbooks/ff-service-token-rotation.md).
- ADR rationale (admin-writes, secrets, deny-by-default) →
  [`DECISIONS.md`](DECISIONS.md) (ED-0002, ED-0003, ED-0016, ED-0017).
