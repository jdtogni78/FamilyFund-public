<x-app-layout>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('funds.index') }}">Funds</a></li>
        <li class="breadcrumb-item"><a href="{{ route('funds.show', $fund->id) }}">{{ $fund->name }}</a></li>
        <li class="breadcrumb-item active">Portfolios</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('layouts.flash-messages')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-folder-open me-2"></i>
                                <strong>{{ $fund->name }} - Portfolios</strong>
                                <span class="badge bg-primary ms-2">{{ $portfolios->count() }}</span>
                            </div>
                            <a class="btn btn-sm btn-primary" href="{{ route('portfolios.create') }}">
                                <i class="fa fa-plus me-1"></i> Add Portfolio
                            </a>
                        </div>
                        <div class="card-body">
                            @include('funds.portfolios_table', [
                                'portfolios' => $portfolios,
                                'showActions' => true,
                                'compact' => false
                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
