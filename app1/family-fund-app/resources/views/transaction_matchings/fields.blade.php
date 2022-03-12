<!-- Matching Rule Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('matching_rule_id', 'Matching Rule Id:') !!}
    {!! Form::text('matching_rule_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Transaction Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('transaction_id', 'Transaction Id:') !!}
    {!! Form::text('transaction_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Reference Transaction Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('reference_transaction_id', 'Reference Transaction Id:') !!}
    {!! Form::text('reference_transaction_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('transactionMatchings.index') }}" class="btn btn-secondary">Cancel</a>
</div>
