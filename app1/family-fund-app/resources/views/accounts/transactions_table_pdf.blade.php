@if(isset($api['transactions']) && count($api['transactions']) > 0)
<table style="width: 100%; font-size: 11px;">
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Status</th>
            <th class="col-number">Value</th>
            <th class="col-number">Share Price</th>
            <th class="col-number">Shares</th>
            <th class="col-number">Current Value</th>
            <th class="col-number">Balance</th>
        </tr>
    </thead>
    <tbody>
    @foreach($api['transactions'] as $trans)
        <tr>
            <td>{{ \Carbon\Carbon::parse($trans->timestamp)->format('Y-m-d') }}</td>
            <td>
                @php
                    $typeClass = match($trans->type_string()) {
                        'Deposit' => 'badge-success',
                        'Withdrawal' => 'badge-danger',
                        'Transfer In' => 'badge-info',
                        'Transfer Out' => 'badge-warning',
                        default => 'badge-secondary'
                    };
                @endphp
                <span class="badge {{ $typeClass }}">{{ $trans->type_string() }}</span>
            </td>
            <td>{{ $trans->status_string() }}</td>
            <td class="col-number">${{ number_format($trans->value ?? 0, 2) }}</td>
            <td class="col-number">${{ number_format($trans->share_price ?? 0, 4) }}</td>
            <td class="col-number">{{ number_format($trans->shares ?? 0, 2) }}</td>
            <td class="col-number {{ ($trans->current_performance ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                ${{ number_format($trans->current_value ?? 0, 2) }}
                <small>({{ number_format($trans->current_performance ?? 0, 1) }}%)</small>
            </td>
            <td class="col-number">{{ number_format($trans->balance?->shares ?? 0, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="text-muted" style="padding: 20px; text-align: center; background: #f8fafc; border-radius: 6px;">
    No transactions found.
</div>
@endif
