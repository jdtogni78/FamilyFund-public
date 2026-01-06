@php
    $summary = $api['summary'];
    $allocatedPercent = $summary['allocated_shares_percent'] ?? 0;
    $unallocatedPercent = $summary['unallocated_shares_percent'] ?? 0;
    $allocatedValue = $summary['value'] - ($summary['unallocated_value'] ?? 0);
@endphp

<div class="row g-4">
    {{-- Fund Name & Date --}}
    <div class="col-lg-3 col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h4 class="card-title mb-3" style="color: #2563eb;">
                    <i class="fa fa-landmark me-2"></i>{{ $api['name'] }}
                </h4>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">As of:</span>
                    <span class="badge bg-secondary">{{ $api['as_of'] }}</span>
                </div>
                @isset($api['admin'])
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="text-muted">Role:</span>
                    <span class="badge bg-primary">ADMIN</span>
                </div>
                @endisset
            </div>
        </div>
    </div>

    {{-- Total Value & Allocation Combined --}}
    <div class="col-lg-9 col-md-8">
        <div class="card h-100">
            <div class="card-body">
                {{-- Top: Total Value and Share Info --}}
                <div class="row mb-3">
                    <div class="col-md-4 text-center border-end">
                        <small class="text-muted d-block">Total Value</small>
                        <h3 class="mb-0" style="color: #2563eb;">${{ number_format($summary['value'], 2) }}</h3>
                    </div>
                    <div class="col-md-4 text-center border-end">
                        <small class="text-muted d-block">Total Shares</small>
                        <h4 class="mb-0">{{ number_format($summary['shares'], 2) }}</h4>
                    </div>
                    <div class="col-md-4 text-center">
                        <small class="text-muted d-block">Share Price</small>
                        <h4 class="mb-0">${{ number_format($summary['share_value'], 2) }}</h4>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="progress mb-3" style="height: 20px;">
                    <div class="progress-bar" role="progressbar"
                         style="width: {{ $allocatedPercent }}%; background-color: #16a34a;"
                         aria-valuenow="{{ $allocatedPercent }}" aria-valuemin="0" aria-valuemax="100">
                        @if($allocatedPercent > 15)
                            <span class="fw-bold small">{{ number_format($allocatedPercent, 1) }}%</span>
                        @endif
                    </div>
                    <div class="progress-bar" role="progressbar"
                         style="width: {{ $unallocatedPercent }}%; background-color: #d97706;"
                         aria-valuenow="{{ $unallocatedPercent }}" aria-valuemin="0" aria-valuemax="100">
                        @if($unallocatedPercent > 15)
                            <span class="fw-bold small">{{ number_format($unallocatedPercent, 1) }}%</span>
                        @endif
                    </div>
                </div>

                {{-- Allocation Details --}}
                <div class="row">
                    {{-- Allocated --}}
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background-color: rgba(22, 163, 74, 0.1);">
                            <div class="d-flex align-items-center">
                                <span style="width: 10px; height: 10px; background-color: #16a34a; border-radius: 2px; display: inline-block; margin-right: 8px;"></span>
                                <strong style="color: #16a34a;">Allocated</strong>
                                <span class="badge ms-2" style="background-color: #16a34a;">{{ number_format($allocatedPercent, 1) }}%</span>
                            </div>
                            <div class="text-end">
                                <span class="d-block small text-muted">{{ number_format($summary['allocated_shares'], 2) }} shares</span>
                                <strong style="color: #16a34a;">${{ number_format($allocatedValue, 2) }}</strong>
                            </div>
                        </div>
                    </div>

                    {{-- Unallocated --}}
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background-color: rgba(217, 119, 6, 0.1);">
                            <div class="d-flex align-items-center">
                                <span style="width: 10px; height: 10px; background-color: #d97706; border-radius: 2px; display: inline-block; margin-right: 8px;"></span>
                                <strong style="color: #d97706;">Unallocated</strong>
                                <span class="badge ms-2" style="background-color: #d97706;">{{ number_format($unallocatedPercent, 1) }}%</span>
                            </div>
                            <div class="text-end">
                                <span class="d-block small text-muted">{{ number_format($summary['unallocated_shares'], 2) }} shares</span>
                                <strong style="color: #d97706;">${{ number_format($summary['unallocated_value'] ?? 0, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
