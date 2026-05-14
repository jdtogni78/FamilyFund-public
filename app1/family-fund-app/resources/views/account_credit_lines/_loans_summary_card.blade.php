{{--
    Loans summary card (UC-49) for an account.
    Inputs:
      $loansSummary – output of LoansSummaryBuilder::forAccount()
--}}
@php
    $loansSummary = $loansSummary ?? [];
    $totalDisbursed = $loansSummary['total_disbursed_shares']  ?? 0;
    $totalRepaid    = $loansSummary['total_repaid_shares']     ?? 0;
    $netOutstanding = $loansSummary['net_outstanding_shares']  ?? 0;
    $nextDueDate    = $loansSummary['next_due_date']           ?? null;
    $nextDueShares  = $loansSummary['next_due_shares']         ?? 0;
    $activeCount    = $loansSummary['active_line_count']       ?? 0;
    $behindCount    = $loansSummary['behind_plan_count']       ?? 0;
    $outstandingVal = $loansSummary['outstanding_value']       ?? 0;
@endphp

<div class="card mb-3">
    <div class="card-header">
        <i class="fa fa-hand-holding-usd me-2"></i>
        <strong>Loans summary</strong>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted">Lifetime disbursed (shares)</small>
                <div class="h5">{{ number_format($totalDisbursed, 4) }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Lifetime repaid (shares)</small>
                <div class="h5">{{ number_format($totalRepaid, 4) }}</div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Net outstanding (shares)</small>
                <div class="h5">{{ number_format($netOutstanding, 4) }}</div>
                <small class="text-muted">≈ ${{ number_format($outstandingVal, 2) }} today</small>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Next due</small>
                <div class="h5">
                    {{ $nextDueDate ?? '—' }}
                    @if($nextDueShares > 0)
                        <small class="text-muted d-block">{{ number_format($nextDueShares, 4) }} shares</small>
                    @endif
                </div>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-6 small text-muted">
                Active lines: <strong>{{ $activeCount }}</strong>
                &middot; Behind plan: <strong class="{{ $behindCount > 0 ? 'text-danger' : '' }}">{{ $behindCount }}</strong>
            </div>
            <div class="col-md-6 small text-muted text-end">
                You owe {{ number_format($netOutstanding, 4) }} shares (currently valued at ${{ number_format($outstandingVal, 2) }}).
                The share count is what you owe back &mdash; it doesn't change with the market. The dollar value will move up or down with the fund's share price.
            </div>
        </div>
    </div>
</div>
