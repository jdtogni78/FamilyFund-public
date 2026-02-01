<x-app-layout>
    <ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('Dashboard') }}</li>
    </ol>

    <div class="container-fluid py-4">
        <div class="row g-4">
            <!-- Funds -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #0d9488, #0891b2);">
                        <h5 class="mb-0"><i class="fa fa-money me-2"></i>Funds</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="{{ route('funds.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-money text-teal-600 me-3" style="width: 20px;"></i>Funds
                            </a>
                            <a href="{{ route('portfolios.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-folder text-teal-600 me-3" style="width: 20px;"></i>Portfolios
                            </a>
                            <a href="{{ route('portfolioAssets.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-list text-teal-600 me-3" style="width: 20px;"></i>Portfolio Assets
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accounts -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #0d9488, #0891b2);">
                        <h5 class="mb-0"><i class="fa fa-bank me-2"></i>Accounts</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="{{ route('accounts.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-bank text-teal-600 me-3" style="width: 20px;"></i>Accounts
                            </a>
                            <a href="{{ route('goals.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-bullseye text-teal-600 me-3" style="width: 20px;"></i>Goals
                            </a>
                            <a href="{{ route('matchingRules.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-link text-teal-600 me-3" style="width: 20px;"></i>Matching Rules
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #0d9488, #0891b2);">
                        <h5 class="mb-0"><i class="fa fa-exchange-alt me-2"></i>Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="{{ route('transactions.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-money text-teal-600 me-3" style="width: 20px;"></i>Transactions
                            </a>
                            <a href="{{ route('accountBalances.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-balance-scale text-teal-600 me-3" style="width: 20px;"></i>Account Balances
                            </a>
                            <a href="{{ route('depositRequests.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-download text-teal-600 me-3" style="width: 20px;"></i>Deposit Requests
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trading -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #0d9488, #0891b2);">
                        <h5 class="mb-0"><i class="fa fa-exchange me-2"></i>Trading</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="{{ route('tradePortfolios.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-exchange text-teal-600 me-3" style="width: 20px;"></i>Trade Portfolios
                            </a>
                            <a href="{{ route('cashDeposits.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-download text-teal-600 me-3" style="width: 20px;"></i>Cash Deposits
                            </a>
                            <a href="{{ route('assets.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-line-chart text-teal-600 me-3" style="width: 20px;"></i>Assets
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #0d9488, #0891b2);">
                        <h5 class="mb-0"><i class="fa fa-file-text me-2"></i>Reports</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="{{ route('fundReports.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-file-text-o text-teal-600 me-3" style="width: 20px;"></i>Fund Reports
                            </a>
                            <a href="{{ route('accountReports.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-file-text-o text-teal-600 me-3" style="width: 20px;"></i>Account Reports
                            </a>
                            <a href="{{ route('scheduledJobs.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-clock-o text-teal-600 me-3" style="width: 20px;"></i>Scheduled Jobs
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin -->
            @if(auth()->user() && method_exists(auth()->user(), 'isSystemAdmin') && auth()->user()->isSystemAdmin())
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
                        <h5 class="mb-0"><i class="fa fa-shield me-2"></i>Admin</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="{{ route('operations.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-cogs text-red-600 me-3" style="width: 20px;"></i>Operations
                            </a>
                            <a href="{{ route('users.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-user text-red-600 me-3" style="width: 20px;"></i>Users
                            </a>
                            <a href="{{ route('emails.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fa fa-envelope text-red-600 me-3" style="width: 20px;"></i>Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
