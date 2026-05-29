## FamilyFund — pre-flip GitHub Actions secrets-surface audit (issue #53)

**Date:** 2026-05-28 · **Status:** EVIDENCE COLLECTED — current
secrets-surface is empty across every dimension. No workflow consumes
`${{ secrets.* }}`, no `pull_request_target:` usage. Owner sign-off still
required before flipping the repo public.

This audit complements the history audit in
[`HISTORY-AUDIT-66.md`](HISTORY-AUDIT-66.md): that one cleared the *past*
(commit history); this one clears the *present* (live secrets surface that
becomes exfil-relevant the moment the repo is public).

> Visibility check: `gh repo view jdtogni78/FamilyFund --json visibility` ->
> `"visibility":"PRIVATE"`. This is a *pre*-flip audit.

---

## 1. Secrets surface — all zero

Enumerated 2026-05-28 against `jdtogni78/FamilyFund` (post-#66 clean repo, id
`1249592859`):

| Dimension | Command | Result |
|---|---|---|
| Actions secrets | `gh api repos/jdtogni78/FamilyFund/actions/secrets` | `total_count: 0` |
| Deploy keys | `gh api repos/jdtogni78/FamilyFund/keys` | `[]` |
| Webhooks | `gh api repos/jdtogni78/FamilyFund/hooks` | `[]` |
| Actions variables | `gh api repos/jdtogni78/FamilyFund/actions/variables` | `total_count: 0` |
| Environments | `gh api repos/jdtogni78/FamilyFund/environments` | `total_count: 0` |
| Dependabot secrets | `gh api repos/jdtogni78/FamilyFund/dependabot/secrets` | `total_count: 0` |
| Codespaces secrets | `gh api repos/jdtogni78/FamilyFund/codespaces/secrets` | `total_count: 0` |
| Self-hosted runners | `gh api repos/jdtogni78/FamilyFund/actions/runners` | `total_count: 0` |

Expected: the repo was re-created from scratch as part of #66's repo-swap
(see `project_history_purge_66_done.md`). The old dirty repo (private,
archived as `FamilyFund-archive`) is the one that historically carried the
real secrets, all of which were rotated per `SECURITY-EXPOSURE.md` §3.

**Implication:** there is no live credential a public fork could exfiltrate
through Actions. The post-flip risk window the ticket warns about
(`pull_request_target`-style attacks) collapses to "consumption of compute"
rather than "credential theft".

---

## 2. Actions org/repo policy posture

`gh api repos/jdtogni78/FamilyFund/actions/permissions`:

```json
{"enabled": true, "allowed_actions": "all", "sha_pinning_required": false}
```

`gh api repos/jdtogni78/FamilyFund/actions/permissions/workflow`:

```json
{"default_workflow_permissions": "read", "can_approve_pull_request_reviews": false}
```

`gh api repos/jdtogni78/FamilyFund/actions/permissions/access`:

```json
{"access_level": "none"}
```

- **`default_workflow_permissions: read`** — the runtime `GITHUB_TOKEN`
  starts read-only; any write needs an explicit `permissions:` block in the
  workflow. None of our workflows escalate.
- **`can_approve_pull_request_reviews: false`** — Actions can't
  rubber-stamp its own PRs.
- **`access_level: none`** — this repo's workflows can't be reused from
  other repos.
- **`allowed_actions: "all"`** and **`sha_pinning_required: false`** are
  permissive defaults. Tightening these is an optional post-flip hardening
  item (§5), not a blocker — there are no secrets to steal even via a
  poisoned third-party action.

---

## 3. Workflow audit — `.github/workflows/*.yml`

Two workflow files: `security-scan.yml`, `tests.yml`. Both set
`permissions: contents: read` at the workflow level. Both run on
`pull_request:` / `push: main` / `schedule:` / `workflow_dispatch:` only.

### 3a. `pull_request_target:` — none

```
grep -nR "pull_request_target" .github/  ->  no matches
```

This is the headline finding from the ticket: `pull_request_target` is the
event that runs *workflow code from the base branch* against a fork PR's HEAD
*with secrets*. We don't use it anywhere. (And with §1, there'd be nothing to
leak even if we did.)

### 3b. No workflow consumes `${{ secrets.* }}`

```
grep -nR "secrets\." .github/  ->  no matches
```

The `${{ secrets.GITHUB_TOKEN }}` that some Actions need is supplied
implicitly with the read-only default permissions from §2.

### 3c. Non-secret env vars in workflows

`tests.yml` and `security-scan.yml` define explicitly-throwaway DB
credentials inline (`FF_DB_ROOT_PASSWORD: ci-secret`, `MARIADB_ROOT_PASSWORD:
123456`) for the ephemeral CI MariaDB container. These never touch real data
and the test runner has no outbound network reach to a real DB. Comments in
the workflows already flag them as throwaway; flagging here so a future
contributor doesn't try to "hide" them by promoting them to repo secrets
(which would defeat the inline self-documentation).

### 3d. Fork-PR exposure on `pull_request:`

On a public repo, `pull_request:` workflows run the **fork's** code with the
**fork's** privileges:

- Read-only `GITHUB_TOKEN` (GitHub-enforced for fork PRs).
- No repository secrets visible to forks (GitHub-enforced).
- The runner still executes fork-supplied code — typical abuse vectors are
  cryptomining, resource exhaustion, or outbound attacks. Mitigations:
  - **GitHub setting (post-flip, owner action):** Settings -> Actions ->
    General -> "Approval for first-time contributors" — set to
    *"Require approval for all outside collaborators"* or
    *"Require approval for first-time contributors who are new to GitHub"*.
    This is the standard public-repo guardrail. Listed in §4.

### 3e. Heavy / network-touching jobs are gated to schedule/manual

These jobs boot a docker compose stack or run network scans, and would be the
most expensive fork-abuse vectors:

| Job | Gate |
|---|---|
| `trivy-filesystem`, `trivy-image`, `sbom`, `osv-scanner` | `if: github.event_name == 'schedule' \|\| github.event_name == 'workflow_dispatch'` |
| `zap-baseline`, `zap-authenticated` | same |

They cannot be triggered by a fork PR.

### 3f. Jobs that DO run on every PR (including fork PRs once public)

`composer-audit`, `phpstan`, `npm-audit`, `gitleaks`, `semgrep`,
`trivy-config`, `security-route-guardrails`, `security-access-regressions`,
and `tests` (which does boot a compose stack).

Footprint is bounded by:
- the GitHub-hosted runner's quota,
- read-only `GITHUB_TOKEN`,
- no secrets,
- and the §3d approval gate once enabled.

---

## 4. Pre-flip checklist (owner sign-off)

These items the *human owner* ticks immediately before flipping the repo
public. Each maps 1:1 to a line in the ticket body.

- [x] **Actions secrets** — `gh api repos/jdtogni78/FamilyFund/actions/secrets`
      -> 0 (§1).
- [x] **Deploy keys** — `gh api repos/jdtogni78/FamilyFund/keys` -> `[]` (§1).
- [x] **Webhooks** — `gh api repos/jdtogni78/FamilyFund/hooks` -> `[]` (§1).
- [x] **`pull_request_target:` audit** — none in tree (§3a).
- [x] **Actions variables** — `gh api .../actions/variables` -> 0 (§1).
- [ ] **Owner sign-off (manual)** — owner re-runs §1's commands within ~24h
      of the flip and re-verifies all-zero. (The audit is only as good as the
      delta between "now" and "the flip moment".)
- [ ] **Owner sets fork-PR approval gate (post-flip, before first external
      PR)** — Settings -> Actions -> General -> "Require approval for outside
      collaborators (or first-time contributors)" (§3d).
- [ ] **Owner verifies `Settings -> Actions -> General -> Workflow permissions`
      reads "Read repository contents permission"** (§2 says it does, but the
      setting is owner-mutable so re-verify).

---

## 5. Optional post-flip hardening (not blockers)

Track separately if/when adopted. None is required to flip the repo:

1. **Pin third-party Actions to commit SHA** instead of tag (`@v4` ->
   `@<40-char-sha>`). Mitigates a `@v4` tag-rewrite supply-chain attack.
   Targets: `shivammathur/setup-php`, `aquasecurity/trivy-action`,
   `anchore/sbom-action`, `google/osv-scanner-action`,
   `zaproxy/action-baseline`. `actions/*` (first-party GitHub) is lower
   priority.
2. **Tighten `allowed_actions` to "selected"** with an allowlist. Useful
   once the action set is stable; redundant while §1 stays at all-zero.
3. **Enable `sha_pinning_required`** at the repo-policy level (forces #1
   above).
4. **Add a CODEOWNERS-gated review for `.github/workflows/**`** so workflow
   edits need owner approval (defense vs. a malicious PR that adds
   `pull_request_target:`).

---

## 6. Cross-repo

This ticket was filed identically on `FamilyFund`, `dstrader`, and
`dstrader-docker` (per ticket body footer). Each repo gets its own audit
file; recommendations transfer but the §1 enumerations are per-repo.

## 7. Re-run recipe (for the next pre-flip checkpoint)

```bash
# Whole §1 in one block (paste into a shell with `gh` authed):
REPO=jdtogni78/FamilyFund
for ep in actions/secrets keys hooks actions/variables environments \
          dependabot/secrets codespaces/secrets actions/runners; do
  printf '%-22s  ' "$ep"
  gh api "repos/$REPO/$ep" --jq '.total_count // length // .'
done

# §3 (workflow review):
grep -nR "pull_request_target\|secrets\." .github/  # expect: no matches
grep -nR "permissions:" .github/workflows/          # expect: contents: read
```
