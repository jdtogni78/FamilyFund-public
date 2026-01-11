<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('tradeBandReports.index') }}">Trade Band Reports</a>
        </li>
        <li class="breadcrumb-item active">Report #{{ $tradeBandReport->id }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-chart-bar me-2"></i>
                                <strong>Trade Band Report #{{ $tradeBandReport->id }}</strong>
                                @if($tradeBandReport->portfolio)
                                    <span class="text-body-secondary ms-2">
                                        (<a href="{{ route('portfolios.show', $tradeBandReport->portfolio_id) }}">{{ $tradeBandReport->portfolio->source }}</a>)
                                    </span>
                                @endif
                            </div>
                            <div>
                                <a href="{{ route('tradeBandReports.edit', $tradeBandReport->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('tradeBandReports.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                                @if($tradeBandReport->as_of && $tradeBandReport->as_of->format('Y') !== '9999')
                                    <a href="{{ route('tradeBandReports.viewPdf', $tradeBandReport->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fa fa-file-pdf me-1"></i> View PDF
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-secondary" disabled title="PDF not available - no valid date">
                                        <i class="fa fa-file-pdf me-1"></i> View PDF
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            @include('trade_band_reports.show_fields')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
