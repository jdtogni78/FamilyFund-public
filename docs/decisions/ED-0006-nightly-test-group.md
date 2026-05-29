# ED-0006 — Default test run excludes the `nightly` group


- **Date:** 2026-05-24 · **Status:** Accepted
- **Context:** The full suite was ~17 min; a handful of classes dominated.
- **Decision:** Tag the slowest tests `@group nightly` and exclude them from the
  default run via `phpunit.xml` (~6 min). `AclMatrixTest` keeps a fast
  critical-routes subset that runs every time; the exhaustive sweep is nightly.
  Run the slow group with `bin/test-nightly.sh`.
- **Consequences:** The exhaustive ACL sweep only runs nightly/on demand —
  security-sensitive routes are still covered every run by the critical subset.
- **Source:** `phpunit.xml` (`8b82fc8e`), `tests/Feature/AclMatrixTest.php`.
