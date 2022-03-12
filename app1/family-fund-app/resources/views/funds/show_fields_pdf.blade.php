@php
    $field_props = ['class' => 'form-control', 'readonly']
@endphp
<div class="row" style="min-height: 310px">
    <div class="form-group col-sm-6 col-left">
        @php $field = 'name'; @endphp
        {!! Form::label($field, 'Name:') !!}
        {!! Form::text($field, $api[$field], $field_props) !!}
    </div>
    <div class="form-group col-sm-6 col-right">
        @php $field = 'shares'; @endphp
        {!! Form::label($field, 'Shares:') !!}
        {!! Form::number($field, $api['summary'][$field],  $field_props) !!}
    </div>
    <div class="form-group col-sm-6 col-left">
        @php $field = 'allocated_shares'; @endphp
        {!! Form::label($field, 'Allocated Shares:') !!}
        <div class="input-group">
            {!! Form::number($field, $api['summary'][$field],  $field_props) !!}
            {!! Form::number($field . '_percent', $api['summary'][$field . '_percent'],  $field_props) !!}
            <div class="input-group-text a">%</div>
        </div>
    </div>
    <div class="form-group col-sm-6 col-right">
        @php $field = 'unallocated_shares'; @endphp
        {!! Form::label($field, 'Unallocated Shares:') !!}
        <div class="">
            {!! Form::number($field, $api['summary'][$field],  $field_props) !!}
            {!! Form::number($field . '_percent', $api['summary'][$field . '_percent'],  $field_props) !!}
            <div class="input-group-text igt">%</div>
        </div>
    </div>
    <div class="form-group col-sm-6 col-left">
        @php $field = 'value'; @endphp
        {!! Form::label($field, 'Total Value:') !!}
        <div class="">
            <div class="input-group-text">$</div>
            {!! Form::number($field, $api['summary'][$field],  $field_props) !!}
        </div>
    </div>
    <div class="form-group col-sm-6 col-right">
        @php $field = 'share_value'; @endphp
        {!! Form::label($field, 'Share Price:') !!}
        <div class="input-group">
            <div class="input-group-text">$</div>
            {!! Form::number($field, $api['summary'][$field],  $field_props) !!}
        </div>
    </div>
    <div class="form-group col-sm-6 col-left">
        @php $field = 'as_of'; @endphp
        {!! Form::label($field, 'As Of:') !!}
        {!! Form::text($field, $api[$field],  $field_props) !!}
    </div>
</div>
