@php
    $field_props = ['class' => 'form-control', 'readonly'];
    $disb = $api['disbursable'];
@endphp
<div class="row">
    <div class="form-group col-sm-6">
        @php $field = 'value'; @endphp
        <label for="{{ $field }}">Value:</label>
        <input type="text" name="{{ $field }}" value="{{ '$' . $disb[$field] }}" >
    </div>
    <div class="form-group col-sm-6">
        @php $field = 'limit'; @endphp
        <label for="{{ $field }}">Cap:</label>
        <input type="text" name="{{ $field }}" value="{{ $disb[$field] . '%' }}" >
    </div>
    <div class="form-group col-sm-6">
        @php $field = 'performance'; @endphp
        <label for="{{ $field }}">Performance:</label>
        <input type="text" name="{{ $field }}" value="{{ $disb[$field] . '%' }}" >
    </div>
    <div class="form-group col-sm-6">
        @php $field = 'year'; @endphp
        <label for="{{ $field }}">Year:</label>
        <input type="text" name="{{ $field }}" value="{{ $disb[$field] }}" >
    </div>
</div>
