{{-- Summary Stats Row --}}
@php
    $currentValue = $api['summary']['currentValue'];
    $dollarChange = $api['summary']['dollarChange'];
    $percentChange = $api['summary']['percentChange'];
    $isPositive = $dollarChange >= 0;
    $changeColor = $isPositive ? '#16a34a' : '#dc2626';
@endphp
<div class="card-body py-4" style="background: #f0fdfa;" id="summary-container">
    <div class="row text-center">
        <div class="col-md-4 mb-3 mb-md-0" style="border-right: 1px solid #99f6e4;">
            <div id="current-value" style="font-size: 2rem; font-weight: 700; color: #0d9488;">
                ${{ number_format($currentValue, 0) }}
            </div>
            <div class="text-muted text-uppercase small">Net Worth</div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0" style="border-right: 1px solid #99f6e4;">
            <div id="dollar-change" style="font-size: 2rem; font-weight: 700; color: {{ $changeColor }};">
                {{ $isPositive ? '+' : '' }}${{ number_format($dollarChange, 0) }}
            </div>
            <div class="text-muted text-uppercase small">{{ $api['periodLabel'] }} Change</div>
        </div>
        <div class="col-md-4">
            <div id="percent-change" style="font-size: 2rem; font-weight: 700; color: {{ $changeColor }};">
                {{ $isPositive ? '+' : '' }}{{ number_format($percentChange, 1) }}%
            </div>
            <div class="text-muted text-uppercase small">Return</div>
        </div>
    </div>
</div>
