<!-- Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('type', 'Type:') !!}
    {!! Form::select('type', $api['typeMap'], 'PUR', ['class' => 'form-control']);  !!}
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

<!-- Flags Field -->
<div class="form-group col-sm-6">
    {!! Form::label('flags', 'Flags:') !!}
    {!! Form::select('flags', $api['flagsMap'], null, ['class' => 'form-control']);  !!}
</div>

<!-- Timestamp Field -->
<div class="form-group col-sm-6">
    {!! Form::label('timestamp', 'Timestamp:') !!}
    {!! Form::date('timestamp', null, ['class' => 'form-control', 'id' => 'timestamp']) !!}
</div>

@push('scripts')
   <script type="text/javascript">
       api = {!! json_encode($api) !!};
       function updateShareValue() {
           if ($('#type').val() === 'INI') {
               return;
           }
           $('#__share_price').val(0);
           $('#shares').val(0);

           account = $('#account_id').find(":selected").val();
           // get value from timestamp
           dt = $('#timestamp').val();
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
                   updateSharePrice();
               }
           });
       }

       $("#account_id").change(function() {
           updateShareValue();
       })

       function updateSharePrice() {
           value = $('#value').val();
           if ($('#type').val() === 'INI') {
               shares = $('#shares').val();
               share_price = value / shares;
               $('#__share_price').val(share_price);
           } else {
               share_price = $('#__share_price').val();
               if (share_price === 0) {
                   $('#shares').val(0);
               } else {
                   $('#shares').val(value / share_price);
               }
           }
       }

       $("#value").change(function() {
           value = $('#value').val();
           updateSharePrice();
       })

       $("#shares").change(function() {
           if ($('#type').val() === 'INI') {
               updateSharePrice();
           }
       });

       $("#type").change(function() {
           value = $('#type').val();
           // make shares editable if INI
              if (value === 'INI') {
                $('#shares').prop('readonly', false);
              } else {
                $('#shares').prop('readonly', true);
              }
       });

       $('#timestamp')
           // .datetimepicker({
           //     format: 'YYYY-MM-DD',
           //         useCurrent: true,
           //         icons: {
           //             up: "icon-arrow-up-circle icons font-2xl",
           //             down: "icon-arrow-down-circle icons font-2xl"
           //         },
           //         sideBySide: true
           // })
           .on('dp.change', function(e){
           if (e.date) {
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
    {!! Form::label('shares', 'Shares:') !!}
    {!! Form::number('shares', null, ['class' => 'form-control', 'readonly' => 'true']) !!}
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

<!-- Scheduled Job Id Field -->
{{--<div class="form-group col-sm-6">--}}
{{--    {!! Form::label('scheduled_job_id', 'Scheduled Job Id:') !!}--}}
{{--    {!! Form::number('scheduled_job_id', null, ['class' => 'form-control']) !!}--}}
{{--</div>--}}

<!-- Submit Field -->
<div class="form-group col-sm-12">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('transactions.index') }}" class="btn btn-secondary">Cancel</a>
</div>
