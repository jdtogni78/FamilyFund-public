<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('transactions.index') }}">Transactions</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('transactions.create_bulk') }}">Bulk Create</a>
        </li>
        <li class="breadcrumb-item active">Preview</li>
    </ol>

    <div class="container-fluid">
        @include('coreui-templates.common.errors')

        @php
            $totalValue = count($previews) * ($input['value'] ?? 0);
            $isDebit = ($input['value'] ?? 0) < 0;
        @endphp

        <!-- Summary Card -->
        <div class="card mb-4 border-{{ $isDebit ? 'danger' : 'success' }}">
            <div class="card-header bg-{{ $isDebit ? 'danger' : 'success' }} text-white">
                <i class="fa fa-{{ $isDebit ? 'arrow-up' : 'arrow-down' }} me-2"></i>
                <strong>Bulk {{ $isDebit ? 'Withdrawal' : 'Deposit' }} Preview</strong>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3 border-end">
                        <div class="text-muted small text-uppercase">Accounts</div>
                        <div class="fs-2 fw-bold text-primary">{{ count($previews) }}</div>
                    </div>
                    <div class="col-md-3 border-end">
                        <div class="text-muted small text-uppercase">Per Account</div>
                        <div class="fs-3 fw-bold">${{ number_format(abs($input['value'] ?? 0), 2) }}</div>
                    </div>
                    <div class="col-md-3 border-end">
                        <div class="text-muted small text-uppercase">Total Amount</div>
                        <div class="fs-2 fw-bold text-{{ $isDebit ? 'danger' : 'success' }}">
                            ${{ number_format(abs($totalValue), 2) }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small text-uppercase">Date</div>
                        <div class="fs-4 fw-bold">{{ \Carbon\Carbon::parse($input['timestamp'])->format('M j, Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Type and Status Info -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="alert alert-light border mb-0">
                    <strong>Type:</strong> {{ $api['typeMap'][$input['type']] ?? $input['type'] }}
                    &nbsp;|&nbsp;
                    <strong>Status:</strong> {{ $api['statusMap'][$input['status']] ?? $input['status'] }}
                    @if(!empty($input['flags']))
                        &nbsp;|&nbsp;
                        <strong>Flags:</strong> {{ $api['flagsMap'][$input['flags']] ?? $input['flags'] }}
                    @endif
                    @if(!empty($input['descr']))
                        <br><strong>Description:</strong> {{ $input['descr'] }}
                    @endif
                </div>
            </div>
        </div>

        <!-- Fund Shares Source (Admin Only) -->
        @if(isset($fundSharesData) && count($fundSharesData) > 0 && in_array(Auth::user()?->email, array_merge(config('familyfund.admin_emails'), ['claude@test.local'])))
        <div class="row mb-4">
            @foreach($fundSharesData as $fundId => $fundShares)
            @php
                $fundChange = $fundShares['change'];
                $colorClass = $fundChange >= 0 ? 'success' : 'warning';
                $textClass = $fundChange >= 0 ? 'white' : 'dark';
                $iconClass = $fundChange >= 0 ? 'plus' : 'minus';
            @endphp
            <div class="col-md-6 mb-3">
                <div class="card h-100 border-{{ $colorClass }}">
                    <div class="card-header bg-{{ $colorClass }} text-{{ $textClass }}">
                        <i class="fa fa-university me-2"></i>
                        <strong>Fund Shares Source</strong>
                        <span class="float-end">{{ $fundShares['fund_name'] }}</span>
                    </div>
                    <div class="card-body">
                        <!-- Change Badge at Top -->
                        <div class="text-center mb-3">
                            <span class="badge bg-{{ $colorClass }} text-{{ $textClass }} fs-5 px-4 py-2">
                                <i class="fa fa-{{ $iconClass }} me-1"></i>
                                {{ number_format(abs($fundChange), 4) }} SHARES
                            </span>
                        </div>

                        <!-- Before/After Flow -->
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <div class="text-center px-3">
                                <div class="text-muted small text-uppercase">Before</div>
                                <div class="fs-3 text-muted">{{ number_format($fundShares['before'], 4) }}</div>
                                <div class="small text-muted">unallocated</div>
                            </div>
                            <div class="mx-3">
                                <i class="fa fa-long-arrow-right fa-2x text-{{ $colorClass }}"></i>
                            </div>
                            <div class="text-center px-3">
                                <div class="text-muted small text-uppercase">After</div>
                                <div class="fs-3 fw-bold text-{{ $colorClass }}">
                                    {{ number_format($fundShares['after'], 4) }}
                                </div>
                                <div class="small fw-bold">unallocated</div>
                            </div>
                        </div>

                        <hr>
                        <div class="text-muted small text-center">
                            @if($fundChange < 0)
                                <i class="fa fa-arrow-right me-1 text-success"></i> Shares moving to Accounts
                            @else
                                <i class="fa fa-arrow-left me-1 text-warning"></i> Shares returning to Fund
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Transactions Table -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <i class="fa fa-list me-2"></i>
                <strong>Transactions to Create</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Account</th>
                                <th>User</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Share Price</th>
                                <th class="text-end">Shares</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($previews as $index => $preview)
                            @php
                                $account = $preview['account'];
                            @endphp
                            <tr>
                                <td class="text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $account->nickname }}</strong>
                                    @if($account->code)
                                        <span class="text-muted">({{ $account->code }})</span>
                                    @endif
                                </td>
                                <td>
                                    @if($account->user)
                                        {{ $account->user->name }}
                                        @if($account->email_cc)
                                            <br><small class="text-muted">{{ $account->email_cc }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <span class="text-{{ $isDebit ? 'danger' : 'success' }}">
                                        {{ $isDebit ? '-' : '+' }}${{ number_format(abs($preview['input']['value']), 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    ${{ number_format($preview['share_price'], 4) }}
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-{{ $preview['shares'] >= 0 ? 'success' : 'danger' }}">
                                        {{ $preview['shares'] >= 0 ? '+' : '' }}{{ number_format($preview['shares'], 4) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <td colspan="3" class="text-end fw-bold">Total:</td>
                                <td class="text-end fw-bold text-{{ $isDebit ? 'danger' : 'success' }}">
                                    {{ $isDebit ? '-' : '+' }}${{ number_format(abs($totalValue), 2) }}
                                </td>
                                <td></td>
                                <td class="text-end fw-bold">
                                    @php
                                        $totalShares = collect($previews)->sum('shares');
                                    @endphp
                                    <span class="badge bg-{{ $totalShares >= 0 ? 'success' : 'danger' }}">
                                        {{ $totalShares >= 0 ? '+' : '' }}{{ number_format($totalShares, 4) }}
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <form method="POST" action="{{ route('transactions.store_bulk') }}">
            @csrf
            <!-- Pass through all input data -->
            @foreach($input['account_ids'] as $accountId)
                <input type="hidden" name="account_ids[]" value="{{ $accountId }}">
            @endforeach
            <input type="hidden" name="type" value="{{ $input['type'] }}">
            <input type="hidden" name="status" value="{{ $input['status'] }}">
            <input type="hidden" name="value" value="{{ $input['value'] }}">
            <input type="hidden" name="timestamp" value="{{ $input['timestamp'] }}">
            <input type="hidden" name="flags" value="{{ $input['flags'] ?? '' }}">
            <input type="hidden" name="descr" value="{{ $input['descr'] ?? '' }}">

            <div class="d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-lg" onclick="window.history.back()">
                    <i class="fa fa-arrow-left me-2"></i>Go Back & Edit
                </button>
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fa fa-check me-2"></i>Confirm & Create {{ count($previews) }} Transactions
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
