@php
    $field_props = ['class' => 'form-control', 'readonly']
@endphp
<div class="row">
<div class="form-group col-sm-6">
    @php $field = 'nickname'; @endphp
    {!! Form::label($field, 'Nickname:') !!}
    {!! Form::text($field, $api[$field], $field_props) !!}
</div>
<div class="form-group col-sm-6">
    @php $field = 'fund'; @endphp
    {!! Form::label($field, 'Fund:') !!}
    <div class="input-group">
        {!! Form::text($field, $api[$field]['name'], $field_props) !!}
        <a href="{{ route('funds.show', [$api[$field]['id']]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
    </div>
</div>
<div class="form-group col-sm-6">
    @php $field = 'user'; @endphp
    {!! Form::label($field, 'User:') !!}
    {!! Form::text($field, $api[$field]['name'], $field_props) !!}
</div>
<div class="form-group col-sm-6">
    @php $field = 'email_cc'; @endphp
    {!! Form::label($field, 'Email CC:') !!}
    {!! Form::text($field, $api[$field], $field_props) !!}
</div>
    <div class="form-group col-sm-6">
        @php $field = 'shares'; @endphp
        {!! Form::label($field, 'Shares:') !!}
        <div class="input-group">
            {!! Form::number($field, $api['balances'][0][$field],  $field_props) !!}
        </div>
    </div>
    <div class="form-group col-sm-6">
        @php $field = 'market_value'; @endphp
        {!! Form::label($field, 'Market Value:') !!}
        <div class="input-group">
            <div class="input-group-text">$</div>
            {!! Form::number($field, $api['balances'][0][$field],  $field_props) !!}
        </div>
    </div>
    <div class="form-group col-sm-6">
        @php $field = 'matching_available'; @endphp
        {!! Form::label($field, 'Matching Available:') !!}
        <div class="input-group">
            <div class="input-group-text">$</div>
            {!! Form::number($field, $api[$field],  $field_props) !!}
        </div>
    </div>
<div class="form-group col-sm-6">
    @php $field = 'as_of'; @endphp
    {!! Form::label($field, 'As Of:') !!}
    {!! Form::text($field, $api[$field], $field_props) !!}
</div>
</div>
