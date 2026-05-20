{{--
    Fund loan-share exposure section (UC-15).
    Inputs:
      $fund – FundExt
--}}
@php
    $exposure = [];
    try {
        $exposure = app(\App\Services\CreditLine\Reporting\FundExposureBuilder::class)->forFund($fund);
    } catch (\Throwable $e) {
        $exposure = [];
    }
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-hand-holding-usd me-2"></i>
                <strong>Shares Loan Exposure</strong>
            </div>
            <div class="card-body">
                @if(!empty($exposure) && ($exposure['total_lines'] ?? 0) > 0)
                <div class="row">
                    <div class="col-md-3">
                        <small class="text-muted">Outstanding (shares)</small>
                        <div class="h5">{{ number_format($exposure['outstanding_shares'] ?? 0, 4) }}</div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Outstanding value</small>
                        <div class="h5">${{ number_format($exposure['outstanding_value'] ?? 0, 2) }}</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Active lines</small>
                        <div class="h5">{{ $exposure['active_line_count'] ?? 0 }}</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Behind plan</small>
                        <div class="h5 {{ ($exposure['behind_plan_count'] ?? 0) > 0 ? 'text-danger' : '' }}">
                            {{ $exposure['behind_plan_count'] ?? 0 }}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Total lines (lifetime)</small>
                        <div class="h5">{{ $exposure['total_lines'] ?? 0 }}</div>
                    </div>
                </div>
                <p class="text-muted small mb-0 mt-2">
                    Loaned shares are share-denominated, funded from unallocated shares, and reported separately from
                    available unallocated shares so they do not change fund size.
                </p>
                @else
                <p class="text-muted mb-0">No loan shares on this fund.</p>
                @endif
            </div>
        </div>
    </div>
</div>
