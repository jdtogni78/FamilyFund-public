<!-- Descr Field -->
<div class="form-group">
    {!! Form::label('descr', 'Descr:') !!}
    <p>{{ $schedule->descr }}</p>
</div>

<!-- Type Field -->
<div class="form-group">
    {!! Form::label('type', 'Type:') !!}
    <p>{{ $schedule->type }}</p>
</div>

<!-- Value Field -->
<div class="form-group">
    {!! Form::label('value', 'Value:') !!}
    <p>{{ $schedule->value }}</p>
</div>

