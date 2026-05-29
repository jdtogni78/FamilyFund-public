# ED-0003 — Deny-by-default authorization


- **Date:** 2026-05-24 · **Status:** Accepted
- **Context:** Legacy `AuthorizationService` scopes (accounts/transactions/
  credit-lines) returned **all** rows for a user with no access (empty-closure
  leak; e.g. `GET /api/accounts` leaked everything). ACL gating on `fund.full`
  was also incomplete on admin/deposit/PII surfaces.
- **Decision:** Make authorization **deny-by-default**: no matching scope ⇒ no
  rows. Extend `fund.full` gating to admin/deposit/PII and to write operations.
  Enforce with an ACL matrix golden test (every GET route × every role) plus a
  fast critical-routes subset that runs every time.
- **Consequences:** Adding/removing a GET web route changes the AclMatrix golden —
  prefer surgical key edits over a full regen. New endpoints must opt in to a
  scope explicitly or they return nothing.
- **Source:** #74 (merge `884053eb`), #44 (`66318fff`), `tests/Feature/AclMatrixTest.php`,
  `tests/golden/acl_matrix.json`.
