# Engineering Decisions (ADR index)

The canonical, living log of significant engineering decisions for FamilyFund
— a lightweight ADR index. One file per decision under
[`decisions/`](decisions/). Each entry follows
**Context → Decision → Consequences**, with a date, a status, and a source
you can verify (commit, PR/issue, or doc).

> Renamed from `ENGINEERING_DECISIONS.md`. The legacy file is now a thin
> pointer so existing `[ED-NNNN](docs/ENGINEERING_DECISIONS.md)` links still
> resolve — update new references to point at the per-ADR file under
> [`decisions/`](decisions/).

## How to use this file

- Add a new `ED-NNNN-<slug>.md` under [`decisions/`](decisions/) when a
  decision is non-obvious, hard to reverse, or future-you would otherwise
  re-litigate. Don't duplicate what the code / `AGENTS.md` already makes
  obvious.
- Then add the new row to the **Index** table below (and re-sort by ID).
- Never supersede in place — add a new entry and flip the old one's
  **Status** to `Superseded by ED-NNNN`. History stays readable.
- Keep secret VALUES out of these files (decisions only).
- **Cross-repo** decisions (the worktree workflow, multi-agent ticket
  coordination, the env/test pools, secrets posture, etc.) live in the
  ai-harness `DECISIONS.md` (`GD-NNNN`) — this index is FamilyFund-only.

## Status legend

| Status | Meaning |
|--------|---------|
| **Proposed** | Captured here so we don't re-derive it from a plan doc; not yet implemented. The plan doc remains authoritative until the ED flips to Accepted. |
| **Accepted** | In effect in the codebase / runtime. Default for shipped work. |
| **Superseded** | Replaced by a later ED; the entry stays in the index for history. Link `Superseded by ED-NNNN` in the row and in the file's header. |
| **Deprecated** | Still factually in the tree but on the way out. New code must not extend it; flip to `Superseded` when a replacement ADR lands. |

## Index

| ID | Date | Status | Decision |
|----|------|--------|----------|
| [ED-0001](decisions/ED-0001-secrets-out-of-git.md) | 2026-05-22 | Accepted | Secrets stay out of git (git-ignored `.env` + rotation + scanning), not a secrets server |
| [ED-0002](decisions/ED-0002-sops-age-encrypted-secrets.md) | 2026-05-24 | Accepted (location amended by ED-0017) | SOPS + age for in-repo, committed, encrypted env secrets |
| [ED-0003](decisions/ED-0003-deny-by-default-authorization.md) | 2026-05-24 | Accepted | Deny-by-default authorization across scopes + `fund.full` ACL gating |
| [ED-0004](decisions/ED-0004-object-level-api-authorization.md) | 2026-05-24 | Accepted | Object-level API authorization via `AuthorizesApiAccess` trait |
| [ED-0005](decisions/ED-0005-security-scan-ci-gate.md) | 2026-05-24 | Accepted | Security Scan CI is the gate; CodeQL dropped for PHP/private-repo reasons |
| [ED-0006](decisions/ED-0006-nightly-test-group.md) | 2026-05-24 | Accepted | Slow tests tagged `@group nightly`, excluded from the default run |
| [ED-0007](decisions/ED-0007-isolated-testpool-dbs.md) | 2026-05-24 | Accepted | Isolated `testpool` DBs for destructive/coverage runs, separate from the shared dev DB |
| [ED-0008](decisions/ED-0008-repository-pattern-ext-models.md) | (pre-existing) | Accepted | Repository pattern for data access; `*Ext` model variants for business logic |
| [ED-0009](decisions/ED-0009-goal-current-is-net-shares.md) | 2026-05 | Accepted | Goal "Current" uses net shares (OWN − BOR), not gross |
| [ED-0010](decisions/ED-0010-loan-share-label-only-rename.md) | 2026-05 | Accepted | "Loan Share" is a UI label-only rename of credit_line |
| [ED-0011](decisions/ED-0011-vite-6-frontend-build.md) | 2026-05 | Accepted | Frontend build pinned to Vite 6 (lowest major with patched esbuild + current plugin's peer range) |
| [ED-0012](decisions/ED-0012-no-public-api-endpoints.md) | 2026-05-24 | Accepted | No intentionally-public API endpoints; overview-data + exchange holidays are auth-locked |
| [ED-0013](decisions/ED-0013-phpstan-baseline-non-blocking.md) | 2026-05-24 | Accepted | Larastan/PHPStan static analysis at level 5 behind a baseline; non-blocking gate to start |
| [ED-0014](decisions/ED-0014-synthetic-test-baseline.md) | 2026-05 | Accepted | Test baseline is a synthetic seeder (`TestBaselineSeeder`), not a committed real-data dump |
| [ED-0015](decisions/ED-0015-management-pages-controller-scoping.md) | 2026-05 | Accepted | Management index pages get a second, in-controller scoping layer behind `fund.full` |
| [ED-0016](decisions/ED-0016-reference-data-writes-admin-only.md) | 2026-05-25 | Accepted | Global reference-data API writes are system-admin-only (flag-gated); dstrader uses a system-admin service token |
| [ED-0017](decisions/ED-0017-secrets-in-private-repo.md) | 2026-05-25 | Accepted | Encrypted env secrets move OUT of the (soon-public) app repo into a private `familyfund-secrets` repo |
| [ED-0018](decisions/ED-0018-independent-stage-app-key.md) | 2026-05-28 | Accepted | Stage `APP_KEY` is independent of prod; encrypted 2FA columns are nulled on restore |
| [ED-0019](decisions/ED-0019-familyfund-semgrep-rules.md) | 2026-05-29 | Accepted | FamilyFund-specific Semgrep custom rules (`.semgrep/familyfund.yml`) run alongside the registry packs |
| [ED-0020](decisions/ED-0020-multi-tenant-fund-scoped-roles.md) | 2026-05-29 | Accepted | Multi-tenant strategy: the Fund is the tenant boundary; authority is `(role, fund_id)` (no orgs above) |
| [ED-0021](decisions/ED-0021-money-flow-recipient-registry.md) | 2026-05-29 | Proposed | Money-flow subsystem: USD-only booking + recipient registry + cash-buffer principle |
