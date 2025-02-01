<div class="form-group">
<label for="account_id">Account Id:</label>
    <p>{{ $api['account']->id }}</p>
</div>

<div class="form-group">
<label for="account_nickname">Account Nickname:</label>
    <p>{{ $api['account']->nickname }}</p>
</div>

<div class="form-group">
<label for="effective_from">Effective From:</label>
    <p>{{ max($api['mr']->date_start, $accountMatchingRule->created_at) }}</p>
</div>


