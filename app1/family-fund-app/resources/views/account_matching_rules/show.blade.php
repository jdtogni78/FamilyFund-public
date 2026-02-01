<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('accountMatchingRules.index') }}">Account Matching Rules</a>
        </li>
        <li class="breadcrumb-item active">{{ $api['account']->nickname }} - {{ $api['mr']->name }}</li>
    </ol>

    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')

            <div class="row">
                <!-- Left Column - Account & Matching Info -->
                <div class="col-lg-8">
                    <!-- Header Card -->
                    <div class="card mb-4" style="border-left: 4px solid #9333ea;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1"><i class="fa fa-gift text-purple me-2" style="color: #9333ea;"></i> Matching Rule Assignment</h4>
                                    <p class="text-muted mb-0">Contribution matching for {{ $api['account']->nickname }}</p>
                                </div>
                                <span class="badge bg-success" style="padding: 8px 16px; font-size: 14px;">Active</span>
                            </div>
                        </div>
                    </div>

                    <!-- Account Details Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <strong><i class="fa fa-user me-2"></i> Account</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">Nickname</td>
                                            <td class="fw-bold">{{ $api['account']->nickname }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Code</td>
                                            <td>{{ $api['account']->code }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Email</td>
                                            <td>{{ $api['account']->email_cc ?? 'Not set' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">User</td>
                                            <td>{{ $api['account']->user?->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Effective From</td>
                                            <td>{{ \Carbon\Carbon::parse(max($api['mr']->date_start, $accountMatchingRule->created_at))->format('M j, Y') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Matching Rule Details Card -->
                    <div class="card mb-4" style="border-left: 4px solid #9333ea;">
                        <div class="card-header" style="background-color: #9333ea; color: white;">
                            <strong><i class="fa fa-percentage me-2"></i> Matching Rule: {{ $api['mr']->name }}</strong>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-4">
                                <div class="col-md-4">
                                    <div class="border rounded p-3">
                                        <div class="text-muted small text-uppercase">Match Rate</div>
                                        <div class="fs-2 fw-bold" style="color: #9333ea;">{{ $api['mr']->match_percent }}%</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3">
                                        <div class="text-muted small text-uppercase">Minimum</div>
                                        <div class="fs-4 fw-bold">${{ number_format($api['mr']->dollar_range_start, 0) }}</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3">
                                        <div class="text-muted small text-uppercase">Maximum</div>
                                        <div class="fs-4 fw-bold">${{ number_format($api['mr']->dollar_range_end, 0) }}</div>
                                    </div>
                                </div>
                            </div>

                            <table class="table table-borderless">
                                <tr>
                                    <td class="text-muted" style="width: 30%;">Valid Period</td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($api['mr']->date_start)->format('M j, Y') }}
                                        &mdash;
                                        {{ $api['mr']->date_end ? \Carbon\Carbon::parse($api['mr']->date_end)->format('M j, Y') : 'Ongoing' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Description</td>
                                    <td>
                                        Contributions between <strong>${{ number_format($api['mr']->dollar_range_start, 0) }}</strong>
                                        and <strong>${{ number_format($api['mr']->dollar_range_end, 0) }}</strong>
                                        will be matched at <strong>{{ $api['mr']->match_percent }}%</strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Actions -->
                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <strong><i class="fa fa-cog me-2"></i> Actions</strong>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('accountMatchingRules.resend-email', [$accountMatchingRule->id]) }}"
                                   class="btn btn-primary"
                                   onclick="return confirm('Send email notification to {{ $api['account']->email_cc ?? "account" }}?')">
                                    <i class="fa fa-envelope me-2"></i> Send Notification Email
                                </a>
                                <a href="{{ route('accountMatchingRules.edit', [$accountMatchingRule->id]) }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-edit me-2"></i> Edit
                                </a>
                                <a href="{{ route('accountMatchingRules.index') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-arrow-left me-2"></i> Back to List
                                </a>
                                <hr class="my-2">
                                <form action="{{ route('accountMatchingRules.destroy', $accountMatchingRule->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger w-100"
                                            onclick="return confirm('Are you sure you want to delete this matching rule assignment?')">
                                        <i class="fa fa-trash mr-2"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Info Card -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <strong><i class="fa fa-info-circle me-2"></i> Quick Info</strong>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-2">
                                <strong>How matching works:</strong>
                            </p>
                            <p class="small text-muted">
                                When {{ $api['account']->nickname }} makes a deposit, the fund will automatically
                                contribute an additional {{ $api['mr']->match_percent }}% of the deposit amount
                                (up to ${{ number_format($api['mr']->dollar_range_end, 0) }}).
                            </p>
                            <hr>
                            <p class="small text-muted mb-1">
                                <strong>Created:</strong> {{ $accountMatchingRule->created_at->format('M j, Y g:i A') }}
                            </p>
                            <p class="small text-muted mb-0">
                                <strong>ID:</strong> {{ $accountMatchingRule->id }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
