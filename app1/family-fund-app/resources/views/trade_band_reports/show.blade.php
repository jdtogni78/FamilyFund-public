<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('tradeBandReports.index') }}">Trade Band Reports</a>
        </li>
        <li class="breadcrumb-item active">Detail</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>Details</strong>
                            <div>
                                <a href="{{ route('tradeBandReports.index') }}" class="btn btn-sm btn-outline-secondary">
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
