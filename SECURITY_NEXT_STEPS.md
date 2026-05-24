# Security Automation Next Steps

## Immediate Automation Work

1. Keep the security access tests in required CI.
   - It captures expected secure behavior for anonymous API access, cross-account API access, account reports, and fund reports.
   - The initial API/report authorization findings covered by this suite are fixed and the suite is green.
   - `SecurityApiAclMatrixTest` now adds role-based API account matrix checks for anonymous, unassigned, beneficiary, financial manager, fund admin, and system admin.

2. Shrink the temporary route guardrail baselines.
   - Remove API routes from `TEMPORARY_UNAUTHENTICATED_API_READ_ALLOWLIST` as they move behind auth.
   - Remove API routes from `TEMPORARY_UNAUTHENTICATED_API_MUTATION_ALLOWLIST` as they move behind auth.
   - DONE (#50): the resend/send-all/announce `GET` endpoints were converted to
     POST with CSRF and removed from `TEMPORARY_SIDE_EFFECT_GET_ALLOWLIST` (now
     empty — the guardrail now blocks any new side-effect-like GET).

3. Expand API ACL matrix coverage beyond accounts.
   - DONE (#44): `SecurityApiAclMatrixTest` now asserts the unauthenticated→401
     boundary for funds, transactions, account/fund reports, users, people, and
     id documents (index + detail) — the invariant that holds for every resource
     today.
   - TODO (#49, item A): once those controllers get policy/scoped-query
     enforcement, add their full role→status matrix and assert the absence of
     cross-tenant identifiers in response bodies (deferred because 7 of 8
     controllers have no object-level scoping yet).

4. Tighten CSRF route automation after side-effect GET cleanup.
   - `SecurityCsrfRouteAutomationTest` now verifies web mutation routes stay in the web middleware group, business web mutations require auth, and no project-level CSRF exemptions are configured.
   - Add representative HTTP-level missing-token tests for high-risk static routes once the route set is less noisy.

5. Use route-list artifacts for review.
   - CI now uploads `php artisan route:list --json` from the security route guardrail job.
   - Compare route count and unauthenticated route count between PRs when practical.

## Remaining Security Fixes Before Tightening Automation Further

1. Add policy checks and scoped queries to the remaining generated API controllers, starting with funds, users, people, reports, and transactions.
2. DONE (#50): side-effect `GET` routes (resend/send-all/announce) replaced with POST + CSRF routes.
3. Decide which exchange holiday and market-data endpoints, if any, are intentionally public.
4. Expand API ACL matrix coverage to response-body checks for cross-tenant identifiers.

## Validation Commands

Run the current route guardrails:

```bash
cd app1/family-fund-app
php artisan test --filter=SecurityRouteAutomationTest
php artisan test --filter=SecurityCsrfRouteAutomationTest
```

Run DB-backed security access tests:

```bash
cd app1/family-fund-app
php artisan test --filter=SecurityAccessRegressionTest
php artisan test --filter=SecurityApiAclMatrixTest
```

Run fast local scans:

```bash
cd app1/family-fund-app
bin/security-scan.sh
```

Run DAST baseline against a local/preview app:

```bash
cd app1/family-fund-app
ZAP_TARGET=http://host.docker.internal:3001 bin/zap-baseline.sh
```
