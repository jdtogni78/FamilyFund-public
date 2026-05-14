@php
    $account = $accountReport->account;
    $fund = $account?->fund;
    $user = $account?->user;
@endphp

<div class="row">
    <div class="col-md-6">
        <!-- Account Field -->
        <div class="form-group mb-3">
            <label class="text-muted small">Account</label>
            <p class="mb-0">
                @if($account)
                    @include('partials.view_link', ['route' => route('accounts.show', $account->id), 'text' => $account->nickname ?? $account->code, 'class' => 'fw-bold'])
                    <span class="text-muted">({{ $account->code }})</span>
                @else
                    ID: {{ $accountReport->account_id }}
                @endif
            </p>
        </div>

        <!-- Fund Field -->
        <div class="form-group mb-3">
            <label class="text-muted small">Fund</label>
            <p class="mb-0">
                @if($fund)
                    @include('partials.view_link', ['route' => route('funds.show', $fund->id), 'text' => $fund->name])
                @else
                    -
                @endif
            </p>
        </div>

        <!-- User Field -->
        <div class="form-group mb-3">
            <label class="text-muted small">User</label>
            <p class="mb-0">{{ $user?->name ?? '-' }}</p>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Type Field -->
        <div class="form-group mb-3">
            <label class="text-muted small">Report Type</label>
            <p class="mb-0">
                <span class="badge bg-info">{{ $api['typeMap'][$accountReport->type] ?? $accountReport->type }}</span>
            </p>
        </div>

        <!-- As Of Field -->
        <div class="form-group mb-3">
            <label class="text-muted small">As Of Date</label>
            <p class="mb-0">{{ $accountReport->as_of?->format('F j, Y') }}</p>
        </div>

        <!-- Created At Field -->
        <div class="form-group mb-3">
            <label class="text-muted small">Created</label>
            <p class="mb-0">{{ $accountReport->created_at?->format('F j, Y \a\t H:i') }}</p>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="mt-4 pt-3 border-top">
    <label class="text-muted small d-block mb-2">Quick Actions</label>
    @if($account)
        <a href="{{ route('accounts.show', $account->id) }}" class="btn btn-sm btn-outline-primary me-2">
            <i class="fa fa-user me-1"></i> View Account
        </a>
        <a href="/accounts/{{ $account->id }}/pdf_as_of/{{ $accountReport->as_of?->format('Y-m-d') }}" class="btn btn-sm btn-outline-secondary me-2" target="_blank">
            <i class="fa fa-file-pdf me-1"></i> Download PDF
        </a>
    @endif
    <a href="{{ route('accountReports.edit', $accountReport->id) }}" class="btn btn-sm btn-outline-info">
        <i class="fa fa-edit me-1"></i> Edit Report
    </a>
</div>

