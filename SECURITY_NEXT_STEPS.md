# Security Automation Next Steps

## Immediate Automation Work

1. Turn `SecurityAccessRegressionTest` on after the known API/report authorization findings are fixed.
   - It currently captures expected secure behavior for anonymous API access, cross-account API access, account reports, and fund reports.
   - Keep it out of required CI until those fixes land, or mark individual tests skipped with issue IDs if partial rollout is needed.

2. Shrink the temporary route guardrail baselines.
   - Remove API routes from `TEMPORARY_UNAUTHENTICATED_API_READ_ALLOWLIST` as they move behind auth.
   - Remove API routes from `TEMPORARY_UNAUTHENTICATED_API_MUTATION_ALLOWLIST` as they move behind auth.
   - Remove side-effect `GET` routes from `TEMPORARY_SIDE_EFFECT_GET_ALLOWLIST` after converting them to POST/CSRF-protected actions.

3. Add API ACL matrix coverage.
   - Mirror the existing `AclMatrixTest` approach for API routes.
   - Cover anonymous, unassigned, beneficiary, financial manager, fund admin, and system admin.
   - Record both status code and absence of cross-tenant identifiers in response bodies.

4. Add focused CSRF route tests.
   - Iterate over web `POST`, `PUT`, `PATCH`, and `DELETE` routes.
   - Assert missing/invalid CSRF tokens are rejected for routes in the web middleware group.
   - Keep route parameters resolved from fixtures or a small route-specific resolver map.

5. Add artifact capture for route drift.
   - In CI, upload `php artisan route:list --json` as a workflow artifact.
   - Compare route count and unauthenticated route count between PRs when practical.

## Security Fixes Needed Before Tightening Automation

1. Put generated API routes behind authentication.
2. Add policy checks and scoped queries to generated API controllers.
3. Add object-level authorization to account/fund report web controllers.
4. Replace side-effect `GET` routes such as resend/send/announce endpoints with POST routes.
5. Decide which exchange holiday and market-data endpoints, if any, are intentionally public.

## Validation Commands

Run the current route guardrails:

```bash
cd app1/family-fund-app
php artisan test --filter=SecurityRouteAutomationTest
```

Run the future access regression suite after fixing known findings:

```bash
cd app1/family-fund-app
php artisan test --filter=SecurityAccessRegressionTest
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
