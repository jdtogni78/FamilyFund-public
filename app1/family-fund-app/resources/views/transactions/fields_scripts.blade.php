@push('scripts')
<script type="text/javascript">
    var api = {!! json_encode($api) !!};

    function updateAccountInfo() {
        var account = $('#account_id').find(":selected").val();
        var dt = $('#timestamp').val();

        // Reset if no account or date
        if (!account || !dt) {
            $('#accountInfoPanel').hide();
            $('#accountPlaceholder').show();
            $('#previewCard').hide();
            return;
        }

        var myUrl = '/api/accounts/' + account + '/share_value_as_of/' + dt;

        $.ajax({
            type: 'GET',
            url: myUrl,
            success: function(data) {
                var d = data['data'];
                var share_price = parseFloat(d['share_price']) || 0;
                var account_shares = parseFloat(d['account_shares']) || 0;
                var account_value = parseFloat(d['account_value']) || 0;

                // Update Account/User Info
                var nickname = d['account_nickname'] || '-';
                if (d['account_code']) nickname += ' (' + d['account_code'] + ')';
                $('#__account_nickname').text(nickname);
                var userInfo = d['user_name'] || 'No user';
                if (d['user_email']) userInfo += ' - ' + d['user_email'];
                $('#__user_info').text(userInfo);

                // Update Balance Info
                $('#__account_balance_lg').text('$' + account_value.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#__account_shares_sm').text(account_shares.toFixed(4));
                $('#__share_price_lg').text('$' + share_price.toFixed(4));

                // Show/hide panels
                $('#accountPlaceholder').hide();
                $('#accountInfoPanel').show();

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

        var shares = 0;
        if (type === 'INI') {
            // For initial, shares are manually entered
            shares = parseFloat($('#shares').val()) || 0;
            if (shares !== 0 && value !== 0) {
                share_price = value / shares;
                $('#__share_price').val(share_price);
            }
        } else {
            // Calculate shares from value and price
            if (share_price !== 0) {
                shares = value / share_price;
            }
            $('#shares').val(shares);
        }

        // Update display
        var sharesClass = shares >= 0 ? 'text-success' : 'text-danger';
        $('#__shares_display')
            .text((shares >= 0 ? '+' : '') + shares.toFixed(4))
            .removeClass('text-success text-danger')
            .addClass(sharesClass);

        // Show preview card if we have a value
        if (value !== 0 && share_price !== 0) {
            $('#previewCard').show();
        } else {
            $('#previewCard').hide();
        }
    }

    // Event handlers
    $("#account_id, #timestamp").change(function() {
        updateAccountInfo();
    });

    $("#value").on('input change', function() {
        updateSharesCalculation();
    });

    $("#type").change(function() {
        var value = $(this).val();
        if (value === 'INI') {
            $('#shares').prop('readonly', false);
        } else {
            $('#shares').prop('readonly', true);
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
        if ($('#account_id').val() && $('#timestamp').val()) {
            updateAccountInfo();
        }
        if ($('#value').val()) {
            updateSharesCalculation();
        }
    });
</script>
@endpush
