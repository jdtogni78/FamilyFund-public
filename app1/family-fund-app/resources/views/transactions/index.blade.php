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
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             Transactions
                             <span class="pull-right">
                                 <a href="{{ route('transactions.create_bulk') }}" class="btn btn-outline-primary btn-sm me-2" title="Bulk Create">
                                     <i class="fa fa-users me-1"></i>Bulk
                                 </a>
                                 <a href="{{ route('transactions.create') }}" title="Create Single">
                                     <i class="fa fa-plus-square fa-lg"></i>
                                 </a>
                             </span>
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

