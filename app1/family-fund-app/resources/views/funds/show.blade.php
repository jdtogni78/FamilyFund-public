<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('funds.index') }}">Funds</a>
        </li>
        <li class="breadcrumb-item active">{{ $fund->name }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-landmark me-2"></i>
                                <strong>{{ $fund->name }}</strong>
                            </div>
                            <div>
                                <a href="{{ route('funds.edit', $fund->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('funds.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('funds.show_fields')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
