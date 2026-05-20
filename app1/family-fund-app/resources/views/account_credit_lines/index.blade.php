<x-app-layout>
@section('content')
<ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('accounts.index') }}">Accounts</a></li>
    <li class="breadcrumb-item"><a href="{{ route('accounts.show', $account->id) }}">{{ $account->nickname }}</a></li>
    <li class="breadcrumb-item active">Loan Shares</li>
</ol>
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Loan Shares — {{ $account->nickname }}</strong>
            @if(auth()->user()?->is_admin())
            <a href="{{ route('credit_lines.create', ['account' => $account->id]) }}"
               class="btn btn-sm btn-primary">New loan share</a>
            @endif
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3"
                  action="{{ route('credit_lines.index', ['account' => $account->id]) }}">
                <div class="col-auto">
                    <input type="text" name="nickname" class="form-control form-control-sm"
                           value="{{ $nickname }}" placeholder="Filter by nickname">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                    @if($nickname !== '')
                        <a href="{{ route('credit_lines.index', ['account' => $account->id]) }}"
                           class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
            @if($lines->isEmpty())
                <p class="text-muted mb-0">No loan shares.</p>
            @else
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>#</th><th>Nickname</th><th>Status</th><th>Principal</th><th>Outstanding</th>
                        <th>Term</th><th>Frequency</th><th>Origination</th><th>Maturity</th><th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($lines as $line)
                    <tr>
                        <td>{{ $line->id }}</td>
                        <td>{{ $line->nickname }}</td>
                        <td>{{ $line->status }}</td>
                        <td>{{ number_format($line->principal_shares, 4) }}</td>
                        <td>{{ number_format($line->outstanding_shares, 4) }}</td>
                        <td>{{ $line->term_months }} mo</td>
                        <td>{{ $line->payment_frequency }}</td>
                        <td>{{ optional($line->origination_date)->format('Y-m-d') }}</td>
                        <td>{{ optional($line->maturity_date)->format('Y-m-d') }}</td>
                        <td><a href="{{ route('credit_lines.show', ['line' => $line->id]) }}"
                               class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
