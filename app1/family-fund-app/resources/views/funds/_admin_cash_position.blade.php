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
    $loanedValue      = 0.0;
    $activeLines     = collect();

    try {
        $portfolioValue  = (float) $fund->valueAsOf($asOf);
        $loanedValue = (float) $fund->creditLineReceivableValueAsOf($asOf);

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
                <span class="text-muted">Loaned share value:</span>
                <strong>${{ number_format($loanedValue, 2) }}</strong>
            </div>
            <div class="col-md-4">
                <span class="text-muted">Fund size impact:</span>
                <strong>$0.00</strong>
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
                        <th class="text-end">Age</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($activeLines as $line)
                    @php
                        $oVal = $line->outstanding_shares * ($sharePrice ?? 0);
                        if ($line->origination_date) {
                            $orig = \Illuminate\Support\Carbon::parse($line->origination_date);
                            $days = (int) $orig->diffInDays(now());
                            $months = (int) $orig->diffInMonths(now());
                            $age = $days . ' d / ' . $months . ' mo';
                        } else {
                            $age = '—';
                        }
                        $nickname = $line->account?->nickname ?? ('account #' . $line->account_id);
                    @endphp
                    <tr>
                        <td>{{ $nickname }}</td>
                        <td class="text-end">{{ number_format($line->principal_shares, 4) }}</td>
                        <td class="text-end">{{ number_format($line->outstanding_shares, 4) }}</td>
                        <td class="text-end">${{ number_format($oVal, 2) }}</td>
                        <td class="text-end">{{ $age }}</td>
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
