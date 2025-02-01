<!-- Source Field -->
<div class="form-group col-sm-6">
<label for="source">Source:</label>
<input type="text" name="source" class="form-control" maxlength="30">
</div>

<!-- Name Field -->
<div class="form-group col-sm-6">
<label for="name">Name:</label>
<input type="text" name="name" class="form-control" maxlength="128">
</div>

<!-- Type Field -->
<div class="form-group col-sm-6">
<label for="type">Type:</label>
<input type="text" name="type" class="form-control" maxlength="20">
</div>

<!-- Display Group Field -->
<div class="form-group col-sm-6">
<label for="display_group">Display Group:</label>
<input type="text" name="display_group" class="form-control" maxlength="50">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('assets.index') }}" class="btn btn-secondary">Cancel</a>
</div>
