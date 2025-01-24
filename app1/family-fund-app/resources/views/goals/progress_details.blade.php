<ul>
    <li>This goal is to hit <strong>
        @if($goal->target_type == \App\Models\GoalExt::TARGET_TYPE_TOTAL)
            ${{ number_format($goal->target_amount, 0) }}
        @else
            ${{ number_format($goal->target_amount, 0) }}/year
        @endif
        </strong>
        @if($goal->target_type == \App\Models\GoalExt::TARGET_TYPE_TOTAL)
            as account's value by
        @else
            of disposable income by
        @endif
        <strong>{{ $goal->end_dt->format('Y-m-d') }}</strong>.
    </li>
    <li><strong>{{ number_format($goal->progress['period'][0]/365, 2) }}</strong> of <strong>{{ number_format($goal->progress['period'][1]/365, 2) }}</strong> years passed
    ({{ number_format($goal->progress['period'][2], 2) }}% of the time passed)</li>
    <li>Yield Considered: <strong>{{ $goal->target_pct * 100 }}%</strong></li>
    <li>Starting Value as of {{ $goal->start_dt->format('Y-m-d') }}: 
        @if($goal->target_type == \App\Models\GoalExt::TARGET_TYPE_TOTAL)
            <strong>${{ number_format($goal->progress['start_value']['value'], 0) }}</strong> - yield: <strong>${{ number_format($goal->progress['start_value']['value_4pct'], 0) }}/year</strong>
        @else
            <strong>${{ number_format($goal->progress['start_value']['value_4pct'], 0) }}/year</strong> - account's value: <strong>${{ number_format($goal->progress['start_value']['value'], 0) }}</strong>
        @endif
    </li>
    <li>Expected Value as of {{ $goal->as_of }}: 
        @if($goal->target_type == \App\Models\GoalExt::TARGET_TYPE_TOTAL)
            <strong>${{ number_format($goal->progress['expected']['value'], 0) }}</strong> - yield: <strong>${{ number_format($goal->progress['expected']['value_4pct'], 0) }}/year</strong>
        @else
            <strong>${{ number_format($goal->progress['expected']['value_4pct'], 0) }}/year</strong> - account's value: <strong>${{ number_format($goal->progress['expected']['value'], 0) }}</strong>
        @endif
    </li>
    <li>Current Value as of {{ $goal->as_of }}: 
        @if($goal->target_type == \App\Models\GoalExt::TARGET_TYPE_TOTAL)
            <strong>${{ number_format($goal->progress['current']['value'], 0) }}</strong> - yield: <strong>${{ number_format($goal->progress['current']['value_4pct'], 0) }}/year</strong>
        @else
            <strong>${{ number_format($goal->progress['current']['value_4pct'], 0) }}/year</strong> - account's value: <strong>${{ number_format($goal->progress['current']['value'], 0) }}</strong>
        @endif
    </li>
</ul>