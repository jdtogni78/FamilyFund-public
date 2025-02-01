<!-- Type Field -->
<div class="form-group">
<label for="type">Type:</label>
    <p>{{ $transaction->type }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
<label for="status">Status:</label>
    <p>{{ $transaction->status }}</p>
</div>

<!-- Value Field -->
<div class="form-group">
<label for="value">Value:</label>
    <p>{{ $transaction->value }}</p>
</div>

<!-- Shares Field -->
<div class="form-group">
<label for="shares">Shares:</label>
    <p>{{ $transaction->shares }}</p>
</div>

<!-- Timestamp Field -->
<div class="form-group">
<label for="timestamp">Timestamp:</label>
    <p>{{ $transaction->timestamp }}</p>
</div>

<!-- Account Id Field -->
<div class="form-group">
<label for="account_id">Account Id:</label>
    <p>{{ $transaction->account_id }}</p>
</div>

<!-- Descr Field -->
<div class="form-group">
<label for="descr">Descr:</label>
    <p>{{ $transaction->descr }}</p>
</div>

<!-- Flags Field -->
<div class="form-group">
<label for="flags">Flags:</label>
    <p>{{ $transaction->flags }}</p>
</div>

<!-- Cash Deposit Id Field -->
<div class="form-group">
<label for="cash_deposit_id">Cash Deposit Id:</label>
    <p>{{ $transaction->cashDeposit?->id }}</p>
</div> 

<!-- Deposit Request Id Field -->
<div class="form-group">
<label for="deposit_request_id">Deposit Request Id:</label>
    <p>{{ $transaction->depositRequest?->id }}</p>
</div> 

<!-- Balance Field -->
<div class="form-group">
<label for="balance">Balance:</label>
    <p>{{ $transaction->balance?->id }}</p>
</div> 
