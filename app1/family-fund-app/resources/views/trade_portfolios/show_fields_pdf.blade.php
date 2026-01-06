<table class="detail-grid">
    <tr>
        <td>Account Name</td>
        <td>{{ $tradePortfolio->account_name }}</td>
    </tr>
    <tr>
        <td>Portfolio Source</td>
        <td>{{ $api['portfolio']['source'] ?? '-' }}</td>
    </tr>
    <tr>
        <td>Cash Target</td>
        <td>{{ number_format($tradePortfolio->cash_target * 100, 2) }}%</td>
    </tr>
    <tr>
        <td>Cash Reserve Target</td>
        <td>{{ number_format($tradePortfolio->cash_reserve_target * 100, 2) }}%</td>
    </tr>
    <tr>
        <td>Max Single Order</td>
        <td>{{ number_format($tradePortfolio->max_single_order * 100, 2) }}%</td>
    </tr>
    <tr>
        <td>Minimum Order</td>
        <td>${{ number_format($tradePortfolio->minimum_order, 2) }}</td>
    </tr>
    <tr>
        <td>Rebalance Period</td>
        <td>{{ $tradePortfolio->rebalance_period }} days</td>
    </tr>
    <tr>
        <td>Total Shares</td>
        <td class="{{ abs($tradePortfolio->total_shares - 100) < 0.01 ? 'text-success' : 'text-danger' }}">
            <strong>{{ number_format($tradePortfolio->total_shares, 2) }}%</strong>
        </td>
    </tr>
</table>
