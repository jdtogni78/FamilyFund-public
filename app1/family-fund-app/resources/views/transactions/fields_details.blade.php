@php
    $trans = $transaction ?? null;
    $defaultType = $trans->type ?? 'PUR';
    $defaultStatus = $trans->status ?? 'P';
    $defaultValue = $trans->value ?? '';
    $defaultFlags = $trans->flags ?? null;
    $defaultDescr = $trans->descr ?? '';
@endphp

<div class="row mb-3">
    <div class="col-md-6 d-flex flex-column">
        <label for="type" class="form-label text-muted small text-uppercase mb-1">Transaction Type</label>
        <select name="type" class="form-select" id="type" required>
            @foreach($api['typeMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultType == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <div class="form-text small" id="type_legend">
            <span class="text-success"><i class="fa fa-arrow-right"></i> Cash to Fund, Shares to Account</span>
        </div>
    </div>
    <div class="col-md-6 d-flex flex-column">
        <label for="value" class="form-label text-muted small text-uppercase mb-1">Amount</label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="value" class="form-control" step="0.01"
                   id="value" value="{{ $defaultValue }}" placeholder="0.00" required>
        </div>
        <div class="form-text small">
            <span class="text-success">+ Deposit</span> | <span class="text-danger">- Withdrawal</span>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6 d-flex flex-column">
        <label for="status" class="form-label text-muted small text-uppercase mb-1">Status</label>
        <div class="input-group">
            <select name="status" class="form-select" id="status">
                @foreach($api['statusMap'] as $value => $label)
                    <option value="{{ $value }}" {{ $defaultStatus == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-outline-info" id="recurrentBtn" title="Set as recurrent (scheduled) transaction">
                <i class="fa fa-redo"></i> Recurrent
            </button>
        </div>
        <div class="form-text small">&nbsp;</div>
    </div>
    <div class="col-md-6 d-flex flex-column">
        <label for="flags" class="form-label text-muted small text-uppercase mb-1">Flags</label>
        <select name="flags" class="form-select" id="flags">
            @foreach($api['flagsMap'] as $value => $label)
                <option value="{{ $value }}" {{ $defaultFlags == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <div class="form-text small">
            <b>No matching:</b> Skip match | <b>Add Cash:</b> Create cash entry
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <label for="descr" class="form-label text-muted small text-uppercase mb-1">Description</label>
        <input type="text" name="descr" class="form-control" id="descr"
               maxlength="255" value="{{ $defaultDescr }}" placeholder="Optional note...">
    </div>
</div>
