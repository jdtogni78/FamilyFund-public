# Contributing

Thanks for working on FamilyFund. The big-picture conventions — tech stack,
commands, architecture, testing layout, security posture — live in
[AGENTS.md](AGENTS.md), the canonical guide for both AI agents and human
contributors. Read that first.

This file is the short, process-focused companion: how to land a change.

## Workflow

1. Pick (or file) a ticket on the **Trading & Fund** GitHub Project board
   ([#1](https://github.com/users/jdtogni78/projects/1)) — the source of truth
   for FamilyFund work (see [docs/GITHUB_PROJECTS.md](docs/GITHUB_PROJECTS.md)).
2. Branch from `main`. Worktrees are encouraged so the canonical checkout
   stays on `main` for parallel work.
3. Make the change. Run the relevant tests (`bin/test.sh`, or
   `~/.familyfund-pool/testpool.sh` for `RefreshDatabase` / coverage / Dusk —
   see AGENTS.md "Testing").
4. Open a PR against `main`. The PR template
   ([`.github/PULL_REQUEST_TEMPLATE.md`](.github/PULL_REQUEST_TEMPLATE.md))
   prompts for a summary, test plan, and a memory-bank-sync check.
5. Pre-commit hooks (gitleaks, etc.) and the Security Scan CI gate
   ([ED-0005](docs/decisions/)) must be green before merge.

## When to update the memory bank

The **memory bank** is `AGENTS.md` + everything under `docs/` (architecture,
ADRs in [docs/DECISIONS.md](docs/DECISIONS.md), runbooks, security notes,
subsystem guides like [docs/MONEY_SUBSYSTEM.md](docs/MONEY_SUBSYSTEM.md)). It
is the shared picture of the system that AI agents and humans both read
before starting work. **A code change that invalidates that picture must
update the relevant doc in the same PR** — otherwise the next contributor
(human or AI) plans against a lie. Concretely, update the memory bank when
you:

- change architecture, conventions, or directory layout that AGENTS.md
  describes;
- add, remove, or rename a CLI/script, env var, compose service, or build
  step listed in AGENTS.md;
- make a non-obvious decision worth recording — add a new
  `ED-NNNN` under [`docs/decisions/`](docs/decisions/) and index it in
  [docs/DECISIONS.md](docs/DECISIONS.md) (don't supersede an existing ADR
  in place);
- alter ACL, security, or operational behavior described in
  [docs/ACL_IMPLEMENTATION.md](docs/ACL_IMPLEMENTATION.md),
  [docs/SECURITY.md](docs/SECURITY.md), or any
  [docs/runbooks/](docs/runbooks/) entry;
- ship a subsystem that does not yet have a memory-bank index page (file a
  follow-up ticket if it's larger than this PR).

Pure refactors, test-only fixes, and dependency bumps usually need **no**
doc change — just say so in the PR's memory-bank-sync line. If you are
unsure, default to updating: a short note now is cheaper than future
contributors re-learning from the code.

## Tips

- Keep memory-bank docs concise and fact-rich (1-3 pages each). Link to
  code with `path:line` refs where useful, and link related ADRs by
  number (e.g. `[ED-0005](docs/decisions/)`).
- For non-obvious decisions, the ADR is the durable record; PR
  descriptions and commit messages should not be the only home for the
  rationale.
- Don't commit unencrypted secrets. The pre-commit hook is a backstop,
  not a primary control (see [ED-0001](docs/decisions/),
  [ED-0002](docs/decisions/)).
