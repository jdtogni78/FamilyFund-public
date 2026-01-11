<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('phones.index') }}">Phones</a>
        </li>
        <li class="breadcrumb-item active">Phone #{{ $phone->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-phone me-2"></i>
                                <strong>{{ $phone->number }}</strong>
                                @if($phone->person)
                                    <span class="text-body-secondary ms-2">
                                        (<a href="{{ route('people.show', $phone->person_id) }}">{{ $phone->person->first_name }} {{ $phone->person->last_name }}</a>)
                                    </span>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('phones.edit', $phone->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('phones.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('phones.show_fields')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
