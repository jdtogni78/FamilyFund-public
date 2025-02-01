<!-- Date Field -->
<div class="form-group">
<label for="date">Date:</label>
    <p>{{ $depositRequest->date }}</p>
</div>

<!-- Description Field -->
<div class="form-group">
<label for="description">Description:</label>
    <p>{{ $depositRequest->description }}</p>
</div>

<!-- Amount Field -->
<div class="form-group">
<label for="amount">Amount:</label>
    <p>{{ $depositRequest->amount }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
<label for="status">Status:</label>
    <p>{{ $depositRequest->status_string() }}</p>
</div>

<!-- Account Id Field -->
<div class="form-group">
<label for="account_id">Account Id:</label>
    <p>{{ $depositRequest->account->nickname }}</p>
</div>

<!-- Cash Deposit Id Field -->
<div class="form-group">
<label for="cash_deposit_id">Cash Deposit Id:</label>
    <p>{{ $depositRequest->cash_deposit_id }}</p>
</div>

<!-- Transaction Id Field -->
<div class="form-group">
<label for="transaction_id">Transaction Id:</label>
    <p>{{ $depositRequest->transaction_id }}</p>
</div>

