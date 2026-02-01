<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Cash Deposits</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('layouts.flash-messages')

             {{-- Filter Bar --}}
             @include('partials.index_filter_bar', [
                 'filterRoute' => 'cashDeposits.index',
                 'showFund' => true,
                 'showAccount' => true,
                 'showMatchingRule' => false,
                 'showDates' => false,
             ])

             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header d-flex justify-content-between align-items-center">
                             <div>
                                 <i class="fa fa-dollar-sign me-2"></i>
                                 <strong>Cash Deposits</strong>
                                 <span class="badge bg-primary ms-2">{{ $cashDeposits->count() }}</span>
                             </div>
                             <a class="btn btn-sm btn-primary" href="{{ route('cashDeposits.create') }}">
                                 <i class="fa fa-plus me-1"></i> New Deposit
                             </a>
                         </div>
                         <div class="card-body">
                             @include('cash_deposits.table')
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
</x-app-layout>

