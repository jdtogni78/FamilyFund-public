<!--
Concise PR template. See CONTRIBUTING.md and AGENTS.md for the
memory-bank conventions referenced below.
-->

## Summary

<!-- 1-3 bullets: what changed and why. -->

## Test plan

<!--
What you actually ran (commands, tests, manual steps). For UI changes,
include the browser-verified steps or attach screenshots. See AGENTS.md
"Testing" for the testpool / Dusk options.
-->

- [ ] `bin/test.sh` (or relevant filtered subset) passes locally

## Memory-bank doc sync

The repo treats `AGENTS.md` + `docs/` as the **memory bank** (single shared
picture of the system for AI agents and humans). When code/process
changes invalidate that picture, update the docs in the **same PR**.

- [ ] Did you update memory-bank docs? (or: no docs change needed —
      explain in one line below)

<!--
If yes, list the files touched. If no, briefly say why (e.g. "pure
refactor, no architectural change", "test-only fix", "behavior covered
by existing docs"). See CONTRIBUTING.md "When to update the memory
bank" for the criteria.
-->

## Related

<!-- Issue refs (Closes #N / Refs #N), ADR refs (ED-NNNN), linked PRs. -->
