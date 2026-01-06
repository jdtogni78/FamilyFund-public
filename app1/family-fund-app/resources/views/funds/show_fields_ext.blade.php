@php
    $summary = $api['summary'];
    $allocatedPercent = $summary['allocated_shares_percent'] ?? 0;
    $unallocatedPercent = $summary['unallocated_shares_percent'] ?? 0;
@endphp

<div class="row g-4">
    {{-- Fund Name & Date --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h4 class="card-title mb-3" style="color: #2563eb;">
                    <i class="fa fa-landmark me-2"></i>{{ $api['name'] }}
                </h4>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">As of:</span>
                    <span class="badge bg-secondary fs-6">{{ $api['as_of'] }}</span>
                </div>
                @isset($api['admin'])
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="text-muted">Role:</span>
                    <span class="badge bg-primary fs-6">ADMIN</span>
                </div>
                @endisset
            </div>
        </div>
    </div>

    {{-- Total Value --}}
    <div class="col-md-6">
        <div class="card h-100" style="border-left: 4px solid #2563eb;">
            <div class="card-body">
                <h6 class="text-muted mb-2">Total Value</h6>
                <h2 class="mb-3" style="color: #2563eb;">${{ number_format($summary['value'], 2) }}</h2>
                <div class="row text-center">
                    <div class="col-6">
                        <small class="text-muted d-block">Total Shares</small>
                        <strong>{{ number_format($summary['shares'], 2) }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Share Price</small>
                        <strong>${{ number_format($summary['share_value'], 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Allocated Shares --}}
    <div class="col-md-6">
        <div class="card h-100" style="border-left: 4px solid #16a34a;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="text-muted mb-0">Allocated</h6>
                    <span class="badge fs-6" style="background-color: #16a34a;">{{ number_format($allocatedPercent, 2) }}%</span>
                </div>
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted d-block">Shares</small>
                        <strong style="color: #16a34a;">{{ number_format($summary['allocated_shares'], 2) }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Value</small>
                        <strong style="color: #16a34a;">${{ number_format($summary['value'] - ($summary['unallocated_value'] ?? 0), 2) }}</strong>
                    </div>
                </div>
                {{-- Progress bar --}}
                <div class="progress mt-3" style="height: 8px;">
                    <div class="progress-bar" role="progressbar" style="width: {{ $allocatedPercent }}%; background-color: #16a34a;"
                         aria-valuenow="{{ $allocatedPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Unallocated Shares --}}
    <div class="col-md-6">
        <div class="card h-100" style="border-left: 4px solid #d97706;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="text-muted mb-0">Unallocated</h6>
                    <span class="badge fs-6" style="background-color: #d97706;">{{ number_format($unallocatedPercent, 2) }}%</span>
                </div>
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted d-block">Shares</small>
                        <strong style="color: #d97706;">{{ number_format($summary['unallocated_shares'], 2) }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Value</small>
                        <strong style="color: #d97706;">${{ number_format($summary['unallocated_value'] ?? 0, 2) }}</strong>
                    </div>
                </div>
                {{-- Progress bar --}}
                <div class="progress mt-3" style="height: 8px;">
                    <div class="progress-bar" role="progressbar" style="width: {{ $unallocatedPercent }}%; background-color: #d97706;"
                         aria-valuenow="{{ $unallocatedPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>
</div>
