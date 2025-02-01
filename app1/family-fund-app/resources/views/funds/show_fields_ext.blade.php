@php
    $field_props = ['class' => 'form-control', 'readonly']
@endphp
<div class="row">
<div class="form-group col-sm-6">
    @php $field = 'name'; @endphp
<label for="{{ $field }}">Name:</label>
<input type="text" name="{{ $field }}" value="{{ $api[$field] }}" >
</div>
<div class="form-group col-sm-6">
    @php $field = 'shares'; @endphp
<label for="{{ $field }}">Shares:</label>
<input type="number" name="{{ $field }}" value="{{ $api['summary'][$field] }}" >
</div>
<div class="form-group col-sm-6">
    @php $field = 'allocated_shares'; @endphp
<label for="{{ $field }}">Allocated Shares:</label>
    <div class="input-group">
<input type="number" name="{{ $field }}" value="{{ $api['summary'][$field] }}" >
<input type="number" name="{{ $field . '_percent' }}" value="{{ $api['summary'][$field . '_percent'] }}" >
        <div class="input-group-text">%</div>
    </div>
</div>
<div class="form-group col-sm-6">
    @php $field = 'unallocated_shares'; @endphp
<label for="{{ $field }}">Unallocated Shares:</label>
    <div class="input-group">
<input type="number" name="{{ $field }}" value="{{ $api['summary'][$field] }}" >
<input type="number" name="{{ $field . '_percent' }}" value="{{ $api['summary'][$field . '_percent'] }}" >
        <div class="input-group-text">%</div>
    </div>
</div>
<div class="form-group col-sm-6">
    @php $field = 'unallocated_value'; @endphp
<label for="{{ $field }}">Unallocated Value:</label>
    <div class="input-group">
        <div class="input-group-text">$</div>
<input type="number" name="{{ $field }}" value="{{ $api['summary'][$field] }}" >
    </div>
</div>
<div class="form-group col-sm-6">
    @php $field = 'value'; @endphp
<label for="{{ $field }}">Total Value:</label>
    <div class="input-group">
        <div class="input-group-text">$</div>
<input type="number" name="{{ $field }}" value="{{ $api['summary'][$field] }}" >
    </div>
</div>
<div class="form-group col-sm-6">
    @php $field = 'share_value'; @endphp
<label for="{{ $field }}">Share Price:</label>
    <div class="input-group">
        <div class="input-group-text">$</div>
<input type="number" name="{{ $field }}" value="{{ $api['summary'][$field] }}" >
    </div>
</div>
@isset($api['admin'])
<div class="form-group col-sm-6">
    @php $field = 'admin'; @endphp
<label for="{{ $field }}">Admin:</label>
    ADMIN
</div>
@endisset
<div class="form-group col-sm-6">
    @php $field = 'as_of'; @endphp
<label for="{{ $field }}">As Of:</label>
<input type="text" name="{{ $field }}" value="{{ $api[$field] }}" >
</div>
</div>
