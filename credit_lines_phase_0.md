# Credit Lines — Phase 0: Foundation

**Status:** in-progress
**Started:** 2026-05-13
**Branch:** `claude/elated-pare-eac2e9`
**Plan:** [`credit_lines_plan.md`](credit_lines_plan.md)

## Goal

Lay the foundation other phases code against. No user-visible feature. After this phase:

- `AccountExt::sharesAsOf()` correctly discounts BOR shares (UC-24).
- Fund cash-flow approach decided and documented.
- All credit-line schema is in place (tables + columns on `transactions`).
- Bare Eloquent models and repositories exist with relations + casts but no business logic yet.
- Locked contracts for parallel wave 1.

## Scope

### In
- Fund cash-flow design decision doc (see §11 first risk in plan).
- Fix `AccountExt::sharesAsOf()` BOR discount + unit test (UC-24).
- Migrations:
  - `create_account_credit_lines_table`
  - `create_credit_line_payments_table`
  - `create_credit_line_adjustments_table`
  - `create_transaction_reversals_table`
  - `add_credit_line_columns_to_transactions` (`account_credit_line_id`, `credit_line_match_status`, `reversed`)
- Models (bare): `AccountCreditLine` + `AccountCreditLineExt`, `CreditLinePayment`, `CreditLineAdjustment`, `TransactionReversal`.
- Repositories for each new model.
- `TransactionExt` enum constants for `credit_line_match_status` values.
- `AccountCreditLineExt::STATUS_*` enum constants.

### Out (deferred to later phases)
- Services (open/repay/readjust/match/reverse) — wave 1.
- Controllers, routes, form requests — Phase 2 integration.
- Views, reports, emails — Phase 2 / later.
- `Transaction::saved` model event hookup — wave 1 / Phase 2.

## Tracked Use Cases
- UC-24 — `AccountExt::sharesAsOf()` discounts BOR.

(All other UCs depend on Phase 0 but are implemented in later phases.)

## Checklist

- [x] Fund cash-flow design decision recorded in [`docs/credit_lines/fund_cashflow.md`](docs/credit_lines/fund_cashflow.md).
- [x] `AccountExt::sharesAsOf()` updated to subtract BOR — [`AccountExt.php`](app1/family-fund-app/app/Models/AccountExt.php).
- [x] Unit test for `sharesAsOf` BOR discount — `test_shares_as_of_discounts_borrowed_shares`, `test_shares_as_of_with_only_borrowed_balance_is_negative` in [`AccountExtTest.php`](app1/family-fund-app/tests/Unit/AccountExtTest.php).
- [x] Migration: `create_account_credit_lines_table`.
- [x] Migration: `create_credit_line_payments_table`.
- [x] Migration: `create_credit_line_adjustments_table`.
- [x] Migration: `create_transaction_reversals_table`.
- [x] Migration: `add_credit_line_columns_to_transactions`.
- [x] Model + Ext: `AccountCreditLine` / `AccountCreditLineExt`.
- [x] Model: `CreditLinePayment`.
- [x] Model: `CreditLineAdjustment`.
- [x] Model: `TransactionReversal`.
- [x] Repositories for the four new models.
- [x] `TransactionExt` constants for `credit_line_match_status`.
- [x] `AccountExt::creditLines()` relation added.
- [x] `Transaction::accountCreditLine()` and `Transaction::reversal()` relations added.
- [x] PHP syntax check on all new/modified files (`php -l`) — clean.
- [x] Migrations run cleanly in docker (all 5 new migrations DONE in 2026-05-13 session).
- [x] All 4 new `sharesAsOf` tests pass (`test_shares_as_of_returns_shares`, `..._returns_zero_when_no_balance`, `..._discounts_borrowed_shares`, `..._with_only_borrowed_balance_is_negative`).
- [x] AccountExtTest: 24 of 26 pass; 2 failures (`value_as_of_calculates_correctly`, `share_value_as_of_delegates_to_fund`) confirmed **pre-existing** by running on baseline via `git stash` — caused by wiped dev DB missing fund/portfolio seed data, not by Phase 0 changes.
- [x] Locked contracts section below filled in.

## Locked Contracts (for parallel wave 1)

Filled in at the end of Phase 0 so wave-1 agents can code against a stable surface.

### Model: `AccountCreditLine`
- Table: `account_credit_lines`
- Status enum: `active`, `paid_off`, `cancelled`
- Payment frequency: `monthly`, `quarterly`, `annual`
- Key fields: `account_id`, `principal_shares`, `outstanding_shares`, `term_months`, `origination_date`, `maturity_date`, `payment_frequency`, `status`, `descr`, `imputed_interest_rate` (nullable)

### Model: `CreditLinePayment`
- Table: `credit_line_payments`
- Status enum: `scheduled`, `paid`, `partial`, `late`, `cancelled`
- Key fields: `account_credit_line_id`, `due_date`, `shares_due`, `status`, `paid_transaction_id` (nullable)

### Model: `CreditLineAdjustment`
- Table: `credit_line_adjustments` (immutable rows)
- Key fields: `account_credit_line_id`, `adjusted_at`, `adjusted_by_user_id` (nullable), `outstanding_shares_at_adjustment`, `old_term_months`, `new_term_months`, `old_payment_frequency`, `new_payment_frequency`, `old_maturity_date`, `new_maturity_date`, `old_planned_payoff_date`, `new_planned_payoff_date`, `reason` (nullable)

### Model: `TransactionReversal`
- Table: `transaction_reversals` (immutable rows)
- Key fields: `transaction_id`, `original_target_credit_line_id` (nullable), `reversed_at`, `reversed_by_user_id`, `reason`

### `transactions` table additions
- `account_credit_line_id` (nullable FK → `account_credit_lines.id`)
- `credit_line_match_status` (nullable enum: `auto_matched`, `manual`, `ambiguous`, `unmatched`)
- `reversed` (boolean, default false)

### `TransactionExt` new constants
- `MATCH_STATUS_AUTO_MATCHED = 'auto_matched'`
- `MATCH_STATUS_MANUAL = 'manual'`
- `MATCH_STATUS_AMBIGUOUS = 'ambiguous'`
- `MATCH_STATUS_UNMATCHED = 'unmatched'`

### `AccountCreditLineExt` constants
- `STATUS_ACTIVE = 'active'`
- `STATUS_PAID_OFF = 'paid_off'`
- `STATUS_CANCELLED = 'cancelled'`
- `FREQUENCY_MONTHLY = 'monthly'`
- `FREQUENCY_QUARTERLY = 'quarterly'`
- `FREQUENCY_ANNUAL = 'annual'`

### `AccountExt` semantic change
- `sharesAsOf($asOf)` returns `OWN_shares − BOR_shares` (was: `OWN_shares` only, BOR ignored).
- Downstream impact: `valueAsOf`, `periodPerformance`, anything reading "spendable" balance. Expected — see §11 risk "Existing TODO in `AccountExt::sharesAsOf()`".

## Open Questions
- Whether `disbursement_cap` interacts with credit-line draw cap. (Deferred — answer in wave 1 when draw service is built.)
- Where to record fund-side receivable: new transaction type, new ledger table, or computed view. Decision in fund cashflow doc.

## Notes / Decisions

- **2026-05-13** — Fund cash-flow design: chose receivable-as-asset, derived view (no separate ledger table). See [`docs/credit_lines/fund_cashflow.md`](docs/credit_lines/fund_cashflow.md). `FundExt::valueAsOf()` work is deferred to Phase 5 (reporting); Phase 0 only guarantees the schema supports the derivation.
- **2026-05-13** — `AccountExt::sharesAsOf()` now returns `OWN - BOR`. Will return **negative** if BOR alone (no OWN row). Phase 1 draw-cap logic must explicitly handle "no OWN row" before subtracting, or this can mask programming errors. Documented in test `test_shares_as_of_with_only_borrowed_balance_is_negative`.
- **2026-05-13** — `transactions.account_credit_line_id` is a soft pointer — nullable, and a credit-line transaction (BOR/REP) only gets the FK after the matcher classifies it. NULL is valid until classification runs.
- **2026-05-13** — `transaction_reversals.transaction_id` is `unique()` — enforces "a transaction can be reversed at most once" at the DB level. Re-applying after a reversal is a new forward transaction, not editing the reversal row.
- **2026-05-13** — Did not modify `DataFactory::createBalance()` to accept a `type` argument. Phase 1 should add this overload (e.g. `createBalance($shares, $tran, $account, $start_dt, $end_dt, $type='OWN')`); for now the Phase 0 unit tests instantiate `AccountBalance::factory()` directly when `type='BOR'` is needed.
- **2026-05-13** — Docker daemon was not running at end of Phase 0; migrations + full test suite were **not** executed. Static check (`php -l`) on every changed file passed. Before launching wave 1, an operator must:
  1. Start Docker.
  2. `docker exec familyfund php artisan migrate`.
  3. `docker exec familyfund php artisan test --exclude-group=incomplete,needs-data-refactor`.
  4. Inspect `AccountExtTest` BOR-discount tests pass and no existing test regressed from the `OWN - BOR` semantic change.
