# Fund-Side Cash Flow for Credit Lines

**Status:** decided (Phase 0)
**Date:** 2026-05-13
**Plan ref:** [`credit_lines_plan.md`](../../credit_lines_plan.md) §5 rule 2, §11 first risk

## Problem

Today the fund's cash position changes only through portfolio activity (asset purchases/sales) and matching contributions. A credit-line **draw** disburses cash *out of the fund* to a borrower with no corresponding asset purchase; a **repayment** brings cash back in. Naively, this would:

- Drop NAV at draw (cash leaves, no offsetting asset).
- Cause NAV to swing on every draw/repay, distorting share-price history.
- Make `as_of` historical reconstruction inconsistent.

`FundExt::shareValueAsOf()` computes `valueAsOf / sharesAsOf`. If disbursed cash drops out of `valueAsOf`, NAV moves on draws — not what we want.

## Decision: receivable-as-asset

Treat the **outstanding cash receivable** (cash the borrower owes back, valued in dollars at the current share price) as a fund-level asset. At draw time, fund cash drops by the disbursed amount and a receivable of equal value is recorded — **NAV is unchanged at the moment of draw**.

During the life of the line, the receivable is **valued at `outstanding_shares × current_share_price`**. Because share price drifts, the receivable's dollar value drifts with it. That drift = the fund's P&L on the loan over its life. This is the right economic model: the fund holds a share-denominated claim and bears the price-drift exposure on the cash side.

At repayment:
- Cash returns to the fund (`shares_repaid × share_price_on_repayment_date`).
- Receivable balance decreases by `shares_repaid` (share-denominated).
- NAV impact = the price-drift since draw, naturally reflected.

## Why not the alternatives

1. **"Treat disbursement as fund value loss"**: simple, but every draw/repay would jolt NAV. History becomes a sawtooth driven by lending activity, not investment performance. Reports become unreadable.
2. **"New BOR transaction type only, no receivable"**: hides the loan from the fund's books. NAV would drop at draw. Same problem.
3. **"Escrow account holding the BOR shares"**: introduces a second pseudo-account. Doubles the bookkeeping. The existing `account_balances` BOR row already does the job per-account; we just need a fund-level mirror.

## Where the receivable lives

**Option A (chosen): derived view, no new table.** The fund's "credit-line receivable" is computed on demand from active `AccountCreditLine` rows belonging to accounts in the fund:

```
receivable_shares(fund, as_of) = Σ AccountCreditLine.outstanding_shares
                                   for active lines on accounts in fund
                                   as_of <= now
receivable_value(fund, as_of)  = receivable_shares × fund.shareValueAsOf(as_of)
```

`FundExt::valueAsOf()` adds `receivable_value` to the existing computation. `sharesAsOf()` is unchanged — share count of the fund itself doesn't change when shares are borrowed against accounts; the accounts' OWN shares are still in the fund's books, the BOR shares are simply a tag on the account-level balance.

Wait — that's important to get right. The BOR row on the account is **also** an entry in `account_balances`. Does the fund's `sharesAsOf()` (sum of all account balances) double-count? Let's verify when we implement: if the existing aggregation sums OWN only, no double-count. If it sums both OWN and BOR, we either filter or net them. Note for Phase 1 implementer.

**Option B (not chosen): explicit ledger table** (`fund_cash_movements` or `fund_receivables`). Cleaner audit trail but introduces a write path that must stay in sync with `account_balances`. Risk of drift. Defer until/unless reconciliation problems demand it.

## Recording cash movement out of the fund

The disbursement itself — the actual cash leaving the fund to reach the borrower — is **modeled in the [money flow sub-project](../../money_flow_plan.md)**. From the *fund's books* perspective, it's:

1. A BOR `Transaction` row on the account (`type='BOR'`, `shares=principal_shares`, `value=principal_shares × share_price`). This already exists in the schema.
2. The receivable derives from that BOR transaction via the active `AccountCreditLine`. No new transaction type needed on the fund side.

So the fund-side accounting is **derivative of the account-side transactions** plus the credit-line metadata. There is no separate "fund cash out" transaction.

## NAV math (worked example)

Initial state:
- Fund has $1,000 cash, 100 shares outstanding, share price $10.

Borrower draws 5 shares (principal):
- Account: BOR balance +5, OWN balance unchanged at (say) 20.
- Fund cash: $1,000 − $50 = $950 (paid to borrower).
- Receivable: 5 shares × $10 = $50.
- Fund total value: $950 + $50 = $1,000. **NAV unchanged**: $1,000 / 100 = $10. ✓

Share price drifts to $11 (asset appreciation in the rest of the portfolio):
- Fund cash: $950.
- Receivable: 5 × $11 = $55.
- Fund total value: $950 + $55 = $1,005.
- NAV: $1,005 / 100 = $10.05.
- The receivable carried us along with the market — the loan didn't penalize the fund.

Borrower repays 5 shares at price $11:
- Cash: $950 + $55 = $1,005.
- Receivable: 0.
- Fund total: $1,005. NAV: $10.05. ✓
- Borrower paid back the same 5 shares, but $55 of cash (vs. the $50 they received). The extra $5 is the fund's earned-over-the-loan-life.

This is exactly the economics we want: the fund is indifferent to lending vs. holding, on average; share-price drift over the loan's life is the only source of P&L.

## Implementation notes for Phase 1+

- `FundExt::valueAsOf()` extension is **Phase 5 reporting work**, not Phase 0. The receivable view is derivable from data the migrations create.
- In Phase 0 we only need to ensure the schema supports the derivation (active `AccountCreditLine` rows with `outstanding_shares` reachable from a fund via `accounts.fund_id`). Confirmed — schema does support it.
- When `FundExt::valueAsOf()` is updated, write a test that does the worked example above end-to-end.

## Tax caveat

This receivable model treats the loan as economically neutral to the fund. For US trust tax-reporting, that may not align with IRS §7872 below-market-loan treatment. The `imputed_interest_rate` field on `AccountCreditLine` is the accountant's reporting hook (informational only — does not affect share math). **Verify with counsel before real-money deployment.** See plan §11 first item.
