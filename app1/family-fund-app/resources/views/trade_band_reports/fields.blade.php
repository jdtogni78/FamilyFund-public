<!-- Fund Id Field -->
<div class="form-group col-sm-6">
    <label for="fund_id">Fund:</label>
    <select name="fund_id" class="form-control" required>
        <option value="">Select Fund</option>
        @foreach($api['fundMap'] as $id => $name)
            <option value="{{ $id }}" {{ (old('fund_id', $tradeBandReport->fund_id ?? '') == $id) ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
</div>

<!-- As Of Field -->
<div class="form-group col-sm-6">
    <label for="as_of">As Of:</label>
    <input type="text" name="as_of" class="form-control" id="as_of" value="{{ old('as_of', isset($tradeBandReport) ? $tradeBandReport->as_of->format('Y-m-d') : '') }}" required>
</div>

@push('scripts')
   <script type="text/javascript">
           $('#as_of').datetimepicker({
               format: 'YYYY-MM-DD',
               useCurrent: true,
               icons: {
                   up: "icon-arrow-up-circle icons font-2xl",
                   down: "icon-arrow-down-circle icons font-2xl"
               },
               sideBySide: true
           })
       </script>
@endpush

<!-- Scheduled Job Id Field -->
<div class="form-group col-sm-6">
    <label for="scheduled_job_id">Scheduled Job Id:</label>
    <input type="number" name="scheduled_job_id" class="form-control" value="{{ old('scheduled_job_id', $tradeBandReport->scheduled_job_id ?? '') }}">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('tradeBandReports.index') }}" class="btn btn-secondary">Cancel</a>
</div>
