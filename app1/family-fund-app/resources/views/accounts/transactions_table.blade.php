@php
    use App\Models\TransactionExt;

    $typeClasses = [
        TransactionExt::TYPE_PURCHASE => ['class' => 'badge-tx-purchase', 'icon' => 'fa-arrow-up'],
        TransactionExt::TYPE_INITIAL => ['class' => 'badge-tx-initial', 'icon' => 'fa-star'],
        TransactionExt::TYPE_SALE => ['class' => 'badge-tx-sale', 'icon' => 'fa-arrow-down'],
        TransactionExt::TYPE_MATCHING => ['class' => 'badge-tx-matching', 'icon' => 'fa-gift'],
        TransactionExt::TYPE_BORROW => ['class' => 'badge-tx-borrow', 'icon' => 'fa-hand-holding-usd'],
        TransactionExt::TYPE_REPAY => ['class' => 'badge-tx-repay', 'icon' => 'fa-undo'],
    ];

    $statusClasses = [
        TransactionExt::STATUS_PENDING => ['class' => 'badge-status-pending'],
        TransactionExt::STATUS_CLEARED => ['class' => 'badge-status-cleared'],
        TransactionExt::STATUS_SCHEDULED => ['class' => 'badge-status-scheduled'],
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
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($api['transactions'] as $trans)
            @php
                $typeStyle = $typeClasses[$trans->type] ?? ['class' => 'badge-gray', 'icon' => 'fa-circle'];
                $statusStyle = $statusClasses[$trans->status] ?? ['class' => 'badge-gray'];
                $perfValue = floatval($trans->current_performance ?? 0);
            @endphp
            <tr>
                <td><small class="text-muted">{{ $trans->id }}</small></td>
                <td>{{ \Carbon\Carbon::parse($trans->timestamp)->format('Y-m-d') }}</td>
                <td>
                    <span class="{{ $typeStyle['class'] }} d-inline-flex align-items-center">
                        <i class="fa {{ $typeStyle['icon'] }} me-1" style="font-size: 0.7em;"></i>
                        {{ $trans->type_string() }}
                    </span>
                </td>
                <td>
                    <span class="{{ $statusStyle['class'] }}">
                        {{ $trans->status_string() }}
                    </span>
                </td>
                <td class="text-end">
                    <span class="{{ $trans->value >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }} font-medium">
                        @if($trans->value >= 0)+@endif${{ number_format($trans->value, 2) }}
                    </span>
                </td>
                <td class="text-end">${{ number_format($trans->share_price, 2) }}</td>
                <td class="text-end">
                    <span class="{{ $trans->shares >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                        @if($trans->shares >= 0)+@endif{{ number_format($trans->shares, 4) }}
                    </span>
                </td>
                <td class="text-end">
                    ${{ number_format($trans->current_value, 2) }}
                    <small class="{{ $perfValue >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
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
                <td>
                    <a href="{{ route('transactions.show', $trans->id) }}" class="btn btn-sm btn-ghost-info" title="View">
                        <i class="fa fa-eye"></i>
                    </a>
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
