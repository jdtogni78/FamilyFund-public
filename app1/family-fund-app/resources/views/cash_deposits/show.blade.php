<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('cashDeposits.index') }}">Cash Deposits</a>
        </li>
        <li class="breadcrumb-item active">Deposit #{{ $cashDeposit->id }}</li>
    </ol>

    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            @include('layouts.flash-messages')

            @php
                $unassigned = $cashDeposit->amount - $cashDeposit->depositRequests->sum('amount');
                $assignedPercent = $cashDeposit->amount > 0 ? (($cashDeposit->amount - $unassigned) / $cashDeposit->amount) * 100 : 0;
                $statusColors = [
                    'PENDING' => 'warning',
                    'DEPOSITED' => 'info',
                    'ALLOCATED' => 'primary',
                    'COMPLETED' => 'success',
                    'CANCELLED' => 'secondary',
                ];
                $statusColor = $statusColors[$cashDeposit->status] ?? 'secondary';
            @endphp

            <!-- Header Card -->
            <div class="card mb-4 border-{{ $statusColor }}">
                <div class="card-header bg-{{ $statusColor }} text-white d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa fa-money-bill-wave me-2"></i>
                        <strong>Cash Deposit</strong>
                        <span class="badge bg-light text-{{ $statusColor }} ms-2">{{ $cashDeposit->status_string() }}</span>
                    </div>
                    <div class="btn-group">
                        @if($unassigned > 0 && $cashDeposit->status != 'CANCELLED')
                            <a href="{{ route('cashDeposits.assign', [$cashDeposit->id]) }}" class="btn btn-light" title="Assign to accounts">
                                <i class="fa fa-user-plus me-1"></i>Assign
                            </a>
                        @endif
                        <a href="{{ route('cashDeposits.resend-email', [$cashDeposit->id]) }}" class="btn btn-outline-light" title="Resend Email">
                            <i class="fa fa-envelope me-1"></i>Resend
                        </a>
                        <a href="{{ route('cashDeposits.edit', [$cashDeposit->id]) }}" class="btn btn-outline-light" title="Edit">
                            <i class="fa fa-edit me-1"></i>Edit
                        </a>
                        <a href="{{ route('cashDeposits.index') }}" class="btn btn-outline-light">
                            <i class="fa fa-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Amount Display -->
                        <div class="col-md-4 text-center border-end">
                            <div class="text-muted small text-uppercase mb-1">Amount</div>
                            <div class="fs-1 fw-bold text-success">${{ number_format($cashDeposit->amount, 2) }}</div>
                            <div class="text-muted small">{{ $cashDeposit->date ? $cashDeposit->date->format('M j, Y') : 'No date' }}</div>
                        </div>

                        <!-- Account Info -->
                        <div class="col-md-4 text-center border-end">
                            <div class="text-muted small text-uppercase mb-1">Account</div>
                            <div class="fs-4 fw-bold">{{ $cashDeposit->account->nickname ?? 'N/A' }}</div>
                            @if($cashDeposit->account?->user)
                                <div class="text-muted small">{{ $cashDeposit->account->user->name }}</div>
                            @endif
                            @if($cashDeposit->account?->fund)
                                <span class="badge bg-secondary">{{ $cashDeposit->account->fund->name }}</span>
                            @endif
                        </div>

                        <!-- Allocation Status -->
                        <div class="col-md-4 text-center">
                            <div class="text-muted small text-uppercase mb-1">Allocation</div>
                            <div class="d-flex justify-content-center align-items-baseline gap-2">
                                <span class="fs-4 fw-bold text-{{ $unassigned == 0 ? 'success' : 'warning' }}">
                                    ${{ number_format($cashDeposit->amount - $unassigned, 2) }}
                                </span>
                                <span class="text-muted">/ ${{ number_format($cashDeposit->amount, 2) }}</span>
                            </div>
                            <div class="progress mt-2" style="height: 8px;">
                                <div class="progress-bar bg-{{ $unassigned == 0 ? 'success' : 'warning' }}"
                                     role="progressbar"
                                     style="width: {{ $assignedPercent }}%"
                                     aria-valuenow="{{ $assignedPercent }}"
                                     aria-valuemin="0"
                                     aria-valuemax="100">
                                </div>
                            </div>
                            @if($unassigned > 0)
                                <div class="text-danger small mt-1">
                                    <i class="fa fa-exclamation-circle me-1"></i>${{ number_format($unassigned, 2) }} unassigned
                                </div>
                            @else
                                <div class="text-success small mt-1">
                                    <i class="fa fa-check-circle me-1"></i>Fully allocated
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description Card (if exists) -->
            @if($cashDeposit->description)
            <div class="card mb-4">
                <div class="card-body">
                    <i class="fa fa-comment-alt text-muted me-2"></i>
                    <span class="text-muted">Description:</span>
                    {{ $cashDeposit->description }}
                </div>
            </div>
            @endif

            <!-- Deposit Requests -->
            @if($cashDeposit->depositRequests && $cashDeposit->depositRequests->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa fa-list me-2"></i>
                        <strong>Deposit Requests</strong>
                        <span class="badge bg-primary ms-2">{{ $cashDeposit->depositRequests->count() }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Account</th>
                                    <th class="text-end">Amount</th>
                                    <th>Status</th>
                                    <th>Transaction</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cashDeposit->depositRequests as $depositRequest)
                                <tr>
                                    <td>
                                        <strong>{{ $depositRequest->account->nickname ?? 'N/A' }}</strong>
                                        @if($depositRequest->account?->user)
                                            <br><small class="text-muted">{{ $depositRequest->account->user->name }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="text-success fw-bold">${{ number_format($depositRequest->amount, 2) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $drStatusColor = match($depositRequest->status) {
                                                'PENDING' => 'warning',
                                                'DEPOSITED' => 'info',
                                                'COMPLETED' => 'success',
                                                'CANCELLED' => 'secondary',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $drStatusColor }}">{{ $depositRequest->status_string() }}</span>
                                    </td>
                                    <td>
                                        @if($depositRequest->transaction_id)
                                            <a href="{{ route('transactions.show', $depositRequest->transaction_id) }}" class="text-decoration-none">
                                                #{{ $depositRequest->transaction_id }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('depositRequests.show', $depositRequest->id) }}" class="btn btn-outline-secondary" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ route('depositRequests.edit', $depositRequest->id) }}" class="btn btn-outline-secondary" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td class="fw-bold">Total</td>
                                    <td class="text-end fw-bold text-success">${{ number_format($cashDeposit->depositRequests->sum('amount'), 2) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Transactions -->
            @php
                $depTrans = [];
                foreach ($cashDeposit?->depositRequests as $dr) {
                    if ($dr->transaction) $depTrans[] = $dr->transaction;
                }
                $transactions = [];
                if ($cashDeposit->transaction?->id) {
                    $transactions[] = $cashDeposit->transaction;
                }
                if (count($depTrans) > 0) {
                    $transactions = array_merge($transactions, $depTrans);
                }
            @endphp

            @if(count($transactions) > 0)
            <div class="card mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fa fa-exchange-alt me-2"></i>
                        <strong>Transactions</strong>
                        <span class="badge bg-primary ms-2">{{ count($transactions) }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Account</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Shares</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $trans)
                                <tr>
                                    <td>
                                        <a href="{{ route('transactions.show', $trans->id) }}" class="text-decoration-none fw-bold">
                                            #{{ $trans->id }}
                                        </a>
                                    </td>
                                    <td>{{ $trans->timestamp ? $trans->timestamp->format('M j, Y') : '-' }}</td>
                                    <td>{{ $trans->account->nickname ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $trans->type == 'PUR' ? 'success' : ($trans->type == 'SAL' ? 'danger' : 'info') }}">
                                            {{ $trans->type }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-{{ $trans->value >= 0 ? 'success' : 'danger' }}">
                                            {{ $trans->value >= 0 ? '+' : '' }}${{ number_format($trans->value, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="text-{{ $trans->shares >= 0 ? 'success' : 'danger' }}">
                                            {{ $trans->shares >= 0 ? '+' : '' }}{{ number_format($trans->shares, 4) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $tStatusColor = match($trans->status) {
                                                'P' => 'warning',
                                                'C' => 'success',
                                                'X' => 'secondary',
                                                default => 'info'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $tStatusColor }}">{{ $trans->status }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Metadata -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <i class="fa fa-info-circle me-2"></i>
                    <strong>Details</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-muted small text-uppercase">Deposit ID</div>
                            <div class="fw-bold">#{{ $cashDeposit->id }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small text-uppercase">Transaction ID</div>
                            <div class="fw-bold">
                                @if($cashDeposit->transaction_id)
                                    <a href="{{ route('transactions.show', $cashDeposit->transaction_id) }}">#{{ $cashDeposit->transaction_id }}</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small text-uppercase">Created</div>
                            <div class="fw-bold">{{ $cashDeposit->created_at?->format('M j, Y H:i') ?? '-' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-muted small text-uppercase">Updated</div>
                            <div class="fw-bold">{{ $cashDeposit->updated_at?->format('M j, Y H:i') ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
