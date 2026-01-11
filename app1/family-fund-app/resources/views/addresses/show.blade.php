<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('addresses.index') }}">Addresses</a>
        </li>
        <li class="breadcrumb-item active">Address #{{ $address->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-map-marker-alt me-2"></i>
                                <strong>Address #{{ $address->id }}</strong>
                                @if($address->person)
                                    <span class="text-body-secondary ms-2">
                                        (<a href="{{ route('people.show', $address->person_id) }}">{{ $address->person->first_name }} {{ $address->person->last_name }}</a>)
                                    </span>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('addresses.edit', $address->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('addresses.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('addresses.show_fields')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
