<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('matchingRules.index') }}">Matching Rules</a>
        </li>
        <li class="breadcrumb-item active">{{ $matchingRule->name }}</li>
    </ol>

    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('layouts.flash-messages')

            <div class="row">
                <!-- Left Column - Rule Details -->
                <div class="col-lg-8">
                    <!-- Header Card -->
                    <div class="card mb-4" style="border-left: 4px solid #9333ea;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1" style="color: #9333ea;">
                                        <i class="fa fa-percentage me-2"></i> {{ $matchingRule->name }}
                                    </h4>
                                    <p class="text-muted mb-0">Matching rule for {{ $accountMatchingRules->count() }} account(s)</p>
                                </div>
                                <span class="badge {{ $matchingRule->date_end && $matchingRule->date_end < now() ? 'bg-secondary' : 'bg-success' }}" style="padding: 8px 16px; font-size: 14px;">
                                    {{ $matchingRule->date_end && $matchingRule->date_end < now() ? 'Expired' : 'Active' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Rule Stats -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="border rounded p-3">
                                        <div class="text-muted small text-uppercase">Match Rate</div>
                                        <div class="fs-2 fw-bold" style="color: #9333ea;">{{ $matchingRule->match_percent }}%</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3">
                                        <div class="text-muted small text-uppercase">Minimum</div>
                                        <div class="fs-4 fw-bold">${{ number_format($matchingRule->dollar_range_start, 0) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3">
                                        <div class="text-muted small text-uppercase">Maximum</div>
                                        <div class="fs-4 fw-bold">${{ number_format($matchingRule->dollar_range_end, 0) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3">
                                        <div class="text-muted small text-uppercase">Accounts</div>
                                        <div class="fs-4 fw-bold">{{ $accountMatchingRules->count() }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accounts Table -->
                    <div class="card mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <strong><i class="fa fa-users me-2"></i> Assigned Accounts</strong>
                            @if($accountMatchingRules->count() > 0)
                            <a href="{{ route('matchingRules.send-all-emails', $matchingRule->id) }}"
                               class="btn btn-sm btn-outline-primary"
                               onclick="return confirm('Send email notifications to all {{ $accountMatchingRules->count() }} account(s)?')">
                                <i class="fa fa-envelope me-1"></i> Email All
                            </a>
                            @endif
                        </div>
                        <div class="card-body">
                            @if($accountMatchingRules->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped" id="accounts-table">
                                    <thead>
                                        <tr>
                                            <th>Account</th>
                                            <th>Code</th>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Assigned</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($accountMatchingRules as $amr)
                                        <tr>
                                            <td>
                                                <a href="{{ route('accounts.show', $amr->account->id) }}">
                                                    {{ $amr->account->nickname }}
                                                </a>
                                            </td>
                                            <td>{{ $amr->account->code }}</td>
                                            <td>{{ $amr->account->user?->name ?? '-' }}</td>
                                            <td>
                                                @if($amr->account->email_cc)
                                                    <span class="text-success"><i class="fa fa-check-circle me-1"></i></span>
                                                    <small>{{ $amr->account->email_cc }}</small>
                                                @else
                                                    <span class="text-muted"><i class="fa fa-times-circle me-1"></i> None</span>
                                                @endif
                                            </td>
                                            <td>{{ $amr->created_at->format('M j, Y') }}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('accountMatchingRules.show', $amr->id) }}" class="btn btn-outline-secondary" title="View">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    @if($amr->account->email_cc)
                                                    <a href="{{ route('accountMatchingRules.resend-email', $amr->id) }}" class="btn btn-outline-secondary" title="Send Email">
                                                        <i class="fa fa-envelope"></i>
                                                    </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="text-center text-muted py-4">
                                <i class="fa fa-users fa-3x mb-3"></i>
                                <p>No accounts assigned to this matching rule yet.</p>
                                <a href="{{ route('accountMatchingRules.create') }}" class="btn btn-primary">
                                    <i class="fa fa-plus me-1"></i> Assign Account
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Right Column - Actions & Info -->
                <div class="col-lg-4">
                    <!-- Actions Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <strong><i class="fa fa-cog me-2"></i> Actions</strong>
                        </div>
                        <div class="card-body">
                            <div class="btn-group d-flex flex-wrap" role="group">
                                <a href="{{ route('matchingRules.clone', $matchingRule->id) }}" class="btn btn-outline-warning">
                                    <i class="fa fa-copy me-1"></i> Clone
                                </a>
                                @if($accountMatchingRules->count() > 0)
                                <a href="{{ route('matchingRules.send-all-emails', $matchingRule->id) }}"
                                   class="btn btn-outline-secondary"
                                   onclick="return confirm('Send email notifications to all {{ $accountMatchingRules->count() }} account(s)?')">
                                    <i class="fa fa-envelope me-1"></i> Resend Email
                                </a>
                                @endif
                                <a href="{{ route('matchingRules.edit', $matchingRule->id) }}" class="btn btn-outline-info">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('matchingRules.index') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Details Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <strong><i class="fa fa-info-circle me-2"></i> Details</strong>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <td class="text-muted">ID</td>
                                    <td>{{ $matchingRule->id }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Start Date</td>
                                    <td>{{ \Carbon\Carbon::parse($matchingRule->date_start)->format('M j, Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">End Date</td>
                                    <td>{{ $matchingRule->date_end ? \Carbon\Carbon::parse($matchingRule->date_end)->format('M j, Y') : 'Ongoing' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Created</td>
                                    <td>{{ $matchingRule->created_at->format('M j, Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Updated</td>
                                    <td>{{ $matchingRule->updated_at->format('M j, Y') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- How It Works Card -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <strong><i class="fa fa-lightbulb me-2"></i> How It Works</strong>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-0">
                                When an assigned account makes a deposit between
                                <strong>${{ number_format($matchingRule->dollar_range_start, 0) }}</strong> and
                                <strong>${{ number_format($matchingRule->dollar_range_end, 0) }}</strong>,
                                the fund contributes an additional
                                <strong>{{ $matchingRule->match_percent }}%</strong> of that amount.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#accounts-table').DataTable({
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: -1 }
            ],
            pageLength: 25
        });
    });
</script>
@endpush
