<!-- Person Id Field -->
<div class="form-group col-sm-6">
<label for="person_id">Person Id:</label>
<select name="person_id" class="form-control">
    @foreach(] as $value => $label)
        <option value="{ $value }" { null == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Number Field -->
<div class="form-group col-sm-6">
<label for="number">Number:</label>
<input type="text" name="number" class="form-control" maxlength="50">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('idDocuments.index') }}" class="btn btn-secondary">Cancel</a>
</div>
