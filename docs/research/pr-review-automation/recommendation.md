# PR-review automation — recommendation

Status: **reference / proposal** — not yet committed.
Companion to [`research.md`](research.md) (read that for the option
taxonomy + criteria). This doc names the concrete tools, ranks them against
the criteria from `research.md` section 4, and proposes a phased rollout.

## 1. The vendor / tool matrix

Scored against the eight criteria from `research.md` §4. Scoring is rough:
**++** strong fit, **+** acceptable, **0** neutral, **-** weak, **--** blocker.

| Tool | Laravel | Cost | Signal | Self-merge | Privacy | Ops | Suggest | Context | Notes |
|------|:-------:|:----:|:------:|:----------:|:-------:|:---:|:-------:|:-------:|-------|
| **CodeRabbit** (SaaS) | ++ | + (free for OSS, $24/user/mo private) | + tunable | + advisory by default | 0 (no-training, US) | ++ install | ++ commit suggestions | ++ indexes repo | Best off-the-shelf reviewer; cost only viable post-public-flip |
| **Codium PR-Agent** (self-hosted GH Action) | + (model-dependent) | ++ token-only | + per-prompt tunable | ++ pure GHA, easy to gate | ++ vendor-of-choice | + YAML to tune | + commit suggestions | + diff + named files | Best self-hosted; runs as a GH Action calling Anthropic/OpenAI directly |
| **Greptile** (SaaS) | + | -- $30+/user/mo, scales by repo size | + | + | 0 | ++ install | 0 review only | ++ indexes repo | Strong repo-context awareness, but priciest tier; overkill for a 1-app repo |
| **GitHub Copilot for PRs** | + | + ($10/mo, already paid for many devs) | - very chatty, limited config | + advisory | + GitHub-native, no extra processor | ++ click-to-enable | + summary + suggestions | + diff-focused | Cheapest entry if Copilot is already paid; signal is the weakness |
| **Bito Wingman** | + | + (free tier + $15/mo Pro) | 0 | + | 0 | + | 0 | + | Mid-pack on every axis; no standout |
| **Sourcery** (PHP-Laravel-aware) | ++ (claims it; verify) | + (free for OSS, $12/mo private) | + opinionated, fewer comments | + | 0 | ++ | ++ suggestions | + | Underrated; quieter than CodeRabbit, but smaller team / less momentum |
| **DeepSource Autofix** | + | + (free OSS, $8/contributor/mo) | + deterministic + LLM hybrid | + | 0 | + | ++ autofix PRs | + | More of a linter+autofixer; complements but doesn't replace 3.3-style review |
| **`danger.systems` + custom rules** | n/a (rules are yours) | ++ free | ++ you write it | ++ GHA-native | ++ no data leaves | 0 you maintain YAML | -- no suggestions | -- only what you grep | Always-on baseline; not a "reviewer" but covers PR-policy gap cheaply |
| **`reviewdog`** (post inline PHPStan findings) | + (via PHPStan) | ++ free | ++ deterministic | ++ | ++ | + | 0 | - diff-only | Drop-in improvement to the existing PHPStan job; should land regardless |

## 2. What the criteria *actually* point to

Re-reading `research.md` §4 with the matrix in front of us:

- **#1 Laravel fluency**: CodeRabbit, Sourcery, and Codium PR-Agent (with
  Claude or GPT-4-class models) all clear the bar. The DeepSource/Bito tier
  is acceptable. Generic linters do not score here at all.
- **#2 Cost under agent-volume PRs**: Token-based and per-seat models win.
  Per-PR (some Greptile tiers, Bito Pro) lose. **Codium PR-Agent
  self-hosted** is the only one that scales purely with token spend, which
  is predictable and cap-able via the API key's spend limit.
- **#3 Signal/noise**: The biggest delta between vendors. Sourcery and
  Codium PR-Agent (configurable prompts) win; CodeRabbit and Copilot are
  notoriously chatty out of the box but configurable.
- **#4 Self-merge compatibility**: All advisory by default. Only relevant
  if the maintainer wants to *promote* the reviewer to a required check on
  high-risk paths, which all of them support via branch-protection rules.
- **#5 Privacy**: Codium PR-Agent self-hosted is the only option that keeps
  data inside the maintainer's chosen vendor pipeline. Everything else adds
  a SaaS processor. **This dominates the choice while the repo is private**;
  it stops dominating once the repo is public.
- **#6 Operability**: CodeRabbit, Sourcery, Copilot, Greptile all install
  in one click. Codium PR-Agent self-hosted costs ~30 minutes of YAML +
  ongoing prompt-tuning.
- **#7 Suggested-fix commits**: CodeRabbit, Codium PR-Agent, Sourcery,
  DeepSource. Strong nice-to-have, not a tiebreaker.
- **#8 Repo-context awareness**: CodeRabbit and Greptile index the whole
  repo. Codium PR-Agent reads diff + selected files. For a 1-app repo, this
  matters less than it would in a monorepo.

## 3. The recommendation

A **phased, low-regret** rollout that doesn't commit to a SaaS subscription
before the repo's privacy posture changes.

### Phase 0 — *Tighten what's already there* (do now, no tooling change)

These don't need any new vendor and close the highest-value gaps from
`research.md` §5:

- **`reviewdog` on the existing PHPStan job.** `tests.yml` already runs
  PHPStan; pipe its output through `reviewdog -reporter=github-pr-review`
  to surface findings inline. Token: `${{ secrets.GITHUB_TOKEN }}` only —
  no third party. Implementation: ~10 lines of YAML.
- **A minimal `Dangerfile`** for the policy gaps in `research.md` §3.2:
  - Reject PRs whose title doesn't reference a `#NN` ticket.
  - Warn if the diff touches `auth/`, `Http/Middleware/`, or
    `database/migrations/` and does not add a Feature test.
  - Warn if the diff adds `dd(` / `Log::debug(` / `console.log(` strings.
  - Warn if the diff touches `docs/ENGINEERING_DECISIONS.md` without a new
    `ED-NNNN` heading.
  - Reject pushes that contain `*.sql` (the repo already disallows these
    locally; mirror it at PR-level).

Phase 0 is pure deterministic guardrail; ship-it cost is one PR and zero
ongoing SaaS spend. Even if every subsequent phase is skipped, this alone
captures a large fraction of what a chatty LLM reviewer would also flag —
without the privacy and signal/noise downsides.

### Phase 1 — *Self-hosted LLM reviewer behind the public-flip gate*

Adopt **Codium PR-Agent** as a GitHub Action, gated behind the public-flip
of the repo (epic in memory). Trigger on `pull_request` against `main` for
non-draft PRs. Use Anthropic via `ANTHROPIC_API_KEY` (already in dev
environment).

- Configure to post a single summary comment + at most 3 inline comments.
- Restrict `auto_review_max_files` to ~25 to bound token cost per PR.
- Add the prompt prefix mentioned in `research.md` §6.5: "this PR is most
  likely authored by an agentic Claude Code session; bias your review
  toward catching over-broad changes, premature abstraction, comments that
  explain WHAT instead of WHY, and missing tests for new public surface."
- Surface ED-NNNN drift, repository-pattern bypass, and missing-trait
  (`AuthorizesApiAccess`) calls as explicit categories.

Budget cap: $20/mo via a hard spend limit on the API key. If that's
exhausted, the reviewer silently stops; CI is unaffected (Phase 0
guardrails still run).

### Phase 2 — *Optional SaaS layer once public*

After the public-flip and ~6 weeks of Phase 1 telemetry, evaluate
**CodeRabbit** (free tier for public repos) as a complement — not
replacement — for Codium PR-Agent. CodeRabbit's repo-indexing catches
"this already exists" cases that the diff-only Codium PR-Agent setup
misses. If both reviewers post the same finding from different angles, the
maintainer can drop Codium PR-Agent and keep CodeRabbit free-tier.

**Do not pay for any SaaS reviewer until Phase 1 has proven the
operating model is viable** — past iterations of this stack have shown
that signal/noise tuning is the hard part, and a free Phase 1 lets us
tune the prompt without burning subscription dollars.

### Phase 3 — *Required-check promotion on high-risk paths*

Once Phase 1 (and optionally Phase 2) have run for ~2 months and the
maintainer trusts the signal on at least one category (e.g. "missing
`AuthorizesApiAccess` on a new API controller"), promote that category
to a *required* check on a CODEOWNERS-bounded path glob: `app/Http/
Controllers/API/**`, `app/Policies/**`, `app/Http/Middleware/**`. This is
the agent-self-merge-compatible path to making the reviewer actually
block bad merges instead of merely commenting.

## 4. Things we are explicitly *not* doing

- **Not adopting CodeRabbit while private.** Free tier doesn't cover
  private repos; the paid tier ($24/user/mo) blows past the budget
  ceiling without a track record.
- **Not adopting Greptile.** Best-in-class context awareness, but $30+
  per user per month and overkill for a single-app repo.
- **Not auto-merging based on bot approval alone.** The reviewer is
  advisory or required, never *both the gate and the approver*.
- **Not running the reviewer on draft PRs.** Burns tokens during the
  agent's iterative work-in-progress commits.
- **Not running on push (only on `pull_request`).** A single PR with N
  pushes should not get reviewed N times.

## 5. Decision triggers — when to revisit this doc

- **Repo goes public** (per the three-tier-public epic). Phase 1 → Phase 2.
- **A PR ships a real bug that a Phase 1 review would have caught.**
  Validates the spend; consider Phase 3 promotion for that category.
- **Phase 1 monthly cost exceeds $20.** Tighten max-files / drop to
  `@bot review`-only trigger.
- **Maintainer hires / collaborator joins.** Per-seat tooling re-enters the
  matrix; revisit Sourcery and CodeRabbit.
- **GitHub ships native first-party Copilot PR review at acceptable
  signal/noise.** Could replace Phase 1 + Phase 2 wholesale; check at
  every Copilot release.

## 6. What an "ED-NNNN" entry for this decision would look like

If/when Phase 0 ships:

> **ED-00NN: PR-policy guardrails via `reviewdog` + `Dangerfile`.**
> *Decision:* enforce ticket-reference, missing-test-for-auth-paths,
> debug-statement, ED-drift, and `*.sql` policies at PR level via a
> single GitHub Action job that combines `reviewdog` (PHPStan inline)
> and a `Dangerfile` (policy rules). *Why:* closes the deterministic gaps
> in `research.md` §5 without any third-party data processor; same
> token/data surface as existing CI. *Alternatives rejected:* CodeRabbit
> (private-repo cost), Greptile (cost), no-tool (gaps stay open).

If/when Phase 1 ships:

> **ED-00NN: Self-hosted LLM PR reviewer via Codium PR-Agent.**
> *Decision:* adopt Codium PR-Agent as a self-hosted GH Action against
> Anthropic, advisory by default, capped at $20/mo via API-key spend
> limit. *Why:* data flows through Anthropic only (no SaaS processor);
> token-based pricing scales with PR volume; the prompt is owned by
> this repo. *Alternatives rejected:* CodeRabbit / Greptile (SaaS
> processor + cost), Copilot for PRs (signal/noise + per-seat cost
> when collaborators join).

---

See also: [`research.md`](research.md) for the option taxonomy + criteria
this recommendation is built on.
