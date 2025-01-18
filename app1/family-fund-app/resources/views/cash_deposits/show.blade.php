@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('cashDeposits.index') }}">Cash Deposit</a>
            </li>
            <li class="breadcrumb-item active">Detail</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
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
                 @isset($cashDeposit->transaction_id)
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <strong>Transaction</strong>
                                </div>
                                <div class="card-body">
                                    @include('transactions.show_fields', ['transaction' => $cashDeposit->transaction])
                                </div>
                            </div>
                        </div>
                    </div>
                 @endisset
          </div>
    </div>
@endsection
