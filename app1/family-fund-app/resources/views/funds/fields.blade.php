<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control','maxlength' => 30,'maxlength' => 30]) !!}
</div>

<!-- Goal Field -->
<div class="form-group col-sm-6">
    {!! Form::label('goal', 'Goal:') !!}
    {!! Form::text('goal', null, ['class' => 'form-control','maxlength' => 1024,'maxlength' => 1024]) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('funds.index') }}" class="btn btn-secondary">Cancel</a>
</div>
