<!-- Object Field -->
<div class="form-group col-sm-6">
<label for="object">Object:</label>
<input type="text" name="object" class="form-control" maxlength="50">
</div>

<!-- Content Field -->
<div class="form-group col-sm-12 col-lg-12">
<label for="content">Content:</label>
<textarea name="content" class="form-control"></textarea>
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('changeLogs.index') }}" class="btn btn-secondary">Cancel</a>
</div>
