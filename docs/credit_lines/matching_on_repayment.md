# Matching contributions on credit-line repayments — investigation

**Question raised:** Should the existing `MatchingRule` / `TransactionMatching`
contribution-matching subsystem apply to credit-line **REP** transactions
(beneficiary repays the trust)? Should it be configurable per rule?

## Current behavior (as of commit `24c313f`)

### Where matching triggers

`TransactionExt::processPending()` (the lifecycle method that flips a
transaction from `pending` → `cleared`) calls `createMatching()` for every
non-fund-account transaction that doesn't carry `FLAGS_NO_MATCH`:

```php
// app/Models/TransactionExt.php:276-285
$noMatch = $this->flags == TransactionExt::FLAGS_NO_MATCH;
$createMatch = !($isFundAccount || $noMatch);
if ($createMatch) {
    $matchingResult = $this->createMatching(...);
}
```

There is **no filter by transaction type**. PUR / BOR / REP / SAL all flow
through `createMatching()` if they hit `processPending()`.

### What `createMatching` does

For each `AccountMatchingRule` attached to the account, it calls
`AccountMatchingRuleExt::match($this)`. That method computes:

```php
// app/Models/AccountMatchingRuleExt.php:74-96
$applicable = $this->applicableValue($deposits, $tranToMatch->value);
$matchValue = $applicable * ($mr->match_percent / 100);
```

So the match amount is proportional to `$tranToMatch->value`. If `value` is
zero, `matchValue` is zero, no `MAT` transaction is created.

### Implication for credit-line REPs

Wave 1a's `RepayService` creates REP transactions with `value = 0` (the cash
leg is deferred per [`fund_cashflow.md`](fund_cashflow.md)). So **today**:

- A REP passes through `createMatching()` …
- … but every matching rule computes `matchValue = 0` (because the REP's
  `value` is 0) …
- … and no `MAT` transaction is written.

**Net result:** credit-line REPs do NOT trigger matching contributions today,
even though the matching machinery is technically reached. It's a silent
no-op caused by `value = 0`.

`depositedValueBetween()` (used for the per-period "deposits so far"
baseline) also treats REP as `+value` and BOR as `-value`. With both at 0
they contribute nothing to that baseline. The cumulative "matchable deposits"
window is unaffected by credit-line activity.

### Note on the new detection pipeline

Phase 1d's `TransactionDetectionService::ingest()` routes BOR/REP through
`CreditLineClassifier` and PUR through `ContributionClassifier`. The
contribution classifier is a **read-only** adapter (after Phase 6 UC-37):
it inspects `TransactionMatching` rows that `createMatching` has already
written and reports them — it does not write matching rows itself. So the
new pipeline doesn't change the legacy behavior; it only observes it.

## Should this change?

The user asked: *"in principle, I want to let matchings be allowed on
repayment."*

Two questions are tangled together:

### Q1: Should matching apply to REPs at all?

**Argument for**: a REP returns shares to the fund, semantically similar to
a contribution. If the trust wants to incentivize repayment, matching is the
lever — give the borrower a small kicker for each repayment.

**Argument against**: REPs are settlement, not fresh deposits. Matching them
could double-count (the borrower originally took shares from the fund; now
they put some back and get more again).

The user's "in principle, yes" suggests the trust intends to use matching as
a repayment incentive. Reasonable.

### Q2: Per-rule toggle, or universal?

A toggle (`applies_to_rep` boolean on `matching_rules`, default `true` per
user's preference) lets the trustee enable matching for some rules but not
others — e.g., a "tuition contribution match" rule probably shouldn't apply
to credit-line repayments, while a "general savings match" might.

Default to `true` (per user) and allow the trustee to opt specific rules
out.

### Q3: Does it actually work today even with the toggle on?

No. As described above, REPs have `value = 0`. Even with the toggle on, the
matching math returns 0. The toggle alone is insufficient — we'd also need
either:

- **Option A**: Populate `REP.value = shares × share_value_at_repayment_date`
  in `RepayService`. This is a bigger change — it affects
  `depositedValueBetween`, anywhere `value` is summed, and the per-tx
  performance math (which is why Phase 5b had to add div-by-zero guards).
- **Option B**: Localize the match-base computation. In
  `AccountMatchingRuleExt::match()`, when `$tranToMatch->type === REP` and
  `$tranToMatch->value === 0`, fall back to `shares × shareValueAsOf`.
  Smaller blast radius — doesn't affect anything else that reads `value`.

Recommendation: **Option B** for the first iteration. Less risk; can be
upgraded to Option A later if we decide credit-line transactions really
should carry a non-zero value across the board (which would also fix the
trajectory-cash leg deferred from Phase 1).

## Proposed feature shape

1. **Migration**: `add_applies_to_rep_to_matching_rules` adds
   `applies_to_rep BOOLEAN DEFAULT TRUE` to `matching_rules`. Default true
   to satisfy "in principle, yes" — trustees opt OUT per rule.
2. **Model**: add to `fillable` + `casts` on `MatchingRule`.
3. **Match logic** (`AccountMatchingRuleExt::match()`):
   - If `$tranToMatch->type === TransactionExt::TYPE_REPAY` and
     `!$mr->applies_to_rep` → return 0.
   - When matching a REP whose `value === 0`, compute effective base from
     `shares × $account->shareValueAsOf($tranToMatch->timestamp)`.
4. **Edit UI** on `MatchingRuleControllerExt::edit` adds the toggle.
5. **Tests** (regression for current + new behavior).

## Regression tests (current behavior)

Two locked-in tests:

- `tests/Unit/CreditLineRepMatchingTest::test_credit_line_rep_does_not_create_matching_with_zero_value`
  — locks in the silent no-op so a future change to give REPs non-zero
  value won't accidentally start triggering matching everywhere.
- `tests/Unit/CreditLineRepMatchingTest::test_purchase_still_creates_matching`
  — sanity check that the existing matching flow still works for PURs.

When the toggle feature ships, add two more:

- `test_applies_to_rep_default_true_creates_matching_on_rep`
- `test_applies_to_rep_false_skips_rep`
