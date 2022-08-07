<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control','maxlength' => 50,'maxlength' => 50]) !!}
</div>

<!-- Dollar Range Start Field -->
<div class="form-group col-sm-6">
    {!! Form::label('dollar_range_start', 'Dollar Range Start:') !!}
    {!! Form::number('dollar_range_start', null, ['class' => 'form-control']) !!}
</div>

<!-- Dollar Range End Field -->
<div class="form-group col-sm-6">
    {!! Form::label('dollar_range_end', 'Dollar Range End:') !!}
    {!! Form::number('dollar_range_end', null, ['class' => 'form-control']) !!}
</div>

<!-- Date Start Field -->
<div class="form-group col-sm-6">
    {!! Form::label('date_start', 'Date Start:') !!}
    {!! Form::text('date_start', null, ['class' => 'form-control','id'=>'date_start']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#date_start').datetimepicker({
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


<!-- Date End Field -->
<div class="form-group col-sm-6">
    {!! Form::label('date_end', 'Date End:') !!}
    {!! Form::text('date_end', null, ['class' => 'form-control','id'=>'date_end']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
           $('#date_end').datetimepicker({
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


<!-- Match Percent Field -->
<div class="form-group col-sm-6">
    {!! Form::label('match_percent', 'Match Percent:') !!}
    {!! Form::number('match_percent', null, ['class' => 'form-control']) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('matchingRules.index') }}" class="btn btn-secondary">Cancel</a>
</div>
