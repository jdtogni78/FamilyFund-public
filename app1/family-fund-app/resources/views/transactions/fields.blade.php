<!-- Type Field -->
<div class="form-group col-sm-6">
<label for="type">Type:</label>
<select name="type" class="form-control">
    @foreach($api['typeMap'] as $value => $label)
        <option value="{{ $value }}" {{ 'PUR' == $value ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
</select>
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
<label for="status">Status:</label>
<select name="status" class="form-control">
    @foreach($api['statusMap'] as $value => $label)
        <option value="{{ $value }}" {{ 'P' == $value ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
</select>
</div>

<!-- Value Field -->
<div class="form-group col-sm-6">
<label for="value">Value:</label>
<input type="number" name="value" class="form-control" step="any">
</div>

<!-- Flags Field -->
<div class="form-group col-sm-6">
<label for="flags">Flags:</label>
<select name="flags" class="form-control">
    @foreach($api['flagsMap'] as $value => $label)
        <option value="{{ $value }}" {{ null == $value ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
</select>
</div>

<!-- Timestamp Field -->
<div class="form-group col-sm-6">
<label for="timestamp">Timestamp:</label>
<input type="date" name="timestamp" value="" class="form-control" id="timestamp">
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
<label for="account_id">Account:</label>
<select name="account_id" class="form-control">
    @foreach($api['accountMap'] as $value => $label)
        <option value="{{ $value }}" {{ null == $value ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
</select>
</div>

<!-- CALC Shares -->
<div class="form-group col-sm-6">
<label for="shares">Shares:</label>
<input type="number" name="shares" class="form-control" readonly="true">
</div>

<!-- CALC Share Prices -->
<div class="form-group col-sm-6">
<label for="__share_price">Share Price:</label>
<input type="text" name="__share_price" class="form-control" readonly="true">
</div>

<!-- Descr Field -->
<div class="form-group col-sm-6">
<label for="descr">Descr:</label>
<input type="text" name="descr" class="form-control" maxlength="255">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Preview</button>
</div>