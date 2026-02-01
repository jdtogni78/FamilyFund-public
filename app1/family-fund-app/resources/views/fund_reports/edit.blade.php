<x-app-layout>

@section('content')
    <ol class="breadcrumb">
          <li class="breadcrumb-item">
             <a href="{!! route('fundReports.index') !!}">Fund Report</a>
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
                              <strong>Edit Fund Report</strong>
                          </div>
                          <div class="card-body">
<form method="POST" action="{{ route('fundReports.update', $fundReport->id) }}">
                                  @csrf
                                  @method('PATCH')

                              @include('fund_reports.fields')

</form>
                            </div>
                        </div>
                    </div>
                </div>
         </div>
    </div>
</x-app-layout>