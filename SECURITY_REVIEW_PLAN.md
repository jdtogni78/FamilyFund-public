# FamilyFund Security Review Plan

## Purpose

Run a focused security review of FamilyFund before treating the app as hardened. This plan covers code security scans, dependency and container scans, authenticated and unauthenticated penetration tests, authorization matrix validation, evidence capture, triage, remediation, and retest criteria.

The review must use local/dev infrastructure only. Do not test against production at `REDACTED_PROD_HOST`, do not use production credentials, and do not load unredacted production data into evidence files.

## Scope

### In Scope

- Laravel web routes in `app1/family-fund-app/routes/web.php`.
- API routes in `app1/family-fund-app/routes/api.php`.
- Auth, 2FA, password reset, email verification, sessions, and logout flows.
- Role and fund authorization for system admin, fund admin, financial manager, beneficiary, unassigned user, and anonymous visitor.
- Cross-fund and same-fund beneficiary isolation.
- Financial mutation flows: transactions, bulk transactions, cash deposits, deposit requests, scheduled jobs, rebalancing, credit lines, matching rules, and fund setup.
- Report and document generation: PDFs, email logs, attachments, fund reports, account reports, trade band reports.
- Development-only surfaces that must never work in production, especially `/dev-login/{redirect?}` and `/api/clear`.
- Docker, compose, GitHub Actions, dependency lockfiles, npm/Vite assets, and repository-level secrets.

### Out of Scope Unless Explicitly Approved

- Production penetration testing.
- Social engineering, phishing, password spraying, or attacks against personal email accounts.
- Destructive load tests, database corruption tests, or large-volume fuzzing.
- Attempts to access third-party broker/provider accounts.
- Testing with real investor PII in screenshots or exported reports.

## Environments and Accounts

Use one isolated worktree and one local/dev stack:

```bash
cd /Users/claudio1/dev/FamilyFund/.claude/worktrees/security-scan-infra-plan
git status --short --branch
cd app1
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

Use seeded or dev-only users:

- System admin: seeded admin/test user in dev DB.
- Fund admin: `qa-fund-admin@test.local`.
- Financial manager: `qa-financial-manager@test.local`.
- Beneficiary: `qa-beneficiary@test.local`.
- Automation user: `Codex@test.local` with password documented in `AGENTS.md`.

Use `/dev-login/{redirect?}?as=<role>` only on local/dev:

```bash
curl -i -L "http://localhost:3000/dev-login/dashboard?as=admin"
curl -i -L "http://localhost:3000/dev-login/accounts/1?as=beneficiary"
```

Confirm these fail outside `local` and `dev` environments during config review.

## Review Deliverables

- Security findings register with severity, affected path, reproduction steps, impact, owner, fix, and retest result.
- Scan artifacts: Composer audit, npm audit, Gitleaks, Semgrep, CodeQL, Trivy config/fs/image, OSV, SBOM, ZAP baseline, and any manual proxy notes.
- Route and authorization matrix results.
- Manual pen-test notes for each role.
- Remediation PRs or backlog issues for every accepted finding.
- Final retest report showing no unresolved critical/high findings.

## Severity Rules

- Critical: unauthenticated financial mutation, production secret exposure, remote code execution, unrestricted admin action, cross-tenant financial data write, or account takeover.
- High: cross-tenant data read involving financial/PII records, auth bypass, stored XSS in authenticated financial workflows, unsafe file download, token/session leakage, or dependency CVE with practical exploit path.
- Medium: reflected XSS, CSRF on meaningful state change, excessive data exposure, missing rate limiting, weak security headers, or noisy but plausible injection.
- Low: hardening gaps, minor information disclosure, missing audit event, non-sensitive dependency issue, or defense-in-depth improvement.

## Automation Plan

### Fully Automatable

- Dependency CVE checks: `composer audit`, `npm audit`, OSV Scanner, Dependabot.
- Secret scanning: Gitleaks on every PR and full history on a schedule.
- Static security scans: Semgrep Laravel/PHP/OWASP rules and CodeQL PHP/JavaScript.
- Container/config scans: Trivy config, filesystem, and Docker image scans.
- SBOM generation: Syft/CycloneDX artifact on nightly/manual workflows.
- Route inventory drift: `php artisan route:list` artifact and route guardrail tests.
- Existing security tests: `AuthorizationTest`, `CrossTenantIsolationTest`, `AclMatrixTest`, and `TwoFactorAuthTest`.
- Basic DAST: OWASP ZAP baseline unauthenticated, then authenticated crawls once session handling is scripted.
- Security artifact upload: SARIF to GitHub code scanning plus JSON/HTML reports as CI artifacts.

### Mostly Automatable With Custom Tests

- Anonymous access to protected web routes redirects or returns 401/403.
- Anonymous access to API mutation routes fails, or is listed as temporary security debt while remediated.
- Beneficiaries cannot access sibling or cross-fund account/fund/report data.
- Fund admins cannot access other funds.
- Financial managers cannot perform fund-admin-only actions.
- Hidden field tampering for `fund_id`, `account_id`, `user_id`, `role`, `is_admin`, amounts, shares, and status is rejected or ignored.
- CSRF rejection for every state-changing web route.
- Dangerous actions are not exposed as `GET`.
- `/dev-login` and `/api/clear` remain local/dev-only.
- Email attachment and report downloads enforce authorization.
- Admin operations are system-admin-only.
- Bulk transaction, cash deposit, credit-line, and scheduled-job flows reject cross-fund IDs.

### Partially Automatable

- Authenticated ZAP scans per role.
- Fuzzing date, ID, and query parameters for 500s or data leaks.
- XSS payload checks in Blade, PDFs, emails, and reports.
- File traversal checks for attachments, reports, and log views.
- Security header checks.
- Log redaction checks for passwords, tokens, 2FA secrets, recovery codes, and PII-heavy payloads.
- Rate-limit checks for login, password reset, 2FA challenge, and sensitive API routes.

### Human Review Still Required

- Whether an API route is intentionally public.
- Business-logic abuse in financial workflows.
- Whether role permissions match real-world expectations.
- Whether a scan finding is actually exploitable or just noisy.
- Accepted-risk decisions and remediation priority.

### Automation Started

- `tests/Feature/SecurityRouteAutomationTest.php` guards route drift:
  - blocks new unauthenticated API mutation routes unless explicitly added to a temporary baseline,
  - blocks new unauthenticated API read routes unless explicitly added to a temporary baseline,
  - blocks new unauthenticated web routes unless explicitly added to the public-route baseline,
  - blocks new side-effect-like `GET` routes unless explicitly added to a temporary baseline,
  - asserts `/dev-login` is guarded by `local`/`dev`,
  - asserts `/api/clear` is guarded by `local`/`dev`.
- `routes/api.php` now gates `/api/clear` behind `app()->environment('local', 'dev')`.
- `app1/family-fund-app/bin/security-scan.sh` runs the fast local security scan set.
- `app1/family-fund-app/bin/zap-baseline.sh` runs the local ZAP baseline scan.

## Phase 0: Preparation

1. Confirm the worktree branch and isolate local outputs:

```bash
cd /Users/claudio1/dev/FamilyFund/.claude/worktrees/security-scan-infra-plan
git status --short --branch
mkdir -p output/security-review
```

2. Record tool versions:

```bash
cd app1/family-fund-app
php --version || true
composer --version || true
npm --version || true
docker --version
docker compose version || docker-compose version
gitleaks version || true
semgrep --version || true
trivy --version || true
```

3. Capture route inventory:

```bash
cd app1/family-fund-app
docker exec familyfund php artisan route:list --columns=method,uri,name,action,middleware \
  > ../../output/security-review/route-list.txt
```

4. Capture current test/security baseline:

```bash
cd app1/family-fund-app
FF_CONTAINER=familyfund bin/test.sh --filter=AuthorizationTest
FF_CONTAINER=familyfund bin/test.sh --filter=CrossTenantIsolationTest
FF_CONTAINER=familyfund bin/test.sh --filter=AclMatrixTest
FF_CONTAINER=familyfund bin/test.sh --filter=TwoFactorAuthTest
FF_CONTAINER=familyfund bin/test.sh --filter=SecurityRouteAutomationTest
```

## Phase 1: Threat Model

Document assets, actors, trust boundaries, and dangerous actions before testing.

### Assets

- Fund NAV, share price, performance, and account balances.
- Beneficiary PII and account ownership.
- Transactions, cash deposits, credit lines, matching rules, schedules, generated reports, and email attachments.
- User roles and fund permissions.
- Session cookies, 2FA secrets, recovery codes, password reset tokens, Sanctum/API tokens, and app keys.

### Actors

- Anonymous internet user.
- Authenticated unassigned user.
- Beneficiary trying to access sibling or cross-fund data.
- Financial manager trying to exceed role privileges.
- Fund admin trying to access another fund.
- System admin.
- Compromised browser/session.
- CI or developer machine with local secrets.

### Trust Boundaries

- Browser to Laravel web middleware.
- Public API routes to application controllers.
- Laravel app to MariaDB.
- Laravel app to mailhog/mail logs, quickchart, wkhtmltopdf, filesystem storage, and queues.
- GitHub Actions to repository secrets and SARIF uploads.
- Local/dev env to production deploy scripts.

### High-Risk Questions

- Can any unauthenticated API route mutate financial data?
- Can one fund's user read or write another fund's records by changing IDs?
- Can a beneficiary see same-fund sibling accounts or generated reports?
- Can admin-only operations be triggered by non-admin roles?
- Can reports, email attachments, PDFs, or generated files be downloaded by guessing filenames or hashes?
- Can `/dev-login` or `/api/clear` exist outside dev/local?
- Can financial mutation routes be triggered with CSRF, stale IDs, hidden fields, or mass-assigned attributes?
- Can raw SQL, file paths, report templates, or wkhtmltopdf inputs be abused?
- Can secrets leak through committed files, logs, artifacts, screenshots, or SARIF output?

## Phase 2: Automated Scans

Run fast local scans first:

```bash
cd /Users/claudio1/dev/FamilyFund/.claude/worktrees/security-scan-infra-plan/app1/family-fund-app
bin/security-scan.sh 2>&1 | tee ../../output/security-review/security-scan-fast.log
```

Run dependency-specific scans:

```bash
composer audit --locked --format=json | tee ../../output/security-review/composer-audit.json
npm audit --audit-level=moderate --json | tee ../../output/security-review/npm-audit.json
```

Run secret scanning:

```bash
cd /Users/claudio1/dev/FamilyFund/.claude/worktrees/security-scan-infra-plan
gitleaks detect --source . --redact --config .gitleaks.toml \
  --report-format json --report-path output/security-review/gitleaks.json
```

Run static app scans:

```bash
cd app1/family-fund-app
semgrep scan \
  --config p/laravel \
  --config p/php \
  --config p/owasp-top-ten \
  --json --output ../../output/security-review/semgrep.json .
```

Run framework and route-focused manual greps:

```bash
cd /Users/claudio1/dev/FamilyFund/.claude/worktrees/security-scan-infra-plan
rg -n "Artisan::call|loginUsingId|dev-login|withoutMiddleware|except\\(|csrf|auth:api|Route::(get|post|put|patch|delete)|Gate::|authorize\\(|is_admin|isSystemAdmin" \
  app1/family-fund-app/app app1/family-fund-app/routes app1/family-fund-app/resources/views \
  > output/security-review/auth-sensitive-grep.txt

rg -n "DB::raw|whereRaw|havingRaw|orderByRaw|statement\\(|selectRaw|unserialize\\(|eval\\(|shell_exec|exec\\(|passthru|proc_open|file_get_contents|Storage::|download|response\\(\\)->download|wkhtml|Snappy|temporaryDirectory" \
  app1/family-fund-app/app app1/family-fund-app/routes app1/family-fund-app/resources/views \
  > output/security-review/injection-file-pdf-grep.txt
```

Run container and config scans:

```bash
cd /Users/claudio1/dev/FamilyFund/.claude/worktrees/security-scan-infra-plan
trivy config --severity HIGH,CRITICAL --format json --output output/security-review/trivy-config.json .
trivy fs --severity HIGH,CRITICAL --format json --output output/security-review/trivy-fs.json .
cd app1
docker build -t familyfund-security-review:local .
trivy image --severity HIGH,CRITICAL --ignore-unfixed --format json \
  --output ../output/security-review/trivy-image.json familyfund-security-review:local
```

Run OSV and SBOM if installed:

```bash
cd /Users/claudio1/dev/FamilyFund/.claude/worktrees/security-scan-infra-plan
osv-scanner --lockfile app1/family-fund-app/composer.lock --lockfile app1/family-fund-app/package-lock.json \
  --format json > output/security-review/osv.json
syft . -o cyclonedx-json > output/security-review/familyfund-sbom.cdx.json
```

## Phase 3: Dynamic App Scans

Start the dev stack and build frontend assets:

```bash
cd /Users/claudio1/dev/FamilyFund/.claude/worktrees/security-scan-infra-plan/app1
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
cd family-fund-app
npm install
npm run build
```

Run ZAP baseline unauthenticated:

```bash
cd app1/family-fund-app
ZAP_TARGET=http://host.docker.internal:3001 \
ZAP_REPORT_DIR="$PWD/../../output/security-review/zap" \
  bin/zap-baseline.sh
```

For authenticated ZAP/manual proxy testing, log in locally as each role, export the session cookie from the browser/proxy, and run targeted spider/passive checks. Keep active scanning disabled until the target pages are confirmed safe to fuzz.

Suggested authenticated crawl targets:

- `/dashboard`
- `/funds`
- `/accounts`
- `/transactions`
- `/cashDeposits`
- `/depositRequests`
- `/credit-lines`
- `/operations`
- `/emails`
- `/scheduledJobs`
- `/tradePortfolios`
- `/matchingRules`

## Phase 4: Manual Pen-Test Script

For every manual test, record role, URL, request, response status, expected behavior, actual behavior, and whether sensitive content appeared in the response.

### Anonymous Tests

1. Visit every auth-protected route from `route-list.txt`; expect redirect to login or 401/403.
2. Hit API routes without credentials; flag any route that returns sensitive data or mutates state.
3. Test `/api/clear`; it must not be callable by anonymous users in any environment intended for deployment.
4. Test `/dev-login/dashboard?as=admin` with non-local environment config; it must 404 or be absent.
5. Test password reset and email verification for enumeration, token leakage, weak rate limiting, and reusable tokens.
6. Confirm unauthenticated ZAP results contain no sensitive data.

### Auth and Session Tests

1. Login success and failure messages must not disclose whether an email exists.
2. Confirm login throttling works for repeated bad passwords.
3. Confirm logout invalidates session and CSRF token expectations.
4. Confirm session cookie has secure production settings: `HttpOnly`, `SameSite=Lax` or stricter, `Secure` under HTTPS, scoped domain, reasonable lifetime.
5. Confirm 2FA setup, challenge, recovery code use, and disable flows require an authenticated user and password/2FA confirmation where appropriate.
6. Confirm recovery codes are shown once, stored hashed/encrypted if supported by the implementation, and not logged.

### Authorization and IDOR Tests

Run the same ID substitution pattern for `funds`, `accounts`, `transactions`, `cashDeposits`, `depositRequests`, `credit-lines`, `fundReports`, `accountReports`, `tradeBandReports`, `matchingRules`, `accountMatchingRules`, `portfolioAssets`, `scheduledJobs`, `users`, `people`, `addresses`, `phones`, and `id_documents`.

1. Beneficiary X requests own account; expect allowed where intended.
2. Beneficiary X requests beneficiary Y account in another fund; expect 403/redirect and no leaked identifiers.
3. Beneficiary X requests sibling account in same fund; expect 403/redirect and no leaked identifiers.
4. Fund admin for Fund X requests Fund Y objects; expect 403/redirect unless explicitly global admin.
5. Financial manager attempts fund-admin-only actions; expect 403/redirect.
6. Unassigned user attempts any business object; expect 403/redirect.
7. User modifies hidden `fund_id`, `account_id`, `user_id`, `role`, `is_admin`, `balance`, `shares`, or `status` fields in create/update requests; expect server-side rejection or ignored fields.
8. Repeat for API endpoints, not just Blade pages.

### Financial Mutation Tests

1. Transaction create/edit/delete: tamper account IDs, dates, share counts, values, pending transaction IDs, and clone source IDs.
2. Bulk transaction preview/store: send mismatched preview payloads, duplicate records, negative values, stale hidden fields, and cross-fund account IDs.
3. Cash deposits assignment: assign to unauthorized accounts, duplicate assignment, over-allocation, negative values, and stale deposit IDs.
4. Credit lines: create, edit, allocate payment, reverse, resolve match, and simulator endpoints with cross-account IDs and invalid amounts.
5. Rebalancing: post unauthorized portfolio/trade portfolio IDs and mismatched start/end dates.
6. Scheduled jobs: run, force-run, preview, and process pending as each role.
7. Operations dashboard: queue controls, failed-job flush/retry, due jobs, email test, and pending processing must be system-admin-only.

### API Route Tests

Pay special attention to `routes/api.php`; many routes are currently declared without an obvious auth middleware group.

1. For every `GET`, confirm whether public data exposure is intended.
2. For every `POST`, `PUT`, `PATCH`, and `DELETE`, confirm authentication, authorization, validation, and CSRF/token expectations.
3. Attempt bulk update endpoints without credentials: `asset_prices_bulk_update`, `portfolio_assets_bulk_update`, `portfolio_balances_bulk_update`, `schedule_jobs`, `funds/setup`, and exchange holiday sync.
4. Test resource routes for `funds`, `accounts`, `transactions`, `users`, `people`, and `id_documents` with anonymous and low-privilege users.
5. Validate API error messages do not leak SQL, stack traces, filesystem paths, or model internals.

### Injection Tests

1. Search and date parameters: quote characters, SQL comment syntax, long strings, invalid dates, encoded slashes, Unicode confusables, null bytes, and array parameters.
2. Raw SQL call sites from `injection-file-pdf-grep.txt`: verify all user input is bound, whitelisted, or cast.
3. HTML fields rendered in Blade/PDF/email: try `<script>`, event handlers, SVG payloads, style injection, and formula-like strings.
4. CSV/spreadsheet/report exports: confirm cells starting with `=`, `+`, `-`, and `@` are escaped if exported to spreadsheet-compatible formats.
5. File/report paths and attachment filenames: test `../`, encoded traversal, very long names, and MIME confusion.

### CSRF and Browser-Side Tests

1. Confirm every state-changing web route rejects missing or invalid CSRF tokens.
2. Confirm dangerous actions are not reachable by `GET`, especially resend email, send-all emails, queue actions, process actions, and report regeneration.
3. Confirm forms do not trust hidden fields for authorization or sensitive amounts.
4. Confirm CORS policy is closed unless a trusted origin list exists.
5. Confirm security headers: CSP or documented plan, `X-Frame-Options`/`frame-ancestors`, `X-Content-Type-Options`, `Referrer-Policy`, and HSTS in production.

### File, PDF, and Email Tests

1. Email attachment route: verify hash alone is not sufficient for unauthorized download; role checks and filename checks must apply.
2. Email log pages: test filename traversal and direct access to arbitrary log files.
3. PDF generation: test user-controlled HTML, external URL fetching, local file access, and JavaScript execution settings for wkhtmltopdf.
4. Generated reports: confirm fund/account report downloads enforce the same access rules as source pages.
5. Confirm generated files are stored outside public web root unless intentionally public.

### Logging and Privacy Tests

1. Trigger validation, auth, and server errors; logs must not include passwords, 2FA secrets, recovery codes, tokens, app keys, PII-heavy payloads, or full financial data dumps.
2. Confirm debug mode is off in production config.
3. Confirm exception pages are not exposed outside local/dev.
4. Confirm security-relevant events are logged: login failure, password reset, 2FA changes, role changes, financial mutations, report generation, and admin operations.

## Phase 5: Code Review Checklist

Review these files first:

- `routes/web.php`, `routes/api.php`, `routes/auth.php`.
- `app/Http/Controllers/WebV1/*`.
- `app/Http/Controllers/API/*` and `app/Http/Controllers/APIv1/*`.
- `app/Http/Requests/*` for validation and authorization.
- `app/Policies/*`, `app/Services/AuthorizationService.php`, `app/Repositories/Traits/AuthorizesQueries.php`.
- `app/Models/User.php`, `app/Models/UserExt.php`, and all fund/account relation helpers.
- PDF/email/report traits and controllers.
- `config/session.php`, `config/auth.php`, `config/sanctum.php` if present, `config/cors.php` if present, `config/snappy.php`, `config/filesystems.php`, `config/logging.php`, and env examples.
- Dockerfiles, compose files, GitHub Actions, and deployment scripts referenced by `AGENTS.md`.

For each controller action, answer:

- Is the route authenticated?
- Is the action authorized for the specific object, not merely the route?
- Are repository queries scoped to the user/fund?
- Are request fields validated and mass assignment constrained?
- Are IDs server-derived where possible?
- Is the response free of foreign tenant data?
- Are mutations protected by CSRF or API auth tokens?
- Are dangerous actions POST-only and audited?
- Are redirects safe from open redirect behavior?

## Phase 6: Findings Workflow

Use this finding format:

```text
ID:
Title:
Severity:
Affected component:
Role required:
Environment:
Impact:
Reproduction:
Evidence:
Root cause:
Recommended fix:
Retest steps:
Status:
Owner:
```

Triage order:

1. Stop and fix any critical finding immediately.
2. Batch high findings by root cause, especially missing API auth and shared authorization helper gaps.
3. Fix medium findings that have low code risk or protect financial workflows.
4. Convert accepted risk to explicit backlog items with owner and review date.

## Phase 7: Remediation Acceptance Criteria

The review is complete when:

- No unauthenticated API mutation route remains unless documented and deliberately public.
- `AuthorizationTest`, `CrossTenantIsolationTest`, `AclMatrixTest`, `TwoFactorAuthTest`, and `SecurityRouteAutomationTest` pass.
- New or changed high-risk routes have role/tenant tests.
- Fast security scans pass or have narrow, documented allowlists.
- Trivy/OSV critical and high findings are fixed, ignored with justification, or assigned.
- ZAP baseline has no untriaged high or medium findings.
- Manual pen-test notes show each role tested across the main financial workflows.
- `/dev-login` and `/api/clear` cannot be exposed in production.
- No evidence artifact contains real secrets, production credentials, or unredacted PII.

## Immediate FamilyFund-Specific Review Hotspots

- `routes/api.php` contains route definitions outside an explicit auth group; review every API route before relying on route-level protection.
- `/api/clear` calls Artisan cache-clearing commands; confirm it is removed, restricted to local/dev, or admin-protected before deployment.
- `/dev-login/{redirect?}` is useful for testing but must stay impossible outside local/dev.
- Email attachment downloads use `{hash}/{filename}`; verify hash guessing, filename traversal, and role checks.
- Operations, exchange holiday sync, scheduled jobs, and queue controls are high-impact admin surfaces.
- Bulk financial workflows and report generation need server-side authorization on every referenced ID.
- Wkhtmltopdf/Snappy should not fetch arbitrary local files or attacker-controlled external URLs.
- Existing ACL tests are valuable; expand them when new protected routes or API auth decisions are made.
