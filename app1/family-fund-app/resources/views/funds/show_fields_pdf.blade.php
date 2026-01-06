<table class="detail-grid">
    <tr>
        <td>Fund Name</td>
        <td>{{ $api['name'] }}</td>
    </tr>
    <tr>
        <td>Report Date</td>
        <td>{{ $api['as_of'] }}</td>
    </tr>
    <tr>
        <td>Total Shares</td>
        <td>{{ number_format($api['summary']['shares'], 2) }}</td>
    </tr>
    <tr>
        <td>Share Price</td>
        <td>${{ number_format($api['summary']['share_value'], 4) }}</td>
    </tr>
    <tr>
        <td>Allocated Shares</td>
        <td>
            {{ number_format($api['summary']['allocated_shares'], 2) }}
            <span class="text-muted text-sm">({{ number_format($api['summary']['allocated_shares_percent'], 1) }}%)</span>
        </td>
    </tr>
    <tr>
        <td>Unallocated Shares</td>
        <td>
            {{ number_format($api['summary']['unallocated_shares'], 2) }}
            <span class="text-muted text-sm">({{ number_format($api['summary']['unallocated_shares_percent'], 1) }}%)</span>
        </td>
    </tr>
    <tr>
        <td>Total Value</td>
        <td><strong class="text-primary">${{ number_format($api['summary']['value'], 2) }}</strong></td>
    </tr>
</table>
