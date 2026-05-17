<x-app-layout>
@section('content')
<ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('credit_lines.global_index') }}">Credit Lines</a></li>
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
                        $overdue = \Illuminate\Support\Carbon::parse($row->due_date)->lt($today);
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
                                    #{{ $line->id }}
                                </a>
                            @else
                                &mdash;
                            @endif
                        </td>
                        <td>{{ number_format($row->shares_due, 4) }}</td>
                        <td>
                            @if($row->status === 'late')
                                <span class="badge bg-danger">late</span>
                            @elseif($row->status === 'partial')
                                <span class="badge bg-warning text-dark">partial</span>
                            @elseif($row->status === 'paid')
                                <span class="badge bg-success">paid</span>
                            @elseif($row->status === 'cancelled')
                                <span class="badge bg-secondary text-decoration-line-through">cancelled</span>
                            @else
                                <span class="badge bg-secondary">scheduled</span>
                            @endif
                        </td>
                        <td>
                            @if($line && in_array($row->status, ['scheduled', 'partial', 'late']))
                            <a href="{{ route('credit_lines.payments.register_form', ['line' => $line->id, 'payment' => $row->id]) }}"
                               class="btn btn-sm btn-outline-success">Register payment</a>
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
