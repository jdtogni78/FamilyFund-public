<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Deposit Requests</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('layouts.flash-messages')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header d-flex justify-content-between align-items-center">
                             <div>
                                 <i class="fa fa-hand-holding-usd me-2"></i>
                                 <strong>Deposit Requests</strong>
                                 <span class="badge bg-primary ms-2">{{ $depositRequests->count() }}</span>
                             </div>
                             <a class="btn btn-sm btn-primary" href="{{ route('depositRequests.create') }}">
                                 <i class="fa fa-plus me-1"></i> New Request
                             </a>
                         </div>
                         <div class="card-body">
                             @include('deposit_requests.table')
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
</x-app-layout>

