# ED-0012 — No intentionally-public API endpoints


- **Date:** 2026-05-24 · **Status:** Accepted · **Builds on:** ED-0003, ED-0004
- **Context:** The 2026-05 route audit (#14 item C, `SECURITY_NEXT_STEPS.md`
  "Remaining" #3) asked whether any `api/`-prefixed endpoint is *intentionally*
  public. At audit time `api/funds/{id}/overview-data` and the exchange-holiday
  endpoints were the open questions; they needed an explicit classification, not
  silent ambiguity that the route-guardrail tests would keep flagging as drift.
- **Decision:** **No FamilyFund API endpoint is intentionally public.**
  Classification of the audited routes:
  - **`api/funds/{id}/overview-data`** (`FundControllerExt@overviewData`, name
    `api.funds.overview_data`) is the AJAX data backer of the auth-gated fund
    **overview page**. It deliberately uses the **web `auth`** guard (session),
    *not* `auth:sanctum`, because it is consumed first-party by that page; it
    also runs `authorize('view', $fund)` (FundPolicy), so it is tenant-authorized
    identically to the page. The `api/` prefix is a URL convention only — it is a
    web route in `routes/web.php`, inside the `Route::middleware('auth')` group.
  - **`api/exchange_holidays/{exchange}/{year}`, `/status`, `/sync`** are
    auth-locked under the `auth:sanctum` group in `routes/api.php`. Exchange
    holidays are shared reference data (no tenant/IDOR dimension), but reads and
    the sync write still require authentication. (Admin-only write-authz hardening
    of global reference data is a separate concern, out of this classification —
    tracked in #82.)
  - There is **no public market-data / quote endpoint**; `api/asset_prices*` are
    all auth-locked.
  - The only unauthenticated `api/` route is **`api/clear`** (cache/route clear),
    which is **env-gated to `local`/`dev`** and never registered in production.
- **Consequences:** The route-guardrail allowlists
  (`TEMPORARY_UNAUTHENTICATED_API_READ_ALLOWLIST` /
  `…_MUTATION_ALLOWLIST` in `SecurityRouteAutomationTest`) stay **empty** —
  deny-by-default: any new unauthenticated `api/` route fails CI. The audited
  endpoints are additionally pinned authenticated **by name**
  (`test_audited_api_endpoints_are_authenticated_not_public`), so neither a move
  out of their auth group nor a delete-and-readd-as-public regresses silently;
  overview-data's anonymous→login redirect and per-role authz are also pinned in
  the ACL matrix golden, and `api/clear`'s env gating in
  `test_api_clear_route_is_environment_gated_in_source`. Introducing a genuinely
  public API endpoint in future is a conscious act that must update this ED and
  the guardrail.
- **Source:** #51 (item C), #14; `routes/web.php`, `routes/api.php`,
  `tests/Feature/SecurityRouteAutomationTest.php`, `tests/golden/acl_matrix.json`,
  `SECURITY_NEXT_STEPS.md`.
