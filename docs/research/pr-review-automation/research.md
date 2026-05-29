# PR-review automation — options analysis

Status: **research / reference** — no commitment to ship.
Audience: future-me, deciding whether to bolt a PR-review bot onto this repo.

This document inventories the options for automating PR review on FamilyFund,
the constraints that matter *for this repo*, and the decision criteria. The
vendor comparison + concrete recommendation lives in
[`recommendation.md`](recommendation.md) — read that for the "what should I
actually do" answer.

## 1. Why this question exists

FamilyFund is a solo-maintainer Laravel 11 app with an unusual operating model:

- Almost all PRs are opened by **agentic Claude Code sessions** (see CLAUDE.md
  and the `start-work` / `close-work` skill flow) — they claim a board
  ticket, work in a worktree, push a branch, open a PR, and self-merge when CI
  is green.
- The human reviewer (one person) is a bottleneck on landings; deep review
  bandwidth is scarce.
- Existing CI already enforces correctness on the **machine-checkable** axes:
  PHPUnit (~1791 tests), AclMatrixTest golden file, PHPStan/Larastan level 5
  (baseline-gated, non-blocking — ED-0013), Composer audit, ZAP authenticated
  scans on the `Security Scan` workflow (#12/#14/#44/#74).
- The repo is **going public** in tiers. Once it's public, third-party
  PR-review bots become cheaper (free GitHub-Marketplace tiers often only
  apply to public repos) and the reputational cost of a sloppy merge rises.

So the question isn't "should we automate testing" — that's done — but
"should we add an AI-driven *reviewer* in front of the human, to catch the
stuff PHPUnit + PHPStan + ZAP do not catch."

What machine-checkable CI does **not** catch:

- **Logic errors that pass typed tests** — wrong math, off-by-one, wrong
  account scoped, wrong tier in role gating. AclMatrix catches *route*
  authorization but not *row-scoping* in a controller body.
- **Drift from project conventions** — e.g. controllers that don't use the
  repository pattern, Volt components that bypass `AuthorizesApiAccess`,
  Blade templates that hardcode `text-white` instead of the dark-mode-aware
  card-header classes.
- **PII / secret hygiene** — golden-data dumps, env values, `dd($user)` left
  in a debug session. The repo has a long history of these (history-purge
  #66, PII-scrub #79).
- **Over-broad changes** — agents occasionally rewrite adjacent code on a
  bugfix ticket; a reviewer would push back, CI does not.
- **Missing tests** — CI only fails if the test exists. New routes shipped
  without a feature test slip through unless AclMatrixTest hits them.

## 2. Constraints that shape the option set

1. **Cost.** Solo project; budget for an external SaaS reviewer is ~$0–$20/mo.
   Pricing models that scale per-seat or per-author are fine (one seat);
   pricing per-PR or per-token is risky once agent sessions start opening
   PRs at the cadence they do (multiple per day).
2. **Privacy of code.** The repo is mid-transition from private to public.
   While private, source-code-leaving-the-org is a concern for any SaaS that
   trains on customer data. Public-repo tiers usually opt out of training
   contractually, so the calculus shifts at public-cutover.
3. **Self-host preference.** The maintainer already self-hosts dstrader,
   familyfund, mailpit, quickchart. Adding one more docker service is cheap;
   adding one more SaaS subscription has a recurring cognitive cost.
4. **Integration surface.** GitHub-native is mandatory (the project board,
   issues, PRs, and Actions are all on GitHub). GitLab/Bitbucket integrations
   are dead weight here.
5. **Agent workflow compatibility.** A reviewer that demands human-in-the-loop
   blocking decisions (e.g. "PR cannot merge until human approves bot
   comment") defeats the auto-merge flow. The reviewer must be *advisory* by
   default, with a clearly-bounded "blocking" mode for high-risk surface
   (auth, money, prod migrations).
6. **Signal/noise tolerance is low.** Past experiments with PHPStan at level 5
   (#77) showed the maintainer treats CI noise as a blocker — a tool that
   posts 30 comments on a 5-line PR will be uninstalled inside a week.
7. **Laravel-fluency required.** Generic "lint your JavaScript" bots are
   useless. The reviewer must understand Eloquent, Blade, Volt, Livewire,
   queue jobs, policies, and Laravel idioms well enough not to flag them as
   "unusual."

## 3. The option taxonomy

There are four broad shapes a PR-review automation can take. They're not
mutually exclusive — most real setups stack 2–3.

### 3.1 Linter-style bots (deterministic)

Examples: `reviewdog`, `danger.systems`, `github/super-linter`, repo-specific
PHPStan-on-diff workflows.

- **What they do.** Run a deterministic checker (linter, static analyzer,
  custom rule script) over the diff and post findings as inline PR comments.
- **Strengths.** Zero hallucination, deterministic, free, no data leaves the
  repo. The PHPStan/Larastan job (ED-0013) is already 80% of this category.
- **Weaknesses.** Cannot reason about intent. Cannot say "this function looks
  like it shadows the existing `AuthorizesApiAccess` trait." Cannot infer
  missing tests.
- **Fit.** Always-on baseline. Already partly in place — see `tests.yml` and
  `security-scan.yml`. Marginal improvement available from adding
  `reviewdog`-style inline-comment posting for the existing PHPStan job (it
  currently logs to step output only).

### 3.2 Rule-based PR-policy bots

Examples: `danger.systems` with custom Dangerfile, GitHub's CODEOWNERS +
required-reviews, `release-drafter`, custom GH Actions that grep the diff for
forbidden patterns.

- **What they do.** Enforce *policy* invariants on the PR itself: title
  format, mandatory `#NN` ticket reference, no `.sql` files, no `dd()` /
  `console.log()` / `Log::debug()` in the diff, no `--no-verify` evidence,
  changelog entry present, etc.
- **Strengths.** Cheap, deterministic, easy to extend. The recent commit
  style (`feat(#NN):`, `security(#NN):`, `ops(#NN):`) is already a policy
  worth enforcing.
- **Weaknesses.** Policy bots only catch what you remember to ask them about.
  Every miss requires a new rule.
- **Fit.** Strong fit — there are several existing conventions in this repo
  that nothing currently enforces (commit-message format, ED-NNNN reference
  on architectural changes, PII-file globs already blocked at the
  pre-commit hook level but not at PR level on a forked clone).

### 3.3 LLM-as-reviewer SaaS

Examples: CodeRabbit, Codium PR-Agent (hosted), Greptile, Bito Wingman,
Sourcery, GitHub Copilot for Pull Requests.

- **What they do.** When a PR opens (or on `@bot review`), an LLM reads the
  diff (and often the surrounding repo) and posts a summary + inline review
  comments. Some auto-suggest fixes as commits.
- **Strengths.** Closest thing to a human reviewer. Catches logic errors,
  convention drift, missing tests, hallucinated APIs, off-by-ones. Some have
  Laravel-specific training (CodeRabbit, Greptile both advertise this).
- **Weaknesses.**
  - **Signal/noise.** All current vendors post too many low-value
    comments by default. Configuration tuning is non-optional and ongoing.
  - **Privacy.** Source code is sent to the vendor's backend, often to a
    third-party model API behind that. Private-repo trial often requires
    explicit DPA acceptance.
  - **Cost.** Per-seat free tiers usually cap at 1–5 PRs/day; agent
    workflows blow through that.
  - **Verifiability.** Comments are not deterministic — a stale review can
    contradict itself across runs.
- **Fit.** Probably the right primary tool *once the repo is public* (most
  vendors are free for public repos). Risky on a private fork.

### 3.4 Self-hosted LLM-reviewer

Examples: Codium PR-Agent in self-hosted mode (against OpenAI / Anthropic /
local), `coderabbitai/ai-pr-reviewer` GH Action, custom GH Action that calls
the Anthropic API via an `ANTHROPIC_API_KEY` secret.

- **What they do.** Same as 3.3, but the runner is a GitHub Action you own,
  calling the model API directly. The vendor middleware is replaced by ~200
  lines of YAML/Python.
- **Strengths.** No third-party data processor. Code only leaves the repo
  by going to the model vendor directly (Anthropic, OpenAI), under the
  existing API ToS. Full control over which model, which prompt, which
  files get sent. Cost = raw token cost, no SaaS margin.
- **Weaknesses.** Maintenance burden — prompt tuning, token-window
  management, choosing what to send for large PRs. No vendor support.
- **Fit.** Strong fit *for this repo specifically* — the maintainer already
  uses Claude Code daily and is comfortable with prompt engineering. The
  marginal cost over "writing a Dangerfile" is small. Codium PR-Agent has
  a one-line GH Action that does most of this out of the box.

## 4. The criteria grid

When comparing concrete vendors and configurations (in `recommendation.md`),
the criteria the maintainer cares about, in priority order:

| # | Criterion | Why it matters here |
|---|-----------|---------------------|
| 1 | **Laravel fluency** | The repo is 95% PHP/Blade. A Java-trained reviewer adds noise. |
| 2 | **Cost ceiling under agent-volume PR load** | Agents open multiple PRs/day; per-PR pricing is a footgun. |
| 3 | **Signal/noise control** | Configurable comment thresholds, ability to silence whole categories. |
| 4 | **Self-merge compatibility** | Reviewer must be advisory by default; required-check mode only on high-risk paths. |
| 5 | **Privacy posture** | While the repo is private, no-training contractual commitments matter. After the public flip, this drops in importance. |
| 6 | **Operability** | One-line install? Or do I have to host a service, rotate keys, maintain a YAML?  |
| 7 | **Inline-suggestion fix-it commits** | Nice-to-have: a "click apply" suggested change reduces my latency. |
| 8 | **Repo-context awareness** | Bots that only see the diff miss "this function already exists." Bots that index the whole repo cost more but catch more. |

## 5. What the *existing* CI already does (so we don't pay twice)

| Surface | Tool | Mode | Catches |
|---------|------|------|---------|
| Unit / feature behavior | PHPUnit (1791 tests, `tests.yml`) | Required | Logic regressions inside tested surface |
| Route x role authorization | AclMatrixTest golden file | Required (critical subset) | New routes leaking past their role gate |
| Static types | PHPStan / Larastan level 5 (ED-0013) | Non-blocking, baseline-gated | New type errors above the baseline |
| Dependency CVEs | `composer audit --locked` | Required | Known-CVE packages |
| Web vulns | OWASP ZAP authenticated scan (`security-scan.yml`) | Reports artifacts | Top-10 web vulns on the running stack |
| Secrets at commit time | local pre-commit hook (sops + globs) | Local-only | Accidental .env / *.sql commits |
| Browser-end UI | Dusk via `testpool.sh tour` | Manual / nightly | Visual regressions in covered flows |

**Gap analysis** (what an LLM reviewer should focus on, because nothing else
checks it):

- Convention drift inside controllers, repositories, Blade partials.
- Missing tests (new public route, new policy method, no Feature test added).
- Wrong account/scope/tier in a hand-edited controller body — passes typed
  tests because the test calls the right scope by accident.
- Commit-message + PR-title hygiene.
- ED-NNNN reference missing on architectural changes.
- Unsalted random tokens, weak crypto, raw SQL, unsanitized output in Blade.

## 6. Operating-model decisions to settle before adopting any tool

These come up no matter which option above is picked:

1. **Advisory vs blocking.** Default to advisory on every path; blocking on
   `auth/`, `Http/Middleware/`, `database/migrations/`, `Policies/`,
   `Repositories/` for IDOR-prone surface.
2. **PR open vs `@bot review` trigger.** Auto-trigger is convenient but burns
   tokens on draft PRs. `@bot review` is cheaper but slower. Compromise:
   auto-trigger on PRs to `main`, only when `draft == false`.
3. **What to send.** Full repo context > diff-only > diff + named files.
   Full-repo costs the most; diff-only misses "this already exists."
   Recommendation: diff + the list of files imported by the diff (cheap
   middle ground).
4. **Where the review lives.** Inline comments scale poorly past ~10
   findings; prefer a single summary comment + 1–3 inline comments for
   highest-confidence issues.
5. **How to handle agent-author PRs.** The reviewer should know it's
   reviewing agent work and bias toward *checks the agent typically misses*
   (over-broad changes, premature abstraction, comments that explain WHAT
   instead of WHY) — these are tracked in `CLAUDE.md` already and would be
   useful as a prompt prefix.

## 7. Out-of-scope here

- **Test-generation bots.** Codium/Cover, GitHub Copilot, Sweep AI all
  generate tests. Separate concern from review; revisit after a reviewer
  lands.
- **Code-search / repo-Q&A bots.** Greptile, Sourcegraph Cody. Useful but
  not a review automation.
- **Bug-triage bots.** Linear/Jira-integrated triage. The board (#1) is
  small enough that this is over-engineered.
- **Release-notes bots.** `release-drafter` etc. Trivial to add later.

---

For the concrete vendor matrix + the recommendation this repo should
actually act on, see [`recommendation.md`](recommendation.md).
