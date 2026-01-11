<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Code Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="code" class="form-label">
            <i class="fa fa-hashtag me-1"></i> Code <span class="text-danger">*</span>
        </label>
        <input type="text" name="code" id="code" class="form-control" maxlength="15"
               value="{{ $account->code ?? old('code') }}" required>
        <small class="text-body-secondary">Unique account identifier (max 15 characters)</small>
    </div>

    <!-- Nickname Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="nickname" class="form-label">
            <i class="fa fa-user me-1"></i> Nickname
        </label>
        <input type="text" name="nickname" id="nickname" class="form-control" maxlength="15"
               value="{{ $account->nickname ?? old('nickname') }}">
        <small class="text-body-secondary">Display name for the account holder</small>
    </div>
</div>

<div class="row">
    <!-- Fund Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="fund_id" class="form-label">
            <i class="fa fa-landmark me-1"></i> Fund <span class="text-danger">*</span>
        </label>
        <select name="fund_id" id="fund_id" class="form-control form-select" required>
            @foreach($api['fundMap'] as $value => $label)
                <option value="{{ $value }}" {{ ($account->fund_id ?? old('fund_id')) == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Fund this account belongs to</small>
    </div>

    <!-- User Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="user_id" class="form-label">
            <i class="fa fa-user-cog me-1"></i> User
        </label>
        <select name="user_id" id="user_id" class="form-control form-select">
            @foreach($api['userMap'] as $value => $label)
                <option value="{{ $value }}" {{ ($account->user_id ?? old('user_id')) == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Portal user linked to this account</small>
    </div>
</div>

<hr class="my-3">

<div class="row">
    <!-- Email Cc Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="email_cc" class="form-label">
            <i class="fa fa-envelope me-1"></i> Email CC
        </label>
        <input type="text" name="email_cc" id="email_cc" class="form-control" maxlength="1024"
               value="{{ $account->email_cc ?? old('email_cc') }}">
        <small class="text-body-secondary">Additional email addresses to CC on reports (comma separated)</small>
    </div>

    <!-- Disbursement Cap Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="disbursement_cap" class="form-label">
            <i class="fa fa-percentage me-1"></i> Disbursement Cap
        </label>
        <div class="input-group">
            <input type="number" name="disbursement_cap" id="disbursement_cap" class="form-control" step="0.01" min="0" max="1"
                   value="{{ $account->disbursement_cap ?? old('disbursement_cap') }}">
            <span class="input-group-text">%</span>
        </div>
        <small class="text-body-secondary">Max quarterly disbursement as decimal (e.g., 0.02 = 2%). Leave empty for default 2%</small>
    </div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('accounts.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
