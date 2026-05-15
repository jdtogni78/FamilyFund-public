<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\MatchingRule;
use App\Models\TransactionExt;
use App\Models\TransactionMatching;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DataFactory;
use Tests\TestCase;

/**
 * Behaviour of the contribution-matching subsystem against credit-line
 * REP transactions, post-`applies_to_rep` toggle.
 *
 * See docs/credit_lines/matching_on_repayment.md for the full design.
 * Default applies_to_rep=TRUE → REPs match when shares × shareValue > 0.
 * applies_to_rep=FALSE on the rule → REPs are skipped.
 */
class CreditLineRepMatchingTest extends TestCase
{
    use DatabaseTransactions;

    private DataFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        Asset::firstOrCreate(
            ['name' => 'CASH', 'type' => 'CSH'],
            ['source' => 'MANUAL', 'display_group' => 'Cash']
        );

        $this->factory = new DataFactory();
        // 1000 shares for $1000 → share value = $1.00
        $this->factory->createFund(1000, 1000, '2022-01-01');
        $this->factory->createUser();
    }

    public function test_match_returns_non_zero_for_rep_when_applies_to_rep_default_true(): void
    {
        $this->factory->createMatching(
            dollar_end: 1000,
            match: 100,
            start: '2026-01-01',
            end: '2027-01-01'
        );
        $account = $this->factory->userAccount;
        $amr = $account->accountMatchingRules()->first();

        // applies_to_rep defaults to TRUE on new rules.
        $this->assertTrue((bool) $amr->matchingRule->applies_to_rep);

        // REP with shares > 0 but value=0 (Phase 1a deferred cash leg).
        $rep = $this->factory->createTransaction(
            value: 0,
            account: $account,
            type: TransactionExt::TYPE_REPAY,
            status: TransactionExt::STATUS_PENDING,
            flags: null,
            timestamp: '2026-06-15'
        );
        $rep->shares = 10;
        $rep->save();
        $rep->refresh();

        // With applies_to_rep=true and the shares×shareValue fallback,
        // match() should now return non-zero. (10 shares × $1.00 × 100%)
        $matchValue = $amr->match($rep);

        $this->assertGreaterThan(0, (float) $matchValue,
            'REP with shares>0 and applies_to_rep=true should produce non-zero match via the share-value fallback.');
    }

    public function test_match_returns_zero_for_rep_when_applies_to_rep_false(): void
    {
        $this->factory->createMatching(
            dollar_end: 1000,
            match: 100,
            start: '2026-01-01',
            end: '2027-01-01'
        );
        $account = $this->factory->userAccount;
        $amr = $account->accountMatchingRules()->first();

        // Trustee opts this rule out of REP matching.
        $amr->matchingRule->applies_to_rep = false;
        $amr->matchingRule->save();
        $amr->refresh();

        $rep = $this->factory->createTransaction(
            value: 0,
            account: $account,
            type: TransactionExt::TYPE_REPAY,
            status: TransactionExt::STATUS_PENDING,
            flags: null,
            timestamp: '2026-06-15'
        );
        $rep->shares = 10;
        $rep->save();
        $rep->refresh();

        $matchValue = $amr->match($rep);

        $this->assertSame(0.0, (float) $matchValue,
            'applies_to_rep=false must short-circuit and skip REP matching entirely.');
    }

    public function test_match_uses_shares_times_share_value_when_rep_value_is_zero(): void
    {
        // 50% match, $1000 cap, share value = $1.00 → 10 shares × $1 × 50% = $5.00
        $this->factory->createMatching(
            dollar_end: 1000,
            match: 50,
            start: '2026-01-01',
            end: '2027-01-01'
        );
        $account = $this->factory->userAccount;
        $amr = $account->accountMatchingRules()->first();

        $rep = $this->factory->createTransaction(
            value: 0,
            account: $account,
            type: TransactionExt::TYPE_REPAY,
            status: TransactionExt::STATUS_PENDING,
            flags: null,
            timestamp: '2026-06-15'
        );
        $rep->shares = 10;
        $rep->save();
        $rep->refresh();

        $shareValue = (float) $account->shareValueAsOf($rep->timestamp);
        $expected = round(10 * $shareValue * (50 / 100.0), 2);

        $matchValue = (float) $amr->match($rep);

        $this->assertEqualsWithDelta($expected, $matchValue, 0.01,
            'REP match base must equal shares × shareValueAsOf when value=0.');
    }

    public function test_applies_to_rep_defaults_to_true_on_new_rule(): void
    {
        $rule = MatchingRule::factory()->create();
        $rule->refresh();
        $this->assertTrue((bool) $rule->applies_to_rep,
            'New MatchingRule rows must default applies_to_rep to true.');
    }

    public function test_match_returns_non_zero_for_pur_with_real_value(): void
    {
        $this->factory->createMatching(
            dollar_end: 1000,
            match: 100,
            start: '2026-01-01',
            end: '2027-01-01'
        );
        $account = $this->factory->userAccount;
        $amr = $account->accountMatchingRules()->first();

        $pur = $this->factory->createTransaction(
            value: 50,
            account: $account,
            type: TransactionExt::TYPE_PURCHASE,
            status: TransactionExt::STATUS_PENDING,
            flags: null,
            timestamp: '2026-06-15'
        );

        $matchValue = $amr->match($pur);

        $this->assertGreaterThan(0, (float) $matchValue,
            'PUR with non-zero value should produce a non-zero match.');
    }
}
