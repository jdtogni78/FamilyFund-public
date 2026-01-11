<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Asset Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="asset_id" class="form-label">
            <i class="fa fa-coins me-1"></i> Asset <span class="text-danger">*</span>
        </label>
        <select name="asset_id" id="asset_id" class="form-control form-select" required>
            @foreach($api['assetMap'] ?? [] as $value => $label)
                <option value="{{ $value }}" {{ (isset($assetPrice) && $assetPrice->asset_id == $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-body-secondary">Asset to record price for</small>
    </div>

    <!-- Price Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="price" class="form-label">
            <i class="fa fa-dollar-sign me-1"></i> Price <span class="text-danger">*</span>
        </label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="price" id="price" class="form-control" step="0.0001"
                   value="{{ $assetPrice->price ?? old('price') }}" required>
        </div>
        <small class="text-body-secondary">Price per unit of asset</small>
    </div>
</div>

<hr class="my-3">

<div class="row">
    <!-- Start Dt Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="start_dt" class="form-label">
            <i class="fa fa-calendar me-1"></i> Start Date <span class="text-danger">*</span>
        </label>
        <input type="text" name="start_dt" id="start_dt" class="form-control"
               value="{{ $assetPrice->start_dt ?? old('start_dt') }}" required>
        <small class="text-body-secondary">When this price became effective</small>
    </div>

    <!-- End Dt Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="end_dt" class="form-label">
            <i class="fa fa-calendar-check me-1"></i> End Date
        </label>
        <input type="text" name="end_dt" id="end_dt" class="form-control"
               value="{{ $assetPrice->end_dt ?? old('end_dt') }}">
        <small class="text-body-secondary">When this price ended (blank for current)</small>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
    $('#start_dt').datetimepicker({
        format: 'YYYY-MM-DD HH:mm:ss',
        useCurrent: true,
        icons: {
            up: "icon-arrow-up-circle icons font-2xl",
            down: "icon-arrow-down-circle icons font-2xl"
        },
        sideBySide: true
    });
    $('#end_dt').datetimepicker({
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
    <a href="{{ route('assetPrices.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
