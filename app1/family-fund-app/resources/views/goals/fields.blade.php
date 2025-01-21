<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control','maxlength' => 30]) !!}
</div>

<!-- Description Field -->
<div class="form-group col-sm-6">
    {!! Form::label('description', 'Description:') !!}
    {!! Form::text('description', null, ['class' => 'form-control','maxlength' => 1024]) !!}
</div>

<!-- Start Dt Field -->
<div class="form-group col-sm-6">
    {!! Form::label('start_dt', 'Start Dt:') !!}
    {!! Form::text('start_dt', null, ['class' => 'form-control','id'=>'start_dt']) !!}
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
    {!! Form::text('end_dt', null, ['class' => 'form-control','id'=>'end_dt']) !!}
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
    {!! Form::select('target_type', $api['targetTypeMap'], null, ['class' => 'form-control']) !!}
</div>

<!-- Target Amount Field -->
<div class="form-group col-sm-6">
    {!! Form::label('target_amount', 'Target Amount:') !!}
    {!! Form::number('target_amount', null, ['class' => 'form-control']) !!}
</div>

<!-- Pct4 Field -->
<div class="form-group col-sm-6">
    {!! Form::label('pct4', 'Pct4:') !!}
    {!! Form::number('pct4', null, ['class' => 'form-control']) !!}
</div>

<!-- Accounts Field -->
<div class="form-group col-sm-6">
    {!! Form::label('accounts[]', 'Accounts:') !!}
    {!! Form::select('accounts[]', $api['accountMap'] ?? [], null, ['class' => 'form-control', 'multiple' => 'multiple']) !!}
</div>


<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('goals.index') }}" class="btn btn-secondary">Cancel</a>
</div>
