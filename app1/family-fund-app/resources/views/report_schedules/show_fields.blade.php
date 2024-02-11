<!-- Descr Field -->
<div class="form-group">
    {!! Form::label('descr', 'Descr:') !!}
    <p>{{ $reportSchedule->descr }}</p>
</div>

<!-- Type Field -->
<div class="form-group">
    {!! Form::label('type', 'Type:') !!}
    <p>{{ $reportSchedule->type }}</p>
</div>

<!-- Value Field -->
<div class="form-group">
    {!! Form::label('value', 'Value:') !!}
    <p>{{ $reportSchedule->value }}</p>
</div>

