<!-- Account Id Field -->
<div class="form-group col-sm-6">
<label for="account_id">Account Id:</label>
<input type="number" name="account_id" class="form-control">
</div>

<!-- Type Field with api typemap -->
<div class="form-group col-sm-6">
<label for="type">Type:</label>
<select name="type" class="form-control custom-select">
    @foreach($api['typeMap'] as $value => $label)
        <option value="{ $value }" { null == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- As Of Field -->
<div class="form-group col-sm-6">
<label for="as_of">As Of:</label>
<input type="text" name="as_of" class="form-control" id="as_of">
</div>

@push('scripts')
   <script type="text/javascript">
           $('#as_of').datetimepicker({
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


<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('accountReports.index') }}" class="btn btn-secondary">Cancel</a>
</div>
