@extends('layouts.pdf')

@section('content')
                 <div class="row">
                     <div class="col">
                         <div class="card">
                             <div class="card-header">
                                 <strong>Details</strong>
                             </div>
                             <div class="card-body">
                             @include('accounts.show_fields_pdf')
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="row">
                     <div class="col">
                         <div class="card">
                             <div class="card-header">
                                 <strong>Performance</strong>
                             </div>
                             <div class="card-body">
                                 <img src="{{$files['monthly_performance.png']}}" alt="Monthly Performance"/>
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="row">
                     <div class="col">
                         <div class="card">
                             <div class="card-header">
                                 <strong>Performance</strong>
                             </div>
                             <div class="card-body">
                                 <img src="{{$files['yearly_performance.png']}}" alt="Yearly Performance"/>
                             </div>
                         </div>
                     </div>
                 </div>
                <div class="row">
                    <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <strong>Shares</strong>
                            </div>
                            <div class="card-body">
                                <img src="{{$files['shares.png']}}" alt="Shares"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <strong>Performance</strong>
                            </div>
                            <div class="card-body">
                                @include('accounts.performance_table')
                            </div>
                        </div>
                    </div>
                 </div>
                 <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <strong>Transactions</strong>
                            </div>
                            <div class="card-body">
                                @include('accounts.transactions_table')
                            </div>
                        </div>
                    </div>
                 </div>
          </div>
@endsection
