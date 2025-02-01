<x-app-layout>

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('cashDeposits.index') }}">Cash Deposit</a>
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
                                  <a href="{{ route('cashDeposits.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('cash_deposits.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
                 @isset($cashDeposit->depositRequests)
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>Deposit Requests</strong>
                            </div>
                            <div class="card-body">
                                @include('deposit_requests.table', ['depositRequests' => $cashDeposit->depositRequests])
                            </div>
                        </div>
                    </div>
                </div>
                @endisset
                @php
                    $depTrans = [];
                    foreach ($cashDeposit?->depositRequests as $dr) {
                        if ($dr->transaction) $depTrans[] = $dr->transaction;
                    }
                    $transactions = [];
                    if ($cashDeposit->transaction?->id) {
                        $transactions[] = $cashDeposit->transaction;
                    }
                    if (count($depTrans) > 0) {
                        $transactions = array_merge($transactions, $depTrans);
                    }
                @endphp
                @isset($transactions)
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>Transaction</strong>
                                </div>
                                <div class="card-body">
                                    @include('transactions.table', ['transactions' => $transactions])
                                </div>
                            </div>
                        </div>
                    </div>
                 @endisset
          </div>
    </div>
</x-app-layout>
