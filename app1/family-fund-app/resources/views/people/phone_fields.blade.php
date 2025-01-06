<div class="row phone-entry">
    {!! Form::hidden("phones[$index][id]", $phone->id) !!}
    <div class="col-sm-4">
        {!! Form::text("phones[$index][number]", $phone->number, ['class' => 'form-control', 'placeholder' => 'Phone Number']) !!}
    </div>
    <div class="col-sm-3">
        {!! Form::select("phones[$index][type]", ['mobile' => 'Mobile', 'home' => 'Home', 'work' => 'Work', 'other' => 'Other'], $phone->type, ['class' => 'form-control']) !!}
    </div>
    <div class="col-sm-2">
        {!! Form::checkbox("phones[$index][is_primary]", 1, $phone->is_primary, ['class' => 'is_primary']) !!} Primary
    </div>
    <div class="col-sm-1">
        <button type="button" class="btn btn-danger btn-sm remove-phone d-none"><i class="fa fa-trash"></i></button>
    </div>
</div>
