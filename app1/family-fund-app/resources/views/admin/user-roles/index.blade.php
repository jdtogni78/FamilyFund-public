<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Home</a></li>
        <li class="breadcrumb-item active">User Role Management</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('layouts.flash-messages')

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-users me-2"></i>
                                <strong>User Role Management</strong>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm" id="users-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>System Admin</th>
                                            <th>Fund Roles</th>
                                            <th>2FA</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($users as $item)
                                        @php $user = $item['user']; @endphp
                                        <tr>
                                            <td>{{ $user->id }}</td>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                @if($item['isSystemAdmin'])
                                                    <span class="badge bg-danger"><i class="fa fa-shield-alt me-1"></i>System Admin</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @forelse($item['fundRoles'] as $fundId => $roles)
                                                    @php
                                                        $fund = $funds->firstWhere('id', $fundId);
                                                        $fundName = $fund ? $fund->name : "Fund #{$fundId}";
                                                    @endphp
                                                    @foreach($roles as $role)
                                                        <span class="badge bg-{{ $role->name === 'fund-admin' ? 'primary' : ($role->name === 'financial-manager' ? 'info' : 'secondary') }}" title="{{ $fundName }}">
                                                            {{ ucfirst(str_replace('-', ' ', $role->name)) }} ({{ Str::limit($fundName, 15) }})
                                                        </span>
                                                    @endforeach
                                                @empty
                                                    <span class="text-muted">No fund roles</span>
                                                @endforelse
                                            </td>
                                            <td>
                                                @if($user->hasTwoFactorEnabled())
                                                    <span class="badge bg-success"><i class="fa fa-check me-1"></i>Enabled</span>
                                                @else
                                                    <span class="badge bg-secondary">Disabled</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.user-roles.show', $user->id) }}" class="btn btn-ghost-primary btn-sm" title="Manage Roles">
                                                    <i class="fa fa-edit"></i> Manage
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
            $('#users-table').DataTable({
                order: [[1, 'asc']],
                pageLength: 25,
            });
        });
    </script>
    @endpush
</x-app-layout>
