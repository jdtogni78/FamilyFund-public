<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Trade Portfolios</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('layouts.flash-messages')

             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             TradePortfolios
                             <a class="pull-right" href="{{ route('tradePortfolios.create') }}"><i class="fa fa-plus-square fa-lg"></i></a>
                         </div>
                         <div class="card-body">
                             @include('trade_portfolios.table')
                              <div class="pull-right mr-3">

                              </div>
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
</x-app-layout>

