@php
    $field_props = ['class' => 'form-control', 'readonly']
@endphp
<div class="row">
<div class="form-group col-sm-6">
    @php $field = 'name'; @endphp
    {!! Form::label($field, 'Name:') !!}
    {!! Form::text($field, $api[$field], $field_props) !!}
</div>
<div class="form-group col-sm-6">
    @php $field = 'shares'; @endphp
    {!! Form::label($field, 'Shares:') !!}
    {!! Form::number($field, $api['summary'][$field],  $field_props) !!}
</div>
<div class="form-group col-sm-6">
    @php $field = 'allocated_shares'; @endphp
    {!! Form::label($field, 'Allocated Shares:') !!}
    <div class="input-group">
        {!! Form::number($field, $api['summary'][$field],  $field_props) !!}
        {!! Form::number($field . '_percent', $api['summary'][$field . '_percent'],  $field_props) !!}
        <div class="input-group-text">%</div>
    </div>
</div>
<div class="form-group col-sm-6">
    @php $field = 'unallocated_shares'; @endphp
    {!! Form::label($field, 'Unallocated Shares:') !!}
    <div class="input-group">
        {!! Form::number($field, $api['summary'][$field],  $field_props) !!}
        {!! Form::number($field . '_percent', $api['summary'][$field . '_percent'],  $field_props) !!}
        <div class="input-group-text">%</div>
    </div>
</div>
<div class="form-group col-sm-6">
    @php $field = 'value'; @endphp
    {!! Form::label($field, 'Total Value:') !!}
    <div class="input-group">
        <div class="input-group-text">$</div>
        {!! Form::number($field, $api['summary'][$field],  $field_props) !!}
    </div>
</div>
<div class="form-group col-sm-6">
    @php $field = 'share_value'; @endphp
    {!! Form::label($field, 'Share Price:') !!}
    <div class="input-group">
        <div class="input-group-text">$</div>
        {!! Form::number($field, $api['summary'][$field],  $field_props) !!}
    </div>
</div>
@isset($api['admin'])
<div class="form-group col-sm-6">
    @php $field = 'admin'; @endphp
    {!! Form::label($field, 'Admin:') !!}
</div>
@endisset
<div class="form-group col-sm-6">
    @php $field = 'as_of'; @endphp
    {!! Form::label($field, 'As Of:') !!}
    {!! Form::text($field, $api[$field],  $field_props) !!}
</div>
</div>
