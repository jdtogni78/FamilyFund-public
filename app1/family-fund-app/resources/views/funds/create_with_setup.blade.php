<x-app-layout>

@section('content')
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
         <a href="{!! route('funds.index') !!}">Fund</a>
      </li>
      <li class="breadcrumb-item active">Create with Setup</li>
    </ol>
     <div class="container-fluid">
          <div class="animated fadeIn">
                @include('coreui-templates.common.errors')
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-plus-square-o fa-lg"></i>
                                <strong>Create Fund with Complete Setup</strong>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-4">
                                    <i class="fa fa-info-circle me-1"></i>
                                    This will create a fund with account, portfolio, and initial transaction all at once.
                                    You can preview the setup before creating.
                                </p>

                                <form method="POST" action="{{ route('funds.storeWithSetup') }}">
                                    @csrf

                                    @include('funds.fields_with_setup')

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
           </div>
    </div>
</x-app-layout>
