# Security Scan Automation Plan

## Goal

Build an automated security pass that runs consistently on pull requests, on a schedule, and locally before risky changes land. The first version should catch dependency CVEs, leaked secrets, common Laravel/PHP security bugs, vulnerable container packages, and unsafe infrastructure configuration without depending on the production server or production data.

## Current Repo Context

- Laravel app lives in `app1/family-fund-app`.
- Docker compose entrypoint lives in `app1/`; tests must run inside the `familyfund` container.
- PHP and JS lockfiles are present: `composer.lock` and `package-lock.json`.
- Existing test wrapper: `app1/family-fund-app/bin/test.sh`.

## Implemented in This Branch

- `.github/workflows/security-scan.yml`
  - PR/push checks: Composer audit, npm audit, Gitleaks, Semgrep, and Trivy config scan.
  - Scheduled/manual checks: Trivy filesystem scan, Trivy Docker image scan, SBOM generation, OSV scanner, and non-blocking OWASP ZAP baseline.
- `.github/workflows/codeql.yml`
  - CodeQL security-and-quality analysis for PHP and JavaScript/TypeScript.
- `.github/dependabot.yml`
  - Weekly Composer, npm, and GitHub Actions update PRs.
- `.gitleaks.toml`, `.semgrepignore`, and `.trivyignore`
  - Initial scanner config with narrow local-development exceptions and generated/vendor path ignores.
- `app1/family-fund-app/bin/security-scan.sh`
  - Local fast security scan wrapper. It uses host Composer when present, otherwise falls back to a running FamilyFund container.
- `app1/family-fund-app/bin/zap-baseline.sh`
  - Local OWASP ZAP baseline wrapper for scanning a local or preview FamilyFund URL and writing reports under `storage/security/zap`.

Note: third-party GitHub Actions are version-pinned but not commit-SHA pinned yet. Pin them to commit SHAs before making this workflow a protected required check.

## Target Automated Pass

### PR Gate: Fast Required Checks

Add `.github/workflows/security-scan.yml` with required jobs that finish quickly enough to run on every PR:

1. **PHP dependency audit**
   - Working directory: `app1/family-fund-app`
   - Command: `composer audit --locked --format=json`
   - Fail on known vulnerable installed packages.

2. **Node dependency audit**
   - Working directory: `app1/family-fund-app`
   - Command: `npm audit --audit-level=moderate`
   - Start at `moderate`; tighten later once noise is understood.

3. **Secret scanning**
   - Tool: `gitleaks`
   - Command: `gitleaks detect --source . --redact --config .gitleaks.toml`
   - Add `.gitleaks.toml` with repo-specific allowlists for known fake/local credentials only.

4. **Static application security scan**
   - Tool: `semgrep`
   - Configs:
     - `p/laravel`
     - `p/php`
     - `p/owasp-top-ten`
   - Output SARIF for GitHub code scanning.
   - Start as warning-only if initial findings are noisy; promote high-confidence rules to required.

5. **IaC and repository configuration scan**
   - Tool: `trivy config`
   - Targets: `app1/Dockerfile`, `app1/docker-compose*.yml`, `.github/workflows/*.yml`
   - Fail on `HIGH` and `CRITICAL` once baseline is clean.

6. **Security route guardrails**
   - Test: `php artisan test --filter=SecurityRouteAutomationTest`
   - Blocks new unauthenticated API mutation routes unless explicitly added to a temporary baseline.
   - Blocks new unauthenticated API read routes unless explicitly added to a temporary baseline.
   - Blocks new unauthenticated web routes unless explicitly added to the public-route baseline.
   - Blocks new side-effect-like `GET` routes unless explicitly added to a temporary baseline.
   - Verifies `/dev-login` and `/api/clear` stay guarded by `local`/`dev` environment checks.
   - Treat temporary allowlists as security debt, not approval.

7. **CSRF route guardrails**
   - Test: `php artisan test --filter=SecurityCsrfRouteAutomationTest`
   - Verifies web mutation routes stay in the `web` middleware group.
   - Verifies business web mutation routes require authentication.
   - Verifies no project-level CSRF exemptions are configured without review.

8. **DB-backed access regressions**
   - Tests:
     - `php artisan test --filter=SecurityAccessRegressionTest`
     - `php artisan test --filter=SecurityApiAclMatrixTest`
   - Requires MariaDB and migrations.
   - Covers anonymous API blocking, beneficiary sibling/cross-fund API denial, report-page denial, and role-based account API matrix behavior.

9. **Route inventory artifact**
   - Command: `php artisan route:list --json > route-list.json`
   - Upload as a GitHub Actions artifact on PRs.
   - Use during review to spot route count changes and unauthenticated route drift.

### Nightly Scheduled Checks: Deeper Coverage

Add a scheduled workflow, or a second job in the same workflow, that runs nightly:

1. **Filesystem CVE scan**
   - Tool: `trivy fs`
   - Target: repo root.
   - Include PHP, Node, Dockerfile, OS package metadata where available.

2. **Docker image scan**
   - Build the development image from `app1/Dockerfile`.
   - Scan with `trivy image`.
   - Fail on fixable `CRITICAL` vulnerabilities first, then expand to `HIGH`.

3. **SBOM generation**
   - Tool: `syft`
   - Produce CycloneDX JSON artifact for the repo and/or built image.
   - Upload as workflow artifact.

4. **OSV scanner**
   - Tool: `osv-scanner`
   - Run against `composer.lock` and `package-lock.json`.
   - This overlaps with Composer/npm audits but catches advisory-source differences.

5. **DAST baseline**
   - Tool: OWASP ZAP baseline scan.
   - Boot the dev compose stack with test/local env only.
   - Scan `http://localhost:3001`.
   - Keep non-blocking at first; fail only on high-confidence alerts after triage.

### Laravel/PHP Hardening Checks

Add a repo script, `app1/family-fund-app/bin/security-scan.sh`, to make local and CI behavior match:

```bash
#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

composer audit --locked
npm audit --audit-level=moderate
gitleaks detect --source ../.. --redact --config ../../.gitleaks.toml
semgrep --config p/laravel --config p/php --config p/owasp-top-ten .
```

Then add optional deeper PHP analysis:

- Add `nunomaduro/larastan` and `phpstan/phpstan` as dev dependencies.
- Add `phpstan.neon` with Laravel-aware level 4 or 5 to start.
- Add security-focused custom ignores only with comments and issue links.
- Consider `vimeo/psalm` later if taint analysis becomes important; do not add both Larastan and Psalm as required PR gates at the same time until runtime is known.

## Dependency Update Automation

Add `.github/dependabot.yml`:

- Composer updates for `app1/family-fund-app`.
- npm updates for `app1/family-fund-app`.
- GitHub Actions updates for `.github/workflows`.
- Weekly cadence.
- Group non-major updates by ecosystem.
- Require the security scan workflow before merge.

## Baseline and Triage Policy

1. Run all proposed tools once locally and capture initial findings.
2. Fix true positives that are cheap and obvious.
3. Add narrow allowlist entries only for:
   - documented local-only credentials,
   - generated files that cannot contain production secrets,
   - accepted risks with a linked issue.
4. Avoid broad path ignores for `app/`, `routes/`, `resources/`, `config/`, and `database/`.
5. Track remaining findings in a security backlog before making the corresponding job required.

## Secrets and Environment Files

Expected local-only files currently include `app1/family-fund-app/.env.dev`. The scanner should:

- redact secret values in logs,
- fail on new private keys, tokens, API keys, and production-like credentials,
- allow known local Docker credentials such as `famfun_dev`, `1234`, and `123456` only where they appear in dev compose/env files,
- prevent committing real `.env` files if they are not already intentionally tracked.

## GitHub Code Scanning Integration

For Semgrep and Trivy SARIF:

- Upload SARIF with `github/codeql-action/upload-sarif`.
- Use read-only token permissions except where SARIF upload requires `security-events: write`.
- Pin third-party GitHub Actions to commit SHAs after the workflow is stable.

## Rollout Plan

### Phase 1: Local Script and Fast CI

- Add `bin/security-scan.sh`.
- Add `tests/Feature/SecurityRouteAutomationTest.php` for route-level security drift detection.
- Add `tests/Feature/SecurityCsrfRouteAutomationTest.php` for CSRF-related route drift detection.
- Add `tests/Feature/SecurityAccessRegressionTest.php` and `tests/Feature/SecurityApiAclMatrixTest.php` as DB-backed security regression coverage.
- Add `.gitleaks.toml`.
- Add `.semgrepignore` if generated/vendor paths create noise.
- Add `.github/workflows/security-scan.yml` with Composer audit, npm audit, Gitleaks, Semgrep, Trivy config, and security route guardrails.
- Run locally and in GitHub; record the first baseline.

### Phase 2: Required PR Gate

- Fix or explicitly triage initial critical/high findings.
- Mark fast CI jobs as required branch checks.
- Add Dependabot for Composer, npm, and GitHub Actions.

### Phase 3: Nightly Deep Scan

- Add Trivy image scan and SBOM artifact generation.
- Add OSV scanner.
- Add non-blocking OWASP ZAP baseline against the dev stack.
- Review nightly results weekly until noise is low.

### Phase 4: Advanced App Analysis

- Add Larastan/PHPStan as a separate quality/security-adjacent gate.
- Add targeted Semgrep custom rules for FamilyFund-specific risks:
  - raw SQL around account, transaction, asset, and fund IDs,
  - authorization bypasses in `WebV1` controllers,
  - unsafe file/report generation paths,
  - dev-only auto-login route leakage.

## Proposed Files to Add

- `.github/workflows/security-scan.yml`
- `.github/workflows/codeql.yml`
- `.github/dependabot.yml`
- `.gitleaks.toml`
- `.semgrepignore`
- `.trivyignore`
- `SECURITY_SCAN_AUTOMATION_PLAN.md`
- `app1/family-fund-app/bin/security-scan.sh`
- `app1/family-fund-app/bin/zap-baseline.sh`
- Optional later: `app1/family-fund-app/phpstan.neon`

## Acceptance Criteria

- A developer can run one local command and get the same core security checks as CI.
- Every PR runs dependency, secret, static security, and Docker/config scans.
- Nightly automation covers deeper image, SBOM, OSV, and DAST checks.
- Findings are visible in GitHub checks and code scanning.
- Known local test credentials are narrowly allowlisted; real secrets still fail the scan.
- The scan does not require production access or production data.
