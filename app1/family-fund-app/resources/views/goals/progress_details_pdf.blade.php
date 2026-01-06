<div style="background: #f8fafc; padding: 12px; border-radius: 6px; font-size: 12px;">
    <div style="margin-bottom: 8px;">
        <strong>Goal:</strong>
        @if($goal->target_type == \App\Models\GoalExt::TARGET_TYPE_TOTAL)
            Reach <strong class="text-primary">${{ number_format($goal->target_amount, 0) }}</strong> account value
            (yields ${{ number_format($goal->target_amount * $goal->target_pct, 0) }}/year)
        @else
            Reach <strong class="text-primary">${{ number_format($goal->target_amount, 0) }}/year</strong> yield
            (requires ${{ number_format($goal->target_amount / $goal->target_pct, 0) }} account value)
        @endif
        by <strong>{{ $goal->end_dt->format('M j, Y') }}</strong>
    </div>

    <table style="width: 100%; font-size: 11px;">
        <tr>
            <td style="width: 25%;">
                <strong>Time Progress:</strong><br>
                {{ number_format($goal->progress['period'][0]/365, 1) }} of {{ number_format($goal->progress['period'][1]/365, 1) }} years
                <span class="text-muted">({{ number_format($goal->progress['period'][2], 0) }}%)</span>
            </td>
            <td style="width: 25%;">
                <strong>Yield Rate:</strong><br>
                {{ $goal->target_pct * 100 }}%
            </td>
            <td style="width: 25%;">
                <strong>Expected:</strong><br>
                ${{ number_format($goal->progress['expected']['value'] ?? 0, 0) }}
            </td>
            <td style="width: 25%;">
                <strong>Current:</strong><br>
                @php
                    $currentValue = $goal->progress['current']['value'] ?? 0;
                    $expectedValue = $goal->progress['expected']['value'] ?? 0;
                    $diff = $currentValue - $expectedValue;
                @endphp
                <span class="{{ $diff >= 0 ? 'text-success' : 'text-danger' }}">
                    ${{ number_format($currentValue, 0) }}
                </span>
            </td>
        </tr>
    </table>

    <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0;">
        @if($diff >= 0)
            <span class="badge badge-success">✓ On Track</span>
            <span class="text-success">Ahead by ${{ number_format($diff, 0) }}</span>
        @else
            <span class="badge badge-danger">⚠ Behind</span>
            <span class="text-danger">Behind by ${{ number_format(abs($diff), 0) }}</span>
        @endif
    </div>
</div>
