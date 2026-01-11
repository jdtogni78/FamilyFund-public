<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('accountBalances.index') }}">Account Balances</a>
        </li>
        <li class="breadcrumb-item active">Balance #{{ $accountBalance->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-balance-scale me-2"></i>
                                <strong>Account Balance #{{ $accountBalance->id }}</strong>
                                @if($accountBalance->account)
                                    <span class="text-body-secondary ms-2">
                                        (<a href="{{ route('accounts.show', $accountBalance->account_id) }}">{{ $accountBalance->account->nickname }}</a>)
                                    </span>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('accountBalances.edit', $accountBalance->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('accountBalances.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('account_balances.show_fields')
                        </div>
                    </div>
                </div>
            </div>

            @if($accountBalance->transaction != null)
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-exchange-alt me-2"></i>
                                <strong>Transaction</strong>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('transactions.table', ['transactions' => [$accountBalance->transaction]])
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
