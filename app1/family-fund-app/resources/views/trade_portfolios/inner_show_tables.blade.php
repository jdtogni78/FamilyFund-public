@php
    $tradePortfolios = $api['tradePortfolios'];
@endphp

{{-- Trade Portfolios Summary Table --}}
<div class="row mb-4">
<div class="col">
<div class="card">
    <div class="card-header py-2" style="background: #0d9488; color: white;">
        <strong><i class="fa fa-briefcase mr-2"></i>Trade Portfolios Summary</strong>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
            <thead class="bg-slate-50 dark:bg-slate-700">
                <tr>
                    <th>ID</th>
                    <th>Period</th>
                    <th>Account</th>
                    <th class="text-right">Cash</th>
                    <th class="text-right">Reserve</th>
                    <th class="text-right">Min Order</th>
                    <th class="text-right">Max Order</th>
                    <th class="text-right">Rebalance</th>
                    <th class="text-right">Total</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tradePortfolios as $tp)
                    @php
                        $editable = \Carbon\Carbon::parse($tp->end_dt)->isBefore($asOf);
                    @endphp
                    <tr>
                        <td><strong>{{ $tp->id }}</strong></td>
                        <td>{{ \Carbon\Carbon::parse($tp->start_dt)->format('M j, Y') }} - {{ \Carbon\Carbon::parse($tp->end_dt)->format('M j, Y') }}</td>
                        <td>{{ $tp->account_name }}</td>
                        <td class="text-right" style="color: #0d9488;">{{ $tp->cash_target * 100 }}%</td>
                        <td class="text-right">{{ $tp->cash_reserve_target * 100 }}%</td>
                        <td class="text-right">${{ number_format($tp->minimum_order, 0) }}</td>
                        <td class="text-right">{{ $tp->max_single_order * 100 }}%</td>
                        <td class="text-right">{{ $tp->rebalance_period }}d</td>
                        <td class="text-right">
                            @if($tp->total_shares == 100)
                                <span style="color: #16a34a;"><i class="fa fa-check-circle"></i> {{ $tp->total_shares }}%</span>
                            @else
                                <span style="color: #dc2626;"><i class="fa fa-exclamation-circle"></i> {{ $tp->total_shares }}%</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(!$editable)
                                <a href="{{ route('tradePortfoliosItems.createWithParams', ['tradePortfolioId' => $tp->id]) }}" class="btn btn-sm btn-link p-0" title="Add Item"><i class="fa fa-plus text-success"></i></a>
                                <a href="{{ route('tradePortfolios.edit', [$tp->id]) }}" class="btn btn-sm btn-link p-0 ml-1" title="Edit"><i class="fa fa-edit text-primary"></i></a>
                                <a href="{{ route('tradePortfolios.show_diff', [$tp->id]) }}" class="btn btn-sm btn-link p-0 ml-1" title="Compare"><i class="fa fa-random text-secondary"></i></a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>
</div>

{{-- Trade Portfolios Stocks Table --}}
@php
    $groupColors = [
        'Growth' => '#16a34a',
        'Stability' => '#2563eb',
        'Crypto' => '#d97706',
        'default' => '#64748b',
    ];

    $cashAsset = \App\Models\AssetExt::getCashAsset();
    $cashGroup = $cashAsset->display_group ?? 'Stability';

    // Build a map of all symbols with their group and targets per portfolio
    $symbolMap = [];
    foreach($tradePortfolios as $tp) {
        foreach($tp->items as $item) {
            $symbol = $item->symbol;
            if (!isset($symbolMap[$symbol])) {
                $symbolMap[$symbol] = [
                    'group' => $item->group,
                    'portfolios' => [],
                ];
            }
            $symbolMap[$symbol]['portfolios'][$tp->id] = [
                'target' => $item->target_share * 100,
                'deviation' => $item->deviation_trigger * 100,
                'item_id' => $item->id,
            ];
        }
        // Add cash
        if ($tp->cash_target > 0) {
            if (!isset($symbolMap['CASH'])) {
                $symbolMap['CASH'] = [
                    'group' => $cashGroup,
                    'portfolios' => [],
                    'isCash' => true,
                ];
            }
            $symbolMap['CASH']['portfolios'][$tp->id] = [
                'target' => $tp->cash_target * 100,
                'deviation' => null,
                'item_id' => null,
            ];
        }
    }

    // Sort by group then symbol
    $groupOrder = ['Growth' => 1, 'Stability' => 2, 'Crypto' => 3];
    uksort($symbolMap, function($a, $b) use ($symbolMap, $groupOrder) {
        $groupA = $groupOrder[$symbolMap[$a]['group']] ?? 99;
        $groupB = $groupOrder[$symbolMap[$b]['group']] ?? 99;
        if ($groupA !== $groupB) return $groupA - $groupB;
        if ($a === 'CASH') return 1;
        if ($b === 'CASH') return -1;
        return strcmp($a, $b);
    });
@endphp

<div class="row mb-4">
<div class="col">
<div class="card">
    <div class="card-header py-2" style="background: #047857; color: white;">
        <strong><i class="fa fa-chart-line mr-2"></i>Trade Portfolios Stocks</strong>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
            <thead class="bg-slate-50 dark:bg-slate-700">
                <tr>
                    <th>Group</th>
                    <th>Symbol</th>
                    @foreach($tradePortfolios as $tp)
                        <th class="text-right">
                            Portfolio {{ $tp->id }}
                            <br><small class="text-muted font-weight-normal">{{ \Carbon\Carbon::parse($tp->start_dt)->format('M j, Y') }} - {{ \Carbon\Carbon::parse($tp->end_dt)->format('M j, Y') }}</small>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($symbolMap as $symbol => $data)
                    @php
                        $color = $groupColors[$data['group']] ?? $groupColors['default'];
                        $isCash = $data['isCash'] ?? false;
                    @endphp
                    <tr @if($isCash) style="background: #f0fdfa;" @endif>
                        <td><span class="badge" style="background: {{ $color }}; color: white;">{{ $data['group'] }}</span></td>
                        <td>
                            @if($isCash)
                                <strong><i class="fa fa-coins mr-1" style="color: #0d9488;"></i>CASH</strong>
                            @else
                                <strong>{{ $symbol }}</strong>
                            @endif
                        </td>
                        @foreach($tradePortfolios as $tp)
                            @php
                                $portfolioData = $data['portfolios'][$tp->id] ?? null;
                                $editable = !\Carbon\Carbon::parse($tp->end_dt)->isBefore($asOf);
                            @endphp
                            <td class="text-right">
                                @if($portfolioData)
                                    {{ $portfolioData['target'] }}%
                                    @if($portfolioData['deviation'])
                                        <span class="text-muted" style="font-size: 0.7rem;">±{{ $portfolioData['deviation'] }}%</span>
                                    @endif
                                    @if($portfolioData['item_id'])
                                        <a href="{{ route('tradePortfolioItems.show', [$portfolioData['item_id']]) }}" class="btn btn-sm btn-link p-0 ml-1" title="View"><i class="fa fa-eye text-success"></i></a>
                                        @if($editable)
                                            <a href="{{ route('tradePortfolioItems.edit', [$portfolioData['item_id']]) }}" class="btn btn-sm btn-link p-0 ml-1" title="Edit"><i class="fa fa-edit text-primary"></i></a>
                                        @endif
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
