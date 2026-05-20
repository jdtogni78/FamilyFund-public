{{--
    Admin-only fund cash-position detail panel (Phase 4, deferred from Phase 3).
    Inputs:
      $fund – FundExt

    Renders ONLY for admin users (auth()->user()?->is_admin()).
    Visually subordinate to the main loan-share-exposure card — small heading,
    inline table, no big card chrome.
--}}
@php
    $isAdmin = (bool) (auth()->user()?->is_admin());
@endphp

@if($isAdmin)
@php
    $asOf = now()->toDateString();
    $portfolioValue   = 0.0;
    $receivableValue  = 0.0;
    $combinedValue    = 0.0;
    $activeLines     = collect();

    try {
        $portfolioValue  = (float) $fund->valueAsOf($asOf);
        $receivableValue = (float) $fund->creditLineReceivableValueAsOf($asOf);
        $combinedValue   = (float) $fund->valueWithCreditLinesAsOf($asOf);

        $accountIds = \App\Models\Account::where('fund_id', $fund->id)->pluck('id');
        $activeLines = \App\Models\AccountCreditLine::whereIn('account_id', $accountIds)
            ->where('status', 'active')
            ->with('account')
            ->get();

        $sharePrice = (float) $fund->shareValueAsOf($asOf);
    } catch (\Throwable $e) {
        $sharePrice = 0.0;
    }
@endphp

<div class="row mt-3">
    <div class="col-lg-12">
        <h6 class="text-muted mb-2">
            <i class="fa fa-user-shield me-1"></i> Admin: cash position
        </h6>
        <div class="row small mb-2">
            <div class="col-md-4">
                <span class="text-muted">Portfolio value (cash + assets):</span>
                <strong>${{ number_format($portfolioValue, 2) }}</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted">Credit-line receivable:</span>
                <strong>${{ number_format($receivableValue, 2) }}</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted">Combined value:</span>
                <strong>${{ number_format($combinedValue, 2) }}</strong>
            </div>
        </div>

        @if($activeLines->isNotEmpty())
            <table class="table table-sm table-borderless small mb-0">
                <thead class="text-muted">
                    <tr>
                        <th>Account</th>
                        <th class="text-end">Principal (sh)</th>
                        <th class="text-end">Outstanding (sh)</th>
                        <th class="text-end">Outstanding value</th>
                        <th class="text-end">Days since origination</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($activeLines as $line)
                    @php
                        $oVal = $line->outstanding_shares * ($sharePrice ?? 0);
                        $days = $line->origination_date
                            ? \Illuminate\Support\Carbon::parse($line->origination_date)->diffInDays(now())
                            : '—';
                        $nickname = $line->account?->nickname ?? ('account #' . $line->account_id);
                    @endphp
                    <tr>
                        <td>{{ $nickname }}</td>
                        <td class="text-end">{{ number_format($line->principal_shares, 4) }}</td>
                        <td class="text-end">{{ number_format($line->outstanding_shares, 4) }}</td>
                        <td class="text-end">${{ number_format($oVal, 2) }}</td>
                        <td class="text-end">{{ $days }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted small mb-0">No active loan shares.</p>
        @endif
    </div>
</div>
@endif
