<x-app-layout>

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('accountGoals.index') !!}">Account Goal</a>
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
                                <strong>Create Account Goal</strong>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('accountGoals.store') }}" method="POST">
                                    @csrf
                                    @include('account_goals.fields')
                                </form> 
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
</x-app-layout>
