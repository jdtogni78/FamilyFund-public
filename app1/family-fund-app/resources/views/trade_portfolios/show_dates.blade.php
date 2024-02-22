<!-- Start Date Field -->
<div class="form-group  col-sm-6">
    {!! Form::label('start_dt', 'Start Date:') !!}
    <p id="show_start_dt">{{ $tradePortfolio['show_start_dt']->format('Y-m-d') }}</p>
</div>

<!-- create end date field -->
<div class="form-group  col-sm-6">
    {!! Form::label('end_dt', 'End Date:') !!}
    <p id="show_end_dt">{{ $tradePortfolio['show_end_dt']->format('Y-m-d') }}</p>
</div>
