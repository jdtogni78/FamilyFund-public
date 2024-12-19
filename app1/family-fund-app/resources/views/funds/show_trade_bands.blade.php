@extends('layouts.app')

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('funds.index') }}">Fund</a>
        </li>
        <li class="breadcrumb-item active">Detail</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates::common.errors')
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <strong>Details</strong>
                            <a href="{{ route('funds.index') }}" class="btn btn-light">Back</a>
                        </div>
                        <div class="card-body">
                            {!! Form::open(['route' => ['funds.update', 1]]) !!}
                            @include('funds.show_fields_ext')
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
            @include('funds.performance_line_graph_assets_with_bands')
        </div>
    </div>
@endsection
