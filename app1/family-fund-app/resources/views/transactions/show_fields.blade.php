<!-- Type Field -->
<div class="form-group">
    {!! Form::label('type', 'Type:') !!}
    <p>{{ $transaction->type }}</p>
</div>

<!-- Status Field -->
<div class="form-group">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $transaction->status }}</p>
</div>

<!-- Value Field -->
<div class="form-group">
    {!! Form::label('value', 'Value:') !!}
    <p>{{ $transaction->value }}</p>
</div>

<!-- Shares Field -->
<div class="form-group">
    {!! Form::label('shares', 'Shares:') !!}
    <p>{{ $transaction->shares }}</p>
</div>

<!-- Timestamp Field -->
<div class="form-group">
    {!! Form::label('timestamp', 'Timestamp:') !!}
    <p>{{ $transaction->timestamp }}</p>
</div>

<!-- Account Id Field -->
<div class="form-group">
    {!! Form::label('account_id', 'Account Id:') !!}
    <p>{{ $transaction->account_id }}</p>
</div>

<!-- Descr Field -->
<div class="form-group">
    {!! Form::label('descr', 'Descr:') !!}
    <p>{{ $transaction->descr }}</p>
</div>

<!-- Flags Field -->
<div class="form-group">
    {!! Form::label('flags', 'Flags:') !!}
    <p>{{ $transaction->flags }}</p>
</div>

<!-- Scheduled Job Id Field -->
<div class="form-group">
    {!! Form::label('scheduled_job_id', 'Scheduled Job Id:') !!}
    <p>{{ $transaction->scheduled_job_id }}</p>
</div>

