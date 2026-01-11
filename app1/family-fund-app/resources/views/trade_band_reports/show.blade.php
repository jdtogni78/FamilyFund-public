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
                        <div class="card-header">
                            <strong>Details</strong>
                            <a href="{{ route('tradeBandReports.index') }}" class="btn btn-light">Back</a>
                            <a href="{{ route('tradeBandReports.viewPdf', $tradeBandReport->id) }}" class="btn btn-primary"><i class="fa fa-file-pdf-o"></i> View PDF</a>
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
