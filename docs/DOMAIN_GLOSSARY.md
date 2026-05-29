# Domain Glossary

A canonical, alphabetized list of the domain terms used in FamilyFund — the
"ubiquitous language" (per DDD) that code, tests, UI labels, and
this memory bank all share. When in doubt, this file wins over informal
wording.

Scope: fund accounting, beneficiary accounts, allocations, credit lines, and
the inbound/outbound money-flow subsystem. Disambiguates against trading-side
terms used in the sister repo [`dstrader`](../../dstrader) (see §"Disambiguation"
below).

---

## A

**Account** — A holder of fund shares. Implemented as `App\Models\Account`
(table `accounts`). Both flavors carry `code` (≤15 chars, required) and an
optional `nickname` (≤15 chars) — see `app/Models/Account.php:73-74`.
Distinguished by `user_id`:
- **Fund Account** — `user_id IS NULL`. One per fund. Represents the fund's
  own books — used as the cash/share aggregator. See [`Fund`](#f).
- **Beneficiary Account** — `user_id` set. A user's share holding in the
  fund.

**AccountBalance** — A time-ranged row recording an account's share count of
a given `type` (`OWN` or `BOR`). Open rows carry `end_dt = '9999-12-31'`
(sentinel) and are closed by `TransactionExt::processPending` writing a real
date (`app/Models/TransactionExt.php:138,154`). Each balance row is the
result of one `Transaction` (`transaction_id`) and chains to the previous via
`previous_balance_id`. Model: `App\Models\AccountBalance`. Invariant: an
account can have at most one open balance per `type` at any date — maintained
by `processPending` closing the prior row before opening the new one;
`AccountExt::allSharesAsOf` *checks* the invariant on read and throws if
violated.

**AccountCreditLine** — See [Credit Line](#c).

**Adjustment** — An audit row written when a credit line is readjusted
(extend/shorten term, change payment frequency). Immutable. Records old/new
term and maturity, plus `outstanding_shares_at_adjustment` for reconstruction.
Model: `App\Models\CreditLineAdjustment`.

**Allocation** — Two distinct things in this codebase; always qualify which:
1. **Credit-line payment allocation** — A row recording that *N shares* of a
   particular REP transaction were applied to a particular schedule row.
   Model: `App\Models\CreditLinePaymentAllocation`. This is the unit of truth
   for how a repayment distributes across scheduled installments.
2. **Portfolio target allocation** — A `TradePortfolioItem.target_share` — the
   target weight (0..1) for a symbol in a `TradePortfolio`'s rebalancing
   strategy. Not the same concept; not the same table.

Note: `account_balances.shares` is not called an "allocation" anywhere in
code — those are *share holdings* (see [Shares](#s)).

**Asset** — A held instrument: stock, crypto, real estate. Model:
`App\Models\Asset`. Belongs to one or more `Portfolio`s via `portfolio_assets`
(see [Position](#p)) and has historical prices via `AssetPrice`.

## B

**Balance** — Ambiguous in isolation. Code uses:
- `account_balances.shares` — share count of an `AccountBalance` row.
- `Transaction::balance` accessor — `shares × share_value` (dollar value).
- `AccountCreditLine.outstanding_shares` — current shares still owed.
Always pair "balance" with one of those nouns when writing.

**Beneficiary** — Informal term for the human or trust party behind a
beneficiary `Account`. There is no `beneficiaries` table; the
`accounts.user_id → users.id → persons.*` chain is the data spine. The word
*beneficiary* appears in UI text, transaction docs, and runbooks; code uses
**Account**.

**BOR (Borrow)** — `Transaction.type='BOR'`. Records a credit-line **draw**:
shares move from beneficiary OWN to a BOR balance row on the same account,
and cash leaves the fund to the borrower. The cash leg is modeled separately
(see [`docs/credit_lines/fund_cashflow.md`](credit_lines/fund_cashflow.md)).
Defined at `app/Models/TransactionExt.php:31`.

## C

**Cash Deposit** — A recorded inflow of cash at the fund's brokerage (IBKR).
Model: `App\Models\CashDeposit` (statuses on `App\Models\CashDepositExt`).
Happy-path flow: `PENDING` → `DEPOSITED` → `ALLOCATED` → `COMPLETED`; can
also terminate at `CANCELLED` (see `CashDepositExt::STATUS_*`). Pairs with a
`DepositRequest` when reconciled. Sourced from IB Flex Query CSVs via
`CashDepositTrait::parseCashDeposit`.

**Cleared / Pending / Scheduled** — `Transaction.status` values:
- `P` (Pending) — created, not yet processed.
- `C` (Cleared) — `processPending()` ran, balance updated.
- `S` (Scheduled) — template used by `ScheduledJob` for recurring runs.
Defined at `app/Models/TransactionExt.php:34-36`.

**Contribution** — Informal/UI term for a beneficiary **deposit**, almost
always realized as a `Transaction.type='PUR'`. Sometimes triggers a parental
**matching** contribution (see [Matching Rule](#m)). Not a separate table.

**Credit Line** — A standing agreement that lets a beneficiary borrow a
fixed number of *shares* (not dollars) against their OWN balance for a
chosen term, repaying on a schedule, with no interest, collateral, or
default penalties. Model: `App\Models\AccountCreditLine`. Keyed columns:
`principal_shares`, `outstanding_shares`, `term_months`,
`origination_date`, `maturity_date`, `payment_frequency`
(`monthly|quarterly|annual`), `status` (`active|paid_off|cancelled`),
`imputed_interest_rate` (informational, for below-market-loan disclosure
under the applicable tax regime — US trust today, IRS §7872 specifically;
does not affect share math; see
[`docs/credit_lines/fund_cashflow.md`](credit_lines/fund_cashflow.md)
"Tax caveat"). One account can hold many concurrent lines. **As of
[ED-0010](ENGINEERING_DECISIONS.md#ed-0010--loan-share-is-a-label-only-rename-of-credit-line)**,
**UI labels say "Loan Share"** but tables, classes, and routes keep
`credit_line` — don't assume the UI rename implies a schema change. If
ED-0010 is ever revisited (rename leaks into routes/tables), update this
entry.

**CreditLinePayment** — A row in the schedule generated at draw time (and
re-generated on adjustment). `shares_due`, `due_date`, `status`
(`scheduled|paid|partial|late|cancelled`). Owned by a single
`AccountCreditLine`. The `credit_line_adjustment_id` column tracks which
adjustment generation the row belongs to; `NULL` = the origination generation.

## D

**Distribution** — Generic term for cash going *out* of the fund — covers
withdrawals (`SAL` + cash leg) and credit-line draws (BOR + cash leg). Not
a discriminator in code; appears in reports and operations docs. For the
inbound/outbound payment plumbing, see [Money Flow](#m).

**Draw** — The act of opening a credit line; disburses
`principal_shares × share_price` in cash to the borrower. Recorded as a BOR
transaction + an `AccountCreditLine` row. The cash-flow modeling is owned by
[`docs/credit_lines/fund_cashflow.md`](credit_lines/fund_cashflow.md)
("receivable-as-asset" decision).

## F

**Fund** — An investment vehicle. Model: `App\Models\Fund`. Owns
beneficiary `Account`s (`accounts.fund_id`) and is joined to `Portfolio`s
many-to-many via the `fund_portfolio` pivot. NAV math:
`shareValueAsOf(t) = valueAsOf(t) / sharesAsOf(t)`
(see `FundExt::shareValueAsOf`). Withdrawal/growth parameters
(`withdrawal_*`, `expected_growth_rate`, `independence_*`) are inputs to
the financial-independence forecast pages.

**Fund Account** — See [Account](#a).

## G

**Goal** — A target the beneficiary is saving toward. Model: `App\Models\Goal`.
`target_type`, `target_amount`, `target_pct`, plus date range. Joined to
accounts via `AccountGoal` (pivot, model `App\Models\AccountGoal`). The
"Current" progress number uses **net** shares (OWN − BOR) — see
[ED-0009](ENGINEERING_DECISIONS.md#ed-0009--goal-current-is-net-shares).

## I

**Imputed Interest Rate** — `AccountCreditLine.imputed_interest_rate`. A
reporting-only field for the trust's accountant to support IRS §7872
below-market-loan disclosure. Does not affect share math, repayment
schedule, or `outstanding_shares`. **Verify with counsel before
real-money deployment.**

**INI (Initial)** — `Transaction.type='INI'`. The first transaction that
seeds a fund, providing both `value` and `shares` directly (share price is
not yet defined). Defined at `app/Models/TransactionExt.php:28`.

## M

**MAT (Matching)** — `Transaction.type='MAT'`. System-created sibling of a
PUR when a `MatchingRule` applies — records the trust/parental match.
Linked back to the triggering PUR via `TransactionMatching`. Defined at
`app/Models/TransactionExt.php:30`.

**Matching Rule** — `App\Models\MatchingRule`. Configurable rule that
multiplies a beneficiary deposit by `match_percent` within a dollar range
and date window, generating a MAT. Whether it `applies_to_rep` (credit-line
repayments) is a per-rule flag — see
[`docs/credit_lines/matching_on_repayment.md`](credit_lines/matching_on_repayment.md)
for current behavior. Per-account opt-in via `AccountMatchingRule`.

**Match Status** (credit-line) — `Transaction.credit_line_match_status`:
`auto_matched | manual | ambiguous | unmatched`. Tells how a REP was
assigned to one of an account's active credit lines. Defined at
`app/Models/TransactionExt.php:42-45`.

**Member** — No `member` entity exists in the schema. The party behind a
beneficiary `Account` is modeled as a `User` (login) attached to a `Person`
(PII). Use **Beneficiary** for the role and `Person` or `User` for the
identity row. Avoid "member" in new docs unless quoting external sources.

**Money Flow** — The (in-design as of 2026-05) subsystem that detects
inbound bank/PIX deposits, attributes them to a beneficiary, and books the
right `CashDeposit`/`Transaction`; symmetrically for outbound disbursements.
v1 path: beneficiary Wise → fund US checking → IBKR. See
[`money_flow_plan.md`](money_flow_plan.md) and
[`money_flow_runbook.md`](runbooks/money_flow_runbook.md). When v1 ships, revisit
this entry against the shipped surface.

## N

**NAV (Net Asset Value)** — In FamilyFund, the fund's *value per share*
returned by `FundExt::shareValueAsOf` (`app/Models/FundExt.php:73`).
Computed from portfolio market value plus any derived credit-line receivable
(see `creditLineReceivableValueAsOf`, `FundExt.php:119`; the design behind
why draws don't jolt NAV is in
[`docs/credit_lines/fund_cashflow.md`](credit_lines/fund_cashflow.md)).
"NAV" colloquially also refers to the *total fund value*; in this codebase
the unqualified value is per-share unless context says otherwise.

## O

**Origination** — The opening of a credit line. `origination_date` on
`AccountCreditLine` anchors the payment schedule.

**OWN / BOR** — `account_balances.type` values. `OWN` = shares the account
holds outright; `BOR` = shares it has borrowed (a credit-line draw). An
account's spendable shares = `OWN − BOR` — that's what
`AccountExt::sharesAsOf` returns (`app/Models/AccountExt.php:123-128`).
`sharesWithoutBorrowingAsOf` returns gross OWN if you need the pre-borrow
number.

## P

**Payment** — Two meanings, always qualify:
1. **Scheduled payment** — a `CreditLinePayment` row (an installment).
2. **Repayment transaction** — a `Transaction.type='REP'` actually paying
   one or more scheduled rows down. The link is recorded by
   `CreditLinePaymentAllocation`.

**Payment Frequency** — `AccountCreditLine.payment_frequency`:
`monthly | quarterly | annual`. Drives schedule generation.

**Person** — A real human (or legal entity) with `first_name`, `last_name`,
`email`, optional `birthday`, `legal_guardian_id`. Model: `App\Models\Person`.
A `User` is the login identity attached to a `Person` via `users.person_id
→ persons.id`. Distinct from beneficiary `Account` (which is a holding, not
a person) — one Person can own multiple Accounts across multiple Funds.

**Portfolio** — A collection of `Asset` positions. Model: `App\Models\Portfolio`.
Identified by `source` (≤30 chars, e.g. `IB_TAXABLE`, `COINBASE_CRYPTO`)
used by external sync systems. Joined to `Fund` many-to-many via
`fund_portfolio` (a 2026-01 migration replaced the older single
`portfolios.fund_id` column — `FundExt` accesses portfolios through the
pivot). For consolidation patterns, see [`FUND_SETUP_GUIDE.md`](FUND_SETUP_GUIDE.md).

**Position** — `PortfolioAsset.position`: the number of asset units held in
a portfolio over a `[start_dt, end_dt)` window. Decimal(8). A position is
NOT a share count of the fund — it's a holding count of an underlying
instrument.

**Principal (shares)** — `AccountCreditLine.principal_shares`. The share
count drawn at origination. Distinct from `outstanding_shares`, which is
the remaining unpaid balance.

**PUR (Purchase)** — `Transaction.type='PUR'`. Beneficiary deposit: shares
are issued at the current share value. Defined at
`app/Models/TransactionExt.php:27`.

## R

**Rebalancing** — `TradePortfolio`-driven activity that brings actual asset
weights back toward each `TradePortfolioItem.target_share` (subject to
`deviation_trigger`). Implemented in the WebV1 controllers and the
`dstrader` Java service that consumes the exported plan. Not a transaction
type on its own — generates PUR/SAL on the fund account.

**Receivable** — The fund-side *outstanding cash owed back* by all active
credit-line borrowers, valued in dollars at the current share price. Not a
persisted table; derived as `Σ AccountCreditLine.outstanding_shares ×
FundExt::shareValueAsOf(now)` across active lines belonging to the fund's
accounts (there is no `share_price` column — the share value is always
computed). The design rationale is in
[`docs/credit_lines/fund_cashflow.md`](credit_lines/fund_cashflow.md).

**REP (Repay)** — `Transaction.type='REP'`. Beneficiary pays scheduled
shares back to a credit line; allocated across `CreditLinePayment` rows via
`CreditLinePaymentAllocation`. Defined at `app/Models/TransactionExt.php:32`.

**Reversal** — `App\Models\TransactionReversal`. A pointer that says "this
transaction was undone". Created via the admin reversal flow; does not
delete the original `Transaction` row (audit preserved).

## S

**SAL (Sale)** — `Transaction.type='SAL'`. Beneficiary withdrawal: shares
are redeemed at current share value. SAL does not trigger matching (the
matching path is gated upstream in `TransactionExt::processPending`; SAL
rows simply don't enter it). Defined at `app/Models/TransactionExt.php:29`.

**Payment Schedule** — The full set of `CreditLinePayment` rows for one
`AccountCreditLine`. Generated at draw time (rows have
`credit_line_adjustment_id = NULL`) and re-generated on each adjustment
(rows tagged with the adjustment id; old rows are cancelled). *Not to be
confused with* `App\Models\Schedule`, an unrelated entity joined to
`ScheduledJob`.

**Shares** — Fractional units of fund ownership. Stored to 4 decimal places
on `Transaction.shares` and `AccountBalance.shares`. Disambiguate from
*stock shares* (units of an `Asset` like a stock symbol, tracked as
`PortfolioAsset.position`).

**Share Price / Share Value** — Synonyms in this codebase. The value of one
fund share at time `t`, returned by `FundExt::shareValueAsOf($t)`. Used to
convert between dollars and fund shares everywhere transactions are written.

**Sub-fund** — Conceptual term sometimes used informally for a beneficiary
Account ("the kids' sub-fund"). There is no `sub_fund` entity in the
schema; the unit is `Account`. Avoid the term in new docs unless you mean
"beneficiary account."

## T

**Transaction** — The atomic write to the books. Model: `App\Models\Transaction`
(business logic on `TransactionExt`). Carries `type`, `status`, `value`
(dollars, 2dp), `shares` (4dp), `timestamp`, `account_id`, optional
`account_credit_line_id` + `credit_line_match_status`, `reversed` flag, and
free-text `descr`. Types: PUR, SAL, INI, MAT, BOR, REP (see each).

**TradePortfolio** — The trading-strategy wrapper around a `Portfolio`:
holds target weights (`TradePortfolioItem.target_share`), rebalancing
parameters (`cash_target`, `cash_reserve_target`, `max_single_order`,
`minimum_order`, `rebalance_period`), and IB Flex Query credentials
(`tws_query_id`, `tws_token`) used by `FetchDeposits`. Bridge to the
`dstrader` Java service.

## U

**User** — Laravel auth identity (`App\Models\User`). Linked to a `Person`
for PII and to one or more `Account`s. The Fund Account has
`user_id = NULL` to distinguish it from beneficiary accounts.

---

## Acronyms

| Acronym | Expansion | Context |
|---|---|---|
| ACL | Access Control List | Authorization layer (see [docs/ENGINEERING_DECISIONS.md#ed-0003](ENGINEERING_DECISIONS.md#ed-0003--deny-by-default-authorization)). |
| ACH | Automated Clearing House | US USD bank transfers; v1 inbound rails. |
| ADR | Architecture Decision Record | Format used in `ENGINEERING_DECISIONS.md` (lightweight). |
| BRL | Brazilian Real | Inbound currency in the money-flow subsystem. |
| CPF | Cadastro de Pessoas Físicas | Brazilian tax ID; on PIX recipients. |
| FX | Foreign Exchange | USD↔BRL conversion (handled upstream of our books per money-flow rev 6). |
| IBKR | Interactive Brokers | The fund's brokerage. |
| IB Flex Query | IBKR's CSV report format | Source for `CashDeposit` parsing. |
| NAV | Net Asset Value | Per-share fund value (see N). |
| PIX | Brazilian instant-payment system | Outbound rail to beneficiaries in Brazil. |
| TWS | Trader Workstation | IBKR's trading API endpoint (referenced in `tws_query_id`, `tws_token`). |

## Transaction type codes (non-acronym)

Three-letter `Transaction.type` codes — short tokens, not initialisms.

| Code | Name | See |
|---|---|---|
| `PUR` | Purchase | [PUR](#p) |
| `SAL` | Sale | [SAL](#s) |
| `INI` | Initial | [INI](#i) |
| `MAT` | Matching | [MAT](#m) |
| `BOR` | Borrow | [BOR](#b) |
| `REP` | Repay | [REP](#r) |

---

## Disambiguation against trading terms (`dstrader`)

FamilyFund and `dstrader` share the **fund / portfolio / position / share**
vocabulary but mean different things by some of them. Quick map:

| Term | FamilyFund (this repo) | dstrader (Java sibling) |
|---|---|---|
| **Share** | A fractional unit of fund ownership (`Transaction.shares`, `AccountBalance.shares`). | A stock share (a unit of an exchange-traded instrument). |
| **Portfolio** | A `Portfolio` belonging to a `Fund`, holding `Asset` positions. | A `Portfolio` in `portfolio.model` — the trading-strategy unit consuming a JSON config. |
| **Account** | A beneficiary's share holding (or the fund's own). | An IB account (cash + positions managed by `AccountManager`). |
| **Position** | `PortfolioAsset.position` — number of asset units held. | `StockInfo` quantity / weight inside a trading plan. |
| **Order** | Not a domain entity — orders are external (IBKR side). | `StockOrder` / `Plan` — emitted by a `BaseStrategy` for `OrderExecutor`. |
| **Rebalance** | The PUR/SAL stream resulting from a `TradePortfolio` plan. | `SimpleRebalanceStrategy` / `LinearRegressionStrategy` — the *generator* of that plan. |
| **Cash** | A line on a `Portfolio` (the `CASH` asset), plus the fund's checking balance in money-flow. | `AccountManager` cash position at IBKR. |

When writing cross-repo docs, prefix with "fund " or "trading " when the
context is ambiguous (`fund shares` vs `stock shares`; `fund portfolio`
vs `trading portfolio`).
