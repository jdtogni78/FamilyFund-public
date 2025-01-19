<!-- Date Field -->
<div class="form-group">
    {!! Form::label('date', 'Date:') !!}
    <p>{{ $cashDeposit->date }}</p>
</div>

<!-- Description Field -->
<div class="form-group">
    {!! Form::label('description', 'Description:') !!}
    <p>{{ $cashDeposit->description }}</p>
</div>

<!-- Value Field -->
<div class="form-group">
    {!! Form::label('amount', 'Amount:') !!}
    <p>${{ number_format($cashDeposit->amount, 2) }}</p>
</div>

<div class="form-group">
    @php($unassigned = $cashDeposit->amount - $cashDeposit->depositRequests->sum('amount'))
    {!! Form::label('_unassigned', 'Unassigned:') !!}
    <p id="unassigned" class="text-{{ $unassigned == 0 ? 'primary' : 'danger' }}">${{ number_format($unassigned, 2) }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $cashDeposit->status_string() }}</p>
</div>

<!-- Account Id Field -->
<div class="form-group">
    {!! Form::label('account_id', 'Account Id:') !!}
    <p>{{ $cashDeposit->account->nickname }}</p>
</div>

<!-- Transaction Id Field -->
<div class="form-group">
    {!! Form::label('transaction_id', 'Transaction Id:') !!}
    <p>{{ $cashDeposit->transaction_id }}</p>
</div>

