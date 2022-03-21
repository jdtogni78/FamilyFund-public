@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('funds.index') }}">Fund</a>
            </li>
            <li class="breadcrumb-item active">Detail</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates::common.errors')
                 <div class="row">
                     <div class="col">
                         <div class="card">
                             <div class="card-header">
                                 <strong>Details</strong>
                                  <a href="{{ route('funds.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                             {!! Form::open(['route' => ['funds.update', 1]]) !!}
                             @include('funds.show_fields_ext')
                             {!! Form::close() !!}
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="row">
                     <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <strong>Monthly Performance</strong>
                            </div>
                            <div class="card-body">
                                @include('funds.performance_line_graph')
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
                                @include('funds.performance_graph')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                     <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <strong>Assets</strong>
                            </div>
                            <div class="card-body">
                                @include('funds.assets_graph')
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
                                @include('funds.allocation_graph')
                            </div>
                        </div>
                    </div>
                     <div class="col">
                        <div class="card">
                            <div class="card-header">
                                <strong>Accounts Allocation</strong>
                            </div>
                            <div class="card-body">
                                @include('funds.accounts_graph')
                            </div>
                        </div>
                    </div>
                    @endisset
                </div>
              <div class="row">
                  <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <strong>Yearly Performance</strong>
                          </div>
                          <div class="card-body">
                              @php ($performance_key = 'yearly_performance')
                              @include('funds.performance_table')
                          </div>
                      </div>
                  </div>
              </div>
              <div class="row">
                  <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <strong>Monthly Performance</strong>
                          </div>
                          <div class="card-body">
                              @php ($performance_key = 'monthly_performance')
                              @include('funds.performance_table')
                          </div>
                      </div>
                  </div>
              </div>
                 <div class="row">
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
                 <div class="row">
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
          </div>
    </div>
@endsection
