<!-- Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('type', 'Type:') !!}
    {!! Form::select('type', $api['typeMap'], 'PUR', ['class' => 'form-control']);  !!}
{{--    'SAL' => 'Sale',--}}
{{--    'BOR' => 'Borrow',--}}
{{--    'REP' => 'Repay',--}}
{{--    'MAT' => 'Match',--}}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:') !!}
    {!! Form::select('status', $api['statusMap'], 'P', ['class' => 'form-control']);  !!}
</div>

<!-- Value Field -->
<div class="form-group col-sm-6">
    {!! Form::label('value', 'Value:') !!}
    {!! Form::number('value', null, ['class' => 'form-control', 'step' => 'any']) !!}
</div>

<!-- Timestamp Field -->
<div class="form-group col-sm-6">
    {!! Form::label('timestamp', 'Timestamp:') !!}
    {!! Form::text('timestamp', null, ['class' => 'form-control','id'=>'timestamp']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
       function updateShareValue() {
           $('#__share_price').val("...");
           $('#__shares').val("...");

           account = $('#account_id').find(":selected").val();
           dt = $("#timestamp").datetimepicker('getDate').val();
           console.log('Date chosen: "' + dt + '"');

           if (dt === '')  return;
           myUrl = '/api/accounts/' + account + '/share_value_as_of/' + dt;
           console.log(myUrl);

           $.ajax({
               type:'GET',
               url: myUrl,
               data:'_token = <?php echo csrf_token() ?>',
               success:function(data) {
                   share_price = data['data']['share_price'];
                   console.log(share_price);

                   $('#__share_price').val(share_price);
                   value = $('#value').val();
                   $('#__shares').val(value / share_price);
               }
           });
       }

       $("#account_id").change(function() {
           updateShareValue();
       })

       $("#value").change(function() {
           value = $('#value').val();
           share_price = $('#__share_price').val();
           $('#__shares').val(value / share_price);
       })

       $('#timestamp').datetimepicker({
           format: 'YYYY-MM-DD',
           useCurrent: true,
           icons: {
               up: "icon-arrow-up-circle icons font-2xl",
               down: "icon-arrow-down-circle icons font-2xl"
           },
           sideBySide: true
       }).on('dp.change', function(e){
           if(e.date){
               updateShareValue();
           }
       });

   </script>
@endpush

<!-- Account Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('account_id', 'Account:') !!}
    {!! Form::select('account_id', $api['accountMap'], null, ['class' => 'form-control']) !!}
</div>

<!-- CALC Shares -->
<div class="form-group col-sm-6">
    {!! Form::label('__shares', 'Shares:') !!}
    {!! Form::number('__shares', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
</div>

<!-- CALC Share Prices -->
<div class="form-group col-sm-6">
    {!! Form::label('__share_price', 'Share Price:') !!}
    {!! Form::text('__share_price', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
</div>

<!-- Descr Field -->
<div class="form-group col-sm-6">
    {!! Form::label('descr', 'Descr:') !!}
    {!! Form::text('descr', null, ['class' => 'form-control','maxlength' => 255]) !!}
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('transactions.index') }}" class="btn btn-secondary">Cancel</a>
</div>
