@if(isset($api['balances']) && count($api['balances']) > 0)
<table style="width: 100%;">
    <thead>
        <tr>
            <th>Account</th>
            <th>User</th>
            <th class="col-number">Shares</th>
            <th class="col-number">Value</th>
            <th class="col-number">%</th>
        </tr>
    </thead>
    <tbody>
    @php $totalShares = 0; $totalValue = 0; @endphp
    @foreach($api['balances'] as $balance)
        @php
            $totalShares += $balance['shares'] ?? 0;
            $balanceValue = $balance['market_value'] ?? $balance['value'] ?? 0;
            $totalValue += $balanceValue;
        @endphp
        <tr>
            <td><strong>{{ $balance['nickname'] }}</strong></td>
            <td>{{ $balance['user']['name'] ?? $balance['user_name'] ?? $balance['nickname'] ?? '-' }}</td>
            <td class="col-number">{{ number_format($balance['shares'] ?? 0, 2) }}</td>
            <td class="col-number">${{ number_format($balanceValue, 2) }}</td>
            <td class="col-number">
                @if($api['summary']['shares'] > 0)
                    {{ number_format((($balance['shares'] ?? 0) / $api['summary']['shares']) * 100, 2) }}%
                @else
                    -
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr style="background: #ccfbf1; font-weight: 600; color: #134e4a;">
            <td colspan="2" style="padding: 10px;">TOTAL ALLOCATED</td>
            <td class="col-number" style="padding: 10px;">{{ number_format($totalShares, 2) }}</td>
            <td class="col-number" style="padding: 10px;">${{ number_format($totalValue, 2) }}</td>
            <td class="col-number" style="padding: 10px;">{{ number_format($api['summary']['allocated_shares_percent'], 2) }}%</td>
        </tr>
        @php
            $unallocatedShares = $api['summary']['unallocated_shares'] ?? ($api['summary']['shares'] - $totalShares);
            $unallocatedValue = ($unallocatedShares / $api['summary']['shares']) * $api['summary']['value'];
            $unallocatedPct = $api['summary']['unallocated_shares_percent'] ?? (100 - $api['summary']['allocated_shares_percent']);
        @endphp
        <tr style="background: #fef3c7; font-weight: 600; color: #92400e;">
            <td colspan="2" style="padding: 10px;"><span style="margin-right: 4px;">&#9888;</span> UNALLOCATED</td>
            <td class="col-number" style="padding: 10px;">{{ number_format($unallocatedShares, 2) }}</td>
            <td class="col-number" style="padding: 10px;">${{ number_format($unallocatedValue, 2) }}</td>
            <td class="col-number" style="padding: 10px;">{{ number_format($unallocatedPct, 2) }}%</td>
        </tr>
        <tr style="background: #134e4a; color: #ffffff; font-weight: 700;">
            <td colspan="2" style="padding: 10px;">TOTAL</td>
            <td class="col-number" style="padding: 10px;">{{ number_format($api['summary']['shares'], 2) }}</td>
            <td class="col-number" style="padding: 10px;">${{ number_format($api['summary']['value'], 2) }}</td>
            <td class="col-number" style="padding: 10px;">100.00%</td>
        </tr>
    </tfoot>
</table>
@else
<div class="text-muted" style="padding: 20px; text-align: center; background: #f8fafc; border-radius: 6px;">
    No account data available.
</div>
@endif
