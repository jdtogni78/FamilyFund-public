<div class="row address-entry">
    {!! Form::hidden("addresses[$index][id]", $address?->id) !!}
    <div class="col-sm-12">
        <h5 class="address-title">Address {{ $index }}</h5>
    </div>
    <div class="col-sm-3">
        {!! Form::select("addresses[$index][type]", ['home' => 'Home', 'work' => 'Work', 'other' => 'Other'], $address?->type, ['class' => 'form-control']) !!}
    </div>
    <div class="col-sm-4">
        {!! Form::text("addresses[$index][street]", $address?->street, ['class' => 'form-control', 'placeholder' => 'Street']) !!}
    </div>
    <div class="col-sm-2">
        {!! Form::text("addresses[$index][number]", $address?->number, ['class' => 'form-control', 'placeholder' => 'Number']) !!}
    </div>
    <div class="col-sm-3">
        {!! Form::text("addresses[$index][complement]", $address?->complement, ['class' => 'form-control', 'placeholder' => 'Complement']) !!}
    </div>
    <div class="col-sm-3">
        {!! Form::text("addresses[$index][county]", $address?->county, ['class' => 'form-control', 'placeholder' => 'County']) !!}
    </div>
    <div class="col-sm-3">
        {!! Form::text("addresses[$index][city]", $address?->city, ['class' => 'form-control', 'placeholder' => 'City']) !!}
    </div>
    <div class="col-sm-2">
        {!! Form::text("addresses[$index][state]", $address?->state, ['class' => 'form-control', 'placeholder' => 'State']) !!}
    </div>
    <div class="col-sm-2">
        {!! Form::text("addresses[$index][zip_code]", $address?->zip_code, ['class' => 'form-control', 'placeholder' => 'ZIP Code']) !!}
    </div>
    <div class="col-sm-2">
        {!! Form::text("addresses[$index][country]", $address?->country, ['class' => 'form-control', 'placeholder' => 'Country']) !!}
    </div>
    <div class="col-sm-2">
        {!! Form::checkbox("addresses[$index][is_primary]", 1, $address?->is_primary, ['class' => 'is_primary']) !!} Primary
    </div>
    <div class="col-sm-1">
        <button type="button" class="btn btn-danger btn-sm remove-address d-none"><i class="fa fa-trash"></i></button>
    </div>
</div> 