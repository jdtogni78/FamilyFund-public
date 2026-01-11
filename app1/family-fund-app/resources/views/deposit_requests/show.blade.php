<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('depositRequests.index') }}">Deposit Requests</a>
        </li>
        <li class="breadcrumb-item active">Request #{{ $depositRequest->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-hand-holding-usd me-2"></i>
                                <strong>Deposit Request #{{ $depositRequest->id }}</strong>
                                @if($depositRequest->account)
                                    <span class="text-body-secondary ms-2">
                                        (<a href="{{ route('accounts.show', $depositRequest->account_id) }}">{{ $depositRequest->account->nickname }}</a>)
                                    </span>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('depositRequests.edit', $depositRequest->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('depositRequests.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('deposit_requests.show_fields')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
