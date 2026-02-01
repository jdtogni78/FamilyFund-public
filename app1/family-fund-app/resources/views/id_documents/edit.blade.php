<x-app-layout>

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('id_documents.index') !!}">Id Document</a>
          </li>
          <li class="breadcrumb-item active">Edit</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates.common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>Edit Id Document</strong>
                          </div>
                          <div class="card-body">
<form method="POST" action="{{ route('id_documents.update', $idDocument->id) }}">
                                  @csrf
                                  @method('PATCH')

                              @include('id_documents.fields')

</form>
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
</x-app-layout>