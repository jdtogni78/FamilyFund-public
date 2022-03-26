@extends('layouts.app')

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('accounts.index') }}">Account</a>
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
                                  <a href="{{ route('accounts.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                             {!! Form::open(['route' => ['accounts.update', 1]]) !!}

                             @include('accounts.show_fields_ext')

                             {!! Form::close() !!}
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
                                @include('accounts.performance_line_graph')
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
                              @include('accounts.performance_graph')
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
                              <div>
                                  <canvas id="balancesGraph"></canvas>
                              </div>
                              @include('accounts.balances_graph')
                          </div>
                      </div>
                  </div>
              </div>
              <div class="row">
                  <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <strong>Yearly Performance</strong>
                          </div>
                          <div class="card-body">
                              @php ($performance_key = 'yearly_performance')
                              @include('accounts.performance_table')
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
              <div class="row">
                  <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <strong>Matching Rules</strong>
                          </div>
                          <div class="card-body">
                              @include('accounts.matching_rules_table')
                          </div>
                      </div>
                  </div>
              </div>
          </div>
    </div>
@endsection
