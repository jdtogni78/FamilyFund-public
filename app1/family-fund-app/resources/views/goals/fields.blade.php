<!-- Name Field -->
<div class="form-group col-sm-6">
    <label for="name">Name:</label>
    <input type="text" name="name" class="form-control" maxlength="30" value="{{ old('name', $goal->name ?? '') }}">
</div>

<!-- Description Field -->
<div class="form-group col-sm-6">
    <label for="description">Description:</label>
    <input type="text" name="description" class="form-control" maxlength="1024" value="{{ old('description', $goal->description ?? '') }}">
</div>

<!-- Start Dt Field -->
<div class="form-group col-sm-6">
    <label for="start_dt">Start Date:</label>
    <input type="text" name="start_dt" class="form-control" id="start_dt" value="{{ old('start_dt', isset($goal->start_dt) ? \Carbon\Carbon::parse($goal->start_dt)->format('Y-m-d') : '') }}">
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
    <input type="text" name="end_dt" class="form-control" id="end_dt" value="{{ old('end_dt', isset($goal->end_dt) ? \Carbon\Carbon::parse($goal->end_dt)->format('Y-m-d') : '') }}">
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


<!-- Target Type Field -->
<div class="form-group col-sm-6">
    <label for="target_type">Target Type:</label>
    <select name="target_type" class="form-control">
        @foreach($api['targetTypeMap'] as $value => $label)
            <option value="{{ $value }}" {{ old('target_type', $goal->target_type ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

<!-- Target Amount Field -->
<div class="form-group col-sm-6">
    <label for="target_amount">Target Amount:</label>
    <input type="number" name="target_amount" class="form-control" value="{{ old('target_amount', $goal->target_amount ?? '') }}">
</div>

<!-- Target Pct Field -->
<div class="form-group col-sm-6">
    <label for="target_pct">Target Percentage:</label>
    <input type="number" name="target_pct" class="form-control" step="0.01" value="{{ old('target_pct', $goal->target_pct ?? '') }}">
</div>

<!-- Fund-filtered Account Selector -->
@php
    $selectedAccounts = old('account_ids', isset($goal) ? $goal->accounts->pluck('id')->toArray() : []);
@endphp
@include('partials.fund_account_selector', ['selectedAccounts' => $selectedAccounts, 'multiple' => true])

<!-- Submit Field -->
<div class="form-group col-sm-12">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('goals.index') }}" class="btn btn-secondary">Cancel</a>
</div>
