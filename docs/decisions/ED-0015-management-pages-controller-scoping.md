# ED-0015 — Management index pages get controller-level scoping behind `fund.full`


- **Date:** 2026-05 · **Status:** Accepted
- **Context:** ~15 management index pages (goals, matchingRules, accountGoals,
  accountMatchingRules, portfolios, portfolioAssets, tradePortfolios,
  tradePortfolioItems, cashDeposits, assets, fundReports, accountReports,
  accountBalances, depositRequests, scheduledJobs) relied **solely** on the
  `fund.full` route middleware for tenant isolation; their `index()` methods did
  unscoped `->all()` / manual queries. A beneficiary 403s at the middleware
  today, but it was a single layer — and a full-access fund-admin already saw
  *other* funds' rows. Follow-up to the beneficiary-ACL hardening (ED context in
  commit 66937d01).
- **Decision:** Add a second, in-controller layer mirroring
  Account/Transaction/Fund:
  - **Rows with a fund/account to scope on** are filtered in `index()` via
    `AuthorizationService` — `scopeByAccountRelation` (account-owned),
    `scopeByFundColumn` (direct `fund_id`), `scopePortfoliosQuery` /
    `scopeByPortfolioColumn` / `scopeByPortfolioRelation` (portfolio subtree).
    A fund-admin of X now sees only X's rows; system-admin sees all
    (short-circuit); deny-by-default for no-access users.
  - **Global/template data with no fund or account column** (goals,
    matchingRules, assets, scheduledJobs) instead re-asserts the capability with
    `AppBaseController::requireFullFundAccessSurface()` (=
    `AuthorizationService::canAccessManagementSurface()`), which mirrors the
    `RequireFullFundAccess` middleware. If the route middleware is ever removed,
    the controller still denies non-privileged callers.
- **Consequences:**
  - Portfolios belong to a fund via the legacy `fund_id` column **and** the
    `fund_portfolio` pivot, so portfolio scoping must check both (mirrors
    `AuthorizesApiAccess::scopePortfolioQuery`); plain `scopeByFundColumn` is
    insufficient.
  - `TradePortfolioExt::portfolio()` is an **overridden, validating accessor**
    that throws inside `whereHas`. So portfolio-child resources with a direct
    `portfolio_id` are scoped by **column** (`scopeByPortfolioColumn`
    pre-resolves accessible portfolio ids) rather than by traversing that
    relation. Two-hop children with clean relations (trade_portfolio_items via
    `tradePortfolio.portfolio`) still use the relation form.
  - `WebControllerSmokeTest` runs `WithoutMiddleware` and unauthenticated, so the
    new `/assets` guard correctly 403'd it — proving the defense-in-depth. The
    smoke test now authenticates as a system-admin (these are admin surfaces).
  - No route changes, so the ACL golden is unaffected; status codes per role are
    unchanged (the guard 403s exactly where the middleware already did).
- **Source:** #85.
