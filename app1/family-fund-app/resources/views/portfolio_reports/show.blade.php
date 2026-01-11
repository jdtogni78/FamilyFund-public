<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('portfolioReports.index') }}">Portfolio Reports</a>
        </li>
        <li class="breadcrumb-item active">Report #{{ $portfolioReport->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-chart-pie me-2"></i>
                                <strong>Portfolio Report #{{ $portfolioReport->id }}</strong>
                                @if($portfolioReport->portfolio)
                                    <span class="text-body-secondary ms-2">
                                        (<a href="{{ route('portfolios.show', $portfolioReport->portfolio_id) }}">{{ $portfolioReport->portfolio->source }}</a>)
                                    </span>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('portfolioReports.edit', $portfolioReport->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('portfolioReports.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('portfolio_reports.show_fields')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
