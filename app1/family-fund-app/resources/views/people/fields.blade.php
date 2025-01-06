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
                    @include('people.phone_fields', ['index' => $index, 'phone' => $phone])
                @endforeach
            @endif
            @if(!isset($person) || (isset($isEdit) && $person->phones->isEmpty()))
                @include('people.phone_fields', ['index' => 0])
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
                    @include('people.address_fields', ['index' => $index, 'address' => $address])
                @endforeach
            @endif
            @if(!isset($person) || (isset($isEdit) && $person->addresses->isEmpty()))
                @include('people.address_fields', ['index' => 0])
            @endif
        </div>
        <button type="button" class="btn btn-info add-address">Add Address</button>
    </div>

    <!-- ID Documents Fields -->
    <div class="form-group">
        <h4>ID Documents</h4>
        <div class="id_documents-container">
            @if(isset($person))
                @foreach($person->idDocuments as $index => $doc)
                    @include('people.id_document_fields', ['index' => $index, 'doc' => $doc])
                @endforeach
            @endif
            @if(!isset($person) || (isset($isEdit) && $person->idDocuments->isEmpty()))
                @include('people.id_document_fields', ['index' => 0, 'doc' => null])
            @endif
        </div>
        <button type="button" class="btn btn-info add-id_document">Add Document</button>
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
                // set is_primary to false if not set, or if its null
                if(index > 0) {
                    template.find('.remove-phone').removeClass('d-none');
                }
            });
            template.find('.is_primary').prop('checked', false);
            $('.phones-container').append(template);
        });

        // when is_primary is set to true, set all other is_primary to false
        $('.phones-container').on('change', '.is_primary', function() {
            $('.phones-container').find('.is_primary').not(this).prop('checked', false);
        });
        // same for addresses
        $('.addresses-container').on('change', '.is_primary', function() {
            $('.addresses-container').find('.is_primary').not(this).prop('checked', false);
        });
        
        // hide all remove-phone buttons except the first one
        $('.phones-container').find('.remove-phone').not(':first').removeClass('d-none');
        // hide all remove-address buttons except the first one
        $('.addresses-container').find('.remove-address').not(':first').removeClass('d-none');
        // hide all remove-document buttons except the first one
        $('.id_documents-container').find('.remove-id_document').not(':first').removeClass('d-none');

        $('.phones-container').on('click', '.remove-phone', function() {
            $(this).closest('.phone-entry').remove();
        });

        $('.add-address').click(function() {
            var index = $('.address-entry').length;
            var template = $('.address-entry').first().clone();
            template.find('input, select').each(function() {
                var name = $(this).attr('name').replace('[0]', '[' + index + ']');
                $(this).attr('name', name).val('');
                // update address title
                template.find('.address-title').text('Address ' + index);
                // set is_primary to false if not set, or if its null
                $(this).attr('is_primary', false);
                if(index > 0) {
                    template.find('.remove-address').removeClass('d-none');
                }
            });
            $('.addresses-container').append(template);
        });

        $('.addresses-container').on('click', '.remove-address', function() {
            $(this).closest('.address-entry').remove();
        });

        $('.add-id_document').click(function() {
            var index = $('.id_document-entry').length;
            var template = $('.id_document-entry').first().clone();
            template.find('input, select').each(function() {
                var name = $(this).attr('name').replace('[0]', '[' + index + ']');
                $(this).attr('name', name).val('');
                var currentPlaceholder = $(this).attr('placeholder');
                $(this).attr('placeholder', currentPlaceholder + index);
                if(index > 0) {
                    template.find('.remove-id_document').removeClass('d-none');
                }
            });
            $('.id_documents-container').append(template);
        });

        $('.id_documents-container').on('click', '.remove-id_document', function() {
            $(this).closest('.id_document-entry').remove();
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
