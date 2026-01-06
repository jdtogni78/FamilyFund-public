<x-app-layout>

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('portfolioReports.index') !!}">Portfolio Report</a>
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
                                <strong>Create Portfolio Report</strong>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('portfolioReports.store') }}" method="POST">
                                    @csrf
                                    @include('portfolio_reports.fields')
                                    <button type="submit" class="btn btn-primary">Create</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
</x-app-layout>
