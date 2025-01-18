<!-- Date Field -->
<div class="form-group">
    {!! Form::label('date', 'Date:') !!}
    <p>{{ $depositRequest->date }}</p>
</div>

<!-- Description Field -->
<div class="form-group">
    {!! Form::label('description', 'Description:') !!}
    <p>{{ $depositRequest->description }}</p>
</div>

<!-- Amount Field -->
<div class="form-group">
    {!! Form::label('amount', 'Amount:') !!}
    <p>{{ $depositRequest->amount }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $api['statusMap'][$depositRequest->status] }}</p>
</div>

<!-- Account Id Field -->
<div class="form-group">
    {!! Form::label('account_id', 'Account Id:') !!}
    <p>{{ $depositRequest->account_id }}</p>
</div>

<!-- Cash Deposit Id Field -->
<div class="form-group">
    {!! Form::label('cash_deposit_id', 'Cash Deposit Id:') !!}
    <p>{{ $depositRequest->cash_deposit_id }}</p>
</div>

<!-- Transaction Id Field -->
<div class="form-group">
    {!! Form::label('transaction_id', 'Transaction Id:') !!}
    <p>{{ $depositRequest->transaction_id }}</p>
</div>

