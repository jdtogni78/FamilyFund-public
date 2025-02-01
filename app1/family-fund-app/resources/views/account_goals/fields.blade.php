<!-- Account Id Field -->
<div class="form-group col-sm-6">
<label for="account_id">Account Id:</label>
<select name="account_id" class="form-control">
    @foreach(] as $value => $label)
        <option value="{ $value }" { null == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Goal Id Field -->
<div class="form-group col-sm-6">
<label for="goal_id">Goal Id:</label>
<select name="goal_id" class="form-control">
    @foreach(] as $value => $label)
        <option value="{ $value }" { null == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('accountGoals.index') }}" class="btn btn-secondary">Cancel</a>
</div>
