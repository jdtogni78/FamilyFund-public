<!-- Date Field -->
<div class="form-group col-sm-6">
    <label for="date">Date:</label>
    <input type="text" name="date" class="form-control" id="date" value="{{ old('date', isset($depositRequest->date) ? \Carbon\Carbon::parse($depositRequest->date)->format('Y-m-d') : '') }}">
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
    <input type="text" name="description" class="form-control" value="{{ old('description', $depositRequest->description ?? '') }}">
</div>

<!-- Amount Field -->
<div class="form-group col-sm-6">
    <label for="amount">Amount:</label>
    <input type="number" name="amount" class="form-control" step="0.01" value="{{ old('amount', $depositRequest->amount ?? '') }}">
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    <label for="status">Status:</label>
    <select name="status" class="form-control">
        @foreach($api['statusMap'] ?? ['P' => 'Pending', 'A' => 'Approved', 'R' => 'Rejected'] as $value => $label)
            <option value="{{ $value }}" {{ old('status', $depositRequest->status ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

<!-- Fund-filtered Account Selector -->
@php
    $selectedAccounts = old('account_id', $depositRequest->account_id ?? null);
@endphp
@include('partials.fund_account_selector', [
    'selectedAccounts' => $selectedAccounts ? [$selectedAccounts] : [],
    'multiple' => false,
    'fieldName' => 'account_id'
])

@if ($isEdit ?? false)
<!-- Cash Deposit Id Field -->
<div class="form-group col-sm-6">
    <label for="cash_deposit_id">Cash Deposit Id:</label>
    <input type="text" name="cash_deposit_id" class="form-control" value="{{ old('cash_deposit_id', $depositRequest->cash_deposit_id ?? '') }}">
</div>

<!-- Transaction Id Field -->
<div class="form-group col-sm-6">
    <label for="transaction_id">Transaction Id:</label>
    <input type="text" name="transaction_id" class="form-control" value="{{ old('transaction_id', $depositRequest->transaction_id ?? '') }}">
</div>
@endif

<!-- Submit Field -->
<div class="form-group col-sm-12">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('depositRequests.index') }}" class="btn btn-secondary">Cancel</a>
</div>
