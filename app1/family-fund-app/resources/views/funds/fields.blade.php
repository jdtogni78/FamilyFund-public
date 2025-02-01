<!-- Name Field -->
<div class="form-group col-sm-6">
<label for="name">Name:</label>
<input type="text" name="name" value="{{ $fund?->name ?? null }}" class="form-control" maxlength="30">
</div>

<!-- Goal Field -->
<div class="form-group col-sm-6">
<label for="goal">Goal:</label>
<input type="text" name="goal" value="{{ $fund?->goal ?? null }}" class="form-control" maxlength="1024">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('funds.index') }}" class="btn btn-secondary">Cancel</a>
</div>
