<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', $goal?->name, ['class' => 'form-control','maxlength' => 30]) !!}
</div>

<!-- Description Field -->
<div class="form-group col-sm-6">
    {!! Form::label('description', 'Description:') !!}
    {!! Form::text('description', $goal?->description, ['class' => 'form-control','maxlength' => 1024]) !!}
</div>

<!-- Start Dt Field -->
<div class="form-group col-sm-6">
    {!! Form::label('start_dt', 'Start Dt:') !!}
    {!! Form::text('start_dt', $goal?->start_dt, ['class' => 'form-control','id'=>'start_dt']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#start_dt').datetimepicker({
               format: 'YYYY-MM-DD HH:mm:ss',
               useCurrent: true,
               icons: {
                   up: "icon-arrow-up-circle icons font-2xl",
                   down: "icon-arrow-down-circle icons font-2xl"
               },
               sideBySide: true
           })
       </script>
@endpush


<!-- End Dt Field -->
<div class="form-group col-sm-6">
    {!! Form::label('end_dt', 'End Dt:') !!}
    {!! Form::text('end_dt', $goal?->end_dt, ['class' => 'form-control','id'=>'end_dt']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#end_dt').datetimepicker({
               format: 'YYYY-MM-DD HH:mm:ss',
               useCurrent: true,
               icons: {
                   up: "icon-arrow-up-circle icons font-2xl",
                   down: "icon-arrow-down-circle icons font-2xl"
               },
               sideBySide: true
           })
       </script>
@endpush


<!-- Target Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('target_type', 'Target Type:') !!}
    {!! Form::select('target_type', $api['targetTypeMap'], $goal?->target_type, ['class' => 'form-control']) !!}
</div>

<!-- Target Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('target_amount', 'Target Amount:') !!}
    {!! Form::number('target_amount', $goal?->target_amount, ['class' => 'form-control']) !!}
</div>

<!-- Pct4 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('pct4', 'Pct4:') !!}
    {!! Form::number('pct4', $goal?->pct4, ['class' => 'form-control']) !!}
</div>

<!-- Account Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('account_id', 'Account Id:') !!}
    {!! Form::select('account_id', $api['accountMap'], $goal?->account_id, ['class' => 'form-control']) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('goals.index') }}" class="btn btn-secondary">Cancel</a>
</div>
