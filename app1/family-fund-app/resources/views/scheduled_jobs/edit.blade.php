@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('scheduledJobs.index') !!}">Scheduled Job</a>
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
                              <strong>Edit Scheduled Job</strong>
                          </div>
                          <div class="card-body">
                              {!! Form::model($scheduledJob, ['route' => ['scheduledJobs.update', $scheduledJob->id], 'method' => 'patch']) !!}

                              @include('scheduled_jobs.fields')

                              {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
@endsection