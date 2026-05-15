<x-app-layout>
@section('content')
<ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('accounts.show', $account->id) }}">{{ $account->nickname }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('credit_lines.index', ['account' => $account->id]) }}">Credit Lines</a></li>
    <li class="breadcrumb-item active">New</li>
</ol>
<div class="container-fluid">
    <div class="card">
        <div class="card-header"><strong>Open new credit line</strong></div>
        <div class="card-body">
            @include('coreui-templates.common.errors')
            <form method="POST" action="{{ route('credit_lines.store', ['account' => $account->id]) }}">
                @csrf
                <input type="hidden" name="account_id" value="{{ $account->id }}">
                <div class="mb-3">
                    <label class="form-label">Principal (shares)</label>
                    <input type="number" step="0.0001" class="form-control" name="principal_shares" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Term (months)</label>
                    <input type="number" class="form-control" name="term_months" value="12" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment frequency</label>
                    <select class="form-select" name="payment_frequency" required>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="annual">Annual</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control" name="descr" maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label">Imputed interest rate (decimal, optional)</label>
                    <input type="number" step="0.0001" min="0" max="1" class="form-control" name="imputed_interest_rate">
                </div>
                <button type="submit" class="btn btn-primary">Open</button>
                <a href="{{ route('credit_lines.index', ['account' => $account->id]) }}"
                   class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
