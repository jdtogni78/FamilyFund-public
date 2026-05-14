<x-app-layout>
@section('content')
<ol class="breadcrumb">
    <li class="breadcrumb-item active">Credit-line match review</li>
</ol>
<div class="container-fluid">
    <div class="card">
        <div class="card-header"><strong>Flagged transactions</strong></div>
        <div class="card-body">
            @if($flagged->isEmpty())
                <p class="text-muted mb-0">No transactions need review.</p>
            @else
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Tx #</th><th>Date</th><th>Type</th><th>Account</th>
                        <th>Shares</th><th>Status</th><th>Resolve to line</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($flagged as $tran)
                    @php
                        $candidates = \App\Models\AccountCreditLine::where('account_id', $tran->account_id)
                            ->where('status', 'active')
                            ->get();
                    @endphp
                    <tr>
                        <td>{{ $tran->id }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($tran->timestamp)->format('Y-m-d') }}</td>
                        <td>{{ $tran->type }}</td>
                        <td>{{ $tran->account_id }}</td>
                        <td>{{ number_format($tran->shares, 4) }}</td>
                        <td><span class="badge bg-warning">{{ $tran->credit_line_match_status }}</span></td>
                        <td>
                            <form method="POST"
                                  action="{{ route('credit_lines.resolve', ['transaction' => $tran->id]) }}"
                                  class="d-flex gap-2">
                                @csrf
                                <input type="hidden" name="transaction_id" value="{{ $tran->id }}">
                                <select name="account_credit_line_id" class="form-select form-select-sm">
                                    @foreach($candidates as $line)
                                        <option value="{{ $line->id }}">
                                            #{{ $line->id }} — out {{ number_format($line->outstanding_shares, 4) }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Resolve</button>
                            </form>
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
