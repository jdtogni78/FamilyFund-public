<!-- Action Field -->
<div class="form-group col-sm-6">
<label for="action">Action:</label>
<input type="text" name="action" class="form-control" maxlength="255">
</div>

<!-- Asset Id Field -->
<div class="form-group col-sm-6">
<label for="asset_id">Asset Id:</label>
<input type="number" name="asset_id" class="form-control">
</div>

<!-- Field Field -->
<div class="form-group col-sm-12 col-lg-12">
<label for="field">Field:</label>
<textarea name="field" class="form-control"></textarea>
</div>

<!-- Content Field -->
<div class="form-group col-sm-12 col-lg-12">
<label for="content">Content:</label>
<textarea name="content" class="form-control"></textarea>
</div>

<!-- Datetime Field -->
<div class="form-group col-sm-6">
<label for="datetime">Datetime:</label>
<input type="text" name="datetime" class="form-control" id="datetime">
</div>

@push('scripts')
   <script type="text/javascript">
           $('#datetime').datetimepicker({
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
    <a href="{{ route('assetChangeLogs.index') }}" class="btn btn-secondary">Cancel</a>
</div>
