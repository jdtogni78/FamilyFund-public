<style>
    .form-select, .form-control {
        font-size: 0.875rem;
    }
</style>

<div class="row">
    <!-- Portfolio Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="portfolio_id" class="form-label">
            <i class="fa fa-briefcase me-1"></i> Portfolio ID <span class="text-danger">*</span>
        </label>
        <input type="number" name="portfolio_id" id="portfolio_id" class="form-control"
               value="{{ $portfolioAsset->portfolio_id ?? old('portfolio_id') }}" required>
        <small class="text-body-secondary">ID of the portfolio this asset belongs to</small>
    </div>

    <!-- Asset Id Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="asset_id" class="form-label">
            <i class="fa fa-coins me-1"></i> Asset ID <span class="text-danger">*</span>
        </label>
        <input type="number" name="asset_id" id="asset_id" class="form-control"
               value="{{ $portfolioAsset->asset_id ?? old('asset_id') }}" required>
        <small class="text-body-secondary">ID of the asset</small>
    </div>
</div>

<div class="row">
    <!-- Position Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="position" class="form-label">
            <i class="fa fa-cubes me-1"></i> Position <span class="text-danger">*</span>
        </label>
        <input type="number" name="position" id="position" class="form-control" step="0.0001"
               value="{{ $portfolioAsset->position ?? old('position', 0) }}" required>
        <small class="text-body-secondary">Number of units/shares held</small>
    </div>

    <div class="col-md-6"></div>
</div>

<hr class="my-3">

<div class="row">
    <!-- Start Dt Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="start_dt" class="form-label">
            <i class="fa fa-calendar me-1"></i> Start Date <span class="text-danger">*</span>
        </label>
        <input type="text" name="start_dt" id="start_dt" class="form-control"
               value="{{ ($portfolioAsset->start_dt ?? \Carbon\Carbon::now())->format('Y-m-d') }}" required>
        <small class="text-body-secondary">When this position started (YYYY-MM-DD)</small>
    </div>

    <!-- End Dt Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="end_dt" class="form-label">
            <i class="fa fa-calendar-check me-1"></i> End Date
        </label>
        <input type="text" name="end_dt" id="end_dt" class="form-control"
               value="{{ ($portfolioAsset->end_dt ?? \Carbon\Carbon::now())->format('Y-m-d') }}">
        <small class="text-body-secondary">When this position ended (YYYY-MM-DD)</small>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
    $('#start_dt').datetimepicker({
        format: 'YYYY-MM-DD',
        useCurrent: true,
        icons: {
            up: "icon-arrow-up-circle icons font-2xl",
            down: "icon-arrow-down-circle icons font-2xl"
        },
        sideBySide: true
    });
    $('#end_dt').datetimepicker({
        format: 'YYYY-MM-DD',
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
    <a href="{{ route('portfolioAssets.index') }}" class="btn btn-secondary">
        <i class="fa fa-times me-1"></i> Cancel
    </a>
</div>
