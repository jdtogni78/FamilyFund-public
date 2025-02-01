<x-app-layout>

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('people.index') !!}">Person</a>
      </li>
      <li class="breadcrumb-item active">Create</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates.common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>Create Person</strong>
                            </div>
                            <div class="card-body">
                                <div class="row">
<form method="POST" action="{ route('people.store') }">
@csrf

                                    @include('people.fields')

</form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
</x-app-layout>

