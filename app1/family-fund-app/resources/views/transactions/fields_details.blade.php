@php
    $trans = $transaction ?? null;
    $defaultType = $trans->type ?? 'PUR';
    $defaultStatus = $trans->status ?? 'P';
    $defaultValue = $trans->value ?? '';
    $defaultFlags = $trans->flags ?? null;
    $defaultDescr = $trans->descr ?? '';
@endphp

<div class="row g-3">
    <!-- Row 1: Type and Amount -->
    <div class="col-md-6">
        <label for="type" class="form-label fw-bold">
            <i class="fa fa-tag me-1"></i>Transaction Type
        </label>
        <select name="type" class="form-select form-select-lg" id="type" required>
            @foreach($api['typeMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultType == $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label for="value" class="form-label fw-bold">
            <i class="fa fa-dollar-sign me-1"></i>Amount
        </label>
        <div class="input-group input-group-lg">
            <span class="input-group-text">$</span>
            <input type="number" name="value" class="form-control" step="0.01"
                   id="value" value="{{ $defaultValue }}" placeholder="0.00" required>
        </div>
        <div class="form-text">
            <span class="text-success"><i class="fa fa-plus me-1"></i>Positive = Deposit</span>
            <span class="mx-2">|</span>
            <span class="text-danger"><i class="fa fa-minus me-1"></i>Negative = Withdrawal</span>
        </div>
    </div>

    <!-- Row 2: Status and Flags -->
    <div class="col-md-6">
        <label for="status" class="form-label fw-bold">
            <i class="fa fa-flag me-1"></i>Status
        </label>
        <select name="status" class="form-select" id="status">
            @foreach($api['statusMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultStatus == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label for="flags" class="form-label fw-bold">
            <i class="fa fa-cog me-1"></i>Flags
        </label>
        <select name="flags" class="form-select" id="flags">
            @foreach($api['flagsMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultFlags == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <!-- Row 3: Description -->
    <div class="col-md-12">
        <label for="descr" class="form-label fw-bold">
            <i class="fa fa-comment me-1"></i>Description
        </label>
        <input type="text" name="descr" class="form-control" id="descr"
               maxlength="255" value="{{ $defaultDescr }}" placeholder="Optional note about this transaction...">
    </div>
</div>
