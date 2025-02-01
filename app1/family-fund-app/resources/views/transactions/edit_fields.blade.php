<!-- Type Field -->
<div class="form-group col-sm-6">
<label for="type">Type:</label>
<select name="type" class="form-control">
    @foreach({{ $api['typeMap'] }} as $value => $label)
        <option value="{{ $value }}" { {{ $transaction->type }} == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
<label for="status">Status:</label>
<select name="status" class="form-control">
    @foreach({{ $api['statusMap'] }} as $value => $label)
        <option value="{{ $value }}" { {{ $transaction->status }} == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Value Field -->
<div class="form-group col-sm-6">
<label for="value">Value:</label>
<input type="number" name="value" value="{ $transaction->value }" class="form-control" step="any">
</div>

<!-- Flags Field -->
<div class="form-group col-sm-6">
<label for="flags">Flags:</label>
<select name="flags" class="form-control">
    @foreach({{ $api['flagsMap'] }} as $value => $label)
        <option value="{{ $value }}" { {{ $transaction->flags }} == $value ? 'selected' : '' }>{ $label }</option>
    @endforeach
</select>
</div>

<!-- Timestamp Field -->
<div class="form-group col-sm-6">
<label for="timestamp">Timestamp:</label>
<input type="text" name="timestamp" value="{ $transaction->timestamp }" class="form-control" id="timestamp">
</div>

@push('scripts')
    <script type="text/javascript">
        $('#timestamp').datetimepicker({
            format: 'YYYY-MM-DD',
            useCurrent: true,
            icons: {
                up: "icon-arrow-up-circle icons font-2xl",
                down: "icon-arrow-down-circle icons font-2xl"
            },
            sideBySide: true
        }).on('dp.change', function(e){
            if(e.date){
                updateShareValue();
            }
        });

    </script>
@endpush

<!-- Account Id Field -->
<div class="form-group col-sm-6">
<label for="account_id">Account:</label>
<select name="account_id" class="form-control">
    @foreach($api['accountMap'] as $value => $label)
        <option value="{ $value }" { $transaction->account_id == $value ? 'selected' : '' }>
            { $label }
        </option>
    @endforeach
</select>
</div>

<!-- CALC Shares -->
<div class="form-group col-sm-6">
<label for="shares">Shares:</label>
<input type="number" name="shares" value="{ $transaction->balance?->shares }" class="form-control" step="0.0001">
</div>

<!-- CALC Share Prices -->
<div class="form-group col-sm-6">
<label for="share_price">Share Price:</label>
<input type="text" name="share_price" value="{ $transaction->balance?->share_price }" class="form-control">
</div>

<!-- Descr Field -->
<div class="form-group col-sm-6">
<label for="descr">Descr:</label>
<input type="text" name="descr" value="{ $transaction->descr }" class="form-control">
</div>

<!-- Scheduled Job Id Field -->
<div class="form-group col-sm-6">
<label for="scheduled_job_id">Scheduled Job Id:</label>
<input type="number" name="scheduled_job_id" value="{ $transaction->scheduled_job_id }" class="form-control">
</div>

<!-- Cash Deposit Id Field -->
<div class="form-group col-sm-6">
<label for="cash_deposit_id">Cash Deposit Id:</label>
<input type="number" name="cash_deposit_id" value="{ $transaction->cashDeposit?->id }" class="form-control">
</div>

<!-- Deposit Request Id Field -->
<div class="form-group col-sm-6">
<label for="deposit_request_id">Deposit Request Id:</label>
<input type="number" name="deposit_request_id" value="{ $transaction->depositRequest?->id }" class="form-control">
</div>


<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route('transactions.index') }}" class="btn btn-secondary">Cancel</a>
</div>
