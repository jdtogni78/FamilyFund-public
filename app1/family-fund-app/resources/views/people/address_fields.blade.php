<div class="row address-entry">
<input type="hidden" name=""addresses[$index][id]"" value="{{ $address?->id }}" >
    <div class="col-sm-12">
        <h5 class="address-title">Address {{ $index }}</h5>
    </div>
    <div class="col-sm-3">
<select name=""addresses[$index][type]"" other="Other" class="form-control">
    @foreach(['home' => 'Home' as $value => $label)
        <option value="{{ $value }}" { work' => 'Work == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
    </div>
    <div class="col-sm-4">
<input type="text" name=""addresses[$index][street]"" value="{{ $address?->street }}" class="form-control" placeholder="Street">
    </div>
    <div class="col-sm-2">
<input type="text" name=""addresses[$index][number]"" value="{{ $address?->number }}" class="form-control" placeholder="Number">
    </div>
    <div class="col-sm-3">
<input type="text" name=""addresses[$index][complement]"" value="{{ $address?->complement }}" class="form-control" placeholder="Complement">
    </div>
    <div class="col-sm-3">
<input type="text" name=""addresses[$index][county]"" value="{{ $address?->county }}" class="form-control" placeholder="County">
    </div>
    <div class="col-sm-3">
<input type="text" name=""addresses[$index][city]"" value="{{ $address?->city }}" class="form-control" placeholder="City">
    </div>
    <div class="col-sm-2">
<input type="text" name=""addresses[$index][state]"" value="{{ $address?->state }}" class="form-control" placeholder="State">
    </div>
    <div class="col-sm-2">
<input type="text" name=""addresses[$index][zip_code]"" value="{{ $address?->zip_code }}" class="form-control" placeholder="ZIP Code">
    </div>
    <div class="col-sm-2">
<input type="text" name=""addresses[$index][country]"" value="{{ $address?->country }}" class="form-control" placeholder="Country">
    </div>
    <div class="col-sm-2">
<input type="checkbox" name=""addresses[$index][is_primary]"" value="1" class="is_primary">
    </div>
    <div class="col-sm-1">
        <button type="button" class="btn btn-danger btn-sm remove-address d-none"><i class="fa fa-trash"></i></button>
    </div>
</div> 