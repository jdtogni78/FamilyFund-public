@extends('layouts.app')

@section('content')
    <section class="content-header">
        <h1>Person Details</h1>
    </section>
    <div class="content">
        <div class="box box-primary">
            <div class="box-body">
                <div class="row">
                    @include('persons.show_fields')
                </div>
            </div>
        </div>
        <a href="{{ route('persons.index') }}" class="btn btn-default">Back</a>
    </div>
@endsection 