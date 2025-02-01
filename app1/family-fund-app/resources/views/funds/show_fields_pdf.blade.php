@php
    $field_props = ['class' => 'form-control', 'readonly']
@endphp
<div class="row" style="min-height: 310px">
    <div class="form-group col-sm-6 col-left">
        @php $field = 'name'; @endphp
<label for="{{ $field }}">Name:</label>
<input type="text" name="{{ $field }}" value="{{ $api[$field] }}" >
    </div>
    <div class="form-group col-sm-6 col-right">
        @php $field = 'shares'; @endphp
<label for="{{ $field }}">Shares:</label>
<input type="number" name="{{ $field }}" value="{{ $api['summary'][$field] }}" >
    </div>
    <div class="form-group col-sm-6 col-left">
        @php $field = 'allocated_shares'; @endphp
<label for="{{ $field }}">Allocated Shares:</label>
        <div class="input-group">
<input type="number" name="{{ $field }}" value="{{ $api['summary'][$field] }}" >
<input type="number" name="{{ $field . '_percent' }}" value="{{ $api['summary'][$field . '_percent'] }}" >
            <div class="input-group-text a">%</div>
        </div>
    </div>
    <div class="form-group col-sm-6 col-right">
        @php $field = 'unallocated_shares'; @endphp
<label for="{{ $field }}">Unallocated Shares:</label>
        <div class="">
<input type="number" name="{{ $field }}" value="{{ $api['summary'][$field] }}" >
<input type="number" name="{{ $field . '_percent' }}" value="{{ $api['summary'][$field . '_percent'] }}" >
            <div class="input-group-text igt">%</div>
        </div>
    </div>
    <div class="form-group col-sm-6 col-left">
        @php $field = 'value'; @endphp
<label for="{{ $field }}">Total Value:</label>
        <div class="">
            <div class="input-group-text">$</div>
<input type="number" name="{{ $field }}" value="{{ $api['summary'][$field] }}" >
        </div>
    </div>
    <div class="form-group col-sm-6 col-right">
        @php $field = 'share_value'; @endphp
<label for="{{ $field }}">Share Price:</label>
        <div class="input-group">
            <div class="input-group-text">$</div>
<input type="number" name="{{ $field }}" value="{{ $api['summary'][$field] }}" >
        </div>
    </div>
    <div class="form-group col-sm-6 col-left">
        @php $field = 'as_of'; @endphp
<label for="{{ $field }}">As Of:</label>
<input type="text" name="{{ $field }}" value="{{ $api[$field] }}" >
    </div>
</div>
