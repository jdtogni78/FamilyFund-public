<!-- Name Field -->
<div class="form-group">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $goal->name }}</p>
</div>

<!-- Description Field -->
<div class="form-group">
    {!! Form::label('description', 'Description:') !!}
    <p>{{ $goal->description }}</p>
</div>

<!-- Start Dt Field -->
<div class="form-group">
    {!! Form::label('start_dt', 'Start Dt:') !!}
    <p>{{ $goal->start_dt }}</p>
</div>

<!-- End Dt Field -->
<div class="form-group">
    {!! Form::label('end_dt', 'End Dt:') !!}
    <p>{{ $goal->end_dt }}</p>
</div>

<!-- Target Type Field -->
<div class="form-group">
    {!! Form::label('target_type', 'Target Type:') !!}
    <p>{{ $goal->target_type }}</p>
</div>

<!-- Target Amount Field -->
<div class="form-group">
    {!! Form::label('target_amount', 'Target Amount:') !!}
    <p>{{ $goal->target_amount }}</p>
</div>

<!-- Target Pct Field -->
<div class="form-group">
    {!! Form::label('target_pct', 'Target Percentage:') !!}
    <p>{{ $goal->target_pct }}</p>
</div>

