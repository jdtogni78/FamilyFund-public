<!-- Start Date Field -->
<div class="form-group col-sm-6">
<label for="start_dt">Start Date:</label>
    <input type="date" name="start_dt" value="{{ isset($tradePortfolio) ? $tradePortfolio->start_dt->format('Y-m-d') : new \Carbon\Carbon() }}" class="form-control" id="start_dt">
</div>

<!-- End Date Field -->
<div class="form-group col-sm-6">
<label for="end_dt">End Date:</label>
    <input type="date" name="end_dt" value="{{ isset($tradePortfolio) ? $tradePortfolio->end_dt->format('Y-m-d') : '9999-12-31' }}" class="form-control" id="end_dt">
</div>

