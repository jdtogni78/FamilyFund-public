<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('changeLogs.index') }}">Change Logs</a>
        </li>
        <li class="breadcrumb-item active">Change #{{ $changeLog->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-file-alt me-2"></i>
                                <strong>Change Log #{{ $changeLog->id }}</strong>
                            </div>
                            <div>
                                <a href="{{ route('changeLogs.edit', $changeLog->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('changeLogs.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('change_logs.show_fields')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
