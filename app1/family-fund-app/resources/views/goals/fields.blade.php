<!-- Name Field -->
<div class="form-group col-sm-6">
<label for="name">Name:</label>
<input type="text" name="name" class="form-control" maxlength="30">
</div>

<!-- Description Field -->
<div class="form-group col-sm-6">
<label for="description">Description:</label>
<input type="text" name="description" class="form-control" maxlength="1024">
</div>

<!-- Start Dt Field -->
<div class="form-group col-sm-6">
<label for="start_dt">Start Dt:</label>
<input type="text" name="start_dt" class="form-control" id="start_dt">
</div>

@push('scripts')
   <script type="text/javascript">
           $('#start_dt').datetimepicker({
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


<!-- End Dt Field -->
<div class="form-group col-sm-6">
<label for="end_dt">End Dt:</label>
<input type="text" name="end_dt" class="form-control" id="end_dt">
</div>

@push('scripts')
   <script type="text/javascript">
           $('#end_dt').datetimepicker({
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


<!-- Target Type Field -->
<div class="form-group col-sm-6">
<label for="target_type">Target Type:</label>
<select name="target_type" class="form-control">
    @foreach($api['targetTypeMap'] as $value => $label)
        <option value="{{ $value }}" { null == $value ? 'selected' : '' }>{{ $label }}</option>
    @endforeach
</select>
</div>

<!-- Target Amount Field -->
<div class="form-group col-sm-6">
<label for="target_amount">Target Amount:</label>
<input type="number" name="target_amount" class="form-control">
</div>

<!-- Target Pct Field -->
<div class="form-group col-sm-6">
<label for="target_pct">Target Percentage:</label>
<input type="number" name="target_pct" class="form-control" step="0.01">
</div>

<!-- Accounts Field -->
<div class="form-group col-sm-6">
<label for="account_ids[]">Accounts:</label>
<select name="account_ids[]" class="form-control" multiple="multiple" id="account_ids">
    @foreach($api['accountMap'] as $value => $label)
        <option value="{{ $value }}" { null == $value ? 'selected' : '' }>{{ $label }}</option>
    @endforeach
</select>
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('goals.index') }}" class="btn btn-secondary">Cancel</a>
</div>
