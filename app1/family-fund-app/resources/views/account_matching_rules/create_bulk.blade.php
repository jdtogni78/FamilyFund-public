@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('accountMatchingRules.index') !!}">Account Matching Rule</a>
      </li>
      <li class="breadcrumb-item active">Create</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates::common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>Create Account Matching Rule</strong>
                            </div>
                            <div class="card-body">
                                {!! Form::open(['route' => 'accountMatchingRules.store_bulk']) !!}
                                <!-- Matching Rule Id Field -->
                                <div class="form-group col-sm-6">
                                    {!! Form::label('matching_rule_id', 'Matching Rule Id:') !!}
                                    {!! Form::select('matching_rule_id', $api['mr'], null, ['class' => 'form-control']) !!}
                                </div>
                                <!-- Multiple Account Selection Field -->
                                <div class="form-group col-sm-6">
                                    {!! Form::label('account_ids[]', 'Select Multiple Accounts:') !!}
                                    {!! Form::select('account_ids[]', $api['account'], null, ['class' => 'form-control', 'multiple' => 'multiple', 'size' => '8']) !!}
                                    <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple accounts</small>
                                </div>

                                <!-- Submit Field -->
                                <div class="form-group col-sm-12">
                                    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
                                    <a href="{{ route('accountMatchingRules.index') }}" class="btn btn-secondary">Cancel</a>
                                </div>

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
@endsection
