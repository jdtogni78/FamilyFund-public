<!-- Account Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('account_name', 'Account Name:') !!}
    {!! Form::text('account_name', null, ['class' => 'form-control','maxlength' => 50,'maxlength' => 50]) !!}
</div>

<!-- Fund Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('fund_id', 'Fund Id:') !!}
    {!! Form::number('fund_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Start Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('start_dt', 'Start Date:') !!}
    {!! Form::date('start_dt', null, ['class' => 'form-control', 'id'=>'start_dt']) !!}
</div>

@push('scripts')
<script type="text/javascript">
    $('#start_dt').datepicker({
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

<!-- End Date Field -->
<div class="form-group col-sm-6">
    {!! Form::label('end_dt', 'End Date:') !!}
    {!! Form::date('end_dt', null, ['class' => 'form-control', 'id'=>'end_dt']) !!}
</div>

@push('scripts')
    <script type="text/javascript">
        $('#end_dt').datepicker({
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

<!-- Cash Target Field -->
<div class="form-group col-sm-6">
    {!! Form::label('cash_target', 'Cash Target:') !!}
    {!! Form::number('cash_target', null, ['class' => 'form-control', 'step' => 0.01]) !!}
</div>

<!-- Cash Reserve Target Field -->
<div class="form-group col-sm-6">
    {!! Form::label('cash_reserve_target', 'Cash Reserve Target:') !!}
    {!! Form::number('cash_reserve_target', null, ['class' => 'form-control']) !!}
</div>

<!-- Max Single Order Field -->
<div class="form-group col-sm-6">
    {!! Form::label('max_single_order', 'Max Single Order:') !!}
    {!! Form::number('max_single_order', null, ['class' => 'form-control', 'step' => 0.01]) !!}
</div>

<!-- Minimum Order Field -->
<div class="form-group col-sm-6">
    {!! Form::label('minimum_order', 'Minimum Order:') !!}
    {!! Form::number('minimum_order', null, ['class' => 'form-control']) !!}
</div>

<!-- Rebalance Period Field -->
<div class="form-group col-sm-6">
    {!! Form::label('rebalance_period', 'Rebalance Period:') !!}
    {!! Form::number('rebalance_period', null, ['class' => 'form-control']) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('tradePortfolios.index') }}" class="btn btn-secondary">Cancel</a>
</div>
