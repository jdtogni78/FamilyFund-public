# Parallel-run acceptance checklist — stage→prod cutover gate

> Scope: the **parallel-run window** between prod and stage, per Step 4 of the
> stage-mirror plan in #36. This document is the **sign-off gate** the owner
> ticks **before** the cutover runbook (#50) is allowed to run. The mechanics
> of swapping traffic live in that runbook (private repo); this doc is the
> rule-set for declaring stage "100% working".

Issue: [#49](https://github.com/jdtogni78/FamilyFund/issues/49) · Related: #36
(epic), #47 (DB restore tooling), #50 (cutover runbook), #51 (decom-prod).

---

## 1. Window — N days + calendar

- **N = 7 calendar days** of parallel use with **both envs writable**.
  - Rationale: covers one full weekly cycle (quarterly-report job lives on a
    weekly cron; weekend traffic is qualitatively different from weekday).
    Shorter than 7 misses the weekend; longer than ~10 amplifies the
    divergence-reconciliation cost without adding new failure modes.
- **No-merge-freeze inside the window.** Code changes targeting `main` may
  land, but anything that touches **schema, money math, share-pricing, or
  the API surface dstrader consumes** is held until after cutover (or the
  window restarts — see §6).
- **Window restarts** if any §3 daily check fails, any §5 P0/P1 bug is filed
  during the window, or a schema-touching change ships. The day-counter
  resets to 0; this is intentional and the owner does not negotiate it.
- **Calendar window** is filled in at sign-off (not pre-committed here):
  `start = YYYY-MM-DD`, `end = start + 7 days`.

## 2. User cohorts — who hits stage vs prod

Both envs are writable for the duration. Cohort routing is **manual / opt-in**,
not load-balanced — there is no traffic splitter.

| Cohort | Stage access | Prod access | Notes |
|---|---|---|---|
| Owner (`ADMIN_EMAILS[0]`) | full read+write | full read+write | dual-use; primary divergence detector |
| QA users (`qa-*@test.local`) | full read+write on stage | n/a | seeded only on stage; never touch prod |
| dstrader service user | **read-only on stage** | full read+write on prod | reference-data writes (ED-0016) stay on prod during the window; stage gets a `--dry-run` mirror of the same writes for diffing |
| Real beneficiaries | n/a | full as today | **not invited to stage** during the window — the goal is a fidelity test, not a UAT |

Stage is gated by **basic auth at the edge** + the existing app login; the URL
is never DNS-published. Cohort sign-up is one-by-one (owner provisions on
stage), not self-service.

## 3. Daily checks (run once per calendar day on stage)

Each check is either **green** (counts toward the day) or **red** (resets the
window — §1). Run from a leased preview slot pointed at the stage host, or
from the stage container directly when checking jobs/queues.

- [ ] **App boots.** `GET /` returns 200; `GET /up` (Laravel health) returns
      200; no `MissingAppKeyException` in logs.
- [ ] **Login matrix.** Each role (admin, system-admin, fund-admin,
      financial-manager, beneficiary) can log in via the seeded user and lands
      on its expected dashboard.
- [ ] **ACL matrix delta = 0.** `bin/test.sh --filter=AclMatrixTest` against
      stage's URL diffs to the committed golden (`tests/golden/acl_matrix.json`).
      A non-zero delta is a red day.
- [ ] **Critical money-math tests.** The default suite (excludes `@group
      nightly`) is green against an isolated `testpool` slot pointed at a
      same-day snapshot of stage's DB. ~1791 passing today (ED-0006 / 2026-05-14).
- [ ] **Share-price freshness.** `php artisan prices:check-staleness` (the
      #39 alert) on stage emits zero alerts that prod does not also emit on
      the same trading day. Stage-only alerts mean the dstrader → stage
      ingestion mirror is drifting and is a red day.
- [ ] **NAV parity.** For each fund, `NAV(stage, as_of=yesterday) ==
      NAV(prod, as_of=yesterday)` within ±$0.01 absolute or ±0.001%
      relative, whichever is larger. Drift above that threshold is a red
      day (logs a §5 P1 by default).
- [ ] **Account balance parity.** Same tolerance as NAV, applied per
      account; spot-check covers (a) every account with activity in the
      last 24h, plus (b) a 5% random sample of inactive accounts.
- [ ] **Queue/cron has run.** `schedule:run` fired on stage in the last
      24h; `failed_jobs` table is empty (or only carries entries that prod
      also carries — those are not stage-introduced).
- [ ] **Mail goes nowhere unintended.** Stage `MAIL_*` config points at
      Mailpit (or `log` driver); zero real outbound emails leave stage's
      network during the window. Verified by inspecting Mailpit queue +
      checking the outbound SMTP firewall log.
- [ ] **No prod→stage data-leak alarms.** The PII scrub (#79) is rerun
      against stage's DB; zero hits on `prod_to_dev.sql`'s deny-list.

## 4. Per-session checks (run for each meaningful interactive session)

These are tactile — the owner / QA user runs them while exercising stage,
not on a cron. Failing one is **not** automatically a red day; it's logged
into the §5 bug-bar and triaged.

- [ ] Create a deposit → verify balance + share count update + an email
      lands in Mailpit.
- [ ] Create a withdrawal → verify balance + share count update.
- [ ] Open a fund's overview as a beneficiary — confirm beneficiary-scoped
      filtering (the ACL hardening / #85 surface) still hides other funds.
- [ ] Generate a quarterly report PDF — verify wkhtmltopdf produces output
      and the queue job completes.
- [ ] Hit one read endpoint from the dstrader service token
      (`exchange_holidays` or `asset_prices/gaps`) — confirm the
      reference-data API still answers under the new env.
- [ ] Trigger a self-service password reset (#35) → verify rate-limit + the
      email lands in Mailpit and the link works.

## 5. Bug-bar — which severities block, which ship as known-issues

Triaged by the owner during the window. Severity is **about the bug as it
would behave on prod after cutover**, not about whether QA caught it on stage.

| Sev | Description | Blocks cutover? | Window action |
|---|---|---|---|
| **P0** | Data-loss, silent money-math error, auth bypass, PII leak. | **Yes — hard block.** | Cutover postponed indefinitely. Window restarts after fix lands AND a §3 check rerun is green. |
| **P1** | NAV/balance drift > §3 tolerance; queue/cron silently failing; ACL matrix delta; any prod feature that throws 5xx on stage. | **Yes.** | Fix on `main`, redeploy stage, restart window day-counter (§1). |
| **P2** | Cosmetic/UX regression, slow page (>2× prod), report formatting glitch, mail subject typo. | No. | Logged as a known-issue in §7. Cutover allowed; fix lands post-cutover. |
| **P3** | Stage-only artifact (test-seeder noise, dev-only banner, debug toolbar). | No — not a real bug. | Closed without ticket. |

A bug filed during the window must carry a severity in its title or label,
or the owner assigns one within 24h.

## 6. Divergence handling — both DBs accept writes

Both stage and prod accept writes for N days. At cutover, only one DB
becomes authoritative. The reconciliation rule is:

> **Prod wins for everything except stage-only-test artifacts.**

Concretely, at cutover-time (mechanics in #50):

1. Take a fresh prod backup (#47 tooling). This becomes the post-cutover
   authoritative state.
2. **Discard** stage's parallel writes wholesale, **except**: stage-resident
   QA users (`qa-*@test.local`, `claude@test.local`) and their associated
   accounts/transactions, which are seeded artifacts and are not expected
   to exist on prod.
3. Stage's writes are not "merged back" — the parallel-run is a
   **fidelity test**, not a database fork. Any write a real user makes on
   stage during the window is, by design, lost at cutover.
4. **Cohort §2 enforces this**: real beneficiaries do not touch stage, so
   no real-user writes are lost. The owner is the only human writing to
   stage with credentials that could otherwise survive on prod, and the
   owner is responsible for re-doing those writes on prod (or accepting
   them as lost) at cutover.
5. dstrader writes during the window go to prod only (cohort §2). Stage
   ingests via the `--dry-run` mirror in §3, so stage's reference-data
   tables fall behind prod's during the window — that is expected. At
   cutover, stage is reseeded from the prod backup of Step 1, which closes
   the gap automatically.

Edge case — **schema divergence**: if a migration that hasn't shipped to
prod is on stage's `main`, the cutover runbook (#50) is responsible for
running it during the swap. The acceptance gate's only requirement is
that **the migration ran clean against a same-shape prod-data restore**
inside the window (§3 testpool slot run covers this).

## 7. Known issues at sign-off

Filled in at the end of the window. Format: `- [SEV] #issue — one-line
description.`

Empty until the window runs.

## 8. Sign-off

Owner ticks each line below to authorize the cutover runbook (#50) to run.

- [ ] §1 window completed — 7 consecutive green calendar days, no restarts
      within the closing window. Start/end dates recorded above.
- [ ] §2 cohorts respected — no real beneficiary touched stage; no
      stage-only secret leaked to prod.
- [ ] §3 daily checks — all green on every day of the window.
- [ ] §4 per-session checks — exercised at least 3× during the window by
      the owner, ≥1× by a QA-user cohort member.
- [ ] §5 bug-bar — zero open P0/P1; §7 known-issues list reviewed and
      acknowledged.
- [ ] §6 divergence rule understood — owner accepts that stage-resident
      parallel writes (other than seeded QA artifacts) are discarded at
      cutover.
- [ ] Cutover runbook (#50) reviewed end-to-end within the last 7 days.

Once all boxes are ticked, the cutover may be scheduled.
