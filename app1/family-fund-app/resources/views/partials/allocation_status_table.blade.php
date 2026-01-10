{{--
    Allocation Status Table - Shared Partial

    Shows current allocation percentages vs targets with OK/Under/Over status badges.

    Usage:
    @include('partials.allocation_status_table', [
        'allocationStatus' => [
            'as_of_date' => '2026-01-10',
            'total_value' => 1000000,
            'symbols' => [
                ['symbol' => 'VTI', 'type' => 'ETF', 'target_pct' => 25.0,
                 'deviation_pct' => 5.0, 'min_pct' => 20.0, 'max_pct' => 30.0,
                 'current_pct' => 27.5, 'current_value' => 275000, 'status' => 'ok'],
            ]
        ]
    ])
--}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white;">
        <strong>
            <i class="fa fa-tasks mr-2"></i>Current Allocation Status
            <span class="text-muted ml-2" style="color: rgba(255,255,255,0.7) !important;">
                (as of {{ $allocationStatus['as_of_date'] ?? 'N/A' }})
            </span>
        </strong>
        <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseAllocationStatus"
           role="button" aria-expanded="true" aria-controls="collapseAllocationStatus">
            <i class="fa fa-chevron-down"></i>
        </a>
    </div>
    <div class="collapse show" id="collapseAllocationStatus">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Symbol</th>
                            <th>Type</th>
                            <th class="text-right">Target %</th>
                            <th class="text-right">Deviation</th>
                            <th class="text-right">Min %</th>
                            <th class="text-right">Max %</th>
                            <th class="text-right">Current %</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($allocationStatus['symbols'] ?? []) as $item)
                            <tr>
                                <td><strong>{{ $item['symbol'] }}</strong></td>
                                <td class="text-muted">{{ $item['type'] ?? '-' }}</td>
                                <td class="text-right">{{ number_format($item['target_pct'], 1) }}%</td>
                                <td class="text-right text-muted">± {{ number_format($item['deviation_pct'] ?? 0, 1) }}%</td>
                                <td class="text-right text-muted">{{ number_format($item['min_pct'], 1) }}%</td>
                                <td class="text-right text-muted">{{ number_format($item['max_pct'], 1) }}%</td>
                                <td class="text-right {{ ($item['status'] === 'ok') ? 'text-success' : 'text-danger font-weight-bold' }}">
                                    {{ number_format($item['current_pct'], 2) }}%
                                </td>
                                <td class="text-center">
                                    @if($item['status'] === 'ok')
                                        <span class="badge badge-success">OK</span>
                                    @elseif($item['status'] === 'under')
                                        <span class="badge badge-danger">Under</span>
                                    @else
                                        <span class="badge badge-warning">Over</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">
                                    No allocation data available
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
