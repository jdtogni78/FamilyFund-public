<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Account Balances</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('layouts.flash-messages')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header d-flex justify-content-between align-items-center">
                             <div>
                                 <i class="fa fa-balance-scale me-2"></i>
                                 <strong>Account Balances</strong>
                                 <span class="badge bg-primary ms-2">{{ $accountBalances->count() }}</span>
                             </div>
                             <a class="btn btn-sm btn-primary" href="{{ route('accountBalances.create') }}">
                                 <i class="fa fa-plus me-1"></i> New Balance
                             </a>
                         </div>
                         <div class="card-body">
                             @include('account_balances.table')
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
</x-app-layout>

