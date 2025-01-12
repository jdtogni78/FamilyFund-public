<!-- Account Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('account_id', 'Account Id:') !!}
    {!! Form::select('account_id', $api['account'], null, ['class' => 'form-control']) !!}
</div>

<!-- Matching Rule Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('matching_rule_id', 'Matching Rule Id:') !!}
    {!! Form::select('matching_rule_id', $api['mr'], null, ['class' => 'form-control']) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('accountMatchingRules.index') }}" class="btn btn-secondary">Cancel</a>
</div>
