<!-- Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('type', 'Type:') !!}
    {!! Form::select('type', $api['typeMap'], 'PUR', ['class' => 'form-control']);  !!}
    {{--    'SAL' => 'Sale',--}}
    {{--    'BOR' => 'Borrow',--}}
    {{--    'REP' => 'Repay',--}}
    {{--    'MAT' => 'Match',--}}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:') !!}
    {!! Form::select('status', $api['statusMap'], 'P', ['class' => 'form-control']);  !!}
</div>

<!-- Value Field -->
<div class="form-group col-sm-6">
    {!! Form::label('value', 'Value:') !!}
    {!! Form::number('value', null, ['class' => 'form-control', 'step' => 'any']) !!}
</div>

<!-- Timestamp Field -->
<div class="form-group col-sm-6">
    {!! Form::label('timestamp', 'Timestamp:') !!}
    {!! Form::text('timestamp', null, ['class' => 'form-control','id'=>'timestamp']) !!}
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
    {!! Form::select('account_id', $api['accountMap'], null, ['class' => 'form-control']) !!}
</div>

<!-- CALC Shares -->
<div class="form-group col-sm-6">
    {!! Form::label('shares', 'Shares:') !!}
    {!! Form::number('shares', null, ['class' => 'form-control', 'step' => 0.0001]) !!}
</div>

<!-- CALC Share Prices -->
<div class="form-group col-sm-6">
    {!! Form::label('share_price', 'Share Price:') !!}
    {!! Form::text('share_price', null, ['class' => 'form-control', 'step' => 0.0001]) !!}
</div>

<!-- Descr Field -->
<div class="form-group col-sm-6">
    {!! Form::label('descr', 'Descr:') !!}
    {!! Form::text('descr', null, ['class' => 'form-control','maxlength' => 255]) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('transactions.index') }}" class="btn btn-secondary">Cancel</a>
</div>
