<!-- Descr Field -->
<div class="form-group col-sm-6">
<label for="descr">Descr:</label>
<input type="text" name="descr" class="form-control" maxlength="255">
</div>

<!-- Type Field -->
<div class="form-group col-sm-6">
<label for="type">Type:</label>
<input type="text" name="type" class="form-control" maxlength="3">
</div>

<!-- Value Field -->
<div class="form-group col-sm-6">
<label for="value">Value:</label>
<input type="text" name="value" class="form-control" maxlength="255">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('schedules.index') }}" class="btn btn-secondary">Cancel</a>
</div>
