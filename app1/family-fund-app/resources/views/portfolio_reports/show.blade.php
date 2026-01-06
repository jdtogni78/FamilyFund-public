<x-app-layout>

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('portfolioReports.index') !!}">Portfolio Report</a>
      </li>
      <li class="breadcrumb-item active">Details</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates.common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <strong>Portfolio Report Details</strong>
                                <a href="{{ route('portfolioReports.index') }}" class="btn btn-light float-right">Back</a>
                            </div>
                            <div class="card-body">
                                @include('portfolio_reports.show_fields')
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
</x-app-layout>
