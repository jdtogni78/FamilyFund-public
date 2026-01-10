@php
    $trans = $transaction ?? null;
    $defaultType = $trans->type ?? 'PUR';
    $defaultStatus = $trans->status ?? 'P';
    $defaultValue = $trans->value ?? '';
    $defaultFlags = $trans->flags ?? null;
    $defaultDescr = $trans->descr ?? '';
@endphp

<div class="row mb-3">
    <div class="col-md-6">
        <label for="type" class="form-label">Transaction Type</label>
        <select name="type" class="form-select" id="type" required>
            @foreach($api['typeMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultType == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label for="value" class="form-label">Amount</label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="value" class="form-control" step="0.01"
                   id="value" value="{{ $defaultValue }}" placeholder="0.00" required>
        </div>
        <div class="form-text">
            <span class="text-success">+ Deposit</span> |
            <span class="text-danger">- Withdrawal</span>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="status" class="form-label">Status</label>
        <select name="status" class="form-select" id="status">
            @foreach($api['statusMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultStatus == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label for="flags" class="form-label">Flags</label>
        <select name="flags" class="form-select" id="flags">
            @foreach($api['flagsMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultFlags == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <div class="form-text small">
            <strong>No matching:</strong> Skip match |
            <strong>Add Cash:</strong> Create cash entry |
            <strong>Cash Added:</strong> Already tracked
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <label for="descr" class="form-label">Description</label>
        <input type="text" name="descr" class="form-control" id="descr"
               maxlength="255" value="{{ $defaultDescr }}" placeholder="Optional note...">
    </div>
</div>
