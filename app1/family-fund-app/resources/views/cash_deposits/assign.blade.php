@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('cashDeposits.index') !!}">Cash Deposit</a>
      </li>
      <li class="breadcrumb-item active">Create</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>Create Cash Deposit</strong>
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
                                    <button class="btn btn-link float-left" type="button" data-toggle="collapse" data-target="#transactionCollapse" 
                                        aria-expanded="false" aria-controls="transactionCollapse">
                                        <i class="fa fa-chevron-up"></i>
                                    </button>
                                    <strong>Transaction</strong>
                                </div>
                                <div class="card-body collapse" id="transactionCollapse">
                                    @include('transactions.show_fields', ['transaction' => $cashDeposit->transaction])
                                </div>
                            </div>
                        </div>
                    </div>
                @endisset
                @include('deposit_requests.multi_fields')
           </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#transactionCollapse').collapse('show');
        });
    </script>
@endsection
