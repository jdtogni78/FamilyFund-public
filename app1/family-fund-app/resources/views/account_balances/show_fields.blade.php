<!-- Type Field -->
<div class="form-group">
    {!! Form::label('type', 'Type:') !!}
    <p>{{ $accountBalance->type }}</p>
</div>

<!-- Shares Field -->
<div class="form-group">
    {!! Form::label('shares', 'Shares:') !!}
    <p>{{ $accountBalance->shares }}</p>
</div>

<!-- Previous Shares Field -->
<div class="form-group">
    {!! Form::label('previous_shares', 'Previous Shares:') !!}
    <p>{{ $accountBalance->previousBalance?->shares }}</p>
</div>

<!-- Previous Balance Id Field -->
<div class="form-group">
    {!! Form::label('previous_balance_id', 'Previous Balance Id:') !!}
    <p>{{ $accountBalance->previousBalance?->id }}</p>
</div>

<!-- Account Id Field -->
<div class="form-group">
    {!! Form::label('account_id', 'Account Id:') !!}
    <p>{{ $accountBalance->account_id }}</p>
</div>

<!-- Transaction Id Field -->
<div class="form-group">
    {!! Form::label('transaction_id', 'Transaction Id:') !!}
    <p>{{ $accountBalance->transaction_id }}</p>
</div>

<!-- Start Dt Field -->
<div class="form-group">
    {!! Form::label('start_dt', 'Start Dt:') !!}
    <p>{{ $accountBalance->start_dt }}</p>
</div>

<!-- End Dt Field -->
<div class="form-group">
    {!! Form::label('end_dt', 'End Dt:') !!}
    <p>{{ $accountBalance->end_dt }}</p>
</div>

