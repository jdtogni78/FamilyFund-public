<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.user-roles.index') }}">User Role Management</a></li>
        <li class="breadcrumb-item active">{{ $user->name }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('layouts.flash-messages')

            <div class="row">
                {{-- User Info Card --}}
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-user me-2"></i>
                            <strong>User Information</strong>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">ID</dt>
                                <dd class="col-sm-8">{{ $user->id }}</dd>

                                <dt class="col-sm-4">Name</dt>
                                <dd class="col-sm-8">{{ $user->name }}</dd>

                                <dt class="col-sm-4">Email</dt>
                                <dd class="col-sm-8">{{ $user->email }}</dd>

                                <dt class="col-sm-4">2FA</dt>
                                <dd class="col-sm-8">
                                    @if($user->hasTwoFactorEnabled())
                                        <span class="badge bg-success"><i class="fa fa-check me-1"></i>Enabled</span>
                                    @else
                                        <span class="badge bg-secondary">Disabled</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-4">Registered</dt>
                                <dd class="col-sm-8">{{ $user->created_at->format('Y-m-d H:i') }}</dd>
                            </dl>
                        </div>
                    </div>

                    {{-- Assign Role Card --}}
                    <div class="card mt-3">
                        <div class="card-header bg-primary text-white">
                            <i class="fa fa-plus me-2"></i>
                            <strong>Assign Role</strong>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.user-roles.assign', $user->id) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select name="role" class="form-select" id="role-select" required>
                                        <option value="">Select a role...</option>
                                        <option value="system-admin">System Admin (Global)</option>
                                        <option value="fund-admin">Fund Admin</option>
                                        <option value="financial-manager">Financial Manager</option>
                                        <option value="beneficiary">Beneficiary</option>
                                    </select>
                                </div>
                                <div class="mb-3" id="fund-select-group" style="display: none;">
                                    <label class="form-label">Fund</label>
                                    <select name="fund_id" class="form-select" id="fund-select">
                                        <option value="">Select a fund...</option>
                                        @foreach($funds as $fund)
                                            <option value="{{ $fund->id }}">{{ $fund->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fa fa-plus me-1"></i> Assign Role
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Current Roles Card --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-shield-alt me-2"></i>
                            <strong>Current Roles</strong>
                        </div>
                        <div class="card-body">
                            {{-- System Admin Role --}}
                            <h6 class="border-bottom pb-2 mb-3"><i class="fa fa-globe me-2"></i>Global Roles</h6>
                            @if($userRoles['systemAdmin'])
                                <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                                    <div>
                                        <span class="badge bg-danger me-2"><i class="fa fa-shield-alt me-1"></i>System Admin</span>
                                        <small class="text-muted">Full access to all funds and system settings</small>
                                    </div>
                                    <form action="{{ route('admin.user-roles.revoke', $user->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="role" value="system-admin">
                                        <input type="hidden" name="fund_id" value="0">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Revoke system admin role?')">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <p class="text-muted"><i class="fa fa-info-circle me-1"></i>No global roles assigned</p>
                            @endif

                            {{-- Fund-Scoped Roles --}}
                            <h6 class="border-bottom pb-2 mb-3 mt-4"><i class="fa fa-building me-2"></i>Fund Roles</h6>
                            @php $hasFundRoles = false; @endphp
                            @foreach($userRoles['fundRoles'] as $fundId => $data)
                                @if(!empty($data['roles']))
                                    @php $hasFundRoles = true; @endphp
                                    <div class="mb-3">
                                        <h6 class="text-primary mb-2">
                                            <i class="fa fa-folder-open me-1"></i>{{ $data['fund']->name }}
                                        </h6>
                                        @foreach($data['roles'] as $roleName)
                                            <div class="d-flex justify-content-between align-items-center mb-2 ms-3 p-2 bg-light rounded">
                                                <div>
                                                    @php
                                                        $badgeClass = match($roleName) {
                                                            'fund-admin' => 'bg-primary',
                                                            'financial-manager' => 'bg-info',
                                                            'beneficiary' => 'bg-secondary',
                                                            default => 'bg-dark',
                                                        };
                                                        $roleDescription = match($roleName) {
                                                            'fund-admin' => 'Full access within this fund',
                                                            'financial-manager' => 'Can manage transactions and reports',
                                                            'beneficiary' => 'Can view own accounts and transactions',
                                                            default => '',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }} me-2">{{ ucfirst(str_replace('-', ' ', $roleName)) }}</span>
                                                    <small class="text-muted">{{ $roleDescription }}</small>
                                                </div>
                                                <form action="{{ route('admin.user-roles.revoke', $user->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="role" value="{{ $roleName }}">
                                                    <input type="hidden" name="fund_id" value="{{ $fundId }}">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Revoke {{ $roleName }} role?')">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach

                            @if(!$hasFundRoles)
                                <p class="text-muted"><i class="fa fa-info-circle me-1"></i>No fund-specific roles assigned</p>
                            @endif
                        </div>
                    </div>

                    {{-- Role Descriptions Card --}}
                    <div class="card mt-3">
                        <div class="card-header">
                            <i class="fa fa-info-circle me-2"></i>
                            <strong>Role Descriptions</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><span class="badge bg-danger">System Admin</span></h6>
                                    <p class="small text-muted">Full access to all funds, users, and system settings. Can manage roles for all users.</p>
                                </div>
                                <div class="col-md-6">
                                    <h6><span class="badge bg-primary">Fund Admin</span></h6>
                                    <p class="small text-muted">Full access within a specific fund. Can manage accounts, transactions, portfolios, and reports.</p>
                                </div>
                                <div class="col-md-6">
                                    <h6><span class="badge bg-info">Financial Manager</span></h6>
                                    <p class="small text-muted">Can create and process transactions, generate reports, and view fund data.</p>
                                </div>
                                <div class="col-md-6">
                                    <h6><span class="badge bg-secondary">Beneficiary</span></h6>
                                    <p class="small text-muted">Can view their own accounts and transactions. Read-only access to fund-level reports.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Show/hide fund select based on role selection
            $('#role-select').on('change', function() {
                var role = $(this).val();
                if (role && role !== 'system-admin') {
                    $('#fund-select-group').show();
                    $('#fund-select').prop('required', true);
                } else {
                    $('#fund-select-group').hide();
                    $('#fund-select').prop('required', false);
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
