<x-app-layout>

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('accountReports.index') }}">Account Reports</a>
            </li>
            <li class="breadcrumb-item active">Report #{{ $accountReport->id }}</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates.common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header d-flex justify-content-between align-items-center">
                                 <div>
                                     <i class="fa fa-file-alt me-2"></i>
                                     <strong>Account Report #{{ $accountReport->id }}</strong>
                                 </div>
                                 <a href="{{ route('accountReports.index') }}" class="btn btn-sm btn-outline-secondary">
                                     <i class="fa fa-arrow-left me-1"></i> Back to List
                                 </a>
                             </div>
                             <div class="card-body">
                                 @include('account_reports.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
</x-app-layout>
