@extends('layouts.app')

@section('content')
    <section class="content-header">
        <h1>Create Person</h1>
    </section>
    <div class="content">
        @include('adminlte-templates::common.errors')
        <div class="box box-primary">
            <div class="box-body">
                {!! Form::open(['route' => 'persons.store']) !!}
                    @include('persons.fields')
                {!! Form::close() !!}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.add-phone').click(function() {
        var index = $('.phone-entry').length;
        var template = $('.phone-entry').first().clone();
        template.find('input, select').each(function() {
            var name = $(this).attr('name').replace('[0]', '[' + index + ']');
            $(this).attr('name', name).val('');
        });
        $('.phones-container').append(template);
    });

    $('.add-address').click(function() {
        var index = $('.address-entry').length;
        var template = $('.address-entry').first().clone();
        template.find('input, select').each(function() {
            var name = $(this).attr('name').replace('[0]', '[' + index + ']');
            $(this).attr('name', name).val('');
        });
        $('.addresses-container').append(template);
    });

    $('.add-document').click(function() {
        var index = $('.document-entry').length;
        var template = $('.document-entry').first().clone();
        template.find('input, select').each(function() {
            var name = $(this).attr('name').replace('[0]', '[' + index + ']');
            $(this).attr('name', name).val('');
        });
        $('.documents-container').append(template);
    });
});
</script>
@endpush 