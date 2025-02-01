<!-- Type Field -->
<div class="form-group col-sm-6">
<label for="type">Type:</label>
<input type="text" name="type" class="form-control" maxlength="3">
</div>

<!-- Shares Field -->
<div class="form-group col-sm-6">
<label for="shares">Shares:</label>
<input type="number" name="shares" class="form-control" step="0.0001">
</div>

<!-- Account Id Field -->
<div class="form-group col-sm-6">
<label for="account_id">Account Id:</label>
<input type="number" name="account_id" class="form-control">
</div>

<!-- Transaction Id Field -->
<div class="form-group col-sm-6">
<label for="transaction_id">Transaction Id:</label>
<input type="number" name="transaction_id" class="form-control">
</div>

<!-- Previous Balance Id Field -->
<div class="form-group col-sm-6">
<label for="previous_balance_id">Previous Balance Id:</label>
<input type="number" name="previous_balance_id" class="form-control">
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
    <a href="{{ route('accountBalances.index') }}" class="btn btn-secondary">Cancel</a>
</div>
