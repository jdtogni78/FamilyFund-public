<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{!! route('portfolios.index') !!}">Portfolio</a>
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
                            <strong>Edit Portfolio</strong>
                            @include('portfolios.actions', ['portfolio' => $portfolio])
                        </div>
                        <div class="card-body">
                            <form action="{{ route('portfolios.update', $portfolio->id) }}" method="PATCH">
                                @csrf
                                @include('portfolios.fields')
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>