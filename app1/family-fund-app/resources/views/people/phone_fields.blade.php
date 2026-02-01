<div class="row phone-entry">
    <input type="hidden" name="phones[{{ $index }}][id]" value="{{ $phone?->id }}" >
    <div class="col-sm-4">
        <input type="text" name="phones[{{ $index }}][number]" value="{{ $phone?->number }}" class="form-control" placeholder="Phone Number">
    </div>
    <div class="col-sm-3">
        <select name="phones[{{ $index }}][type]" class="form-control">
            @foreach(['mobile' => 'Mobile', 'home' => 'Home', 'work' => 'Work', 'other' => 'Other'] as $value => $label)
                <option value="{{ $value }}" {{ $phone?->type == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-2">
        <input type="checkbox" name="phones[{{ $index }}][is_primary]" value="1" {{ $phone?->is_primary ? 'checked' : '' }} class="is_primary">
    </div>
    <div class="col-sm-1">
        <button type="button" class="btn btn-danger btn-sm remove-phone d-none"><i class="fa fa-trash"></i></button>
    </div>
</div>
