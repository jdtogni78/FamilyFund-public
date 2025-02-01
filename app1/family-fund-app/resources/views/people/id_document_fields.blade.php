<div class="row id_document-entry">
<input type="hidden" name=""id_documents[$index][id]"" value="{{ $doc?->id }}" >
    <div class="col-sm-4">
        <select name="id_documents[$index][type]" class="form-control">
            @foreach(['CPF' => 'CPF', 'RG' => 'RG', 'CNH' => 'CNH', 'Passport' => 'Passport', 'SSN' => 'SSN', 'other' => 'Other'] as $value => $label)
                <option value="{{ $value }}" { $doc?->type == $value ? 'selected' : '' }>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-4">
        <input type="text" name="id_documents[$index][number]" value="{{ $doc?->number }}" class="form-control" placeholder="Document Number 0">
    </div>
</div>
