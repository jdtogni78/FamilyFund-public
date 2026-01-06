@if(isset($api['transactions']) && count($api['transactions']) > 0)
<table style="width: 100%; font-size: 11px;">
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Status</th>
            <th class="col-number">Original Value</th>
            <th class="col-number">Share Price</th>
            <th class="col-number">Shares</th>
            <th class="col-number">Current Value</th>
            <th class="col-number">Balance</th>
        </tr>
    </thead>
    <tbody>
    @foreach($api['transactions'] as $trans)
        @php
            $typeStr = $trans->type_string();
            $statusStr = $trans->status_string();

            // Type colors: Purchase green, Matching purple, Withdrawal red
            $typeColor = match($typeStr) {
                'Purchase', 'Deposit' => '#16a34a',
                'Withdrawal', 'Sell' => '#dc2626',
                'Transfer In' => '#0ea5e9',
                'Transfer Out' => '#f97316',
                'Matching', 'Match' => '#9333ea',
                default => '#64748b'
            };
            $typeBg = match($typeStr) {
                'Purchase', 'Deposit' => '#dcfce7',
                'Withdrawal', 'Sell' => '#fef2f2',
                'Transfer In' => '#e0f2fe',
                'Transfer Out' => '#fff7ed',
                'Matching', 'Match' => '#faf5ff',
                default => '#f1f5f9'
            };

            // Status colors
            $statusColor = match($statusStr) {
                'Cleared', 'Completed', 'Complete' => '#16a34a',
                'Pending' => '#d97706',
                'Cancelled', 'Canceled' => '#dc2626',
                default => '#64748b'
            };
            $statusBg = match($statusStr) {
                'Cleared', 'Completed', 'Complete' => '#dcfce7',
                'Pending' => '#fef3c7',
                'Cancelled', 'Canceled' => '#fef2f2',
                default => '#f1f5f9'
            };
        @endphp
        <tr>
            <td>{{ \Carbon\Carbon::parse($trans->timestamp)->format('Y-m-d') }}</td>
            <td>
                <span style="background: {{ $typeBg }}; color: {{ $typeColor }}; padding: 2px 6px; border-radius: 3px; font-weight: 600; font-size: 10px;">
                    {{ $typeStr }}
                </span>
            </td>
            <td>
                <span style="background: {{ $statusBg }}; color: {{ $statusColor }}; padding: 2px 6px; border-radius: 3px; font-weight: 600; font-size: 10px;">
                    {{ $statusStr }}
                </span>
            </td>
            <td class="col-number">${{ number_format($trans->value ?? 0, 2) }}</td>
            <td class="col-number">${{ number_format($trans->share_price ?? 0, 4) }}</td>
            <td class="col-number">{{ number_format($trans->shares ?? 0, 2) }}</td>
            @php
                $perfColor = ($trans->current_performance ?? 0) >= 0 ? '#16a34a' : '#dc2626';
            @endphp
            <td class="col-number" style="color: {{ $perfColor }};">
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
