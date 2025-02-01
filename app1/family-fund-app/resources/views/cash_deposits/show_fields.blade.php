<!-- Date Field -->
<div class="form-group">
<label for="date">Date:</label>
    <p>{{ $cashDeposit->date }}</p>
</div>

<!-- Description Field -->
<div class="form-group">
<label for="description">Description:</label>
    <p>{{ $cashDeposit->description }}</p>
</div>

<!-- Value Field -->
<div class="form-group">
<label for="amount">Amount:</label>
    <p>${{ number_format($cashDeposit->amount, 2) }}</p>
</div>

<div class="form-group">
    @php($unassigned = $cashDeposit->amount - $cashDeposit->depositRequests->sum('amount'))
<label for="_unassigned">Unassigned:</label>
    <p id="unassigned" class="text-{{ $unassigned == 0 ? 'primary' : 'danger' }}">${{ number_format($unassigned, 2) }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
<label for="status">Status:</label>
    <p>{{ $cashDeposit->status_string() }}</p>
</div>

<!-- Account Id Field -->
<div class="form-group">
<label for="account_id">Account Id:</label>
    <p>{{ $cashDeposit->account->nickname }}</p>
</div>

<!-- Transaction Id Field -->
<div class="form-group">
<label for="transaction_id">Transaction Id:</label>
    <p>{{ $cashDeposit->transaction_id }}</p>
</div>

