@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('depositRequests.index') !!}">Deposit Request</a>
          </li>
          <li class="breadcrumb-item active">Edit</li>
        </ol>
    <div class="container-fluid">
         <div class="animated fadeIn">
             @include('coreui-templates::common.errors')
             <div class="row">
                 <div class="col-lg-12">
                      <div class="card">
                          <div class="card-header">
                              <i class="fa fa-edit fa-lg"></i>
                              <strong>Edit Deposit Request</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($depositRequest, ['route' => ['depositRequests.update', $depositRequest->id], 'method' => 'patch']) !!}
                              @php($isEdit = true)
                              @include('deposit_requests.fields', ['isEdit' => $isEdit])

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection