<!-- Name Field -->
<div class="form-group col-sm-6">
    <label for="name">Name:</label>
    <input type="text" name="name" class="form-control" maxlength="50" value="{{ old('name', $matchingRule->name ?? '') }}">
</div>

<!-- Dollar Range Start Field -->
<div class="form-group col-sm-6">
    <label for="dollar_range_start">Dollar Range Start:</label>
    <input type="number" name="dollar_range_start" class="form-control" value="{{ old('dollar_range_start', $matchingRule->dollar_range_start ?? '') }}">
</div>

<!-- Dollar Range End Field -->
<div class="form-group col-sm-6">
    <label for="dollar_range_end">Dollar Range End:</label>
    <input type="number" name="dollar_range_end" class="form-control" value="{{ old('dollar_range_end', $matchingRule->dollar_range_end ?? '') }}">
</div>

<!-- Date Start Field -->
<div class="form-group col-sm-6">
    <label for="date_start">Date Start:</label>
    <input type="text" name="date_start" class="form-control" id="date_start" value="{{ old('date_start', isset($matchingRule->date_start) ? \Carbon\Carbon::parse($matchingRule->date_start)->format('Y-m-d') : '') }}">
</div>

@push('scripts')
   <script type="text/javascript">
           $('#date_start').datetimepicker({
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


<!-- Date End Field -->
<div class="form-group col-sm-6">
    <label for="date_end">Date End:</label>
    <input type="text" name="date_end" class="form-control" id="date_end" value="{{ old('date_end', isset($matchingRule->date_end) ? \Carbon\Carbon::parse($matchingRule->date_end)->format('Y-m-d') : '') }}">
</div>

@push('scripts')
   <script type="text/javascript">
           $('#date_end').datetimepicker({
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


<!-- Match Percent Field -->
<div class="form-group col-sm-6">
    <label for="match_percent">Match Percent:</label>
    <input type="number" name="match_percent" class="form-control" step="0.01" value="{{ old('match_percent', $matchingRule->match_percent ?? '') }}">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('matchingRules.index') }}" class="btn btn-secondary">Cancel</a>
</div>
