<!-- Name Field -->
<div class="form-group">
<label for="name">Name:</label>
    {{ $fund->name }}</p>
</div>

<!-- Shares Field -->
<div class="form-group">
<label for="shares">Shares:</label>
    {{ $calculated['shares'] }}</p>
</div>

<!-- Unallocated Shares Field -->
<div class="form-group">
<label for="unallocated_shares">Unallocated Shares:</label>
    {{ $calculated['unallocated_shares'] }}</p>
</div>

<!-- Value Field -->
<div class="form-group">
<label for="value">Value:</label>
    {{ $calculated['value'] }}</p>
</div>

<!-- AsOf Field -->
<div class="form-group">
<label for="asof">AsOf:</label>
    {{ $calculated['as_of'] }}</p>
</div>

