<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">ID Documents</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
             @include('layouts.flash-messages')
             <div class="row">
                 <div class="col-lg-12">
                     <div class="card">
                         <div class="card-header d-flex justify-content-between align-items-center">
                             <div>
                                 <i class="fa fa-id-card me-2"></i>
                                 <strong>ID Documents</strong>
                                 <span class="badge bg-primary ms-2">{{ $idDocuments->count() }}</span>
                             </div>
                             <a class="btn btn-sm btn-primary" href="{{ route('id_documents.create') }}">
                                 <i class="fa fa-plus me-1"></i> New Document
                             </a>
                         </div>
                         <div class="card-body">
                             @include('id_documents.table')
                         </div>
                     </div>
                  </div>
             </div>
         </div>
    </div>
</x-app-layout>

