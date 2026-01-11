<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <div class="col-md-6">
        <!-- First Name Field -->
        <div class="form-group mb-3">
            <label for="first_name" class="form-label">
                <i class="fa fa-user me-1"></i> First Name <span class="text-danger">*</span>
            </label>
            <input type="text" name="first_name" id="first_name" class="form-control" maxlength="255"
                   value="{{ $person->first_name ?? old('first_name') }}" required>
            <small class="text-body-secondary">Person's first/given name</small>
        </div>

        <!-- Last Name Field -->
        <div class="form-group mb-3">
            <label for="last_name" class="form-label">
                <i class="fa fa-user me-1"></i> Last Name <span class="text-danger">*</span>
            </label>
            <input type="text" name="last_name" id="last_name" class="form-control" maxlength="255"
                   value="{{ $person->last_name ?? old('last_name') }}" required>
            <small class="text-body-secondary">Person's last/family name</small>
        </div>

        <!-- Email Field -->
        <div class="form-group mb-3">
            <label for="email" class="form-label">
                <i class="fa fa-envelope me-1"></i> Email
            </label>
            <input type="email" name="email" id="email" class="form-control" maxlength="255"
                   value="{{ $person->email ?? old('email') }}">
            <small class="text-body-secondary">Email address for contact</small>
        </div>

        <!-- Birthday Field -->
        <div class="form-group mb-3">
            <label for="birthday" class="form-label">
                <i class="fa fa-birthday-cake me-1"></i> Birthday
            </label>
            <input type="text" name="birthday" id="birthday" class="form-control"
                   value="{{ $person->birthday ?? old('birthday') }}">
            <small class="text-body-secondary">Date of birth</small>
        </div>

        <!-- Legal Guardian Id Field -->
        <div class="form-group mb-3">
            <label for="legal_guardian_id" class="form-label">
                <i class="fa fa-user-shield me-1"></i> Legal Guardian
            </label>
            <select name="legal_guardian_id" id="legal_guardian_id" class="form-control form-select">
                <option value="">-- None --</option>
                @foreach($legalGuardians ?? [] as $value => $label)
                    <option value="{{ $value }}" {{ (isset($person) && $person->legal_guardian_id == $value) ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <small class="text-body-secondary">Parent/guardian for minors</small>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Phone Fields -->
        <div class="form-group mb-3">
            <h5 class="text-body-secondary">
                <i class="fa fa-phone me-1"></i> Phones
            </h5>
            <div class="phones-container">
                @if(isset($person))
                    @foreach($person->phones as $index => $phone)
                        @include('people.phone_fields', ['index' => $index, 'phone' => $phone])
                    @endforeach
                @endif
                @if(!isset($person) || (isset($isEdit) && $person->phones->isEmpty()))
                    @include('people.phone_fields', ['index' => 0, 'phone' => null])
                @endif
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm add-phone">
                <i class="fa fa-plus me-1"></i> Add Phone
            </button>
        </div>

        <!-- Address Fields -->
        <div class="form-group mb-3">
            <h5 class="text-body-secondary">
                <i class="fa fa-map-marker-alt me-1"></i> Addresses
            </h5>
            <div class="addresses-container">
                @if(isset($person))
                    @foreach($person->addresses as $index => $address)
                        @include('people.address_fields', ['index' => $index, 'address' => $address])
                    @endforeach
                @endif
                @if(!isset($person) || (isset($isEdit) && $person->addresses->isEmpty()))
                    @include('people.address_fields', ['index' => 0, 'address' => null])
                @endif
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm add-address">
                <i class="fa fa-plus me-1"></i> Add Address
            </button>
        </div>

        <!-- ID Documents Fields -->
        <div class="form-group mb-3">
            <h5 class="text-body-secondary">
                <i class="fa fa-id-card me-1"></i> ID Documents
            </h5>
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
            <button type="button" class="btn btn-outline-primary btn-sm add-id_document">
                <i class="fa fa-plus me-1"></i> Add Document
            </button>
        </div>

        <hr class="my-4">

        <!-- Submit Field -->
        <div class="form-group">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save me-1"></i> Save
            </button>
            <a href="{{ route('people.index') }}" class="btn btn-secondary">
                <i class="fa fa-times me-1"></i> Cancel
            </a>
        </div>
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
                var currentPlaceholder = $(this).attr('placeholder');
                $(this).attr('placeholder', currentPlaceholder + index);
                if(index > 0) {
                    template.find('.remove-phone').removeClass('d-none');
                }
            });
            template.find('.is_primary').prop('checked', false);
            $('.phones-container').append(template);
        });

        $('.phones-container').on('change', '.is_primary', function() {
            $('.phones-container').find('.is_primary').not(this).prop('checked', false);
        });

        $('.addresses-container').on('change', '.is_primary', function() {
            $('.addresses-container').find('.is_primary').not(this).prop('checked', false);
        });

        $('.phones-container').find('.remove-phone').not(':first').removeClass('d-none');
        $('.addresses-container').find('.remove-address').not(':first').removeClass('d-none');
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
                template.find('.address-title').text('Address ' + index);
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
    });
</script>
@endpush
