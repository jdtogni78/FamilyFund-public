# ED-0020 — Multi-tenant strategy: fund is the tenant boundary

- **Date:** 2026-05-29 · **Status:** Accepted (de-facto convention — captured here from existing docs and code)
- **Builds on:** [ED-0003](ED-0003-deny-by-default-authorization.md),
  [ED-0008](ED-0008-repository-pattern-ext-models.md)

## Context

FamilyFund has no `organization` / `tenant` / `workspace` table. Every
business entity — accounts, transactions, portfolios, goals, matching rules,
reports — pivots on `fund_id`. Spatie `laravel-permission` is configured with
fund-scoped roles: a single user can be **Fund Admin of Fund A**,
**Beneficiary of Fund B**, and have **no access to Fund C** simultaneously.
`fund_id = 0` is the sentinel for the **System Admin** role (cross-fund
bypass). This shape pre-dates the ADR log and was carried implicitly in
`docs/ACL_IMPLEMENTATION.md` (Role Hierarchy table), the ACL matrix golden,
and the `fund.full` route middleware.

## Decision

**The Fund is the only tenant boundary.** All authorization, scoping, and
reporting pivot on `fund_id`. A user's authority is expressed as
**(role, fund_id)** pairs:
- `fund_id = 0` + role `System Admin` → cross-fund bypass.
- `fund_id = N` + role in {`Fund Admin`, `Financial Manager`, `Beneficiary`}
  → fund-scoped authority over Fund N.
There is no higher container (organization, workspace, account-group). If a
multi-tenant-org-like grouping is ever needed (e.g. for white-labelling), it
must be added as a **new** layer above Fund and re-litigate ACL/scoping; do
not retro-fit by reinterpreting `fund_id`.

## Consequences

- The Spatie pivot table carries the `fund_id` column; every role-grant lives
  at a specific fund.
- `AuthorizationService` scopes (account-owned, fund-column, portfolio
  subtree — see [ED-0015](ED-0015-management-pages-controller-scoping.md))
  all join through fund_id. Cross-fund visibility ⇒ system-admin only.
- The ACL matrix golden tests **role × route** with role implicitly bound to
  one fund; cross-fund leak detection relies on row counts going to zero,
  not on an org-scope check.
- Reference data (assets, asset_prices, matching_rules, exchange_holidays)
  is **global** — explicitly NOT fund-scoped — and gated separately as
  system-admin-only writes
  ([ED-0016](ED-0016-reference-data-writes-admin-only.md)).
- `fund.full` route middleware + the controller-level scoping
  ([ED-0015](ED-0015-management-pages-controller-scoping.md)) and the
  object-level API trait ([ED-0004](ED-0004-object-level-api-authorization.md))
  together enforce the tenant boundary in depth.
- A "switch fund" UX (header dropdown, `?fund=N` parameter) is a navigation
  convenience, not an authority change — the user's
  `(role, fund_id)` grants are what govern access; the dropdown only changes
  which fund-scoped view is rendered.

## Source

`docs/ACL_IMPLEMENTATION.md` (Role Hierarchy, fund-scoped Spatie roles);
`tests/golden/acl_matrix.json`; `RolesAndPermissionsSeeder`;
`AuthorizationService` scope methods.
