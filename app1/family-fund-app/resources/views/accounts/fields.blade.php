<!-- Code Field -->
<div class="form-group col-sm-6">
<label for="code">Code:</label>
<input type="text" name="code" class="form-control" maxlength="15">
</div>

<!-- Nickname Field -->
<div class="form-group col-sm-6">
<label for="nickname">Nickname:</label>
<input type="text" name="nickname" class="form-control" maxlength="15">
</div>

<!-- Email Cc Field -->
<div class="form-group col-sm-6">
<label for="email_cc">Email Cc:</label>
<input type="text" name="email_cc" class="form-control" maxlength="1024">
</div>

<!-- User Id Field -->
<div class="form-group col-sm-6">
<label for="user_id">User Id:</label>
<select name="user_id" class="form-control">
    @foreach($api['userMap'] as $value => $label)
        <option value="{ $value }" { null == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Fund Id Field -->
<div class="form-group col-sm-6">
<label for="fund_id">Fund Id:</label>
<select name="fund_id" class="form-control">
    @foreach($api['fundMap'] as $value => $label)
        <option value="{ $value }" { null == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('accounts.index') }}" class="btn btn-secondary">Cancel</a>
</div>
