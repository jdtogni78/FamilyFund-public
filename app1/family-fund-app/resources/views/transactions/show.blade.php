<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('transactions.index') }}">Transactions</a>
        </li>
        <li class="breadcrumb-item active">Transaction #{{ $transaction->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')

            <!-- Action Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Transaction Details</h4>
                <div class="d-flex flex-wrap" style="gap: 4px;">
                    @if($transaction->status == \App\Models\TransactionExt::STATUS_PENDING)
                        <a href="{{ route('transactions.preview_pending', [$transaction->id]) }}" class="btn btn-sm btn-warning" title="Process Pending">
                            <i class="fa fa-play me-1"></i> Process
                        </a>
                    @endif
                    <a href="{{ route('transactions.clone', [$transaction->id]) }}" class="btn btn-sm btn-outline-primary" title="Clone with today's date">
                        <i class="fa fa-copy me-1"></i> Clone
                    </a>
                    <a href="{{ route('transactions.resend-email', [$transaction->id]) }}" class="btn btn-sm btn-outline-primary" title="Resend confirmation email">
                        <i class="fa fa-envelope me-1"></i> Resend
                    </a>
                    <a href="{{ route('transactions.edit', [$transaction->id]) }}" class="btn btn-sm btn-outline-primary" title="Edit transaction">
                        <i class="fa fa-edit me-1"></i> Edit
                    </a>
                    <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            @include('transactions.show_fields')
        @isset($transaction->scheduledJob)
            <div class="card">
                <div class="card-header">
                    <strong>Scheduled Job</strong>
                </div>
                <div class="card-body">
                    @include('scheduled_jobs.table', ['scheduledJobs' => [$transaction->scheduledJob]])
                </div>
            </div>
            @if($transaction->scheduledJob->entity_descr == \App\Models\ScheduledJobExt::ENTITY_TRANSACTION)
                <div class="card">
                    <div class="card-header">
                        <strong>Transaction Template</strong>
                    </div>
                    <div class="card-body">
                        @include('transactions.table', ['transactions' => [
                            \App\Models\TransactionExt::find($transaction->scheduledJob->entity_id)
                        ]])
                    </div>
                </div>
            @endif
        @endisset
        @if($transaction->scheduledJobs()->count() > 0)
            <div class="card">
                <div class="card-header">
                    <strong>Scheduled Jobs using this transaction as template</strong>
                </div>
                <div class="card-body">
                    @include('scheduled_jobs.table', ['scheduledJobs' => $transaction->scheduledJobs()])
                </div>
            </div>
        @endif
        @if($transaction->status == \App\Models\TransactionExt::STATUS_SCHEDULED)
            <div class="card">
                <div class="card-header">
                    <strong>Scheduled Transaction</strong>
                </div>
                <div class="card-body">
                    @php($scheduled_job = \App\Models\ScheduledJobExt::where('entity_id', $transaction->id)
                        ->where('entity_descr', \App\Models\ScheduledJobExt::ENTITY_TRANSACTION)->first())
                    @include('transactions.table', ['transactions' => $scheduled_job->entities()])
                </div>
            </div>
        @endif

        @isset($transaction->accountBalance)
            <div class="card">
                <div class="card-header">
                    <strong>Account Balances</strong>
                </div>
                <div class="card-body">
                    @include('account_balances.table', ['accountBalances' => [$transaction->accountBalance]])
                </div>
            </div>
        @endisset

        @isset($transaction->cashDeposit)
            <div class="card">
                <div class="card-header">
                    <strong>Cash Deposit</strong>
                </div>
                <div class="card-body">
                    @include('cash_deposits.table', ['cashDeposits' => [$transaction->cashDeposit]])
                </div>
            </div>
        @endisset

        @isset($transaction->depositRequest)
            <div class="card">
                <div class="card-header">
                    <strong>Deposit Request</strong>
                </div>
                <div class="card-body">
                    @include('deposit_requests.table', ['depositRequests' => [$transaction->depositRequest]])
                </div>
            </div>
        @endisset

        @isset($transaction->matchedTransaction)
            <div class="card">
                <div class="card-header">
                    <strong>Matched Transaction</strong>
                </div>
                <div class="card-body">
                    @include('transactions.table', ['transactions' => [$transaction->matchedTransaction]])
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <strong>Reference Transaction</strong>
                </div>
                <div class="card-body">
                    @include('transactions.table', ['transactions' => [$transaction->referenceTransaction]])
                </div>
            </div>
        @endisset


    </div>
</x-app-layout>
