@push('scripts')
<script type="text/javascript">
    var api = {!! json_encode($api) !!};

    function updateAccountInfo() {
        var account = $('#account_id').find(":selected").val();
        var accountName = $('#account_id').find(":selected").text();
        var dt = $('#timestamp').val();

        // Reset if no account or date
        if (!account || !dt) {
            $('#accountInfoPanel').hide();
            $('#accountPlaceholder').show();
            $('#balanceCard').hide();
            $('#previewCard').hide();
            return;
        }

        var myUrl = '/api/accounts/' + account + '/share_value_as_of/' + dt;
        console.log('Fetching: ' + myUrl);

        $.ajax({
            type: 'GET',
            url: myUrl,
            success: function(data) {
                var share_price = parseFloat(data['data']['share_price']) || 0;
                var account_shares = parseFloat(data['data']['account_shares']) || 0;
                var account_value = parseFloat(data['data']['account_value']) || 0;

                // Update Account Info Panel
                $('#__account_name').text(accountName.replace('Select an Account', '-'));
                $('#__account_balance_lg').text('$' + account_value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#__account_shares_sm').text(account_shares.toFixed(4) + ' shares');
                $('#__share_price_lg').text('$' + share_price.toFixed(4));
                $('#__price_date').text('as of ' + dt);

                // Show/hide panels
                $('#accountPlaceholder').hide();
                $('#accountInfoPanel').show();
                $('#balanceCard').show();

                // Update sidebar cards
                $('#__account_balance_display').text('$' + account_value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#__account_shares_display').text(account_shares.toFixed(4) + ' shares');
                $('#__share_price_display').text('$' + share_price.toFixed(4));
                $('#__as_of_date_display').text(dt);

                // Store share price for calculations
                $('#__share_price').val(share_price);

                // Update shares calculation
                updateSharesCalculation();
            },
            error: function(xhr) {
                console.error('Error fetching account info:', xhr);
                $('#accountInfoPanel').hide();
                $('#accountPlaceholder').show();
            }
        });
    }

    function updateSharesCalculation() {
        var value = parseFloat($('#value').val()) || 0;
        var share_price = parseFloat($('#__share_price').val()) || 0;
        var type = $('#type').val();

        if (type === 'INI') {
            // For initial, shares are manually entered
            var shares = parseFloat($('#shares').val()) || 0;
            if (shares !== 0 && value !== 0) {
                share_price = value / shares;
                $('#__share_price').val(share_price);
            }
        } else {
            // Calculate shares from value and price
            var shares = 0;
            if (share_price !== 0) {
                shares = value / share_price;
            }
            $('#shares').val(shares);
        }

        // Update display
        var shares = parseFloat($('#shares').val()) || 0;
        var sharesClass = shares >= 0 ? 'text-success' : 'text-danger';
        var sharesSign = shares >= 0 ? '+' : '';
        $('#__shares_display')
            .text(sharesSign + shares.toFixed(4))
            .removeClass('text-success text-danger')
            .addClass(sharesClass);

        // Show preview card if we have a value
        if (value !== 0) {
            $('#previewCard').show();
        } else {
            $('#previewCard').hide();
        }
    }

    // Event handlers
    $("#account_id").change(function() {
        updateAccountInfo();
    });

    $("#timestamp").change(function() {
        updateAccountInfo();
    });

    $("#value").on('input change', function() {
        updateSharesCalculation();
    });

    $("#type").change(function() {
        var value = $(this).val();
        // Make shares editable if INI (initial value)
        if (value === 'INI') {
            $('#shares').prop('readonly', false).removeClass('bg-light');
        } else {
            $('#shares').prop('readonly', true).addClass('bg-light');
        }
        updateSharesCalculation();
    });

    $("#shares").on('input change', function() {
        if ($('#type').val() === 'INI') {
            updateSharesCalculation();
        }
    });

    // Initialize on page load
    $(document).ready(function() {
        // If account and timestamp are prefilled (e.g., from clone), fetch info
        if ($('#account_id').val() && $('#timestamp').val()) {
            updateAccountInfo();
        }

        // If value is prefilled, update calculations
        if ($('#value').val()) {
            updateSharesCalculation();
        }
    });
</script>
@endpush
