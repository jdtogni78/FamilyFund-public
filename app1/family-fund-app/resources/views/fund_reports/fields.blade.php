<!-- Fund Id Field -->
<div class="form-group col-sm-6">
<label for="fund_id">Fund Id:</label>
<input type="number" name="fund_id" class="form-control">
</div>

<!-- Type Field with typeMap in api -->
<div class="form-group col-sm-6">
<label for="type">Type:</label>
<select name="type" class="form-control">
    @foreach($api['typeMap'] as $value => $label)
        <option value="{ $value }" { 'ADM' == $value ? 'selected' : '' }>{ $label }</option>
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


<!-- Fund Report Schedule Id Field -->
<div class="form-group col-sm-6">
<label for="scheduled_job_id">Scheduled Job Id:</label>
<input type="number" name="scheduled_job_id" class="form-control">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('fundReports.index') }}" class="btn btn-secondary">Cancel</a>
</div>
