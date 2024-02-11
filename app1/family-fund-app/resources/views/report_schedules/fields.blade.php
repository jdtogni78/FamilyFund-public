<!-- Descr Field -->
<div class="form-group col-sm-6">
    {!! Form::label('descr', 'Descr:') !!}
    {!! Form::text('descr', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

<!-- Type Field using typeMap -->
<div class="form-group col-sm-6">
    {!! Form::label('type', 'Type:') !!}
    {!! Form::select('type', $api['typeMap'], 'DOM', ['class' => 'form-control']);  !!}
</div>

<!-- Value Field -->
<div class="form-group col-sm-6">
    {!! Form::label('value', 'Value:') !!}
    {!! Form::text('value', null, ['class' => 'form-control','maxlength' => 255,'maxlength' => 255]) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('reportSchedules.index') }}" class="btn btn-secondary">Cancel</a>
</div>
