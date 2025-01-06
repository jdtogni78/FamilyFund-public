<!-- Person Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('person_id', 'Person Id:') !!}
    {!! Form::select('person_id', ], null, ['class' => 'form-control']) !!}
</div>

<!-- Is Primary Field -->
<div class="form-group col-sm-6">
    {!! Form::label('is_primary', 'Is Primary:') !!}
    <label class="checkbox-inline">
        {!! Form::hidden('is_primary', 0) !!}
        {!! Form::checkbox('is_primary', '1', null) !!}
    </label>
</div>


<!-- Street Field -->
<div class="form-group col-sm-6">
    {!! Form::label('street', 'Street:') !!}
    {!! Form::text('street', null, ['class' => 'form-control','maxlength' => 255]) !!}
</div>

<!-- Number Field -->
<div class="form-group col-sm-6">
    {!! Form::label('number', 'Number:') !!}
    {!! Form::text('number', null, ['class' => 'form-control','maxlength' => 20]) !!}
</div>

<!-- Complement Field -->
<div class="form-group col-sm-6">
    {!! Form::label('complement', 'Complement:') !!}
    {!! Form::text('complement', null, ['class' => 'form-control','maxlength' => 255]) !!}
</div>

<!-- Neighborhood Field -->
<div class="form-group col-sm-6">
    {!! Form::label('neighborhood', 'Neighborhood:') !!}
    {!! Form::text('neighborhood', null, ['class' => 'form-control','maxlength' => 255]) !!}
</div>

<!-- City Field -->
<div class="form-group col-sm-6">
    {!! Form::label('city', 'City:') !!}
    {!! Form::text('city', null, ['class' => 'form-control','maxlength' => 255]) !!}
</div>

<!-- State Field -->
<div class="form-group col-sm-6">
    {!! Form::label('state', 'State:') !!}
    {!! Form::text('state', null, ['class' => 'form-control','maxlength' => 2]) !!}
</div>

<!-- Zip Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('zip_code', 'Zip Code:') !!}
    {!! Form::text('zip_code', null, ['class' => 'form-control','maxlength' => 10]) !!}
</div>

<!-- Country Field -->
<div class="form-group col-sm-6">
    {!! Form::label('country', 'Country:') !!}
    {!! Form::text('country', null, ['class' => 'form-control','maxlength' => 255]) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('addresses.index') }}" class="btn btn-secondary">Cancel</a>
</div>
