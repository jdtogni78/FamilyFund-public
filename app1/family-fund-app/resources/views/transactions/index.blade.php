<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Transactions</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('layouts.flash-messages')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header d-flex justify-content-between align-items-center">
                             <div>
                                 <i class="fa fa-exchange-alt me-2"></i>
                                 <strong>Transactions</strong>
                                 <span class="badge bg-primary ms-2">{{ $transactions->count() }}</span>
                             </div>
                             <div>
                                 <a href="{{ route('transactions.create_bulk') }}" class="btn btn-sm btn-outline-primary me-1">
                                     <i class="fa fa-users me-1"></i> Bulk
                                 </a>
                                 <a class="btn btn-sm btn-primary" href="{{ route('transactions.create') }}">
                                     <i class="fa fa-plus me-1"></i> New Transaction
                                 </a>
                             </div>
                         </div>
                         <div class="card-body">
                             @include('transactions.table')
                              <div class="pull-right mr-3">

                              </div>
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
</x-app-layout>

