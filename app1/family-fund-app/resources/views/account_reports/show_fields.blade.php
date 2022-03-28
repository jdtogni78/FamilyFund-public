<!-- Account Id Field -->
<div class="form-group">
    {!! Form::label('account_id', 'Account Id:') !!}
    <p>{{ $accountReport->account_id }}</p>
</div>

<!-- Type Field -->
<div class="form-group">
    {!! Form::label('type', 'Type:') !!}
    <p>{{ $accountReport->type }}</p>
</div>

<!-- As Of Field -->
<div class="form-group">
    {!! Form::label('as_of', 'As Of:') !!}
    <p>{{ $accountReport->as_of }}</p>
</div>

