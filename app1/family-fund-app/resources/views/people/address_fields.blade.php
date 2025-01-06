<div class="row address-entry">
    <div class="col-sm-12">
        <h5 class="address-title">Address 0</h5>
    </div>
    <div class="col-sm-3">
        {!! Form::select("addresses[$index][type]", ['home' => 'Home', 'work' => 'Work', 'other' => 'Other'], isset($address) ? $address->type : null, ['class' => 'form-control']) !!}
    </div>
    <div class="col-sm-4">
        {!! Form::text("addresses[$index][street]", isset($address) ? $address->street : null, ['class' => 'form-control', 'placeholder' => 'Street']) !!}
    </div>
    <div class="col-sm-2">
        {!! Form::text("addresses[$index][number]", isset($address) ? $address->number : null, ['class' => 'form-control', 'placeholder' => 'Number']) !!}
    </div>
    <div class="col-sm-3">
        {!! Form::text("addresses[$index][complement]", isset($address) ? $address->complement : null, ['class' => 'form-control', 'placeholder' => 'Complement']) !!}
    </div>
    <div class="col-sm-3">
        {!! Form::text("addresses[$index][neighborhood]", isset($address) ? $address->neighborhood : null, ['class' => 'form-control', 'placeholder' => 'Neighborhood']) !!}
    </div>
    <div class="col-sm-3">
        {!! Form::text("addresses[$index][city]", isset($address) ? $address->city : null, ['class' => 'form-control', 'placeholder' => 'City']) !!}
    </div>
    <div class="col-sm-2">
        {!! Form::text("addresses[$index][state]", isset($address) ? $address->state : null, ['class' => 'form-control', 'placeholder' => 'State']) !!}
    </div>
    <div class="col-sm-2">
        {!! Form::text("addresses[$index][zip_code]", isset($address) ? $address->zip_code : null, ['class' => 'form-control', 'placeholder' => 'ZIP Code']) !!}
    </div>
    <div class="col-sm-2">
        {!! Form::text("addresses[$index][country]", isset($address) ? $address->country : null, ['class' => 'form-control', 'placeholder' => 'Country']) !!}
    </div>
    <div class="col-sm-2">
        {!! Form::checkbox("addresses[$index][is_primary]", 1, isset($address) ? $address->is_primary : false) !!} Primary
    </div>
    <div class="col-sm-1">
        <button type="button" class="btn btn-danger btn-sm remove-address d-none"><i class="fa fa-trash"></i></button>
    </div>
</div> 