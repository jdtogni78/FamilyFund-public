<table class="detail-grid">
    <tr>
        <td>Account Name</td>
        <td>{{ $account->nickname }}</td>
    </tr>
    <tr>
        <td>Fund</td>
        <td>{{ $account->fund->name }}</td>
    </tr>
    <tr>
        <td>Account Holder</td>
        <td>{{ $account->user->name }}</td>
    </tr>
    <tr>
        <td>Email</td>
        <td>{{ $account->email_cc }}</td>
    </tr>
    @isset($api['balances'][0])
        <tr>
            <td>Shares</td>
            <td>{{ number_format($account->balances['OWN']->shares ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td>Market Value</td>
            <td><strong class="text-primary">${{ number_format($account->balances['OWN']->market_value ?? 0, 2) }}</strong></td>
        </tr>
    @endisset
    <tr>
        <td>Report Date</td>
        <td>{{ $api['as_of'] }}</td>
    </tr>
</table>
