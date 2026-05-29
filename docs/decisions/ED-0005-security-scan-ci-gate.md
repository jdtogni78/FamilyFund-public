# ED-0005 — Security Scan CI as the gate; CodeQL dropped


- **Date:** 2026-05-24 · **Status:** Accepted
- **Context:** We wanted automated security gating on PRs/push, but the repo is a
  private personal-account repo without GitHub Advanced Security, and CodeQL has
  no PHP support there.
- **Decision:** Run a **Security Scan** workflow as the gate: composer audit, npm
  audit (with documented `.npm-audit-ignore`), gitleaks (working-tree), semgrep
  (`p/php` + `p/owasp-top-ten` + `p/security-audit`), trivy config; plus scheduled
  trivy fs/image, OSV, SBOM, and ZAP. **Drop CodeQL.** Because SARIF upload to the
  Security tab needs GHAS, publish SARIF as **downloadable artifacts** and gate via
  explicit "fail on findings" steps. Mirror the exact gate locally in
  `bin/security-scan.sh`. Restore SARIF upload + `security-events:write` when the
  repo goes public (board #1).
- **Consequences:** Findings are reviewed via artifacts, not the Security tab, until
  the repo is public. Local and CI gates must be kept in sync.
- **Source:** `.github/workflows/security-scan.yml`,
  `app1/family-fund-app/bin/security-scan.sh`, `.gitleaks.toml`.
