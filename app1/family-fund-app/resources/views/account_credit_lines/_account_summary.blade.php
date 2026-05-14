@php
    use App\Models\AccountCreditLine;
    use App\Models\TransactionExt;
    use App\Services\CreditLine\Reporting\LoansSummaryBuilder;

    $lines = AccountCreditLine::where('account_id', $account->id)
        ->orderByDesc('id')
        ->get();

    $flaggedCount = TransactionExt::where('account_id', $account->id)
        ->whereIn('credit_line_match_status', [
            TransactionExt::MATCH_STATUS_AMBIGUOUS,
            TransactionExt::MATCH_STATUS_UNMATCHED,
        ])
        ->count();

    $totalOutstanding = $lines->sum('outstanding_shares');
    $totalPrincipal   = $lines->sum('principal_shares');
    $totalRepaid      = max(0.0, $totalPrincipal - $totalOutstanding);

    $isAdmin = (bool) (auth()->user()?->is_admin());

    $loansSummary = [];
    if ($lines->isNotEmpty()) {
        try {
            $loansSummary = app(LoansSummaryBuilder::class)->forAccount($account);
        } catch (\Throwable $e) {
            $loansSummary = [];
        }
    }
@endphp

@if($lines->isNotEmpty() && !empty($loansSummary))
<div class="row">
    <div class="col-lg-12">
        @include('account_credit_lines._loans_summary_card', ['loansSummary' => $loansSummary])
    </div>
</div>
@endif

@if($flaggedCount > 0)
<div class="row">
    <div class="col-lg-12">
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
            <div>
                <i class="fa fa-exclamation-triangle me-2"></i>
                <strong>{{ $flaggedCount }}</strong> transaction(s) on this account need
                credit-line review (ambiguous or unmatched).
            </div>
            <a href="{{ route('credit_lines.resolve_index') }}" class="btn btn-sm btn-warning">
                Review
            </a>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fa fa-credit-card me-2"></i>
                    <strong>Credit Lines</strong>
                    <span class="badge bg-primary ms-2">{{ $lines->count() }}</span>
                </div>
                <div>
                    <a href="{{ route('credit_lines.index', ['account' => $account->id]) }}"
                       class="btn btn-sm btn-secondary">View all</a>
                    @if($isAdmin)
                    <a href="{{ route('credit_lines.create', ['account' => $account->id]) }}"
                       class="btn btn-sm btn-primary">
                        <i class="fa fa-plus me-1"></i> New credit line
                    </a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <small class="text-muted">Total disbursed (shares)</small>
                        <div class="h5">{{ number_format($totalPrincipal, 4) }}</div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Total repaid (shares)</small>
                        <div class="h5">{{ number_format($totalRepaid, 4) }}</div>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Net outstanding (shares)</small>
                        <div class="h5">{{ number_format($totalOutstanding, 4) }}</div>
                    </div>
                </div>

                @if($lines->isNotEmpty())
                <p class="small text-muted mb-2">
                    Outstanding values below are <strong>share-denominated</strong> &mdash; the count is what you owe back.
                    The dollar value moves with the fund's share price.
                </p>
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Status</th>
                            <th>Principal</th>
                            <th>Outstanding</th>
                            <th>Term</th>
                            <th>Origination</th>
                            <th>Maturity</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($lines as $line)
                        <tr>
                            <td>{{ $line->id }}</td>
                            <td>{{ $line->status }}</td>
                            <td>{{ number_format($line->principal_shares, 4) }}</td>
                            <td>{{ number_format($line->outstanding_shares, 4) }}</td>
                            <td>{{ $line->term_months }} mo</td>
                            <td>{{ optional($line->origination_date)->format('Y-m-d') }}</td>
                            <td>{{ optional($line->maturity_date)->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}"
                                   class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted mb-0">No credit lines for this account.</p>
                @endif
            </div>
        </div>
    </div>
</div>
