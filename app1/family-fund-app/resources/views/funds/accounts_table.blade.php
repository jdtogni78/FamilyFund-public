<div class="table-responsive-sm">
    <table class="table table-striped" id="fund-accounts-table">
        <thead>
            <tr>
                <th scope="col">Account</th>
                <th scope="col">User</th>
                <th scope="col">Allocated Shares</th>
                <th scope="col">Loaned Shares</th>
                <th scope="col">%</th>
                <th scope="col">Value</th>
                <th scope="col">Loaned Value</th>
            </tr>
        </thead>
        <tbody>
        @php
            $totalShares = $api['summary']['shares'] ?? $api['summary']['total_shares'] ?? 0;
            $allocatedShares = 0;
            $allocatedValue = 0;
            $borrowedShares = 0;
            $borrowedValue = 0;
        @endphp
        @foreach($api['balances'] as $bals)
            @php
                $shares = $bals['shares'] ?? 0;
                $value = $bals['market_value'] ?? $bals['value'] ?? 0;
                $rowBorrowedShares = $bals['borrowed_shares'] ?? 0;
                $rowBorrowedValue = $bals['borrowed_value'] ?? 0;
                $percent = $totalShares > 0 ? ($shares / $totalShares) * 100 : 0;
                $allocatedShares += $shares;
                $allocatedValue += $value;
                $borrowedShares += $rowBorrowedShares;
                $borrowedValue += $rowBorrowedValue;
            @endphp
            <tr>
                <th scope="row">
                    <a href="{{ route('accounts.show', [$bals['account_id']]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i>
                    {{ $bals['nickname'] }}</a>
                </th>
                <td>{{ $bals['user']['name'] }}</td>
                <td data-order="{{ $shares }}">{{ number_format($shares, 2) }}</td>
                <td data-order="{{ $rowBorrowedShares }}">{{ $rowBorrowedShares > 0 ? number_format($rowBorrowedShares, 2) : '-' }}</td>
                <td data-order="{{ $percent }}">{{ number_format($percent, 2) }}%</td>
                <td data-order="{{ $value }}">${{ number_format($value, 2) }}</td>
                <td data-order="{{ $rowBorrowedValue }}">{{ $rowBorrowedValue > 0 ? '$' . number_format($rowBorrowedValue, 2) : '-' }}</td>
            </tr>
        @endforeach
        @php
            $borrowedShares = $api['summary']['borrowed_shares'] ?? $borrowedShares;
            $borrowedValue = $api['summary']['borrowed_value'] ?? $borrowedValue;
            $availableUnallocatedShares = $api['summary']['available_unallocated_shares'] ?? 0;
            $availableUnallocatedValue = $api['summary']['available_unallocated_value'] ?? 0;
            $borrowedPercent = $totalShares > 0 ? ($borrowedShares / $totalShares) * 100 : 0;
            $availableUnallocatedPercent = $totalShares > 0 ? ($availableUnallocatedShares / $totalShares) * 100 : 0;
            $allocatedPercent = $totalShares > 0 ? ($allocatedShares / $totalShares) * 100 : 0;
        @endphp
        </tbody>
        <tfoot>
            <tr class="table-subtotal-row">
                <th scope="row">Total Allocated</th>
                <td></td>
                <td>{{ number_format($allocatedShares, 2) }}</td>
                <td>{{ $borrowedShares > 0 ? number_format($borrowedShares, 2) : '-' }}</td>
                <td>{{ number_format($allocatedPercent, 2) }}%</td>
                <td>${{ number_format($allocatedValue, 2) }}</td>
                <td>{{ $borrowedValue > 0 ? '$' . number_format($borrowedValue, 2) : '-' }}</td>
            </tr>
            @if($borrowedShares > 0)
            <tr class="table-warning-row">
                <th scope="row">
                    <i class="fa fa-hand-holding-usd text-warning-dark"></i>
                    Loaned from unallocated
                </th>
                <td>-</td>
                <td>-</td>
                <td>{{ number_format($borrowedShares, 2) }}</td>
                <td>{{ number_format($borrowedPercent, 2) }}%</td>
                <td>-</td>
                <td>${{ number_format($borrowedValue, 2) }}</td>
            </tr>
            @endif
            @if($availableUnallocatedShares > 0)
            <tr class="table-warning-row">
                <th scope="row">
                    <i class="fa fa-exclamation-triangle text-warning-dark"></i>
                    Available unallocated
                </th>
                <td>-</td>
                <td>{{ number_format($availableUnallocatedShares, 2) }}</td>
                <td>-</td>
                <td>{{ number_format($availableUnallocatedPercent, 2) }}%</td>
                <td>${{ number_format($availableUnallocatedValue, 2) }}</td>
                <td>-</td>
            </tr>
            @endif
            <tr class="table-total-row">
                <th scope="row">Total</th>
                <td></td>
                <td>{{ number_format($allocatedShares + $availableUnallocatedShares, 2) }}</td>
                <td>{{ $borrowedShares > 0 ? number_format($borrowedShares, 2) : '-' }}</td>
                <td>100.00%</td>
                <td>${{ number_format(($allocatedValue + $availableUnallocatedValue), 2) }}</td>
                <td>{{ $borrowedValue > 0 ? '$' . number_format($borrowedValue, 2) : '-' }}</td>
            </tr>
        </tfoot>
    </table>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#fund-accounts-table').DataTable({
        order: [[5, 'desc']], // Sort by Value descending
        pageLength: 25,
        paging: false,
        searching: false,
        info: false
    });
});
</script>
@endpush
