<div class="detail-list">
    <div class="detail-item">
        <span class="detail-label">Fund Name</span>
        <span class="detail-value">{{ $api['name'] }}</span>
    </div>
    <div class="detail-item">
        <span class="detail-label">Report Date</span>
        <span class="detail-value">{{ $api['as_of'] }}</span>
    </div>
    <div class="detail-item">
        <span class="detail-label">Total Shares</span>
        <span class="detail-value">{{ number_format($api['summary']['shares'], 2) }}</span>
    </div>
    <div class="detail-item">
        <span class="detail-label">Share Price</span>
        <span class="detail-value">${{ number_format($api['summary']['share_value'], 4) }}</span>
    </div>
    <div class="detail-item">
        <span class="detail-label">Allocated Shares</span>
        <span class="detail-value">
            {{ number_format($api['summary']['allocated_shares'], 2) }}
            <span class="text-muted text-sm">({{ number_format($api['summary']['allocated_shares_percent'], 1) }}%)</span>
        </span>
    </div>
    <div class="detail-item">
        <span class="detail-label">Unallocated Shares</span>
        <span class="detail-value">
            {{ number_format($api['summary']['unallocated_shares'], 2) }}
            <span class="text-muted text-sm">({{ number_format($api['summary']['unallocated_shares_percent'], 1) }}%)</span>
        </span>
    </div>
    <div class="detail-item">
        <span class="detail-label">Total Value</span>
        <span class="detail-value font-bold text-primary">${{ number_format($api['summary']['value'], 2) }}</span>
    </div>
</div>
