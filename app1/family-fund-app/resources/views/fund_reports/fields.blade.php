<!-- Fund Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('fund_id', 'Fund Id:') !!}
    {!! Form::number('fund_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('type', 'Type:') !!}
    {!! Form::text('type', null, ['class' => 'form-control','maxlength' => 3]) !!}
</div>

<!-- As Of Field -->
<div class="form-group col-sm-6">
    {!! Form::label('as_of', 'As Of:') !!}
    {!! Form::text('as_of', null, ['class' => 'form-control','id'=>'as_of']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#as_of').datetimepicker({
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


<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('fundReports.index') }}" class="btn btn-secondary">Cancel</a>
</div>
