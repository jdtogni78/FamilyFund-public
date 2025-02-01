<!-- Type Field -->
<div class="form-group">
<label for="type">Type:</label>
    <p>{{ $accountBalance->type }}</p>
</div>

<!-- Shares Field -->
<div class="form-group">
<label for="shares">Shares:</label>
    <p>{{ $accountBalance->shares }}</p>
</div>

<!-- Previous Shares Field -->
<div class="form-group">
<label for="previous_shares">Previous Shares:</label>
    <p>{{ $accountBalance->previousBalance?->shares }}</p>
</div>

<!-- Previous Balance Id Field -->
<div class="form-group">
<label for="previous_balance_id">Previous Balance Id:</label>
    <p>{{ $accountBalance->previousBalance?->id }}</p>
</div>

<!-- Account Id Field -->
<div class="form-group">
<label for="account_id">Account Id:</label>
    <p>{{ $accountBalance->account_id }}</p>
</div>

<!-- Transaction Id Field -->
<div class="form-group">
<label for="transaction_id">Transaction Id:</label>
    <p>{{ $accountBalance->transaction_id }}</p>
</div>

<!-- Start Dt Field -->
<div class="form-group">
<label for="start_dt">Start Dt:</label>
    <p>{{ $accountBalance->start_dt }}</p>
</div>

<!-- End Dt Field -->
<div class="form-group">
<label for="end_dt">End Dt:</label>
    <p>{{ $accountBalance->end_dt }}</p>
</div>

