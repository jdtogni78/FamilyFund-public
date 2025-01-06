<!-- Person Id Field -->
<div class="form-group">
    {!! Form::label('person_id', 'Person Id:') !!}
    <p>{{ $address->person_id }}</p>
</div>

<!-- Type Field -->
<div class="form-group">
    {!! Form::label('type', 'Type:') !!}
    <p>{{ $address->type }}</p>
</div>

<!-- Is Primary Field -->
<div class="form-group">
    {!! Form::label('is_primary', 'Is Primary:') !!}
    <p>{{ $address->is_primary }}</p>
</div>

<!-- Street Field -->
<div class="form-group">
    {!! Form::label('street', 'Street:') !!}
    <p>{{ $address->street }}</p>
</div>

<!-- Number Field -->
<div class="form-group">
    {!! Form::label('number', 'Number:') !!}
    <p>{{ $address->number }}</p>
</div>

<!-- Complement Field -->
<div class="form-group">
    {!! Form::label('complement', 'Complement:') !!}
    <p>{{ $address->complement }}</p>
</div>

<!-- County Field -->
<div class="form-group">
    {!! Form::label('county', 'County:') !!}
    <p>{{ $address->county }}</p>
</div>

<!-- City Field -->
<div class="form-group">
    {!! Form::label('city', 'City:') !!}
    <p>{{ $address->city }}</p>
</div>

<!-- State Field -->
<div class="form-group">
    {!! Form::label('state', 'State:') !!}
    <p>{{ $address->state }}</p>
</div>

<!-- Zip Code Field -->
<div class="form-group">
    {!! Form::label('zip_code', 'Zip Code:') !!}
    <p>{{ $address->zip_code }}</p>
</div>

<!-- Country Field -->
<div class="form-group">
    {!! Form::label('country', 'Country:') !!}
    <p>{{ $address->country }}</p>
</div>

<!-- Created At Field -->
<div class="form-group">
    {!! Form::label('created_at', 'Created At:') !!}
    <p>{{ $address->created_at }}</p>
</div>

<!-- Updated At Field -->
<div class="form-group">
    {!! Form::label('updated_at', 'Updated At:') !!}
    <p>{{ $address->updated_at }}</p>
</div>

