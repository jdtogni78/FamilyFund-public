<!-- Matching Rule Id Field -->
<div class="form-group">
    {!! Form::label('matching_rule_id', 'Matching Rule Id:') !!}
    <p>{{ $transactionMatching->matching_rule_id }}</p>
</div>

<!-- Transaction Id Field -->
<div class="form-group">
    {!! Form::label('transaction_id', 'Transaction Id:') !!}
    <p>{{ $transactionMatching->transaction_id }}</p>
</div>

<!-- Reference Transaction Id Field -->
<div class="form-group">
    {!! Form::label('reference_transaction_id', 'Reference Transaction Id:') !!}
    <p>{{ $transactionMatching->reference_transaction_id }}</p>
</div>

