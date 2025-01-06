<div class="form-group col-sm-6">
    {!! Form::label('first_name', 'First Name:') !!}
    {!! Form::text('first_name', null, ['class' => 'form-control']) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('last_name', 'Last Name:') !!}
    {!! Form::text('last_name', null, ['class' => 'form-control']) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('email', 'Email:') !!}
    {!! Form::email('email', null, ['class' => 'form-control']) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('birthday', 'Birthday:') !!}
    {!! Form::date('birthday', null, ['class' => 'form-control']) !!}
</div>

<!-- Phone Fields -->
<div class="col-sm-12">
    <h4>Phones</h4>
    <div class="phones-container">
        @if(isset($person))
            @foreach($person->phones as $index => $phone)
                <div class="row phone-entry">
                    <div class="col-sm-4">
                        {!! Form::text("phones[$index][number]", $phone->number, ['class' => 'form-control', 'placeholder' => 'Phone Number']) !!}
                    </div>
                    <div class="col-sm-3">
                        {!! Form::select("phones[$index][type]", ['mobile' => 'Mobile', 'home' => 'Home', 'work' => 'Work', 'other' => 'Other'], $phone->type, ['class' => 'form-control']) !!}
                    </div>
                    <div class="col-sm-2">
                        {!! Form::checkbox("phones[$index][is_primary]", 1, $phone->is_primary) !!} Primary
                    </div>
                </div>
            @endforeach
        @else
            <div class="row phone-entry">
                <div class="col-sm-4">
                    {!! Form::text('phones[0][number]', null, ['class' => 'form-control', 'placeholder' => 'Phone Number']) !!}
                </div>
                <div class="col-sm-3">
                    {!! Form::select('phones[0][type]', ['mobile' => 'Mobile', 'home' => 'Home', 'work' => 'Work', 'other' => 'Other'], null, ['class' => 'form-control']) !!}
                </div>
                <div class="col-sm-2">
                    {!! Form::checkbox('phones[0][is_primary]', 1, true) !!} Primary
                </div>
            </div>
        @endif
    </div>
    <button type="button" class="btn btn-info add-phone">Add Phone</button>
</div>

<!-- Address Fields -->
<div class="col-sm-12">
    <h4>Addresses</h4>
    <div class="addresses-container">
        @if(isset($person))
            @foreach($person->addresses as $index => $address)
                @include('persons.address_fields', ['index' => $index, 'address' => $address])
            @endforeach
        @else
            @include('persons.address_fields', ['index' => 0])
        @endif
    </div>
    <button type="button" class="btn btn-info add-address">Add Address</button>
</div>

<!-- ID Documents Fields -->
<div class="col-sm-12">
    <h4>ID Documents</h4>
    <div class="documents-container">
        @if(isset($person))
            @foreach($person->idDocuments as $index => $doc)
                <div class="row document-entry">
                    <div class="col-sm-4">
                        {!! Form::select("documents[$index][type]", ['CPF' => 'CPF', 'RG' => 'RG', 'CNH' => 'CNH', 'passport' => 'Passport', 'other' => 'Other'], $doc->type, ['class' => 'form-control']) !!}
                    </div>
                    <div class="col-sm-4">
                        {!! Form::text("documents[$index][number]", $doc->number, ['class' => 'form-control', 'placeholder' => 'Document Number']) !!}
                    </div>
                </div>
            @endforeach
        @else
            <div class="row document-entry">
                <div class="col-sm-4">
                    {!! Form::select('documents[0][type]', ['CPF' => 'CPF', 'RG' => 'RG', 'CNH' => 'CNH', 'passport' => 'Passport', 'other' => 'Other'], null, ['class' => 'form-control']) !!}
                </div>
                <div class="col-sm-4">
                    {!! Form::text('documents[0][number]', null, ['class' => 'form-control', 'placeholder' => 'Document Number']) !!}
                </div>
            </div>
        @endif
    </div>
    <button type="button" class="btn btn-info add-document">Add Document</button>
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('persons.index') }}" class="btn btn-default">Cancel</a>
</div> 