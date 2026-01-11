<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Trade Band Reports</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('layouts.flash-messages')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header d-flex justify-content-between align-items-center">
                             <div>
                                 <i class="fa fa-file-alt me-2"></i>
                                 <strong>Trade Band Reports</strong>
                                 <span class="badge bg-primary ms-2">{{ $tradeBandReports->count() }}</span>
                             </div>
                             <a class="btn btn-sm btn-primary" href="{{ route('tradeBandReports.create') }}">
                                 <i class="fa fa-plus me-1"></i> New Report
                             </a>
                         </div>
                         <div class="card-body">
                             @include('trade_band_reports.table')
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
</x-app-layout>
