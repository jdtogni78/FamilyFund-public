<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('accounts.index') }}">Accounts</a>
        </li>
        <li class="breadcrumb-item active">{{ $account->nickname }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-user me-2"></i>
                                <strong>{{ $account->nickname }}</strong>
                                @if($account->fund)
                                    <span class="text-body-secondary ms-2">
                                        (<a href="{{ route('funds.show', $account->fund_id) }}">{{ $account->fund->name }}</a>)
                                    </span>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('accounts.edit', $account->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('accounts.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('accounts.show_fields')
                        </div>
                    </div>
                </div>
            </div>

            @if($account->goals->count() > 0)
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-bullseye me-2"></i>
                                <strong>Goals</strong>
                                <span class="badge bg-primary ms-2">{{ $account->goals->count() }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            @foreach($account->goals as $goal)
                                <h5 class="mb-3">
                                    <a href="{{ route('goals.show', $goal->id) }}">{{ $goal->name }}</a>
                                </h5>
                                @include('goals.progress_summary', ['goal' => $goal, 'format' => 'web'])
                                @include('goals.progress_details_unified', ['goal' => $goal, 'format' => 'web'])
                                @if(!$loop->last)
                                    <hr class="my-4">
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
