<!-- Fund Field -->
<div class="form-group">
    <label for="fund_id">Fund:</label>
    <p>{{ $tradeBandReport->fund->name ?? $tradeBandReport->fund_id }}</p>
</div>

<!-- As Of Field -->
<div class="form-group">
    <label for="as_of">As Of:</label>
    <p>
        @if($tradeBandReport->as_of && $tradeBandReport->as_of->format('Y') !== '9999')
            {{ $tradeBandReport->as_of->format('Y-m-d') }}
        @else
            <span class="text-muted">Not set</span>
        @endif
    </p>
</div>

<!-- Scheduled Job Id Field -->
<div class="form-group">
    <label for="scheduled_job_id">Scheduled Job Id:</label>
    <p>{{ $tradeBandReport->scheduled_job_id }}</p>
</div>

<!-- Created At Field -->
<div class="form-group">
    <label for="created_at">Created At:</label>
    <p>{{ $tradeBandReport->created_at }}</p>
</div>
