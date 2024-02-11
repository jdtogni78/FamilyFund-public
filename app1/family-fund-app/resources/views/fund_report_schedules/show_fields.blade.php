<!-- Fund Report Id Field -->
<div class="form-group">
    {!! Form::label('fund_report_id', 'Fund Report Id:') !!}
    <p>{{ $fundReportSchedule->fund_report_id }}</p>
</div>

<!-- Schedule Id Field -->
<div class="form-group">
    {!! Form::label('schedule_id', 'Schedule Id:') !!}
    <p>{{ $fundReportSchedule->schedule_id }}</p>
</div>

<!-- Start Dt Field -->
<div class="form-group">
    {!! Form::label('start_dt', 'Start Dt:') !!}
    <p>{{ $fundReportSchedule->start_dt }}</p>
</div>

<!-- End Dt Field -->
<div class="form-group">
    {!! Form::label('end_dt', 'End Dt:') !!}
    <p>{{ $fundReportSchedule->end_dt }}</p>
</div>

