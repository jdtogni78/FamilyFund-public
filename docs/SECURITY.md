# SECURITY

Security model for FamilyFund: authentication, ACL/authorization, secrets
handling, automation, and an STRIDE-shaped threat model. Source of truth lives
in code; this doc is a guided map.

> **Companion docs:**
> [`ACL_IMPLEMENTATION.md`](../ACL_IMPLEMENTATION.md) (the full permission/role
> matrix), [`SECURITY_NEXT_STEPS.md`](../SECURITY_NEXT_STEPS.md) (open work),
> [`docs/security/SECURITY-EXPOSURE.md`](security/SECURITY-EXPOSURE.md) (the
> 2026-05 leak incident + history-purge record),
> [`docs/security/HISTORY-AUDIT-66.md`](security/HISTORY-AUDIT-66.md),
> [`docs/runbooks/ff-service-token-rotation.md`](runbooks/ff-service-token-rotation.md),
> [`docs/ENGINEERING_DECISIONS.md`](ENGINEERING_DECISIONS.md) (security-relevant
> EDs include ED-0001/0002 secrets, ED-0003 deny-by-default, ED-0004 IDOR,
> ED-0005 CI, ED-0012 no-public-API, ED-0013 PHPStan, ED-0014 synthetic
> baseline, ED-0015 admin-index scoping, ED-0016 admin-write reference data,
> ED-0017 secrets-in-private-repo — re-check the file when adding a new ED).

---

## 1. Authentication

| Surface          | Guard / mechanism                                              | Notes |
|------------------|----------------------------------------------------------------|-------|
| Web UI           | `web` guard (session cookies, CSRF on every mutation)          | Laravel Fortify-style login via `app/Livewire/Forms/LoginForm.php` |
| Web 2FA          | TOTP + recovery codes; `EnsureTwoFactorIsCompleted` middleware | Encrypted `two_factor_secret` / `two_factor_recovery_codes` on `users` (`app/Models/User.php`) |
| API              | `auth:sanctum` group in `routes/api.php`                       | No public endpoints (see ED-0012); the only unauthenticated route, `/api/clear`, is wrapped in `app()->environment('local','dev')` |
| dstrader service | Sanctum personal-access token for the seeded service user      | Issued via `php artisan security:service-token` and `DstraderServiceUserSeeder` (ED-0016) |
| Dev impersonation| `/dev-login/{path}?as=<alias>`                                 | Local/dev only; `RedirectStrayImpersonation` middleware funnels stray `?as=` through the dev-login route so the footgun becomes a feature |

Login attempts (web and API) are recorded in `login_activities` by
`Livewire\Forms\LoginForm` and `Auth\TwoFactorController` (IP, user-agent,
device, status: `success` / `failed` / `two_factor_pending` / `two_factor_failed`).

## 2. Authorization (ACL)

### 2.1 Model

Spatie Laravel Permission with its **team feature repurposed as fund scope** —
`team_id` in the pivot tables holds a `fund_id`, so the same user can be e.g.
*Fund Admin* in Fund A and *Beneficiary* in Fund B. A `fund_id = 0` row marks
the global `system-admin` role.

Roles (`database/seeders/RolesAndPermissionsSeeder.php`):

- `system-admin` — global, fund_id=0, bypasses every policy via the
  `before()` hook in each `app/Policies/*Policy.php`.
- `fund-admin` — per-fund full CRUD, can read users.
- `financial-manager` — per-fund: create/process transactions and credit lines,
  read accounts (no destructive deletes).
- `beneficiary` — per-fund: own account + own transactions + own credit lines.

Full permission matrix (per resource, per role, with view-own carve-outs):
**[`ACL_IMPLEMENTATION.md`](../ACL_IMPLEMENTATION.md)**. The seeder is the
canonical permission list; the doc mirrors it.

### 2.2 Defense in depth

Four enforcement layers, each meant to fail closed independently
(**ED-0003 deny-by-default**):

1. **Route middleware.** Sanctum (`auth:sanctum`) and `auth` gate every
   business route. `RequireFullFundAccess`
   (`app/Http/Middleware/RequireFullFundAccess.php`) protects generated CRUD
   surfaces with no per-row policy (admin-style list pages — ED-0015).
   `SetFundPermissions` resolves fund context from the URL/body before policy
   checks (`{fund}` → `{fund_id}` → request input → `{account}`).
2. **Policies.** `AccountPolicy`, `FundPolicy`, `TransactionPolicy`,
   `AccountCreditLinePolicy` (`app/Policies/`). Every `before()` short-circuits
   for `isSystemAdmin()`. The `WebV1` controller layer
   (`app/Http/Controllers/WebV1/`) calls `$this->authorize($ability, $model)`
   in every CRUD verb — `AccountControllerExt`, `TransactionControllerExt`,
   `FundControllerExt`, `AccountCreditLineController`,
   `CreditLineMatchResolutionController`.
3. **Query scoping.** `app/Services/AuthorizationService.php` provides
   `scopeAccountsQuery`, `scopeTransactionsQuery`, `scopeFundsQuery`,
   `scopeCreditLinesQuery` and `getAccessibleFundIds()`. Each scope explicitly
   `whereRaw('1 = 0')` when the user has neither full-fund access nor own
   accounts — the comment on `scopeAccountsQuery` warns that an empty closure
   would compile to no `WHERE` clause and leak every row.
4. **Repository trait.** `App\Repositories\Traits\AuthorizesQueries`
   (`withAuthorization()`) wraps repository calls so controllers can't forget
   the scope. Used by `AccountRepository`, `TransactionRepository`,
   `FundRepository`, `AccountCreditLineRepository`.

### 2.3 API object-level scoping (IDOR remediation — ED-0004)

Authenticated-but-cross-tenant access is the main IDOR risk on a generated
controller surface. Resolution (described in the block comment in
`routes/api.php` introducing the `auth:sanctum` resource block):

- **Tenant-scoped** (`funds`, `accounts`, `portfolios`, `transactions`,
  `account_balances`, `account_matching_rules`, `transaction_matchings`,
  `fund_reports`, `account_reports`, `trade_portfolios`,
  `trade_portfolio_items`, `portfolio_assets`, `portfolio_balances`) — index +
  per-object guards via `App\Http\Controllers\Traits\AuthorizesApiAccess`.
- **PII (admin-only):** `users`, `people`, `phones`, `addresses`,
  `id_documents`.
- **System/ops (admin-only):** `scheduled_jobs`.
- **Shared reference data** (`assets`, `asset_prices`, `matching_rules`,
  `schedules`, `change_logs`, `asset_change_logs`, `exchange_holidays`) — reads
  open to any authenticated caller, **writes admin-only** (ED-0016),
  feature-flagged via `config('familyfund.enforce_admin_writes')`.

## 3. Secrets handling

### 3.1 SOPS + age — encrypted twins in a private sibling repo (ED-0002, ED-0017)

- Plaintext `.env.*` files are git-ignored. The app reads them from canonical
  in-app paths (`app1/family-fund-app/.env`, etc.) which are usually symlinks
  to the materialized plaintext.
- Encrypted twins (`<name>.sops`, dotenv format) and the SOPS recipe
  (`.sops.yaml`) live in a **separate private repo**, default
  `$FF_SECRETS_DIR=~/dev/familyfund-secrets` — so the (intended-public) app
  repo ships no secrets, not even encrypted. Legacy fallback to in-repo `.sops`
  files is kept for transitional checkouts.
- Age private key lives off-repo at `~/.config/sops/age/keys.txt`
  (`$SOPS_AGE_KEY_FILE`), never committed.
- Management: `app1/family-fund-app/bin/secrets.sh`
  (`status`/`decrypt`/`encrypt`/`edit`/`rekey`/`recipient`). The `status` command
  never prints values.
- Rotation: `app1/family-fund-app/bin/rotate-dev-secrets.sh` regenerates
  `APP_KEY` + DB / Redis / Mail / AWS / Pusher passwords and re-encrypts.

### 3.2 Why not a secrets server (ED-0001)

Single-developer scale. The committed-encrypted-twin + age-key-off-repo model
gets the bulk of the value (no plaintext secrets in git, history-purgeable on
leak, recoverable from any age-key holder) without the operational tax of a
Vault/SSM deployment. Web search / code search / pre-commit gitleaks catches
recurrence.

### 3.3 Secret-scanning / pre-commit (ED-0001)

- `.gitleaks.toml` + `.pre-commit-config.yaml` block re-commit of leaked
  patterns.
- `.gitignore` post-leak block excludes `**/.env.*`, `*.ibd`, `*.frm`,
  `**/datadir*/`, `database/**/*data*.sql`, `database/csv/*password*`,
  `*.sql.gz`, `**/*.log*` — the exact classes purged by the 2026-05
  history-rewrite (see HISTORY-AUDIT-66.md).

### 3.4 Past incident

The 2026-05 public-exposure window is documented in
[`docs/security/SECURITY-EXPOSURE.md`](security/SECURITY-EXPOSURE.md) and
[`docs/security/HISTORY-AUDIT-66.md`](security/HISTORY-AUDIT-66.md). Dev
`APP_KEY` and `DB_PASSWORD` were rotated; a full-history `git-filter-repo`
rewrite was force-pushed and verified clean (see HISTORY-AUDIT-66.md for the
detailed before/after metrics and the exact `--paths-from-file` spec).

## 4. Security automation (CI gates)

| Gate                                     | Trigger              | Source                                                  |
|------------------------------------------|----------------------|---------------------------------------------------------|
| `composer audit --locked`                | PR / push / nightly  | `.github/workflows/security-scan.yml` → `composer-audit`|
| `npm audit` (above threshold)            | PR / push / nightly  | same workflow → `npm-audit`, with `.npm-audit-ignore`   |
| Gitleaks (working tree by default)       | PR / push / nightly  | same workflow → `gitleaks`, config `.gitleaks.toml`     |
| Semgrep (SARIF)                          | PR / push / nightly  | same workflow → `semgrep`                               |
| Trivy config scan                        | PR / push / nightly  | same workflow → `trivy-config`, `.trivyignore`          |
| PHPStan / Larastan level 5 over `app/`   | PR / push / nightly  | same workflow → `phpstan` (**non-blocking**, ED-0013)   |
| Route + CSRF + ACL invariant tests       | PR (via `tests.yml`) | `tests/Feature/Security*Test.php`                       |
| ZAP DAST baseline                        | manual / preview env | `app1/family-fund-app/bin/zap-baseline.sh` (+ `zap-authenticated.sh`) |
| Local one-shot of the fast scans         | dev machine          | `app1/family-fund-app/bin/security-scan.sh`             |

Test-side invariants worth knowing:

- `SecurityRouteAutomationTest` — every route auth-gated unless explicitly
  allow-listed. The three temporary allow-lists for the API surface
  (`TEMPORARY_UNAUTHENTICATED_API_MUTATION_ALLOWLIST`,
  `TEMPORARY_UNAUTHENTICATED_API_READ_ALLOWLIST`,
  `TEMPORARY_SIDE_EFFECT_GET_ALLOWLIST`) are intentionally empty —
  deny-by-default. The `PUBLIC_WEB_ROUTE_ALLOWLIST` enumerates the expected
  unauthenticated web surface (login/register/forgot-password,
  `sanctum/csrf-cookie`, `livewire/*`, `/dev-login` in non-prod, etc.). The
  same test also pins the audited API endpoints as authenticated by name
  (ED-0012).
- `SecurityCsrfRouteAutomationTest` — web mutations stay in the `web`
  middleware group; no project-level CSRF exemptions; no side-effect GETs.
- `SecurityAccessRegressionTest` — anonymous / cross-account / cross-fund
  regression checks.
- `SecurityApiAclMatrixTest` — role × API resource matrix; pins the
  reference-data writes as admin-only (ED-0016).
- `AclMatrixTest` — discoverable web ACL matrix vs
  `tests/golden/acl_matrix.json`. Full sweep is `@group nightly`; a curated
  `CRITICAL_ROUTES` subset runs on every test pass. `AclWriteBlockingTest`
  hits a write-side subset.
- The standard `bin/test.sh` excludes `@group nightly` (ED-0006);
  `bin/test-nightly.sh` runs them.

## 5. Threat model (STRIDE)

Where the threat lives in code and how each layer mitigates it.

| STRIDE category               | Threat in FamilyFund                                                       | Mitigation |
|-------------------------------|----------------------------------------------------------------------------|------------|
| **S**poofing                  | Stolen session / replayed token / weak password reset                      | Sessions on the `web` guard + 2FA via `EnsureTwoFactorIsCompleted`; Sanctum PAT for API + dstrader service user; password-reset tokens hashed; failed/2FA-pending logins recorded in `login_activities`. |
| **T**ampering — transactions  | Beneficiary or FM tries to forge / re-post / modify a transaction          | CSRF on every web mutation; `TransactionPolicy::create/process/delete`; `withAuthorization()` on `TransactionRepository`; `process`/`delete` denied to beneficiaries; bulk endpoints go through the same authorize path. |
| **T**ampering — money flow    | Cross-fund credit-line manipulation                                        | `AccountCreditLinePolicy` + `AuthorizationService::scopeCreditLinesQuery`; `MatchResolutionController` policy-gated; full audit in `credit_lines_plan.md` / `money_flow_runbook.md`. |
| **R**epudiation               | "I didn't do that login / that trade"                                      | `login_activities` (IP + UA + status); `ChangeLog` + `AssetChangeLog` rows; queue-job logs (`LogQueueJobCompletion`); SES/Mail logs (`LogSentEmail`). |
| **I**nformation disclosure — IDOR | Authenticated caller tries `/api/accounts/{other-fund-id}`              | `AuthorizesApiAccess` trait on every tenant-scoped controller (ED-0004); `SecurityApiAclMatrixTest` pins the role × resource matrix. |
| **I**nformation disclosure — query leak | Forgotten `withAuthorization()` call                             | `AuthorizationService` scopes start with `whereRaw('1 = 0')` when access set is empty (explicit comment in source — empty closure would leak); `SecurityAccessRegressionTest` catches anonymous / cross-account fetches. |
| **I**nformation disclosure — secrets   | `.env` or DB dump in git history (past incident)                 | ED-0001/0002/0017 secrets pipeline; gitleaks pre-commit + CI; `.gitignore` post-leak block; `TestBaselineSeeder` (ED-0014) — synthetic baseline, no real-data dump in the repo. |
| **D**enial of service         | Unauthenticated burst against `auth:sanctum` or login                       | Login form is throttled in `Livewire\Forms\LoginForm::ensureIsNotRateLimited` (5 attempts via `RateLimiter`); password-reset is `throttle:6,1` in `routes/auth.php`. No global API throttle is wired in `bootstrap/app.php` today — re-evaluate if Sanctum traffic grows. |
| **E**levation of privilege    | Beneficiary tries an admin action / Financial Manager tries a delete       | Policy `before()` only escalates for `isSystemAdmin()`; permissions are not assignable from any UI except `UserRoleController` (system-admin only); CRUD verbs each call `authorize()`; `RequireFullFundAccess` blocks generated admin index pages from beneficiaries (ED-0015). |

### 5.1 OWASP Top 10 mapping (full)

- **A01 Broken Access Control** — covered by §2 (defense-in-depth, IDOR
  remediation, deny-by-default scopes).
- **A02 Cryptographic Failures** — `two_factor_secret` and
  `two_factor_recovery_codes` columns use Laravel `encrypted` /
  `encrypted:array` casts; SOPS+age for at-rest secrets (§3); HTTPS is
  terminated upstream in the `dstrader-docker` proxy stack rather than in this
  app — confirm the prod TLS config there before publishing.
- **A03 Injection** — Eloquent / parameter binding throughout; no raw query
  string concatenation in the repository layer.
- **A04 Insecure Design** — addressed by the layered model in §2.2 and the
  IDOR-driven controller redesign in ED-0004 / ED-0015 / ED-0016.
- **A05 Security Misconfiguration** — gitleaks + Trivy config scan + the
  intentionally-empty temporary API allow-lists in
  `SecurityRouteAutomationTest`.
- **A06 Vulnerable & Outdated Components** — `composer audit --locked` and
  `npm audit` jobs in `security-scan.yml` (§4); Trivy config scan flags base
  image / IaC issues.
- **A07 Identification & Authentication Failures** — 2FA +
  `login_activities` audit trail + Sanctum PATs (no long-lived API keys
  checked in).
- **A08 Software & Data Integrity Failures** — partial: composer lockfile is
  audited and Sanctum tokens are scoped, but there is no SLSA-style supply-
  chain attestation on this app. See `SECURITY_NEXT_STEPS.md` for follow-up.
- **A09 Security Logging & Monitoring** — `login_activities`, `change_logs`,
  queue/email log listeners; CI security workflow runs on every PR + nightly.
- **A10 SSRF** — N/A: the app does not make user-controlled outbound HTTP
  requests; integrations are fixed-endpoint (mail, queue, dstrader DB).

## 6. Operational notes & gotchas

- The `before()` system-admin bypass is a single bypass point — assigning
  `system-admin` is irreversible from any non-system-admin path. Only
  `UserRoleController` (with explicit `isSystemAdmin` checks) can grant it.
- `RedirectStrayImpersonation` is local/dev/testing only — there is no
  `/dev-login` route in production. If you ever see one in prod, that's a
  config bug.
- `bin/secrets.sh status` is safe to share in logs/tickets — it never prints
  values, only state per pair.
- After a SOPS recipient change, run `bin/secrets.sh rekey`; after an `APP_KEY`
  rotation, plan for re-keying any DB columns cast as Laravel `encrypted`
  (`two_factor_*`).
- The shared dev-DB password is *not* a secret — local-only, in the encrypted
  `dev.sops`. Prod credentials never live in this repo (see
  ED-0001/SECURITY-EXPOSURE §4 actions).
- Pre-publish (going public) requires the §6 checklist in
  `SECURITY-EXPOSURE.md`: secrets rotated, history purged (done — see
  HISTORY-AUDIT-66.md), synthetic-only seed (done, ED-0014), screenshots
  cleaned.

## 7. Open security work

Tracked in [`SECURITY_NEXT_STEPS.md`](../SECURITY_NEXT_STEPS.md) — see that
file for the live list and validation commands; not duplicated here to avoid
drift.
