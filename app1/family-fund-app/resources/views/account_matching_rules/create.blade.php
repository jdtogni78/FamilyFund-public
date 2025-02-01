<x-app-layout>

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('accountMatchingRules.index') !!}">Account Matching Rule</a>
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
                                <strong>Create Account Matching Rule</strong>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('account-matching-rules.store') }}" class="form-horizontal">
                                    @csrf
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label" for="name">Name:</label>
                                        <div class="col-sm-10">
                                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-2 control-label" for="description">Description:</label>
                                        <div class="col-sm-10">
                                            <textarea name="description" id="description" class="form-control">{{ old('description') }}</textarea>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-offset-2 col-sm-10">
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </div>
                                </form> 
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
</x-app-layout>
