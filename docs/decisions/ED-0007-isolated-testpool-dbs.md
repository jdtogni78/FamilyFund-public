# ED-0007 — Isolated `testpool` DBs, separate from the shared dev DB


- **Date:** 2026-05-24 · **Status:** Accepted
- **Context:** The default suite runs against the shared `familyfund_dev` DB; any
  `RefreshDatabase`/coverage run would clobber other sessions using dev.
- **Decision:** Provide isolated test slots (`~/.familyfund-pool/testpool.sh`,
  `test0..test2`) each with a slot-private `familyfund_testN` DB restored from a
  committed baseline. Use these (not the preview `pool.sh`, which shares dev) for
  any destructive/coverage/parallel test run.
- **Consequences:** A fresh worktree needs its own `.env` for the slot to mount;
  test runs must go through a leased slot, not `docker exec` on the dev container.
- **Source:** CLAUDE.md (Testing → testpool), `~/.familyfund-pool/testpool.sh`.
