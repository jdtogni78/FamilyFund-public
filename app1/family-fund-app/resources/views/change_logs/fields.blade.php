<!-- Object Field -->
<div class="form-group col-sm-6">
    {!! Form::label('object', 'Object:') !!}
    {!! Form::text('object', null, ['class' => 'form-control','maxlength' => 50,'maxlength' => 50]) !!}
</div>

<!-- Content Field -->
<div class="form-group col-sm-12 col-lg-12">
    {!! Form::label('content', 'Content:') !!}
    {!! Form::textarea('content', null, ['class' => 'form-control']) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('changeLogs.index') }}" class="btn btn-secondary">Cancel</a>
</div>
