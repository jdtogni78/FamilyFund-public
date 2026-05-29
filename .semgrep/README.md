# FamilyFund Semgrep ruleset

Repo-local Semgrep rules that complement the registry packs
(`p/php`, `p/owasp-top-ten`, `p/security-audit`) already wired into
`bin/security-scan.sh` and the `Semgrep` job in
`.github/workflows/security-scan.yml`.

These rules target **FamilyFund's own risk surfaces** that the generic packs
miss. They are intentionally **high-precision** (zero findings on the current
clean tree) and run **alongside** the registry packs in both local and CI
scans — there's no separate flag to opt in.

Issue: [#17][i17]. ADR: [ED-0019][ed19] in `docs/ENGINEERING_DECISIONS.md`.

[i17]: https://github.com/jdtogni78/FamilyFund/issues/17
[ed19]: ../docs/ENGINEERING_DECISIONS.md

## Rules

| ID | Severity | Catches |
|----|----------|---------|
| `ff-dev-login-route-outside-env-guard` | ERROR | `/dev-login` route registered outside an `app()->environment(local|dev|testing)` guard — would expose impersonation in prod. |
| `ff-dev-token-call-outside-allowed-context` | ERROR | `Artisan::call('security:dev-token', …)` from anywhere other than `app/Console/`, `database/seeders/`, `tests/`, or `bin/` — a request-flow path to mint admin tokens. |
| `ff-raw-sql-interpolates-request-input` | ERROR | `whereRaw` / `DB::raw` / `DB::statement` / `orderByRaw` that string-concatenate `$request->...` or `$_GET[…]` instead of using the bindings array. |
| `ff-unsafe-file-read-from-request` | ERROR | `file_get_contents` / `fopen` / `readfile` / `Storage::get` / `include` / `require` whose path comes directly from request input. |

## Running

Local (alongside the registry packs):

```bash
cd app1/family-fund-app
bin/security-scan.sh         # full scan; semgrep step now includes the FF rules
```

Local (rules-only, fastest iteration loop):

```bash
semgrep scan --config .semgrep/familyfund.yml app1/family-fund-app
```

Verify the seeded positive cases (the rules' authoritative spec):

```bash
semgrep scan --config .semgrep/familyfund.yml .semgrep/familyfund.test.php
# expect 15 findings, all in familyfund.test.php
```

CI: see the `Semgrep` job in `.github/workflows/security-scan.yml` — the rules
file is appended to the `semgrep scan` invocation; findings surface in the
`semgrep-sarif` artifact and the step gate.

## Adding / tuning a rule

1. Add the rule to `familyfund.yml`. Prefer narrow patterns + paths.include /
   paths.exclude over broad heuristics — see ED-0019 for the precision bar.
2. Add positive AND negative cases to `familyfund.test.php`. Each positive
   match is annotated with `// ruleid: <rule-id>` on the preceding line.
   Unmarked code MUST NOT match (false-positive guard).
3. Run both checks:
   ```bash
   semgrep scan --config .semgrep/familyfund.yml .semgrep/familyfund.test.php  # positives must fire
   semgrep scan --config .semgrep/familyfund.yml app1/family-fund-app          # must be zero
   ```
4. If the live scan is not zero, tighten the rule before committing.

## Deferred / planned

- **`ff-webv1-by-id-action-without-authorize`** — a heuristic flagging
  WebV1 controller actions (`show`/`edit`/`update`/`destroy`) that load a
  record by id but never call `$this->authorize(...)`. Drafted, but its
  current-tree baseline is 53 candidate findings — a mix of real IDOR risk
  and global-resource actions (asset prices, scheduled jobs, exchange
  holidays) where per-object authz doesn't apply. Each finding needs
  per-controller triage (real risk vs. route-middleware-gated vs.
  global-resource) before the rule can be a quiet gate. Tracked as a
  follow-up so this PR keeps the "zero findings on the current tree"
  precision bar intact.
