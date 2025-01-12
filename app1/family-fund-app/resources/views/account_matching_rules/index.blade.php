@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Account Matching Rules</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('flash::message')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header">
                             <i class="fa fa-align-justify"></i>
                             AccountMatchingRules
                             <a class="pull-right" href="{{ route('accountMatchingRules.create') }}"><i class="fa fa-plus-square fa-lg"></i></a>
                             <a class="pull-right" href="{{ route('accountMatchingRules.create_bulk') }}"><i class="fa fa-plus-square-o fa-lg"></i> bulk</a>
                         </div>
                         <div class="card-body">
                             @include('account_matching_rules.table_ext')
                              <div class="pull-right mr-3">
                                     
                              </div>
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
@endsection

