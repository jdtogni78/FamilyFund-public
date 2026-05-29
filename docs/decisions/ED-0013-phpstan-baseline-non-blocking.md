# ED-0013 — Larastan/PHPStan static analysis behind a baseline, non-blocking


- **Date:** 2026-05-24 · **Status:** Accepted · **Builds on:** ED-0005
- **Context:** SECURITY_NEXT_STEPS Phase 4 / issue #77 wanted static analysis as a
  quality/security-adjacent check. A cold run of `larastan` (Laravel-aware
  PHPStan) over `app/` reports ~1,500 findings — mostly the base
  `Illuminate\…\Model` type degrading on dynamic Eloquent attributes/relations
  used by the `*Ext` models (ED-0008), plus some real dynamic-property smells.
  Levels 4 and 5 differ by only ~58 findings, so the level barely moves the count.
- **Decision:** Add `larastan/larastan` + `phpstan/phpstan` (dev) and adopt
  **level 5** (the issue's upper bound — stricter on *new* code) over `app/`.
  Freeze the existing findings in **`phpstan-baseline.neon`** so the gate is green
  from day one and fails only on NEW regressions — an exact, per-occurrence
  baseline beats broad `ignoreErrors` that would mask real bugs. Run it both
  locally (`bin/security-scan.sh`) and in CI, but keep the CI job
  **non-blocking** (`continue-on-error: true`, NOT a required check) until the
  signal/noise is understood. Do **not** also add Psalm as a second required gate.
- **Consequences:** `phpstan-baseline.neon` is large (~6.3k lines); shrinking it
  (and then raising the level / widening `paths`) is the ongoing goal — don't add
  to it casually. The committed `phpstan.neon` uses default parallelism (CI has
  pcntl + RAM); the local `bin/security-scan.sh` Docker fallback runs the stock
  `php:8.4-cli` image **single-process** (that image lacks `pcntl`, so parallel
  workers crash, and small Docker VMs OOM with N workers). Promote to a required
  check — and drop `continue-on-error` — once trusted.
- **Source:** #77; `app1/family-fund-app/phpstan.neon`, `phpstan-baseline.neon`,
  `bin/security-scan.sh` (`run_phpstan`), `.github/workflows/security-scan.yml`
  (`phpstan` job).
