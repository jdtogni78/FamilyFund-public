<!-- Portfolio Id Field -->
<div class="form-group col-sm-6">
<label for="portfolio_id">Portfolio Id:</label>
<input type="number" name="portfolio_id" value="{{ $portfolioAsset?->portfolio_id ?? null }}" class="form-control">
</div>

<!-- Asset Id Field -->
<div class="form-group col-sm-6">
<label for="asset_id">Asset Id:</label>
<input type="number" name="asset_id" value="{{ $portfolioAsset?->asset_id ?? null }}" class="form-control">
</div>

<!-- Position Field -->
<div class="form-group col-sm-6">
<label for="position">Position:</label>
<input type="number" name="position" value="{{ $portfolioAsset?->position ?? 0 }}" class="form-control" step="0.0001">
</div>

<!-- Start Dt Field -->
<div class="form-group col-sm-6">
<label for="start_dt">Start Date:</label>
<input type="text" name="start_dt" value="{{ ($portfolioAsset?->start_dt ?? \Carbon\Carbon::now())->format('Y-m-d') }}" class="form-control" id="start_dt">
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
           })
       </script>
@endpush


<!-- End Dt Field -->
<div class="form-group col-sm-6">
<label for="end_dt">End Date:</label>
<input type="text" name="end_dt" value="{{ ($portfolioAsset?->end_dt ?? \Carbon\Carbon::now())->format('Y-m-d') }}" class="form-control" id="end_dt">
</div>

@push('scripts')
   <script type="text/javascript">
           $('#end_dt').datetimepicker({
               format: 'YYYY-MM-DD',
               useCurrent: true,
               icons: {
                   up: "icon-arrow-up-circle icons font-2xl",
                   down: "icon-arrow-down-circle icons font-2xl"
               },
               sideBySide: true
           })
       </script>
@endpush


<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('portfolioAssets.index') }}" class="btn btn-secondary">Cancel</a>
</div>
