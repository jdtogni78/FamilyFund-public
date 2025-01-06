<!-- Person Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('person_id', 'Person Id:') !!}
    {!! Form::select('person_id', ], null, ['class' => 'form-control']) !!}
</div>

<!-- Number Field -->
<div class="form-group col-sm-6">
    {!! Form::label('number', 'Number:') !!}
    {!! Form::text('number', null, ['class' => 'form-control','maxlength' => 20]) !!}
</div>

<!-- Is Primary Field -->
<div class="form-group col-sm-6">
    {!! Form::label('is_primary', 'Is Primary:') !!}
    <label class="checkbox-inline">
        {!! Form::hidden('is_primary', 0) !!}
        {!! Form::checkbox('is_primary', '1', null) !!}
    </label>
</div>


<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('phones.index') }}" class="btn btn-secondary">Cancel</a>
</div>
