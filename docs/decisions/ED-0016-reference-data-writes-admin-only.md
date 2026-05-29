# ED-0016 — Reference-data API writes are system-admin-only


- **Date:** 2026-05-25 · **Status:** Accepted · **Builds on:** ED-0004, ED-0012
- **Context:** Every `/api/*` route is auth-locked (ED-0012) and tenant data is
  object-scoped (ED-0004), but create/update/delete on the **global shared/
  reference** resources (assets, asset_prices, matching_rules, schedules,
  change_logs, asset_change_logs, exchange_holidays) plus the
  `asset_prices_bulk_update` price feed were writable by **any** authenticated
  user. The only external writer is dstrader (the trading app + report
  scheduler), which historically sent **no auth at all** — already incompatible
  with the auth-locked API (#82).
- **Decision:** Reference-data writes are **system-admin-only**, enforced by
  `AuthorizesApiAccess::adminWriteMiddleware()` (controller middleware on
  store/update/destroy + bulkStore, so it rejects before FormRequest validation)
  and `requireAdminWrite()` (exchange_holidays/sync). The tightening is gated by
  `config('familyfund.enforce_admin_writes')` (env `FF_ENFORCE_ADMIN_WRITES`,
  default on) so a prod cutover can deploy with it **off**, confirm dstrader's
  token works, then flip it **on**. dstrader authenticates as a dedicated
  **system-admin service user** (`DstraderServiceUserSeeder`) using two named,
  expiring Sanctum tokens (`app`, `scheduler`) minted/rotated by
  `php artisan security:service-token`.
- **Consequences:**
  - The positions/balances bulk endpoints already gate via
    `requireFullAccessToAnyFund()` (fund-scoped data) — left as-is; system admins
    satisfy it, so they were **not** tightened to system-admin-only.
  - Enforcement is asserted by
    `SecurityApiAclMatrixTest::test_reference_data_writes_are_admin_only` (+ the
    flag-off path). Generated `tests/APIs/*` and the functional bulk tests use
    `WithoutMiddleware`, which bypasses the ctor gate, so they stay green and keep
    testing controller mechanics.
  - Tokens expire (default 90d); a daily `security:service-token --check-expiry`
    scheduler task warns before lapse and a weekly `--prune-expired` cleans up.
    Actual rotation/distribution runs as an external dstrader-docker cron (it must
    push the new token to dstrader). Runbook:
    `docs/runbooks/ff-service-token-rotation.md`.
  - **Cross-repo:** dstrader (Java `HTTPUtils` / `HolidayAPIClient`) and
    dstrader-docker (`familyfund_bashlib.sh`) must send
    `Authorization: Bearer <token>` and deploy together with this change.
- **Source:** #82 (split from #51); `routes/api.php`; `AuthorizesApiAccess`.
