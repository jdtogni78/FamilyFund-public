@php
    use App\Models\TransactionExt;

    $typeColors = [
        TransactionExt::TYPE_PURCHASE => ['bg' => '#dcfce7', 'text' => '#16a34a', 'icon' => 'fa-arrow-down'],
        TransactionExt::TYPE_INITIAL => ['bg' => '#dbeafe', 'text' => '#2563eb', 'icon' => 'fa-star'],
        TransactionExt::TYPE_SALE => ['bg' => '#fee2e2', 'text' => '#dc2626', 'icon' => 'fa-arrow-up'],
        TransactionExt::TYPE_MATCHING => ['bg' => '#f3e8ff', 'text' => '#9333ea', 'icon' => 'fa-gift'],
        TransactionExt::TYPE_BORROW => ['bg' => '#fef3c7', 'text' => '#d97706', 'icon' => 'fa-hand-holding-usd'],
        TransactionExt::TYPE_REPAY => ['bg' => '#fed7aa', 'text' => '#ea580c', 'icon' => 'fa-undo'],
    ];

    $statusColors = [
        TransactionExt::STATUS_PENDING => ['bg' => '#fef9c3', 'text' => '#ca8a04', 'label' => 'Pending'],
        TransactionExt::STATUS_CLEARED => ['bg' => '#dcfce7', 'text' => '#16a34a', 'label' => 'Cleared'],
        TransactionExt::STATUS_SCHEDULED => ['bg' => '#e0e7ff', 'text' => '#4f46e5', 'label' => 'Scheduled'],
    ];
@endphp

<div class="table-responsive-sm">
    <table class="table table-hover" id="transactions-table">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Type</th>
                <th>Status</th>
                <th class="text-end">Value</th>
                <th class="text-end">Share Price</th>
                <th class="text-end">Shares</th>
                <th class="text-end">Current Value</th>
                <th class="text-end">Balance</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
        @foreach($api['transactions'] as $trans)
            @php
                $typeStyle = $typeColors[$trans->type] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'icon' => 'fa-circle'];
                $statusStyle = $statusColors[$trans->status] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'label' => $trans->status];
                $perfValue = floatval($trans->current_performance ?? 0);
                $perfColor = $perfValue >= 0 ? '#16a34a' : '#dc2626';
                $valueColor = $trans->value >= 0 ? '#16a34a' : '#dc2626';
            @endphp
            <tr>
                <td><small class="text-muted">{{ $trans->id }}</small></td>
                <td>{{ \Carbon\Carbon::parse($trans->timestamp)->format('Y-m-d') }}</td>
                <td>
                    <span class="badge d-inline-flex align-items-center" style="background-color: {{ $typeStyle['bg'] }}; color: {{ $typeStyle['text'] }};">
                        <i class="fa {{ $typeStyle['icon'] }} me-1" style="font-size: 0.7em;"></i>
                        {{ $trans->type_string() }}
                    </span>
                </td>
                <td>
                    <span class="badge" style="background-color: {{ $statusStyle['bg'] }}; color: {{ $statusStyle['text'] }};">
                        {{ $trans->status_string() }}
                    </span>
                </td>
                <td class="text-end">
                    <span style="color: {{ $valueColor }}; font-weight: 500;">
                        @if($trans->value >= 0)+@endif${{ number_format($trans->value, 2) }}
                    </span>
                </td>
                <td class="text-end">${{ number_format($trans->share_price, 2) }}</td>
                <td class="text-end">
                    <span style="color: {{ $trans->shares >= 0 ? '#16a34a' : '#dc2626' }};">
                        @if($trans->shares >= 0)+@endif{{ number_format($trans->shares, 4) }}
                    </span>
                </td>
                <td class="text-end">
                    ${{ number_format($trans->current_value, 2) }}
                    <small style="color: {{ $perfColor }};">
                        (@if($perfValue >= 0)+@endif{{ number_format($perfValue, 1) }}%)
                    </small>
                </td>
                <td class="text-end">{{ number_format($trans->balance?->shares ?? 0, 2) }}</td>
                <td>
                    @isset($trans->reference_transaction)
                        <small class="text-muted">
                            <i class="fa fa-link me-1"></i>Matched #{{ $trans->reference_transaction }}
                        </small>
                    @endisset
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#transactions-table').DataTable({
            order: [[1, 'desc']], // Sort by date descending
            pageLength: 25,
            language: {
                search: "Filter:"
            }
        });
    });
</script>
@endpush
