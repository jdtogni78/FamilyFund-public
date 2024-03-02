<!-- Schedule Id Field -->
<div class="form-group">
    {!! Form::label('schedule_id', 'Schedule Id:') !!}
    <p>{{ $scheduledJob->schedule_id }}</p>
</div>

<!-- Entity Descr Field -->
<div class="form-group">
    {!! Form::label('entity_descr', 'Entity Descr:') !!}
    <p>{{ $scheduledJob->entity_descr }}</p>
</div>

<!-- Entity Id Field -->
<div class="form-group">
    {!! Form::label('entity_id', 'Entity Id:') !!}
    <p>{{ $scheduledJob->entity_id }}</p>
</div>

<!-- Start Dt Field -->
<div class="form-group">
    {!! Form::label('start_dt', 'Start Dt:') !!}
    <p>{{ $scheduledJob->start_dt }}</p>
</div>

<!-- End Dt Field -->
<div class="form-group">
    {!! Form::label('end_dt', 'End Dt:') !!}
    <p>{{ $scheduledJob->end_dt }}</p>
</div>

