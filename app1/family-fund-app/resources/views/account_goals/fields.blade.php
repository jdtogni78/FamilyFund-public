<!-- Account Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('account_id', 'Account Id:') !!}
    {!! Form::select('account_id', ], null, ['class' => 'form-control']) !!}
</div>

<!-- Goal Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('goal_id', 'Goal Id:') !!}
    {!! Form::select('goal_id', ], null, ['class' => 'form-control']) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('accountGoals.index') }}" class="btn btn-secondary">Cancel</a>
</div>
