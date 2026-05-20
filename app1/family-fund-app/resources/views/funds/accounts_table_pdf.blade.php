@if(isset($api['balances']) && count($api['balances']) > 0)
<table style="width: 100%;">
    <thead>
        <tr>
            <th>Account</th>
            <th>User</th>
            <th class="col-number">Allocated Shares</th>
            <th class="col-number">Loaned Shares</th>
            <th class="col-number">Value</th>
            <th class="col-number">Loaned Value</th>
            <th class="col-number">%</th>
        </tr>
    </thead>
    <tbody>
    @php $totalShares = 0; $totalValue = 0; $totalBorrowedShares = 0; $totalBorrowedValue = 0; @endphp
    @foreach($api['balances'] as $balance)
        @php
            $totalShares += $balance['shares'] ?? 0;
            $balanceValue = $balance['market_value'] ?? $balance['value'] ?? 0;
            $borrowedShares = $balance['borrowed_shares'] ?? 0;
            $borrowedValue = $balance['borrowed_value'] ?? 0;
            $totalValue += $balanceValue;
            $totalBorrowedShares += $borrowedShares;
            $totalBorrowedValue += $borrowedValue;
        @endphp
        <tr>
            <td><strong>{{ $balance['nickname'] }}</strong></td>
            <td>{{ $balance['user']['name'] ?? $balance['user_name'] ?? $balance['nickname'] ?? '-' }}</td>
            <td class="col-number">{{ number_format($balance['shares'] ?? 0, 2) }}</td>
            <td class="col-number">{{ $borrowedShares > 0 ? number_format($borrowedShares, 2) : '-' }}</td>
            <td class="col-number">${{ number_format($balanceValue, 2) }}</td>
            <td class="col-number">{{ $borrowedValue > 0 ? '$' . number_format($borrowedValue, 2) : '-' }}</td>
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
            <td class="col-number" style="padding: 10px;">{{ $totalBorrowedShares > 0 ? number_format($totalBorrowedShares, 2) : '-' }}</td>
            <td class="col-number" style="padding: 10px;">${{ number_format($totalValue, 2) }}</td>
            <td class="col-number" style="padding: 10px;">{{ $totalBorrowedValue > 0 ? '$' . number_format($totalBorrowedValue, 2) : '-' }}</td>
            <td class="col-number" style="padding: 10px;">{{ number_format($api['summary']['allocated_shares_percent'], 2) }}%</td>
        </tr>
        @php
            $loanedShares = $api['summary']['borrowed_shares'] ?? $totalBorrowedShares;
            $loanedValue = $api['summary']['borrowed_value'] ?? $totalBorrowedValue;
            $availableUnallocatedShares = $api['summary']['available_unallocated_shares'] ?? 0;
            $availableUnallocatedValue = $api['summary']['available_unallocated_value'] ?? 0;
            $loanedPct = $api['summary']['borrowed_shares_percent'] ?? (($api['summary']['shares'] ?? 0) > 0 ? ($loanedShares / $api['summary']['shares']) * 100 : 0);
            $availableUnallocatedPct = $api['summary']['available_unallocated_shares_percent'] ?? (($api['summary']['shares'] ?? 0) > 0 ? ($availableUnallocatedShares / $api['summary']['shares']) * 100 : 0);
        @endphp
        @if($loanedShares > 0)
        <tr style="background: #fef3c7; font-weight: 600; color: #92400e;">
            <td colspan="2" style="padding: 10px;">LOANED FROM UNALLOCATED</td>
            <td class="col-number" style="padding: 10px;">-</td>
            <td class="col-number" style="padding: 10px;">{{ number_format($loanedShares, 2) }}</td>
            <td class="col-number" style="padding: 10px;">-</td>
            <td class="col-number" style="padding: 10px;">${{ number_format($loanedValue, 2) }}</td>
            <td class="col-number" style="padding: 10px;">{{ number_format($loanedPct, 2) }}%</td>
        </tr>
        @endif
        <tr style="background: #fef3c7; font-weight: 600; color: #92400e;">
            <td colspan="2" style="padding: 10px;"><span style="margin-right: 4px;">&#9888;</span> AVAILABLE UNALLOCATED</td>
            <td class="col-number" style="padding: 10px;">{{ number_format($availableUnallocatedShares, 2) }}</td>
            <td class="col-number" style="padding: 10px;">-</td>
            <td class="col-number" style="padding: 10px;">${{ number_format($availableUnallocatedValue, 2) }}</td>
            <td class="col-number" style="padding: 10px;">-</td>
            <td class="col-number" style="padding: 10px;">{{ number_format($availableUnallocatedPct, 2) }}%</td>
        </tr>
        <tr style="background: #134e4a; color: #ffffff; font-weight: 700;">
            <td colspan="2" style="padding: 10px;">TOTAL</td>
            <td class="col-number" style="padding: 10px;">{{ number_format($totalShares + $availableUnallocatedShares, 2) }}</td>
            <td class="col-number" style="padding: 10px;">{{ $loanedShares > 0 ? number_format($loanedShares, 2) : '-' }}</td>
            <td class="col-number" style="padding: 10px;">${{ number_format($totalValue + $availableUnallocatedValue, 2) }}</td>
            <td class="col-number" style="padding: 10px;">{{ $loanedValue > 0 ? '$' . number_format($loanedValue, 2) : '-' }}</td>
            <td class="col-number" style="padding: 10px;">100.00%</td>
        </tr>
    </tfoot>
</table>
@else
<div class="text-muted" style="padding: 20px; text-align: center; background: #f8fafc; border-radius: 6px;">
    No account data available.
</div>
@endif
