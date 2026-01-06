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
                    <i class="fa fa-landmark mr-2"></i>{{ $api['name'] }}
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
                <div class="d-flex align-items-center justify-content-between p-2 rounded mb-3" style="background-color: #2563eb;">
                    <div class="d-flex align-items-center">
                        <strong style="color: #ffffff;">Total</strong>
                        <span class="badge ml-2" style="background-color: #ffffff; color: #2563eb;">${{ number_format($summary['share_value'], 2) }}/share</span>
                    </div>
                    <div class="text-end">
                        <span class="d-block small" style="color: rgba(255,255,255,0.8);">{{ number_format($summary['shares'], 2) }} shares</span>
                        <strong style="color: #ffffff;">${{ number_format($summary['value'], 2) }}</strong>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="progress mb-3" style="height: 20px;">
                    <div class="progress-bar" role="progressbar"
                         style="width: {{ $allocatedPercent }}%; background-color: #16a34a;"
                         aria-valuenow="{{ $allocatedPercent }}" aria-valuemin="0" aria-valuemax="100">
                        @if($allocatedPercent > 8)
                            <span style="font-weight: bold;">{{ number_format($allocatedPercent, 1) }}%</span>
                        @endif
                    </div>
                    <div class="progress-bar" role="progressbar"
                         style="width: {{ $unallocatedPercent }}%; background-color: #d97706;"
                         aria-valuenow="{{ $unallocatedPercent }}" aria-valuemin="0" aria-valuemax="100">
                        @if($unallocatedPercent > 8)
                            <span style="font-weight: bold;">{{ number_format($unallocatedPercent, 1) }}%</span>
                        @endif
                    </div>
                </div>

                {{-- Allocation Details --}}
                <div class="row">
                    {{-- Allocated --}}
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background-color: rgba(22, 163, 74, 0.1); border-left: 4px solid #16a34a;">
                            <div class="d-flex align-items-center">
                                <strong style="color: #16a34a;">Allocated</strong>
                                <span class="badge ml-2" style="background-color: #16a34a;">{{ number_format($allocatedPercent, 1) }}%</span>
                            </div>
                            <div class="text-end">
                                <span class="d-block small text-muted">{{ number_format($summary['allocated_shares'], 2) }} shares</span>
                                <strong style="color: #16a34a;">${{ number_format($allocatedValue, 2) }}</strong>
                            </div>
                        </div>
                    </div>

                    {{-- Unallocated --}}
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background-color: rgba(217, 119, 6, 0.1); border-left: 4px solid #d97706;">
                            <div class="d-flex align-items-center">
                                <strong style="color: #d97706;">Unallocated</strong>
                                <span class="badge ml-2" style="background-color: #d97706;">{{ number_format($unallocatedPercent, 1) }}%</span>
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
