<div class="col-md-6">
    <!-- First Name Field -->
    <div class="form-group">
        {!! Form::label('first_name', 'First Name:') !!}
        {!! Form::text('first_name', null, ['class' => 'form-control','maxlength' => 255]) !!}
    </div>

    <!-- Last Name Field -->
    <div class="form-group">
        {!! Form::label('last_name', 'Last Name:') !!}
        {!! Form::text('last_name', null, ['class' => 'form-control','maxlength' => 255]) !!}
    </div>

    <!-- Email Field -->
    <div class="form-group">
        {!! Form::label('email', 'Email:') !!}
        {!! Form::email('email', null, ['class' => 'form-control','maxlength' => 255]) !!}
    </div>

    <!-- Birthday Field -->
    <div class="form-group">
        {!! Form::label('birthday', 'Birthday:') !!}
        {!! Form::text('birthday', null, ['class' => 'form-control','id'=>'birthday']) !!}
    </div>

    <!-- Legal Guardian Id Field -->
    <div class="form-group">
        {!! Form::label('legal_guardian_id', 'Legal Guardian Id:') !!}
        {!! Form::select('legal_guardian_id', [], null, ['class' => 'form-control']) !!}
    </div>
</div>

<!-- Phone Fields -->
<div class="col-md-6">
    <div class="form-group">
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
            @endif
            @if(!isset($person) || isset($isEdit))
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
                    <div class="col-sm-1">
                        <button type="button" class="btn btn-danger btn-sm remove-phone d-none"><i class="fa fa-trash"></i></button>
                    </div>
                </div>
            @endif
        </div>
        <button type="button" class="btn btn-info add-phone">Add Phone</button>
    </div>

    <!-- Address Fields -->
    <div class="form-group">
        <h4>Addresses</h4>
        <div class="addresses-container">
            @if(isset($person))
                @foreach($person->addresses as $index => $address)
                    @include('persons.address_fields', ['index' => $index, 'address' => $address])
                @endforeach
            @endif
            @if(!isset($person) || isset($isEdit))
                @include('persons.address_fields', ['index' => 0])
                <div class="row address-entry">
                    <div class="col-sm-1">
                        <button type="button" class="btn btn-danger btn-sm remove-address d-none"><i class="fa fa-trash"></i></button>
                    </div>
                </div>
            @endif
        </div>
        <button type="button" class="btn btn-info add-address">Add Address</button>
    </div>

    <!-- ID Documents Fields -->
    <div class="form-group">
        <h4>ID Documents</h4>
        <div class="documents-container">
            @if(isset($person))
                @foreach($person->idDocuments as $index => $doc)
                    <div class="row document-entry">
                        <div class="col-sm-4">
                            {!! Form::select("documents[$index][type]", 
                                ['CPF' => 'CPF', 'RG' => 'RG', 'CNH' => 'CNH', 'passport' => 'Passport', 'other' => 'Other'], 
                                $doc->type, ['class' => 'form-control']) !!}
                        </div>
                        <div class="col-sm-4">
                            {!! Form::text("documents[$index][number]", 
                                $doc->number, ['class' => 'form-control', 'placeholder' => 'Document Number']) !!}
                        </div>
                    </div>
                @endforeach
            @endif
            @if(!isset($person) || isset($isEdit))
                <div class="row document-entry">
                    <div class="col-sm-4">
                        {!! Form::select('documents[0][type]', 
                            ['CPF' => 'CPF', 'RG' => 'RG', 'CNH' => 'CNH', 'passport' => 'Passport', 'other' => 'Other'], 
                            null, ['class' => 'form-control']) !!}
                    </div>
                    <div class="col-sm-4">
                        {!! Form::text('documents[0][number]', null, 
                            ['class' => 'form-control', 'placeholder' => 'Document Number']) !!}
                    </div>
                    <div class="col-sm-1">
                        <button type="button" class="btn btn-danger btn-sm remove-document d-none"><i class="fa fa-trash"></i></button>
                    </div>
                </div>
            @endif
        </div>
        <button type="button" class="btn btn-info add-document">Add Document</button>
    </div>

    <!-- Submit Field -->
    <div class="form-group">
        {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
        <a href="{{ route('people.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('.add-phone').click(function() {
            var index = $('.phone-entry').length;
            var template = $('.phone-entry').first().clone();
            template.find('input, select').each(function() {
                var name = $(this).attr('name').replace('[0]', '[' + index + ']');
                $(this).attr('name', name).val('');
                // update placeholder
                var currentPlaceholder = $(this).attr('placeholder');
                $(this).attr('placeholder', currentPlaceholder + index);
                if(index > 0) {
                    template.find('.remove-phone').removeClass('d-none');
                }
            });
            $('.phones-container').append(template);
        });

        $('.phones-container').on('click', '.remove-phone', function() {
            $(this).closest('.phone-entry').remove();
        });

        $('.add-address').click(function() {
            var index = $('.address-entry').length;
            var template = $('.address-entry').first().clone();
            template.find('input, select').each(function() {
                var name = $(this).attr('name').replace('[0]', '[' + index + ']');
                $(this).attr('name', name).val('');
                // update placeholder
                var currentPlaceholder = $(this).attr('placeholder');
                $(this).attr('placeholder', currentPlaceholder + index);
                if(index > 0) {
                    template.find('.remove-address').removeClass('d-none');
                }
            });
            $('.addresses-container').append(template);
        });

        $('.addresses-container').on('click', '.remove-address', function() {
            $(this).closest('.address-entry').remove();
        });

        $('.add-document').click(function() {
            var index = $('.document-entry').length;
            var template = $('.document-entry').first().clone();
            template.find('input, select').each(function() {
                var name = $(this).attr('name').replace('[0]', '[' + index + ']');
                $(this).attr('name', name).val('');
                var currentPlaceholder = $(this).attr('placeholder');
                $(this).attr('placeholder', currentPlaceholder + index);
                if(index > 0) {
                    template.find('.remove-document').removeClass('d-none');
                }
            });
            $('.documents-container').append(template);
        });

        $('.documents-container').on('click', '.remove-document', function() {
            $(this).closest('.document-entry').remove();
        });
    });

    $('#birthday').datetimepicker({
        format: 'YYYY-MM-DD HH:mm:ss',
        useCurrent: true,
        icons: {
            up: "icon-arrow-up-circle icons font-2xl",
            down: "icon-arrow-down-circle icons font-2xl"
        },
        sideBySide: true
    })
</script>
@endpush
