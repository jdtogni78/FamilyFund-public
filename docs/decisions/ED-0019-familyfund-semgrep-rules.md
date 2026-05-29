# ED-0019 — FamilyFund-specific Semgrep rules alongside the registry packs


- **Date:** 2026-05-29 · **Status:** Accepted · **Builds on:** ED-0005, ED-0013
- **Context:** The security scan (ED-0005) already runs Semgrep with the
  registry packs `p/php`, `p/owasp-top-ten`, and `p/security-audit`. Those
  catch the usual PHP / OWASP / framework pitfalls but miss FamilyFund's
  *own* risk surfaces — most notably the `/dev-login` impersonation route,
  the `security:dev-token` admin-token primitive, ad-hoc raw SQL with
  request input, and unsafe file reads. #77 (ED-0013) Phase 4 called for
  repo-local rules to cover those gaps.
- **Decision:** Author a small, **high-precision** repo-local ruleset at
  `.semgrep/familyfund.yml` and wire it into both
  `app1/family-fund-app/bin/security-scan.sh` and the `Semgrep` job in
  `.github/workflows/security-scan.yml` alongside the registry packs (one
  pass, single SARIF). Initial rules:
  1. `ff-dev-login-route-outside-env-guard` (ERROR)
  2. `ff-dev-token-call-outside-allowed-context` (ERROR)
  3. `ff-raw-sql-interpolates-request-input` (ERROR)
  4. `ff-unsafe-file-read-from-request` (ERROR)
  Precision bar: every rule must be **quiet on the current clean tree**
  (zero findings) and must fire on the seeded positive cases in
  `.semgrep/familyfund.test.php`. A WebV1 missing-`authorize` heuristic was
  prototyped but deferred — its current baseline is 53 candidate findings
  (real IDOR risk mixed with global-resource actions where per-object authz
  doesn't apply) and needs per-controller triage before it can be a quiet
  gate.
- **Consequences:** Adds two CI checks worth of coverage with no extra
  scanner; the SARIF artifact still covers everything (Semgrep merges
  configs into one report). The CI step keeps its existing `continue-on-error`
  posture inherited from the registry-pack flow; a new finding fails the
  step but doesn't block the workflow until the gate is promoted. Rules are
  pinned to FamilyFund-specific risk surfaces — generic patterns belong in
  the registry packs, not here.
- **Source:** #17 (ticket + rationale); `.semgrep/README.md`;
  `.semgrep/familyfund.yml`; `.semgrep/familyfund.test.php`;
  `app1/family-fund-app/bin/security-scan.sh` (`run_semgrep_scan`);
  `.github/workflows/security-scan.yml` (`Semgrep` job).
