<!-- Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('type', 'Type:') !!}
    {!! Form::select('type', $api['typeMap'], $transaction->type, ['class' => 'form-control']);  !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:') !!}
    {!! Form::select('status', $api['statusMap'], $transaction->status, ['class' => 'form-control']);  !!}
</div>

<!-- Value Field -->
<div class="form-group col-sm-6">
    {!! Form::label('value', 'Value:') !!}
    {!! Form::number('value', $transaction->value, ['class' => 'form-control', 'step' => 'any']) !!}
</div>

<!-- Flags Field -->
<div class="form-group col-sm-6">
    {!! Form::label('flags', 'Flags:') !!}
    {!! Form::select('flags', $api['flagsMap'], $transaction->flags, ['class' => 'form-control']);  !!}
</div>

<!-- Timestamp Field -->
<div class="form-group col-sm-6">
    {!! Form::label('timestamp', 'Timestamp:') !!}
    {!! Form::text('timestamp', $transaction->timestamp, ['class' => 'form-control','id'=>'timestamp']) !!}
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
    {!! Form::label('account_id', 'Account:') !!}
    {!! Form::select('account_id', $api['accountMap'], $transaction->account_id, ['class' => 'form-control']) !!}
</div>

<!-- CALC Shares -->
<div class="form-group col-sm-6">
    {!! Form::label('shares', 'Shares:') !!}
    {!! Form::number('shares', $transaction->balance?->shares, ['class' => 'form-control', 'step' => 0.0001]) !!}
</div>

<!-- CALC Share Prices -->
<div class="form-group col-sm-6">
    {!! Form::label('share_price', 'Share Price:') !!}
    {!! Form::text('share_price', $transaction->balance?->share_price, ['class' => 'form-control', 'step' => 0.0001]) !!}
</div>

<!-- Descr Field -->
<div class="form-group col-sm-6">
    {!! Form::label('descr', 'Descr:') !!}
    {!! Form::text('descr', $transaction->descr, ['class' => 'form-control','maxlength' => 255]) !!}
</div>

<!-- Scheduled Job Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('scheduled_job_id', 'Scheduled Job Id:') !!}
    {!! Form::number('scheduled_job_id', $transaction->scheduled_job_id, ['class' => 'form-control']) !!}
</div>

<!-- Cash Deposit Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('cash_deposit_id', 'Cash Deposit Id:') !!}
    {!! Form::number('cash_deposit_id', $transaction->cashDeposit?->id, ['class' => 'form-control']) !!}
</div>

<!-- Deposit Request Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('deposit_request_id', 'Deposit Request Id:') !!}
    {!! Form::number('deposit_request_id', $transaction->depositRequest?->id, ['class' => 'form-control']) !!}
</div>


<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('transactions.index') }}" class="btn btn-secondary">Cancel</a>
</div>
