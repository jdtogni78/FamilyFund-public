@php
    $disb = $api['disbursable'];
    $performance = floatval($disb['performance'] ?? 0); // Already a percentage (e.g., 15.23 for 15.23%)
    $limit = floatval($disb['limit'] ?? 0); // Already a percentage
    $value = floatval($disb['value'] ?? 0);
    $yearDate = $disb['year'] ?? '';
    $yearNum = $yearDate ? \Carbon\Carbon::parse($yearDate)->format('Y') : '';
    $performanceColor = $performance >= 0 ? '#16a34a' : '#dc2626';
    $effectiveRate = max(0, min($limit, $performance)); // Both are percentages

    // Get market value for calculation display
    $accountBalance = $api['account']->balances['OWN'] ?? null;
    $marketValue = $accountBalance->market_value ?? 0;
@endphp

{{-- Dark header layout (matching other sections) --}}
<div class="card" style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
    <div class="card-header py-2" style="background: #1e293b; border: none;">
        <strong style="color: #ffffff; font-size: 13px; text-transform: uppercase;">
            <i class="fa fa-money-bill-wave me-2"></i>Disbursement Eligibility (Based on {{ $yearNum }} Performance)
        </strong>
    </div>
    <div class="card-body" style="background: #ffffff; padding: 20px;">
        <div class="row text-center">
            {{-- Eligible Value --}}
            <div class="col-md-4" style="border-right: 1px solid #e2e8f0;">
                <small class="d-block mb-1 text-muted text-uppercase" style="font-size: 11px;">Eligible Value</small>
                <h3 class="mb-1" style="color: #059669; font-weight: 700;">${{ number_format($value, 0) }}</h3>
                <small class="text-muted">{{ number_format($effectiveRate, 1) }}% of ${{ number_format($marketValue, 0) }}</small>
            </div>
            {{-- Previous Year Performance --}}
            <div class="col-md-4" style="border-right: 1px solid #e2e8f0;">
                <small class="d-block mb-1 text-muted text-uppercase" style="font-size: 11px;">{{ $yearNum }} Performance</small>
                <h3 class="mb-0" style="color: {{ $performanceColor }}; font-weight: 700;">
                    {{ $performance >= 0 ? '+' : '' }}{{ number_format($performance, 1) }}%
                </h3>
            </div>
            {{-- Annual Cap --}}
            <div class="col-md-4">
                <small class="d-block mb-1 text-muted text-uppercase" style="font-size: 11px;">Annual Cap</small>
                <h3 class="mb-0" style="color: #1e293b; font-weight: 700;">{{ number_format($limit, 0) }}%</h3>
            </div>
        </div>
    </div>
</div>
