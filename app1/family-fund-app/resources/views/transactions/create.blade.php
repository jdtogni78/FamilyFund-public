<x-app-layout>

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('transactions.index') !!}">Transaction</a>
      </li>
      <li class="breadcrumb-item active">Create</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates.common.errors')
                @if (Session::has('error'))
                   <div class="alert alert-danger">{{ Session::get('error') }}</div>
                @endif
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>Create Transaction</strong>
                            </div>
                            <div class="card-body">
<form method="POST" action="{{ route('transactions.preview') }}">
@csrf
                               @include('transactions.fields')
</form>
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
</x-app-layout>