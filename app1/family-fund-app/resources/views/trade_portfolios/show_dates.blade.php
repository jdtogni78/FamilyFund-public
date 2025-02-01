<!-- Start Date Field -->
<div class="form-group  col-sm-6">
<label for="start_dt">Start Date:</label>
    <p id="show_start_dt">{{ ($tradePortfolio['show_start_dt'] ?? $tradePortfolio['start_dt'])->format('Y-m-d') }}</p>
</div>

<!-- create end date field -->
<div class="form-group  col-sm-6">
<label for="end_dt">End Date:</label>
    <p id="show_end_dt">{{ ($tradePortfolio['show_end_dt'] ?? $tradePortfolio['end_dt'])->format('Y-m-d') }}</p>
</div>
