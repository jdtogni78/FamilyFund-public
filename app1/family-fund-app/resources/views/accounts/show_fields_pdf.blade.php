@php
    $field_props = ['class' => 'form-control', 'readonly']
@endphp
<div class="row" style="min-height: 210px">
    <div class="form-group col-sm-6 col-left">
        @php $field = 'nickname'; @endphp
        {!! Form::label($field, 'Nickname:') !!}
        {!! Form::text($field, $api[$field], $field_props) !!}
    </div>
    <div class="form-group col-sm-6 col-right">
        @php $field = 'fund'; @endphp
        {!! Form::label($field, 'Fund:') !!}
        <div class="input-group">
            {!! Form::text($field, $api[$field]['name'], $field_props) !!}
        </div>
    </div>
    <div class="form-group col-sm-6 col-left">
        @php $field = 'user'; @endphp
        {!! Form::label($field, 'User:') !!}
        {!! Form::text($field, $api[$field]['name'], $field_props) !!}
    </div>
    <div class="form-group col-sm-6 col-right">
        @php $field = 'email_cc'; @endphp
        {!! Form::label($field, 'Email CC:') !!}
        {!! Form::text($field, $api[$field], $field_props) !!}
    </div>
    <div class="form-group col-sm-6 col-left">
        @php $field = 'as_of'; @endphp
        {!! Form::label($field, 'As Of:') !!}
        {!! Form::text($field, $api[$field], $field_props) !!}
    </div>
</div>
