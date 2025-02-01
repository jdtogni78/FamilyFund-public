<x-app-layout>

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('people.index') !!}">Person</a>
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
                              <strong>Edit Person</strong>
                          </div>
                          <div class="card-body">
<form method="patch" action="{ route('['route' => ['people.update', $person->id]') }" >
                            <div class="row">
                              @include('people.fields', ['isEdit' => true])
                            </div>
</form>
                        </div>
                    </div>
                </div>
         </div>
    </div>
</x-app-layout>