<!-- Date Field -->
<div class="form-group col-sm-6">
    <label for="date">Date:</label>
    <input type="text" name="date" class="form-control" id="date" value="{{ old('date', isset($cashDeposit->date) ? \Carbon\Carbon::parse($cashDeposit->date)->format('Y-m-d') : '') }}">
</div>

@push('scripts')
   <script type="text/javascript">
           $('#date').datetimepicker({
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


<!-- Description Field -->
<div class="form-group col-sm-6">
    <label for="description">Description:</label>
    <input type="text" name="description" class="form-control" value="{{ old('description', $cashDeposit->description ?? '') }}">
</div>

<!-- Value Field -->
<div class="form-group col-sm-6">
    <label for="amount">Amount:</label>
    <input type="number" name="amount" class="form-control" min="0" step="0.01" value="{{ old('amount', $cashDeposit->amount ?? '') }}">
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    <label for="status">Status:</label>
    <select name="status" class="form-control">
        @foreach($api['statusMap'] as $value => $label)
            <option value="{{ $value }}" {{ old('status', $cashDeposit->status ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

<!-- Fund Account Field (Fund accounts only) -->
<div class="form-group col-sm-6">
    <label for="account_id">Fund Account:</label>
    <select name="account_id" class="form-control">
        @foreach($api['fundAccountMap'] as $value => $label)
            <option value="{{ $value }}" {{ old('account_id', $cashDeposit->account_id ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

@if ($isEdit ?? false)
<!-- Transaction Id Field -->
<div class="form-group col-sm-6">
    <label for="transaction_id">Transaction Id:</label>
    <input type="text" name="transaction_id" class="form-control" value="{{ old('transaction_id', $cashDeposit->transaction_id ?? '') }}">
</div>
@endif

<!-- Submit Field -->
<div class="form-group col-sm-12">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('cashDeposits.index') }}" class="btn btn-secondary">Cancel</a>
</div>
