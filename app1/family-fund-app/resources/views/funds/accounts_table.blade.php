<div class="table-responsive-sm">
    <table class="table table-striped" id="balances-table">
        <thead>
            <tr>
                <th scope="col">Account</th>
                <th scope="col">User</th>
                <th scope="col">Shares</th>
                <th scope="col">%</th>
                <th scope="col">Value</th>
                <th scope="col">Balance Type</th>
            </tr>
        </thead>
        <tbody>
        @php
            $totalShares = $api['summary']['total_shares'] ?? 0;
            $allocatedShares = 0;
            $allocatedValue = 0;
        @endphp
        @foreach($api['balances'] as $bals)
            @php
                $shares = $bals['shares'] ?? 0;
                $value = $bals['market_value'] ?? $bals['value'] ?? 0;
                $percent = $totalShares > 0 ? ($shares / $totalShares) * 100 : 0;
                $allocatedShares += $shares;
                $allocatedValue += $value;
            @endphp
            <tr>
                <th scope="row">
                    <a href="{{ route('accounts.show', [$bals['account_id']]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i>
                    {{ $bals['nickname'] }}</a>
                </th>
                <td>{{ $bals['user']['name'] }}</td>
                <td>{{ number_format($shares, 2) }}</td>
                <td>{{ number_format($percent, 2) }}%</td>
                <td>${{ number_format($value, 2) }}</td>
                <td>{{ $bals['type'] }}</td>
            </tr>
        @endforeach
        @php
            $unallocatedShares = $api['summary']['unallocated_shares'] ?? 0;
            $unallocatedValue = $api['summary']['unallocated_value'] ?? 0;
            $unallocatedPercent = $totalShares > 0 ? ($unallocatedShares / $totalShares) * 100 : 0;
        @endphp
        @if($unallocatedShares > 0)
            <tr style="background-color: #fef3c7;">
                <th scope="row">
                    <i class="fa fa-exclamation-triangle text-warning"></i>
                    Unallocated
                </th>
                <td>-</td>
                <td>{{ number_format($unallocatedShares, 2) }}</td>
                <td>{{ number_format($unallocatedPercent, 2) }}%</td>
                <td>${{ number_format($unallocatedValue, 2) }}</td>
                <td>-</td>
            </tr>
        @endif
        </tbody>
        <tfoot>
            <tr style="background-color: #1e40af; color: #ffffff; font-weight: bold;">
                <th scope="row">Total</th>
                <td></td>
                <td>{{ number_format($totalShares, 2) }}</td>
                <td>100.00%</td>
                <td>${{ number_format(($allocatedValue + $unallocatedValue), 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
