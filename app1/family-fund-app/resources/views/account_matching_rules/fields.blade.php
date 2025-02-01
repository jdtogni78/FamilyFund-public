<!-- Account Id Field -->
<div class="form-group col-sm-6">
<label for="account_id">Account Id:</label>
<select name="account_id" class="form-control">
    @foreach($api['account'] as $value => $label)
        <option value="{ $value }" { null == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Matching Rule Id Field -->
<div class="form-group col-sm-6">
<label for="matching_rule_id">Matching Rule Id:</label>
<select name="matching_rule_id" class="form-control">
    @foreach($api['mr'] as $value => $label)
        <option value="{ $value }" { null == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('accountMatchingRules.index') }}" class="btn btn-secondary">Cancel</a>
</div>
