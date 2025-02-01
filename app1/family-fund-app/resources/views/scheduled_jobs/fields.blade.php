<!-- Schedule Id Field -->
<div class="form-group col-sm-6">
<label for="schedule_id">Schedule Id:</label>
<input type="number" name="schedule_id" class="form-control">
</div>

<!-- Entity Descr Field -->
<div class="form-group col-sm-6">
<label for="entity_descr">Entity Descr:</label>
<input type="text" name="entity_descr" class="form-control" maxlength="255">
</div>

<!-- Entity Id Field -->
<div class="form-group col-sm-6">
<label for="entity_id">Entity Id:</label>
<input type="number" name="entity_id" class="form-control">
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


<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('scheduledJobs.index') }}" class="btn btn-secondary">Cancel</a>
</div>
