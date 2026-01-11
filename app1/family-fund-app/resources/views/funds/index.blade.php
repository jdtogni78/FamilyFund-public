<x-app-layout>
    <ol class="breadcrumb">
        <li class="breadcrumb-item">Funds</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('layouts.flash-messages')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header d-flex justify-content-between align-items-center">
                             <div>
                                 <i class="fa fa-landmark me-2"></i>
                                 <strong>Funds</strong>
                                 <span class="badge bg-primary ms-2">{{ $funds->count() }}</span>
                             </div>
                             <a class="btn btn-sm btn-primary" href="{{ route('funds.create') }}">
                                 <i class="fa fa-plus me-1"></i> New Fund
                             </a>
                         </div>
                         <div class="card-body">
                             @include('funds.table')
                              <div class="pull-right mr-3">
                                     
                              </div>
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
</x-app-layout>

