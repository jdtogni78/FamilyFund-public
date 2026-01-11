<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Matching Rule Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="matching_rule_id" class="form-label">
            <i class="fa fa-link me-1"></i> Matching Rule <span class="text-danger">*</span>
        </label>
        <select name="matching_rule_id" id="matching_rule_id" class="form-control form-select" required>
            @foreach($api['matchingRuleMap'] ?? [] as $value => $label)
                <option value="{{ $value }}" {{ (isset($transactionMatching) && $transactionMatching->matching_rule_id == $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Matching rule used for this transaction pair</small>
    </div>

    <div class="col-md-6"></div>
</div>

<hr class="my-3">

<div class="row">
    <!-- Transaction Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="transaction_id" class="form-label">
            <i class="fa fa-exchange-alt me-1"></i> Transaction <span class="text-danger">*</span>
        </label>
        <select name="transaction_id" id="transaction_id" class="form-control form-select" required>
            @foreach($api['transactionMap'] ?? [] as $value => $label)
                <option value="{{ $value }}" {{ (isset($transactionMatching) && $transactionMatching->transaction_id == $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Primary transaction being matched</small>
    </div>

    <!-- Reference Transaction Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="reference_transaction_id" class="form-label">
            <i class="fa fa-exchange-alt me-1"></i> Reference Transaction <span class="text-danger">*</span>
        </label>
        <select name="reference_transaction_id" id="reference_transaction_id" class="form-control form-select" required>
            @foreach($api['transactionMap'] ?? [] as $value => $label)
                <option value="{{ $value }}" {{ (isset($transactionMatching) && $transactionMatching->reference_transaction_id == $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Matching reference transaction</small>
    </div>
</div>

<hr class="my-4">

<!-- Submit Field -->
<div class="form-group">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save me-1"></i> Save
    </button>
    <a href="{{ route('transactionMatchings.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
