<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

{{-- Fund Information --}}
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="fa fa-landmark me-2"></i> Fund Information
    </div>
    <div class="card-body">
        <div class="row">
            <!-- Name Field -->
            <div class="form-group col-md-6 mb-3">
                <label for="name" class="form-label">
                    <i class="fa fa-landmark me-1"></i> Name <span class="text-danger">*</span>
                </label>
                <input type="text" name="name" id="name" class="form-control" maxlength="30"
                       value="{{ old('name') }}" required>
                <small class="text-body-secondary">Short name for the fund (max 30 characters)</small>
            </div>

            <!-- Goal Field -->
            <div class="form-group col-md-6 mb-3">
                <label for="goal" class="form-label">
                    <i class="fa fa-bullseye me-1"></i> Goal
                </label>
                <input type="text" name="goal" id="goal" class="form-control" maxlength="1024"
                       value="{{ old('goal') }}">
                <small class="text-body-secondary">Description of the fund's investment goal</small>
            </div>
        </div>
    </div>
</div>

{{-- Account Information --}}
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="fa fa-wallet me-2"></i> Fund Account
    </div>
    <div class="card-body">
        <div class="row">
            <div class="form-group col-md-12 mb-3">
                <label for="account_nickname" class="form-label">
                    <i class="fa fa-tag me-1"></i> Account Nickname
                </label>
                <input type="text" name="account_nickname" id="account_nickname" class="form-control" maxlength="100"
                       value="{{ old('account_nickname') }}" placeholder="Leave blank to auto-generate">
                <small class="text-body-secondary">Optional. If blank, will use "[Fund Name] Fund Account"</small>
            </div>
        </div>
    </div>
</div>

{{-- Portfolio Information --}}
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="fa fa-briefcase me-2"></i> Portfolio(s)
    </div>
    <div class="card-body">
        <div class="row">
            <div class="form-group col-md-12 mb-3">
                <label for="portfolio_source" class="form-label">
                    <i class="fa fa-code me-1"></i> Portfolio Source Identifier <span class="text-danger">*</span>
                </label>
                <input type="text" name="portfolio_source" id="portfolio_source" class="form-control" maxlength="30"
                       value="{{ old('portfolio_source') }}" required placeholder="e.g., MONARCH_IBKR_XXXX">
                <small class="text-body-secondary">
                    Unique identifier for this portfolio (used by sync scripts). Max 30 characters.
                </small>
            </div>
        </div>
    </div>
</div>

{{-- Initial Transaction --}}
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="fa fa-money-bill-wave me-2"></i> Initial Transaction
    </div>
    <div class="card-body">
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="create_initial_transaction" id="create_initial_transaction"
                   value="1" {{ old('create_initial_transaction', true) ? 'checked' : '' }}>
            <label class="form-check-label" for="create_initial_transaction">
                Create initial transaction to initialize fund accounting
            </label>
        </div>

        <div id="transaction_fields">
            <div class="row">
                <!-- Initial Shares Field -->
                <div class="form-group col-md-4 mb-3">
                    <label for="initial_shares" class="form-label">
                        <i class="fa fa-chart-pie me-1"></i> Initial Shares
                    </label>
                    <input type="number" name="initial_shares" id="initial_shares" class="form-control"
                           value="{{ old('initial_shares') }}"
                           step="0.00000001" min="0.00000001" placeholder="e.g., 1000.00000000">
                    <small class="text-body-secondary">Number of shares to allocate (optional, can be calculated from value)</small>
                </div>

                <!-- Initial Value Field -->
                <div class="form-group col-md-4 mb-3">
                    <label for="initial_value" class="form-label">
                        <i class="fa fa-dollar-sign me-1"></i> Initial Value
                    </label>
                    <input type="number" name="initial_value" id="initial_value" class="form-control"
                           value="{{ old('initial_value', '0.01') }}"
                           step="0.01" min="0.01" placeholder="0.01">
                    <small class="text-body-secondary">Initial balance amount (defaults to $0.01)</small>
                </div>

                <!-- Transaction Description Field -->
                <div class="form-group col-md-4 mb-3">
                    <label for="transaction_description" class="form-label">
                        <i class="fa fa-comment me-1"></i> Description
                    </label>
                    <input type="text" name="transaction_description" id="transaction_description" class="form-control"
                           value="{{ old('transaction_description', 'Initial fund setup') }}" maxlength="255">
                    <small class="text-body-secondary">Transaction description</small>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fa fa-info-circle me-1"></i>
                <strong>About Shares and Value:</strong>
                <ul class="mb-0 mt-2">
                    <li>If you provide <strong>both shares and value</strong>, the share price will be calculated automatically (Value ÷ Shares)</li>
                    <li>If you provide <strong>only value</strong>, shares will be calculated based on the fund's current share price</li>
                    <li>For a new fund, providing a minimal value ($0.01) with shares (e.g., 1) sets the initial share price</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<hr class="my-4">

<!-- Submit Buttons -->
<div class="form-group">
    <button type="submit" name="preview" value="1" class="btn btn-info">
        <i class="fa fa-eye me-1"></i> Preview Setup
    </button>
    <button type="submit" name="preview" value="0" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Create Fund
    </button>
    <a href="{{ route('funds.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>

<script>
    // Toggle transaction fields visibility based on checkbox
    document.getElementById('create_initial_transaction').addEventListener('change', function() {
        document.getElementById('transaction_fields').style.display = this.checked ? 'block' : 'none';
    });

    // Initialize visibility on page load
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('create_initial_transaction');
        document.getElementById('transaction_fields').style.display = checkbox.checked ? 'block' : 'none';
    });
</script>
