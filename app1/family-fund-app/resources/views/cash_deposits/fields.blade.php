<!-- Date Field -->
<div class="form-group col-sm-6">
<label for="date">Date:</label>
<input type="text" name="date" class="form-control" id="date">
</div>

@push('scripts')
   <script type="text/javascript">
           $('#date').datetimepicker({
               format: 'YYYY-MM-DD HH:mm:ss',
               useCurrent: true,
               icons: {
                   up: "icon-arrow-up-circle icons font-2xl",
                   down: "icon-arrow-down-circle icons font-2xl"
               },
               sideBySide: true
           })
       </script>
@endpush


<!-- Description Field -->
<div class="form-group col-sm-6">
<label for="description">Description:</label>
<input type="text" name="description" class="form-control">
</div>

<!-- Value Field -->
<div class="form-group col-sm-6">
<label for="amount">Amount:</label>
<input type="number" name="amount" class="form-control" min="0" step="0.01">
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
<label for="status">Status:</label>
<select name="status" class="form-control">
    @foreach($api['statusMap'] as $value => $label)
        <option value="{ $value }" { null == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Account Id Field -->
<div class="form-group col-sm-6">
<label for="account_id">Account Id:</label>
<select name="account_id" class="form-control">
    @foreach($api['fundAccountMap'] as $value => $label)
        <option value="{ $value }" { null == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

@if ($isEdit)
<!-- Transaction Id Field -->
<div class="form-group col-sm-6">
<label for="transaction_id">Transaction Id:</label>
<input type="text" name="transaction_id" class="form-control">
</div>
@endif
<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('cashDeposits.index') }}" class="btn btn-secondary">Cancel</a>
</div>
