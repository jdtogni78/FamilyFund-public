{{--
    Goals Progress Summary - Compact row layout
    Used in both PDF and Web views for the main goals overview section

    Required: $goal (with ->progress array populated)
    Optional: $format ('web'|'pdf', defaults to 'web')
--}}
@php
    $format = $format ?? 'web';
    $isPdf = $format === 'pdf';

    $currentPct = $goal->progress['current']['completed_pct'] ?? 0;
    $expectedPct = $goal->progress['expected']['completed_pct'] ?? 0;
    $currentValue = $goal->progress['current']['value'] ?? 0;
    $expectedValue = $goal->progress['expected']['value'] ?? 0;
    $targetValue = $goal->progress['current']['final_value'] ?? $goal->target_amount;

    $diff = $currentValue - $expectedValue;
    $isOnTrack = $diff >= 0;
    $progressColor = $isOnTrack ? '#16a34a' : '#d97706';
    $badgeBg = $isOnTrack ? '#dcfce7' : '#fef2f2';
    $badgeColor = $isOnTrack ? '#16a34a' : '#dc2626';
@endphp

@if($isPdf)
{{-- PDF Layout --}}
<table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: {{ $loop->last ?? false ? '0' : '16px' }};">
    <tr>
        <td>
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="50%">
                        <div style="font-weight: 700; color: #1e40af; font-size: 14px;">{{ $goal->name }}</div>
                        <div style="font-size: 11px; color: #64748b;">Target: ${{ number_format($targetValue, 0) }} by {{ $goal->end_dt->format('M Y') }}</div>
                    </td>
                    <td width="25%" align="center">
                        <span style="font-size: 20px; font-weight: 700; color: {{ $progressColor }};">{{ number_format($currentPct, 0) }}%</span>
                    </td>
                    <td width="25%" align="right">
                        <span style="background: {{ $badgeBg }}; color: {{ $badgeColor }}; padding: 4px 10px; border-radius: 4px; font-weight: 600; font-size: 11px;">
                            ${{ number_format(abs($diff), 0) }} {{ $isOnTrack ? 'ahead' : 'behind' }}
                        </span>
                    </td>
                </tr>
            </table>
            {{-- Progress Bar --}}
            <div style="background: #e2e8f0; border-radius: 4px; height: 8px; margin-top: 8px; overflow: hidden;">
                <div style="background: {{ $progressColor }}; height: 100%; width: {{ min(100, $currentPct) }}%; border-radius: 4px;"></div>
            </div>
        </td>
    </tr>
</table>
@else
{{-- Web Layout --}}
<div class="mb-3 {{ $loop->last ?? false ? 'mb-0' : '' }}">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
            <div style="font-weight: 700; color: #1e40af; font-size: 14px;">{{ $goal->name }}</div>
            <div class="small text-muted">Target: ${{ number_format($targetValue, 0) }} by {{ $goal->end_dt->format('M Y') }}</div>
        </div>
        <div class="d-flex align-items-center">
            <span style="font-size: 1.25rem; font-weight: 700; color: {{ $progressColor }};" class="me-2">
                {{ number_format($currentPct, 0) }}%
            </span>
            <span class="px-2 py-1 rounded small" style="background: {{ $badgeBg }}; color: {{ $badgeColor }}; font-weight: 600;">
                ${{ number_format(abs($diff), 0) }} {{ $isOnTrack ? 'ahead' : 'behind' }}
            </span>
        </div>
    </div>
    {{-- Progress Bar --}}
    <div class="progress" style="height: 8px; background: #e2e8f0;">
        <div class="progress-bar" role="progressbar"
             style="width: {{ min(100, $currentPct) }}%; background-color: {{ $progressColor }};"
             aria-valuenow="{{ $currentPct }}" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
</div>
@endif
