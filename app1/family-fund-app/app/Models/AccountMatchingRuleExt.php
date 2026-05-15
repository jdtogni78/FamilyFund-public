<?php

namespace App\Models;

use App\Http\Controllers\Traits\VerboseTrait;
use App\Repositories\AssetPriceRepository;
use Exception;
use Illuminate\Support\Carbon;
use Log;

class AccountMatchingRuleExt extends AccountMatchingRule
{
    use VerboseTrait;

    public function getMatchValueAsOf(Carbon $now, $isDatedReport, $func, $name): mixed
    {
        $value = 0;
        $mr = $this->matchingRule()->first();
        $account = $this->account()->first();
        if ($this->isInPeriod($now, $isDatedReport)) {
            $this->debug($name . ": " . $mr->transactionMatchings()->count());
            foreach ($mr->transactionMatchings()->get() as $tm) {
                foreach ($tm->$func()->get() as $transaction) {
                    $inTime = $this->isTransactionInTime($isDatedReport, $transaction->timestamp, $now);
                    $this->debug("tran acct : " . $transaction->account_id . " " . $account->id);
                    if ($inTime && $transaction->account_id == $account->id) {
                        $this->debug("vals: " . json_encode([$now, $transaction->toArray()]));
                        $value += $transaction->value;
                    }
                }
            }
        }
        $this->debug("{$name}: $value");
        return $value;
    }
    public function getMatchGrantedAsOf(Carbon $now, $isDatedReport = true): mixed
    {
        return $this->getMatchValueAsOf($now, $isDatedReport, 'transaction', "getMatchGrantedAsOf");
    }
    public function getMatchConsideredAsOf(Carbon $now, $isDatedReport = true): mixed
    {
        return $this->getMatchValueAsOf($now, $isDatedReport, 'referenceTransaction', "getMatchConsideredAsOf");
    }

    public function isTransactionInTime($isDatedReport, Carbon $timestamp, Carbon $now): bool
    {
        $inTime = !$isDatedReport || $now >= $timestamp;
        $this->debug("now: " . $now . " ts " . $timestamp . " pastonly " . $isDatedReport . " inTime " . $inTime . "\n");
        return $inTime;
    }

    public function isInPeriod(Carbon $now, $isDatedReport=false) {
        $mr = $this->matchingRule()->first();
        $ret = $now >= $mr->date_start && $now <= $mr->date_end;
        if ($isDatedReport) {
            $ret = $now >= $mr->date_start;
        }
//        $this->verbose = true;
        $this->debug("inPeriod: ".json_encode([
            $this->id, $this->matchingRule()->first()->id,
            "now", $now,
                "start", $mr->date_start,
                "end", $mr->date_end,
                $now >= $mr->date_start,
                $now <= $mr->date_end,
                "ret", $ret])
            );
        return $ret;
    }

    /**
     * @throws Exception
     */
    public function match(Transaction $tranToMatch) {
        // find unused amount
//        $this->verbose = false;
        $mr = $this->matchingRule()->first();

        // Per-rule opt-out for credit-line REP transactions.
        // See docs/credit_lines/matching_on_repayment.md (§"Proposed feature shape").
        // Default is TRUE (in principle, REPs match); trustees opt out per rule.
        if ($tranToMatch->type === TransactionExt::TYPE_REPAY && !$mr->applies_to_rep) {
            return 0;
        }

        if ($this->isInPeriod($tranToMatch->timestamp, false)) {
            $used = $this->getMatchConsideredAsOf($tranToMatch->timestamp, false);
            $possible = $mr->dollar_range_end - $mr->dollar_range_start;
            if ($used < $possible) {
                $account = $this->account()->first();

                // Wave-1a REPs are written with value=0 (cash leg deferred per
                // fund_cashflow.md). When the trustee wants matching to apply
                // to repayments, fall back to shares × share-value at the REP
                // timestamp so the match base is meaningful. Localized here to
                // keep the blast radius small (Option B in the investigation,
                // §Q3) — we explicitly do NOT mutate $transaction->value
                // globally in RepayService.
                $effectiveValue = (float) $tranToMatch->value;
                if ($effectiveValue == 0.0
                    && $tranToMatch->type === TransactionExt::TYPE_REPAY
                    && (float) $tranToMatch->shares > 0) {
                    $shareValue = $tranToMatch->account?->shareValueAsOf($tranToMatch->timestamp) ?? 0;
                    $effectiveValue = (float) $tranToMatch->shares * (float) $shareValue;
                }

                // I could have used the "used" value, but when is first time we gotta calculate
                $deposits = $account->depositedValueBetween($mr->date_start, $mr->date_end);
                $applicable = round($this->applicableValue($deposits, $effectiveValue), 2);
                $matchValue = round($applicable * ($mr->match_percent / 100.0), 2);
                $this->debug("match: " . json_encode([$used, $possible, $deposits, $applicable,
                            $effectiveValue, $tranToMatch->id, $matchValue]));
                if ($applicable > $effectiveValue) {
                    throw new Exception("Matching ({$matchValue}) more than the transaction value: ({$effectiveValue}) tran id " . $tranToMatch->id);
                }
                return $matchValue;
            }
        }
        return 0;
    }

    private function applicableValue($base, $value) {
        $mr = $this->matchingRule()->first();
        return max(0, // non negative
            min($base + $value, $mr->dollar_range_end) // cap max
            - max($base, $mr->dollar_range_start)); // move base up
    }

}
