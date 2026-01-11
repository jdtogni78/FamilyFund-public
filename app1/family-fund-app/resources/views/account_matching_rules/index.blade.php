<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Account Matching Rules</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('layouts.flash-messages')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header d-flex justify-content-between align-items-center">
                             <div>
                                 <i class="fa fa-link me-2"></i>
                                 <strong>Account Matching Rules</strong>
                                 <span class="badge bg-primary ms-2">{{ $accountMatchingRules->count() }}</span>
                             </div>
                             <div>
                                 <a class="btn btn-sm btn-outline-primary me-1" href="{{ route('accountMatchingRules.create_bulk') }}">
                                     <i class="fa fa-users me-1"></i> Bulk
                                 </a>
                                 <a class="btn btn-sm btn-primary" href="{{ route('accountMatchingRules.create') }}">
                                     <i class="fa fa-plus me-1"></i> New Rule
                                 </a>
                             </div>
                         </div>
                         <div class="card-body">
                             @include('account_matching_rules.table_ext')
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
</x-app-layout>

