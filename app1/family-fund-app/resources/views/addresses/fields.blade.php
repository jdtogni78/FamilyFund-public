<!-- Person Id Field -->
<div class="form-group col-sm-6">
<label for="person_id">Person Id:</label>
<select name="person_id" class="form-control">
    @foreach(] as $value => $label)
        <option value="{ $value }" { null == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Is Primary Field -->
<div class="form-group col-sm-6">
<label for="is_primary">Is Primary:</label>
    <label class="checkbox-inline">
<input type="hidden" name="is_primary" value="0" >
<input type="checkbox" name="is_primary" value="1" >
    </label>
</div>


<!-- Street Field -->
<div class="form-group col-sm-6">
<label for="street">Street:</label>
<input type="text" name="street" class="form-control" maxlength="255">
</div>

<!-- Number Field -->
<div class="form-group col-sm-6">
<label for="number">Number:</label>
<input type="text" name="number" class="form-control" maxlength="20">
</div>

<!-- Complement Field -->
<div class="form-group col-sm-6">
<label for="complement">Complement:</label>
<input type="text" name="complement" class="form-control" maxlength="255">
</div>

<!-- County Field -->
<div class="form-group col-sm-6">
<label for="county">County:</label>
<input type="text" name="county" class="form-control" maxlength="255">
</div>

<!-- City Field -->
<div class="form-group col-sm-6">
<label for="city">City:</label>
<input type="text" name="city" class="form-control" maxlength="255">
</div>

<!-- State Field -->
<div class="form-group col-sm-6">
<label for="state">State:</label>
<input type="text" name="state" class="form-control" maxlength="2">
</div>

<!-- Zip Code Field -->
<div class="form-group col-sm-6">
<label for="zip_code">Zip Code:</label>
<input type="text" name="zip_code" class="form-control" maxlength="10">
</div>

<!-- Country Field -->
<div class="form-group col-sm-6">
<label for="country">Country:</label>
<input type="text" name="country" class="form-control" maxlength="255">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('addresses.index') }}" class="btn btn-secondary">Cancel</a>
</div>
