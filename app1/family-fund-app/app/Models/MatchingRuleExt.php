<?php

namespace App\Models;

use App\Models\MatchingRule;

class MatchingRuleExt extends MatchingRule
{
    public static function ruleMap()
    {
        $recs = MatchingRule::all();
        $out = [null => 'Please Select Rule'];
        foreach ($recs as $row) {
            $out[$row['id']] = '($' . $row['dollar_range_start'] . ' - $' . $row['dollar_range_end'] . ') '
                . ' [' . substr($row['date_start'], 0, 10) . ' - ' . substr($row['date_end'], 0, 10) . '] '
                . $row['match_percent'] . '%';
        }
        return $out;
    }
}
