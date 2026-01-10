@php
    $trans = $transaction ?? null;
    $defaultType = $trans->type ?? 'PUR';
    $defaultStatus = $trans->status ?? 'P';
    $defaultValue = $trans->value ?? '';
    $defaultFlags = $trans->flags ?? null;
    $defaultTimestamp = $trans->timestamp ? \Carbon\Carbon::parse($trans->timestamp)->format('Y-m-d') : '';
    $defaultAccountId = $trans->account_id ?? null;
    $defaultDescr = $trans->descr ?? '';
@endphp

<!-- Type Field -->
<div class="form-group col-sm-6">
<label for="type">Type:</label>
<select name="type" class="form-control" id="type">
    @foreach($api['typeMap'] as $value => $label)
        <option value="{{ $value }}" {{ $defaultType == $value ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
</select>
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
<label for="status">Status:</label>
<select name="status" class="form-control">
    @foreach($api['statusMap'] as $value => $label)
        <option value="{{ $value }}" {{ $defaultStatus == $value ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
</select>
</div>

<!-- Value Field -->
<div class="form-group col-sm-6">
<label for="value">Value:</label>
<input type="number" name="value" class="form-control" step="any" id="value" value="{{ $defaultValue }}">
</div>

<!-- Flags Field -->
<div class="form-group col-sm-6">
<label for="flags">Flags:</label>
<select name="flags" class="form-control">
    @foreach($api['flagsMap'] as $value => $label)
        <option value="{{ $value }}" {{ $defaultFlags == $value ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
</select>
</div>

<!-- Timestamp Field -->
<div class="form-group col-sm-6">
<label for="timestamp">Timestamp:</label>
<input type="date" name="timestamp" value="{{ $defaultTimestamp }}" class="form-control" id="timestamp">
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
                   account_shares = parseFloat(data['data']['account_shares']) || 0;
                   account_value = parseFloat(data['data']['account_value']) || 0;
                   console.log(share_price);

                   $('#__share_price').val(share_price);
                   $('#__account_balance').val('$' + account_value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                   $('#__account_shares_display').text(account_shares.toFixed(4) + ' shares');
                   updateSharePrice();
               }
           });
       }

       $("#account_id").change(function() {
           updateShareValue();
           // Clear balance when account changes until date is selected
           if (!$('#timestamp').val()) {
               $('#__account_balance').val('');
               $('#__account_shares_display').text('');
           }
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

       // Also handle regular change event for HTML5 date input
       $('#timestamp').on('change', function() {
           updateShareValue();
       });

       // On page load, if account and timestamp are prefilled, fetch balance
       $(document).ready(function() {
           if ($('#account_id').val() && $('#timestamp').val()) {
               updateShareValue();
           }
       });

       </script>
@endpush


<!-- Account Id Field -->
<div class="form-group col-sm-6">
<label for="account_id">Account:</label>
<select name="account_id" class="form-control" id="account_id">
    @foreach($api['accountMap'] as $value => $label)
        <option value="{{ $value }}" {{ $defaultAccountId == $value ? 'selected' : '' }}>{{ $label }}</option>
    @endforeach
</select>
</div>

<!-- Account Balance (read-only info) -->
<div class="form-group col-sm-6">
<label>Account Balance:</label>
<div class="input-group">
    <input type="text" id="__account_balance" class="form-control" readonly style="background-color: #e9ecef;">
    <span class="input-group-text" id="__account_shares_display"></span>
</div>
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
<input type="text" name="descr" class="form-control" maxlength="255" value="{{ $defaultDescr }}">
</div>

<!-- Submit Field -->
<div class="form-group col-sm-12">
<button type="submit" class="btn btn-primary">Preview</button>
</div>