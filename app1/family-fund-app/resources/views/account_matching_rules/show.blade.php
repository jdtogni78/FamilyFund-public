<x-app-layout>

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('accountMatchingRules.index') }}">Account Matching Rule</a>
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
                                  <a href="{{ route('accountMatchingRules.index') }}" class="btn btn-light">Back</a>
                             </div>
                             <div class="card-body">
                                 @include('account_matching_rules.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header">
                                 <strong>Matching Rule</strong>
                             </div>
                             <div class="card-body">
                                 @include('matching_rules.show_fields', ['matchingRule' => $api['mr']])
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
</x-app-layout>
