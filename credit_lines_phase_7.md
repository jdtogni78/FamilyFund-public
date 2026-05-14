# Credit Lines — Phase 7: `applies_to_rep` toggle

Ships the per-rule contribution-matching opt-out for credit-line REPs, plus
the value-fallback so the matcher actually computes a base for REPs that have
`value = 0` (Phase 1a deferred cash leg).

Full design context: `docs/credit_lines/matching_on_repayment.md`.

## Checklist

| Step | Status | Notes |
| --- | --- | --- |
| 1. Migration adding `applies_to_rep BOOLEAN NOT NULL DEFAULT TRUE` | DONE | `database/migrations/2026_05_15_000001_add_applies_to_rep_to_matching_rules.php` |
| 2. Model: `$fillable`, `$casts`, `$rules` | DONE | `app/Models/MatchingRule.php` |
| 3. Matcher early-return + value fallback | DONE | `app/Models/AccountMatchingRuleExt.php::match()` |
| 4. UI checkbox on create/edit form | DONE | `resources/views/matching_rules/fields.blade.php` (hidden `0` + checkbox `1`, defaults to checked on new rules, reflects current value when editing) |
| 5. Tests updated + added | DONE | `tests/Unit/CreditLineRepMatchingTest.php` |
| `php -l` on every changed file | DONE | All clean |
| `php artisan migrate` | DONE | Migration applied successfully |
| Targeted regression run | GREEN | 150 passed |
| Full suite (excl. `incomplete`, `needs-data-refactor`) | UNCHANGED | 1758 passed; 6 pre-existing failures (4× `HolidaysSyncApiTest`, 1× `AccountApiGoldenDataTest::account balance as of`) are unrelated to matching/credit lines |

## Match-base formula for REPs

Applied inside `AccountMatchingRuleExt::match()` (Option B in the
investigation — localized, NOT a global mutation of `$transaction->value`):

```
effectiveValue = (float) $tranToMatch->value
if effectiveValue == 0
   AND $tranToMatch->type === TransactionExt::TYPE_REPAY
   AND $tranToMatch->shares > 0:
       effectiveValue = $tranToMatch->shares
                      × $tranToMatch->account->shareValueAsOf($tranToMatch->timestamp)
```

`effectiveValue` then feeds both `applicableValue()` and the
`applicable > value` cap check.

The opt-out short-circuit lives directly above this, before
`isInPeriod()`:

```
if $tranToMatch->type === TYPE_REPAY AND !$mr->applies_to_rep:
    return 0
```

`RepayService` was deliberately NOT touched (rejected Option A — wider blast
radius, would affect `depositedValueBetween`, per-tx performance math, etc.).

## Test count delta

`tests/Unit/CreditLineRepMatchingTest.php`: was 2 tests, now 5.

- (renamed) `test_match_returns_zero_for_rep_with_zero_value`
  → `test_match_returns_non_zero_for_rep_when_applies_to_rep_default_true`
  Behavior flipped: with the toggle default TRUE and the fallback, a REP
  with `shares=10, value=0` now produces non-zero match.
- (new) `test_match_returns_zero_for_rep_when_applies_to_rep_false`
- (new) `test_match_uses_shares_times_share_value_when_rep_value_is_zero`
  Asserts exact formula: `shares × shareValueAsOf × match_percent/100`.
- (new) `test_applies_to_rep_defaults_to_true_on_new_rule` — covers DB
  default + cast.
- (existing) `test_match_returns_non_zero_for_pur_with_real_value` — kept
  as a sanity guard.

Net targeted delta: **+3 tests, 5 passing, 0 failing**.

## Subtleties discovered

- **Boolean cast + DB default**: when a `MatchingRule` is created via factory
  without specifying `applies_to_rep`, the in-memory model has the attribute
  unset; `(bool) null` evaluates to `false` despite the column default being
  `TRUE`. Tests must `->refresh()` to pick up the actual DB row. Production
  code paths read the rule via `matchingRule()->first()`, which always
  hydrates from DB — so production is fine. Documented this in the new
  defaults test.
- **Checkbox-in-form trick**: HTML omits unchecked checkboxes from the POST,
  so a `<input type="hidden" name="applies_to_rep" value="0">` precedes the
  checkbox to guarantee the field arrives as `0` when the user unchecks it.
  Without that the controller's `$request->all()` mass-assign would leave the
  prior value untouched on update.
- **`shares` not in `createTransaction()` signature**: `DataFactory::createTransaction`
  doesn't accept a `shares` arg. Tests set `$rep->shares = N; $rep->save()`
  after creation. (`makeTransaction` does take `$shares` but `createTransaction`
  doesn't forward it.)
- **Pre-existing suite failures**: 6 failing tests in the full run pre-date
  this change (HolidaysSync API + one golden-data baseline). Not introduced
  here.
