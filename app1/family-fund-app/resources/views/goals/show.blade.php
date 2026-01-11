<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('goals.index') }}">Goals</a>
        </li>
        <li class="breadcrumb-item active">{{ $goal->name }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-bullseye me-2"></i>
                                <strong>{{ $goal->name }}</strong>
                            </div>
                            <div>
                                <a href="{{ route('goals.edit', $goal->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('goals.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('goals.show_fields')
                        </div>
                    </div>
                </div>
            </div>

            @if($goal->accounts->count() > 0)
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-users me-2"></i>
                                <strong>Associated Accounts</strong>
                                <span class="badge bg-primary ms-2">{{ $goal->accounts->count() }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('accounts.table', ['accounts' => $goal->accounts])
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
