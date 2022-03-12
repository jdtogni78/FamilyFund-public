@extends('layouts.pdf')

@section('content')
                 <div class="row" style="margin-top: 30px">
                     <div class="col">
                         <div class="card">
                             <div class="card-header">
                                 <strong>Details</strong>
                             </div>
                             <div class="card-body">
                             @include('funds.show_fields_pdf')
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="row new-page" style="display:block; clear:both; page-break-after:always;">
                     <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <strong>Monthly Performance</strong>
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
                                <strong>Yearly Performance</strong>
                            </div>
                            <div class="card-body">
                                <img src="{{$files['yearly_performance.png']}}" alt="Yearly Performance"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row new-page">
                     <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <strong>Assets</strong>
                            </div>
                            <div class="card-body">
                                <img src="{{$files['assets_allocation.png']}}" alt="Accounts Allocation"/>
                            </div>
                        </div>
                    </div>
                    @isset($api['balances'])
                    <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <strong>Fund Allocation</strong>
                            </div>
                            <div class="card-body">
                                <img src="{{$files['shares_allocation.png']}}" alt="Fund Allocation"/>
                            </div>
                        </div>
                    </div>
                     <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <strong>Accounts Allocation</strong>
                            </div>
                            <div class="card-body">
                                <img src="{{$files['accounts_allocation.png']}}" alt="Accounts Allocation"/>
                            </div>
                        </div>
                    </div>
                    @endisset
                </div>
                <div class="row new-page">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <strong>Performance</strong>
                            </div>
                            <div class="card-body">
                                @include('funds.performance_table')
                            </div>
                        </div>
                    </div>
                 </div>
                 <div class="row new-page">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <strong>Assets</strong>
                            </div>
                            <div class="card-body">
                                @include('funds.assets_table')
                            </div>
                        </div>
                    </div>
                 </div>
                @isset($api['balances'])
                 <div class="row new-page">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <strong>Accounts</strong>
                            </div>
                            <div class="card-body">
                                @include('funds.accounts_table')
                            </div>
                        </div>
                    </div>
                 </div>
                 @endisset
@endsection
