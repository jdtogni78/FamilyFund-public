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
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-edit fa-lg me-2"></i>
                                <strong>Edit Portfolio</strong>
                            </div>
                            @include('portfolios.actions', ['portfolio' => $portfolio])
                        </div>
                        <div class="card-body">
                            <form action="{{ route('portfolios.update', $portfolio->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                @include('portfolios.fields')
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>