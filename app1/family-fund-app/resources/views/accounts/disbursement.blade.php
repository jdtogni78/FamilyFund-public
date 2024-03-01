@php
    $field_props = ['class' => 'form-control', 'readonly'];
    $disb = $api['disbursable'];
@endphp
<div class="row">
    <div class="form-group col-sm-6">
        @php $field = 'value'; @endphp
        {!! Form::label($field, 'Value:') !!}
        {!! Form::text($field, '$' . $disb[$field], $field_props) !!}
    </div>
    <div class="form-group col-sm-6">
        @php $field = 'limit'; @endphp
        {!! Form::label($field, 'Cap:') !!}
        {!! Form::text($field, $disb[$field] . '%', $field_props) !!}
    </div>
    <div class="form-group col-sm-6">
        @php $field = 'performance'; @endphp
        {!! Form::label($field, 'Performance:') !!}
        {!! Form::text($field, $disb[$field] . "%", $field_props) !!}
    </div>
    <div class="form-group col-sm-6">
        @php $field = 'year'; @endphp
        {!! Form::label($field, 'Year:') !!}
        {!! Form::text($field, $disb[$field], $field_props) !!}
    </div>
</div>
