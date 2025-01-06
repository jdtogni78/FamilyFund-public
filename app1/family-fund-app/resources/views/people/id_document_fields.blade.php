<div class="row id_document-entry">
    {!! Form::hidden("id_documents[$index][id]", $doc?->id) !!}
    <div class="col-sm-4">
        {!! Form::select("id_documents[$index][type]", 
            ['CPF' => 'CPF', 'RG' => 'RG', 'CNH' => 'CNH', 'Passport' => 'Passport', 'SSN' => 'SSN', 'other' => 'Other'], 
            $doc?->type, ['class' => 'form-control']) !!}
    </div>
    <div class="col-sm-4">
        {!! Form::text("id_documents[$index][number]", 
            $doc?->number, ['class' => 'form-control', 'placeholder' => 'Document Number 0']) !!}
    </div>
</div>
