# ED-0014 — Test baseline is a synthetic seeder, not a committed data dump


- **Date:** 2026-05 · **Status:** Accepted
- **Context:** The suite uses `DatabaseTransactions`, so a baseline dataset must
  be pre-loaded (not just an empty migrate). That baseline was
  `database/test/test-baseline.sql.gz` — a real dev-DB dump carrying ~62 emails
  incl. real Gmail/Hotmail PII + real financials (SECURITY-EXPOSURE §2a). Both CI
  (`tests.yml`) and `testpool.sh` restored it on every run.
- **Decision:** Replace the dump with `Database\Seeders\TestBaselineSeeder` — a
  deterministic, **synthetic** seeder built from the existing factories +
  `Tests\DataFactory`. CI and `testpool.sh` now build the DB with
  `migrate:fresh --seed --seeder=…TestBaselineSeeder`; the dump is removed from
  HEAD. ~93% of tests self-seed via `DataFactory` inside the transaction; the
  seeder only has to satisfy the few fixtures that read pre-existing rows.
- **Consequences:**
  - The seeder reproduces the *load-bearing shape*, not the old data: roles/perms,
    CASH/SPY reference assets **with a wide/dense price history** (a fund's
    NAV/share-price 500s without CASH price; the scheduled-report data check does a
    global `AssetPrice::whereBetween('start_dt',…)` so SPY gets an every-3-days
    series), `user1@dev.familyfund.local` (id 1), the first fund, **account 7**
    (Dusk hard-codes it) with a dated 2021–2022 transaction history, and one of
    every entity the AclMatrix `{param}` resolvers read.
  - The dev-funds fixture migration (`restore_dev_funds.sql`, real dev data) is
    skipped during the build via `FF_SYNTHETIC_BASELINE=1`.
  - The ACL golden (`tests/golden/acl_matrix.json`) was **regenerated** against the
    synthetic baseline and diff-reviewed: no low-privilege escalation; changed
    cells are data-driven (entity now resolves at `fund->id`). Two manager-only
    nightly routes (`accounts/{id}/pdf_as_of`, `transactions/preview_pending`) now
    record `500` — sparse-data artifacts of AclMatrix using `fund->id` as a
    cross-entity id, not auth regressions.
  - One golden-data test (`AccountApiGoldenDataTest::test_account_transactions_as_of`)
    was decoupled from the old dump's hard-coded transaction ids (now derives the
    expectation from account 7's actual synthetic history).
  - Editing `testpool.sh` (in the **ai-harness** repo) is the companion change;
    branches that still ship the dump keep using the restore path.
- **Source:** #78; SECRET-ROTATION-PLAN §5.
