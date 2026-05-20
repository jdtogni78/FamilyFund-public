<x-app-layout>
@section('content')
<ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('credit_lines.global_index') }}">Loan Shares</a></li>
    <li class="breadcrumb-item active">Receivables</li>
</ol>
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <strong>Receivables — all accounts</strong>
                <span class="text-muted small">
                    @if($status === 'open')
                        (scheduled / partial / late)
                    @elseif($status === 'all')
                        (all statuses)
                    @else
                        ({{ $status }})
                    @endif
                </span>
            </span>
            <form method="GET" class="d-flex align-items-center gap-2 mb-0">
                <label for="fund_id" class="text-muted small mb-0">Fund</label>
                <select name="fund_id" id="fund_id" class="form-select form-select-sm"
                        onchange="this.form.submit()" style="width:auto">
                    <option value="">All funds</option>
                    @foreach($funds as $fund)
                        <option value="{{ $fund->id }}" @selected((string) $fundId === (string) $fund->id)>{{ $fund->name }}</option>
                    @endforeach
                </select>

                <label for="account_id" class="text-muted small mb-0">Account</label>
                <select name="account_id" id="account_id" class="form-select form-select-sm"
                        onchange="this.form.submit()" style="width:auto">
                    <option value="">All accounts</option>
                    @foreach($accounts as $acctOpt)
                        <option value="{{ $acctOpt->id }}" @selected((string) $accountId === (string) $acctOpt->id)>{{ $acctOpt->nickname }}</option>
                    @endforeach
                </select>

                <label for="nickname" class="text-muted small mb-0">Nickname</label>
                <input type="text" name="nickname" id="nickname"
                       class="form-control form-control-sm" style="width:auto"
                       value="{{ $nickname }}" placeholder="Filter by nickname"
                       onchange="this.form.submit()">

                <label for="status" class="text-muted small mb-0">Status</label>
                <select name="status" id="status" class="form-select form-select-sm"
                        onchange="this.form.submit()" style="width:auto">
                    <option value="open" @selected($status === 'open')>Open</option>
                    <option value="all" @selected($status === 'all')>All</option>
                    @foreach($statusOptions as $opt)
                        <option value="{{ $opt }}" @selected($status === $opt)>{{ ucfirst($opt) }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="card-body">
            @if($payments->isEmpty())
                <p class="text-muted mb-0">No receivables for this filter.</p>
            @else
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Due date</th><th>Account</th><th>Line</th>
                        <th>Shares due</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($payments as $row)
                    @php
                        $line = $row->creditLine;
                        $acct = $line?->account;
                        $openRow = in_array($row->status, ['scheduled', 'partial', 'late'], true);
                        $overdue = $openRow && \Illuminate\Support\Carbon::parse($row->due_date)->lt($today);
                    @endphp
                    <tr @class(['table-danger' => $row->status === 'late' || $overdue])>
                        <td>{{ \Illuminate\Support\Carbon::parse($row->due_date)->format('Y-m-d') }}</td>
                        <td>
                            @if($acct)
                                <a href="{{ route('accounts.show', $acct->id) }}">{{ $acct->nickname }}</a>
                            @else
                                &mdash;
                            @endif
                        </td>
                        <td>
                            @if($line)
                                <a href="{{ route('credit_lines.show', ['line' => $line->id]) }}">
                                    #{{ $line->id }} — {{ $line->nickname }}
                                </a>
                            @else
                                &mdash;
                            @endif
                        </td>
                        <td>{{ number_format($row->shares_due, 4) }}</td>
                        <td>
                            @include('account_credit_lines._payment_status_badge', ['status' => $row->status])
                        </td>
                        <td class="text-end">
                            @if($line)
                                @include('account_credit_lines._payment_row_actions', ['line' => $line, 'row' => $row])
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
