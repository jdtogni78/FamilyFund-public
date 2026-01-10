<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('transactions.index') }}">Transactions</a>
        </li>
        <li class="breadcrumb-item active">{{ isset($transaction) ? 'Clone' : 'Create' }}</li>
    </ol>

    <div class="container-fluid">
        @include('coreui-templates.common.errors')
        @if (Session::has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i>{{ Session::get('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('transactions.preview') }}" id="transactionForm">
            @csrf

            <div class="row">
                <!-- Left Column - Main Form -->
                <div class="col-lg-8">
                    <!-- Account Selection Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <i class="fa fa-user me-2"></i>
                            <strong>Select Account</strong>
                        </div>
                        <div class="card-body">
                            @include('transactions.fields_account')
                        </div>
                    </div>

                    <!-- Transaction Details Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <i class="fa fa-exchange me-2"></i>
                            <strong>Transaction Details</strong>
                        </div>
                        <div class="card-body">
                            @include('transactions.fields_details')
                        </div>
                    </div>
                </div>

                <!-- Right Column - Summary -->
                <div class="col-lg-4">
                    <!-- Account Balance Card -->
                    <div class="card mb-4" id="balanceCard" style="display: none;">
                        <div class="card-header bg-info text-white">
                            <i class="fa fa-wallet me-2"></i>
                            <strong>Account Balance</strong>
                        </div>
                        <div class="card-body text-center">
                            <div class="text-muted small text-uppercase">Current Value</div>
                            <div class="fs-2 fw-bold text-primary" id="__account_balance_display">-</div>
                            <div class="text-muted" id="__account_shares_display">-</div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="text-muted small">Share Price</div>
                                    <div class="fw-bold" id="__share_price_display">-</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">As of Date</div>
                                    <div class="fw-bold" id="__as_of_date_display">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Preview Card -->
                    <div class="card mb-4" id="previewCard" style="display: none;">
                        <div class="card-header bg-success text-white">
                            <i class="fa fa-calculator me-2"></i>
                            <strong>Calculated Shares</strong>
                        </div>
                        <div class="card-body text-center">
                            <div class="text-muted small text-uppercase">Shares to Transfer</div>
                            <div class="fs-2 fw-bold" id="__shares_display">0.0000</div>
                            <input type="hidden" name="shares" id="shares" value="0">
                            <input type="hidden" name="__share_price" id="__share_price" value="0">
                        </div>
                    </div>

                    <!-- Submit Card -->
                    <div class="card border-primary">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fa fa-eye me-2"></i>Preview Transaction
                                </button>
                                <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

@include('transactions.fields_scripts')
</x-app-layout>
