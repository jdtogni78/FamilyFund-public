<ul>
    <li>This goal is to hit 
        @if($goal->target_type == \App\Models\GoalExt::TARGET_TYPE_TOTAL)
            <strong>${{ number_format($goal->target_amount, 0) }}</strong> as account's value 
            (${{ number_format($goal->target_amount * $goal->target_pct, 0) }}/year as yield)
            @else
            <strong>${{ number_format($goal->target_amount, 0) }}/year</strong> as yield
            (${{ number_format($goal->target_amount / $goal->target_pct, 0) }} as account's value)
        @endif
        by <strong>{{ $goal->end_dt->format('Y-m-d') }}</strong>.
    </li>
    <li><strong>{{ number_format($goal->progress['period'][0]/365, 2) }}</strong> of <strong>{{ number_format($goal->progress['period'][1]/365, 2) }}</strong> years passed
    ({{ number_format($goal->progress['period'][2], 2) }}% of the time passed)</li>
    <li>Yield Considered: <strong>{{ $goal->target_pct * 100 }}%</strong></li>
    @php
    $func = function($goal, $type, $label, $as_of) {
        $as_of_str = $as_of ? ' by ' . $as_of : '';
        if ($goal->target_type == \App\Models\GoalExt::TARGET_TYPE_TOTAL) {
            return $label . ' value ' . $as_of_str . ': <strong>$' . number_format($goal->progress[$type]['value'], 0) . '</strong> - yield: <strong>$' . 
                number_format($goal->progress[$type]['value_4pct'], 0) . '/year</strong>';
        } else {
            return $label . ' yield ' . $as_of_str . ': <strong>$' . number_format($goal->progress[$type]['value_4pct'], 0) . '/year</strong> - value: <strong>$' . 
                   number_format($goal->progress[$type]['value'], 0) . '</strong>';
        }
    };
    @endphp
    <li>{!! $func($goal, 'start_value', 'Starting', $goal->start_dt->format('Y-m-d')) !!}</li>
    <li>{!! $func($goal, 'expected', 'Expected', $goal->as_of) !!}</li>
    <li>{!! $func($goal, 'current', 'Current', $goal->as_of) !!}</li>
    <li>
        <strong>
            @if($goal->progress['current']['value'] >= $goal->progress['expected']['value'])
                <span class="text-success">On track - (over by ${{ number_format($goal->progress['current']['value'] - $goal->progress['expected']['value'], 0) }})</span>
            @else
                <span class="text-danger">Not on track - (behind by ${{ number_format($goal->progress['expected']['value'] - $goal->progress['current']['value'], 0) }})</span>
            @endif
        </strong>
    </li>
</ul>