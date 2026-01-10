<x-app-layout>

@section('content')
     <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('matchingRules.index') }}">Matching Rule</a>
            </li>
            <li class="breadcrumb-item active">Detail</li>
     </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                 @include('coreui-templates.common.errors')
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-header d-flex justify-content-between align-items-center">
                                 <strong>Details</strong>
                                 <div>
                                     <a href="{{ route('matchingRules.index') }}" class="btn btn-light">Back</a>
                                     <a href="{{ route('matchingRules.edit', $matchingRule->id) }}" class="btn btn-info">Edit</a>
                                     <a href="{{ route('matchingRules.clone', $matchingRule->id) }}" class="btn btn-warning">Clone</a>
                                     <button type="button" class="btn btn-danger" onclick="if(confirm('Are you sure you want to delete this matching rule?')) document.getElementById('delete-form').submit();">Delete</button>
                                     <form id="delete-form" action="{{ route('matchingRules.destroy', $matchingRule->id) }}" method="POST" style="display:none;">
                                         @csrf
                                         @method('DELETE')
                                     </form>
                                 </div>
                             </div>
                             <div class="card-body">
                                 @include('matching_rules.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
</x-app-layout>
