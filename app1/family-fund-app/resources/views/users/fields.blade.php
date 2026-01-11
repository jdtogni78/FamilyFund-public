<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Name Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="name" class="form-label">
            <i class="fa fa-user me-1"></i> Name <span class="text-danger">*</span>
        </label>
        <input type="text" name="name" id="name" class="form-control" maxlength="255"
               value="{{ $user->name ?? old('name') }}" required>
        <small class="text-body-secondary">Full name of the user</small>
    </div>

    <!-- Email Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="email" class="form-label">
            <i class="fa fa-envelope me-1"></i> Email <span class="text-danger">*</span>
        </label>
        <input type="email" name="email" id="email" class="form-control" maxlength="255"
               value="{{ $user->email ?? old('email') }}" required>
        <small class="text-body-secondary">Login email address</small>
    </div>
</div>

<div class="row">
    <!-- Password Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="password" class="form-label">
            <i class="fa fa-lock me-1"></i> Password {{ isset($user) ? '' : '<span class="text-danger">*</span>' }}
        </label>
        <input type="password" name="password" id="password" class="form-control" maxlength="255"
               {{ isset($user) ? '' : 'required' }}>
        <small class="text-body-secondary">{{ isset($user) ? 'Leave blank to keep current password' : 'Secure password for login' }}</small>
    </div>

    <!-- Email Verified At Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="email_verified_at" class="form-label">
            <i class="fa fa-check-circle me-1"></i> Email Verified At
        </label>
        <input type="text" name="email_verified_at" id="email_verified_at" class="form-control"
               value="{{ $user->email_verified_at ?? old('email_verified_at') }}">
        <small class="text-body-secondary">Date/time email was verified (YYYY-MM-DD HH:mm:ss)</small>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
    $('#email_verified_at').datetimepicker({
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

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('users.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
