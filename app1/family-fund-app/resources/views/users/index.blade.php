<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Users</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('layouts.flash-messages')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header d-flex justify-content-between align-items-center">
                             <div>
                                 <i class="fa fa-user-cog me-2"></i>
                                 <strong>Users</strong>
                                 <span class="badge bg-primary ms-2">{{ $users->count() }}</span>
                             </div>
                             <a class="btn btn-sm btn-primary" href="{{ route('users.create') }}">
                                 <i class="fa fa-plus me-1"></i> New User
                             </a>
                         </div>
                         <div class="card-body">
                             @include('users.table')
                              <div class="pull-right mr-3">

                              </div>
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
</x-app-layout>

