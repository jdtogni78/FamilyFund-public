<div class="row">
    <!-- Start Date Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="start_dt" class="form-label">
            <i class="fa fa-play me-1"></i> Start Date <span class="text-danger">*</span>
        </label>
        <input type="date" name="start_dt" id="start_dt" class="form-control"
               value="{{ isset($tradePortfolio) ? $tradePortfolio->start_dt->format('Y-m-d') : now()->format('Y-m-d') }}" required>
        <small class="text-body-secondary">When this trade portfolio becomes active</small>
    </div>

    <!-- End Date Field -->
    <div class="form-group col-md-6 mb-3">
        <label for="end_dt" class="form-label">
            <i class="fa fa-stop me-1"></i> End Date <span class="text-danger">*</span>
        </label>
        <input type="date" name="end_dt" id="end_dt" class="form-control"
               value="{{ isset($tradePortfolio) ? $tradePortfolio->end_dt->format('Y-m-d') : '9999-12-31' }}" required>
        <small class="text-body-secondary">When this trade portfolio expires</small>
    </div>
</div>
