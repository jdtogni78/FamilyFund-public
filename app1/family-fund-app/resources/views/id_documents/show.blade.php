<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('id_documents.index') }}">ID Documents</a>
        </li>
        <li class="breadcrumb-item active">Document #{{ $idDocument->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-id-card me-2"></i>
                                <strong>{{ strtoupper($idDocument->type) }}: {{ $idDocument->number }}</strong>
                                @if($idDocument->person)
                                    <span class="text-body-secondary ms-2">
                                        (<a href="{{ route('people.show', $idDocument->person_id) }}">{{ $idDocument->person->first_name }} {{ $idDocument->person->last_name }}</a>)
                                    </span>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('id_documents.edit', $idDocument->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('id_documents.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('id_documents.show_fields')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
