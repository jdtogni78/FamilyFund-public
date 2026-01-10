<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('transactions.index') }}">Transactions</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('transactions.create') }}">Create</a>
        </li>
        <li class="breadcrumb-item active">Preview</li>
    </ol>

    <div class="container-fluid">
        @include('coreui-templates.common.errors')

        @if(null !== $api1['transaction'])
            @php($transaction = $api1['transaction'])
            @php($balance = $transaction->balance)
            @php($isDebit = $transaction->value < 0)

            <div class="row">
                <!-- Transaction Summary Card -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100 border-{{ $isDebit ? 'danger' : 'success' }}">
                        <div class="card-header bg-{{ $isDebit ? 'danger' : 'success' }} text-white">
                            <i class="fa fa-{{ $isDebit ? 'arrow-up' : 'arrow-down' }} me-2"></i>
                            <strong>{{ $isDebit ? 'Withdrawal' : 'Deposit' }}</strong>
                            <span class="float-end">{{ $transaction->account->nickname }}</span>
                        </div>
                        <div class="card-body">
                            <!-- Account Info -->
                            @php($acct = $transaction->account)
                            <div class="bg-light rounded p-2 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $acct->nickname }}</strong>
                                        @if($acct->code)
                                            <span class="text-muted">({{ $acct->code }})</span>
                                        @endif
                                    </div>
                                    <span class="badge bg-secondary">{{ $acct->fund->name ?? 'Fund' }}</span>
                                </div>
                                @if($acct->user)
                                    <div class="small text-muted">
                                        <i class="fa fa-user me-1"></i>{{ $acct->user->name }}
                                        @if($acct->user->email)
                                            - {{ $acct->user->email }}
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Amount Badge -->
                            <div class="text-center mb-3">
                                <span class="badge bg-{{ $isDebit ? 'danger' : 'success' }} fs-5 px-4 py-2">
                                    {{ $isDebit ? '-' : '+' }}${{ number_format(abs($transaction->value), 2) }}
                                </span>
                            </div>

                            <!-- Details -->
                            <div class="d-flex justify-content-around text-center">
                                <div>
                                    <div class="text-muted small text-uppercase">Shares</div>
                                    <div class="fs-5 fw-bold text-{{ $transaction->shares >= 0 ? 'success' : 'danger' }}">
                                        {{ $transaction->shares >= 0 ? '+' : '' }}{{ number_format($transaction->shares, 4) }}
                                    </div>
                                    <div class="text-muted small">@ ${{ number_format($api1['shareValue'], 2) }}</div>
                                </div>
                                <div class="border-start ps-4">
                                    <div class="text-muted small text-uppercase">Date</div>
                                    <div class="fs-5 fw-bold">{{ $transaction->timestamp->format('M j') }}</div>
                                    <div class="text-muted small">{{ $transaction->timestamp->format('Y') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Share Balance Change Card -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <i class="fa fa-chart-pie me-2"></i>
                            <strong>Share Balance</strong>
                            <span class="text-muted ms-2">{{ $balance->account->nickname }}</span>
                        </div>
                        <div class="card-body">
                            @php($delta = $balance->shares - ($balance->previousBalance?->shares ?? 0))
                            @php($prevShares = $balance->previousBalance?->shares ?? 0)

                            <!-- Change Badge at Top -->
                            <div class="text-center mb-3">
                                <span class="badge bg-{{ $delta >= 0 ? 'success' : 'danger' }} fs-5 px-4 py-2">
                                    <i class="fa fa-{{ $delta >= 0 ? 'plus' : 'minus' }} me-1"></i>
                                    {{ number_format(abs($delta), 4) }} SHARES
                                </span>
                            </div>

                            <!-- Before/After Flow -->
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <div class="text-center px-3">
                                    <div class="text-muted small text-uppercase">Before</div>
                                    <div class="fs-3 text-muted">{{ number_format($prevShares, 4) }}</div>
                                    <div class="small text-muted">shares</div>
                                </div>
                                <div class="mx-3">
                                    <i class="fa fa-long-arrow-right fa-2x text-{{ $delta >= 0 ? 'success' : 'danger' }}"></i>
                                </div>
                                <div class="text-center px-3">
                                    <div class="text-muted small text-uppercase">After</div>
                                    <div class="fs-3 fw-bold text-{{ $delta >= 0 ? 'success' : 'danger' }}">
                                        {{ number_format($balance->shares, 4) }}
                                    </div>
                                    <div class="small fw-bold">shares</div>
                                </div>
                            </div>

                            <hr>
                            <div class="text-muted small text-center">
                                <i class="fa fa-calendar me-1"></i>
                                Effective: {{ $balance->start_dt }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fund Shares Source Card -->
            @if(isset($api1['fundShares']))
            @php($fundShares = $api1['fundShares'])
            @php($fundChange = $fundShares['change'])
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100 border-{{ $fundChange >= 0 ? 'success' : 'warning' }}">
                        <div class="card-header bg-{{ $fundChange >= 0 ? 'success' : 'warning' }} text-{{ $fundChange >= 0 ? 'white' : 'dark' }}">
                            <i class="fa fa-university me-2"></i>
                            <strong>Fund Shares Source</strong>
                            <span class="float-end">{{ $fundShares['fund_name'] }}</span>
                        </div>
                        <div class="card-body">
                            <!-- Change Badge at Top -->
                            <div class="text-center mb-3">
                                <span class="badge bg-{{ $fundChange >= 0 ? 'success' : 'warning' }} text-{{ $fundChange >= 0 ? 'white' : 'dark' }} fs-5 px-4 py-2">
                                    <i class="fa fa-{{ $fundChange >= 0 ? 'plus' : 'minus' }} me-1"></i>
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
                                    <i class="fa fa-long-arrow-right fa-2x text-{{ $fundChange >= 0 ? 'success' : 'warning' }}"></i>
                                </div>
                                <div class="text-center px-3">
                                    <div class="text-muted small text-uppercase">After</div>
                                    <div class="fs-3 fw-bold text-{{ $fundChange >= 0 ? 'success' : 'warning' }}">
                                        {{ number_format($fundShares['after'], 4) }}
                                    </div>
                                    <div class="small fw-bold">unallocated</div>
                                </div>
                            </div>

                            <hr>
                            <div class="text-muted small text-center">
                                @if($fundChange < 0)
                                    <i class="fa fa-arrow-right me-1 text-success"></i> Shares moving to Account
                                @else
                                    <i class="fa fa-arrow-left me-1 text-warning"></i> Shares returning to Fund
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Matching Transactions -->
            @if(null !== $api1['matches'] && count($api1['matches']) > 0)
            <div class="card mb-4 border-purple">
                <div class="card-header" style="background-color: #9333ea; color: white;">
                    <i class="fa fa-gift me-2"></i>
                    <strong>Matching Contributions</strong>
                </div>
                <div class="card-body">
                    @foreach($api1['matches'] as $matchTrans)
                    @php($matchBalance = $matchTrans->balance)
                    <div class="row align-items-center {{ !$loop->last ? 'border-bottom pb-3 mb-3' : '' }}">
                        <div class="col-md-4">
                            <div class="fw-bold">{{ $matchTrans->descr }}</div>
                            <div class="text-success fs-5">+${{ number_format($matchTrans->value, 2) }}</div>
                        </div>
                        <div class="col-md-4 text-center">
                            <span class="badge bg-success">+{{ number_format($matchTrans->shares, 4) }} shares</span>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center justify-content-end">
                                <span class="text-muted me-2">{{ number_format($matchBalance->previousBalance?->shares ?? 0, 2) }}</span>
                                <i class="fa fa-arrow-right text-success mx-2"></i>
                                <span class="fw-bold text-success">{{ number_format($matchBalance->shares, 2) }}</span>
                            </div>
                            <div class="text-muted small text-end">{{ $matchBalance->account->nickname }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Projected Account Value Card -->
            @isset($api1['shares_today'])
            @php($prevShares = $balance->previousBalance?->shares ?? 0)
            @php($prevValue = $prevShares * $api1['share_value_today'])
            @php($valueDelta = $api1['value_today'] - $prevValue)
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <i class="fa fa-wallet me-2"></i>
                            <strong>Projected Account Value</strong>
                            <span class="text-muted ms-2">{{ $api1['today']->format('M j, Y') }}</span>
                        </div>
                        <div class="card-body">
                            <!-- Value Change Badge -->
                            <div class="text-center mb-3">
                                <span class="badge bg-{{ $valueDelta >= 0 ? 'success' : 'danger' }} fs-5 px-4 py-2">
                                    {{ $valueDelta >= 0 ? '+' : '-' }}${{ number_format(abs($valueDelta), 2) }}
                                </span>
                            </div>

                            <!-- Before/After Value -->
                            <div class="d-flex justify-content-around text-center">
                                <div>
                                    <div class="text-muted small text-uppercase">Before</div>
                                    <div class="fs-5 text-muted">${{ number_format($prevValue, 2) }}</div>
                                </div>
                                <div>
                                    <i class="fa fa-long-arrow-right fa-lg text-{{ $valueDelta >= 0 ? 'success' : 'danger' }}"></i>
                                </div>
                                <div>
                                    <div class="text-muted small text-uppercase">After</div>
                                    <div class="fs-5 fw-bold text-{{ $valueDelta >= 0 ? 'success' : 'danger' }}">${{ number_format($api1['value_today'], 2) }}</div>
                                </div>
                            </div>

                            <hr>
                            <div class="d-flex justify-content-between small text-muted">
                                <span><i class="fa fa-chart-line me-1"></i>${{ number_format($api1['share_value_today'], 2) }}/share</span>
                                <span>{{ number_format($api1['shares_today'], 4) }} shares</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endisset

            <!-- Fund Cash Position -->
            @if(null !== $api1['fundCash'])
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <i class="fa fa-university me-2"></i>
                    <strong>Fund Cash Position</strong>
                </div>
                <div class="card-body">
                    @php($cashDelta = $api1['fundCash'][0]['position'] - $api1['fundCash'][1])
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="text-center">
                            <div class="text-muted small">Before</div>
                            <div class="fs-5">${{ number_format($api1['fundCash'][1], 2) }}</div>
                        </div>
                        <div class="mx-4">
                            <i class="fa fa-arrow-right fa-lg text-{{ $cashDelta >= 0 ? 'success' : 'danger' }}"></i>
                        </div>
                        <div class="text-center">
                            <div class="text-muted small">After</div>
                            <div class="fs-5 fw-bold text-{{ $cashDelta >= 0 ? 'success' : 'danger' }}">
                                ${{ number_format($api1['fundCash'][0]['position'], 2) }}
                            </div>
                        </div>
                        <div class="ms-4">
                            <span class="badge bg-{{ $cashDelta >= 0 ? 'success' : 'danger' }}">
                                {{ $cashDelta >= 0 ? '+' : '' }}${{ number_format($cashDelta, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Action Buttons -->
            @php($transaction = $api1['transaction'])
            @if($transaction->id !== null)
            <form method="POST" action="{{ route('transactions.process_pending', $transaction->id) }}">
            @else
            <form method="POST" action="{{ route('transactions.store') }}">
            @endif
                @csrf
                @include('transactions.preview_fields')
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <button type="button" class="btn btn-outline-secondary btn-lg" onclick="window.history.back()">
                        <i class="fa fa-arrow-left me-2"></i>Go Back & Edit
                    </button>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fa fa-check me-2"></i>Confirm & Save Transaction
                    </button>
                </div>
            </form>
        @else
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle me-2"></i>
                No transaction data available to preview.
            </div>
        @endif
    </div>
</x-app-layout>
