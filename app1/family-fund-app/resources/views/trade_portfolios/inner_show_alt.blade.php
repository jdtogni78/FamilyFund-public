@php
    $tradePortfolios = $api['tradePortfolios'] ?? collect();
@endphp

@if($tradePortfolios->count() > 0)
@php
    // Group color palette (indexed by hash for consistent colors)
    $groupPalette = [
        ['bg' => '#dcfce7', 'border' => '#16a34a', 'text' => '#15803d'], // green
        ['bg' => '#dbeafe', 'border' => '#2563eb', 'text' => '#1d4ed8'], // blue
        ['bg' => '#fef3c7', 'border' => '#d97706', 'text' => '#b45309'], // amber
        ['bg' => '#f3e8ff', 'border' => '#9333ea', 'text' => '#7e22ce'], // purple
        ['bg' => '#ffe4e6', 'border' => '#e11d48', 'text' => '#be123c'], // rose
        ['bg' => '#ccfbf1', 'border' => '#14b8a6', 'text' => '#0f766e'], // teal
    ];
    $getGroupColors = fn($name) => $groupPalette[crc32($name) % 6];

    $cashAsset = \App\Models\AssetExt::getCashAsset();
    $cashGroup = $cashAsset->display_group ?? 'Stability';

    // Build symbol map for comparison
    $symbolMap = [];
    $allGroups = [];
    // Cache asset groups lookup
    $assetGroups = \App\Models\Asset::pluck('display_group', 'name')->toArray();

    foreach($tradePortfolios as $tp) {
        $items = $tp->tradePortfolioItems ?? $tp->items ?? collect();
        foreach($items as $item) {
            $symbol = $item->symbol;
            // Get group from item->group, or lookup from asset's display_group
            $itemGroup = $item->group ?? $assetGroups[$symbol] ?? 'Stability';
            $allGroups[$itemGroup] = true;
            if (!isset($symbolMap[$symbol])) {
                $symbolMap[$symbol] = [
                    'group' => $itemGroup,
                    'portfolios' => [],
                ];
            }
            $symbolMap[$symbol]['portfolios'][$tp->id] = [
                'target' => $item->target_share * 100,
                'deviation' => $item->deviation_trigger * 100,
                'item_id' => $item->id,
            ];
        }
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

{{-- Alternative Design: Side-by-side Comparison --}}
<div class="row mb-4">
    {{-- Portfolio Cards Row --}}
    @foreach($tradePortfolios as $tp)
        @php
            $asOfDate = $asOf ?? $api['asOf'] ?? $api['as_of'] ?? now()->format('Y-m-d');
            $editable = !\Carbon\Carbon::parse($tp->end_dt)->isBefore($asOfDate);
        @endphp
        <div class="col-md-{{ 12 / count($tradePortfolios) }} mb-3">
            <div class="card h-100">
                {{-- Portfolio Header --}}
                <div class="card-header py-2" style="background: #f0fdfa; border-left: 4px solid #14b8a6; border-bottom: 1px solid #99f6e4;">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong style="color: #0f766e;"><i class="fa fa-columns mr-1" style="color: #14b8a6;"></i> Portfolio {{ $tp->id }}</strong>
                        @if($editable)
                            <a href="{{ route('tradePortfolios.edit', [$tp->id]) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="fa fa-edit"></i></a>
                        @endif
                    </div>
                </div>

                {{-- Period & Settings --}}
                <div class="card-body py-2 bg-slate-50 dark:bg-slate-700" style="border-bottom: 1px solid #e2e8f0;">
                    <div class="small text-center">
                        <div class="text-muted">{{ \Carbon\Carbon::parse($tp->start_dt)->format('M j, Y') }} - {{ \Carbon\Carbon::parse($tp->end_dt)->format('M j, Y') }}</div>
                        <div class="mt-1 d-flex flex-wrap justify-content-center" style="gap: 4px;">
                            <span class="badge badge-cyan">Cash {{ $tp->cash_target * 100 }}%</span>
                            <span class="badge badge-gray">Reserve {{ $tp->cash_reserve_target * 100 }}%</span>
                            <span class="badge badge-success">Min ${{ number_format($tp->minimum_order, 0) }}</span>
                            <span class="badge badge-purple">Max {{ $tp->max_single_order * 100 }}%</span>
                            <span class="badge badge-danger">Rebal {{ $tp->rebalance_period }}d</span>
                        </div>
                    </div>
                </div>

                {{-- Group Totals --}}
                @php
                    try {
                        $tpGroups = $tp->groups ?? [];
                    } catch (\Error $e) {
                        $tpGroups = [];
                    }
                @endphp
                @if(!empty($tpGroups))
                <div class="card-body py-2">
                    <div class="d-flex flex-wrap justify-content-center" style="gap: 0.5rem;">
                        @foreach($tpGroups as $group => $targetPct)
                            @php $colors = $getGroupColors($group); @endphp
                            <span class="badge py-2 px-3" style="background: {{ $colors['bg'] }}; color: {{ $colors['text'] }}; border: 1px solid {{ $colors['border'] }};">
                                {{ $group }}: {{ $targetPct }}%
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Stocks List --}}
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" style="font-size: 0.8rem;">
                        <tbody>
                            @php $currentGroup = null; @endphp
                            @foreach($symbolMap as $symbol => $data)
                                @php
                                    $colors = $getGroupColors($data['group']);
                                    $portfolioData = $data['portfolios'][$tp->id] ?? null;
                                    $isCash = $data['isCash'] ?? false;
                                @endphp

                                {{-- Group separator --}}
                                @if($currentGroup !== $data['group'])
                                    @php $currentGroup = $data['group']; @endphp
                                    <tr style="background: {{ $colors['bg'] }};">
                                        <td colspan="2" class="py-1">
                                            <strong style="color: {{ $colors['text'] }}; font-size: 0.75rem;">{{ $data['group'] }}</strong>
                                        </td>
                                    </tr>
                                @endif

                                <tr @if($isCash) style="background: #f0fdfa;" @endif>
                                    <td class="py-1">
                                        @if($isCash)
                                            <i class="fa fa-coins mr-1" style="color: #0d9488;"></i>
                                        @endif
                                        {{ $symbol }}
                                    </td>
                                    <td class="text-right py-1">
                                        @if($portfolioData)
                                            <strong>{{ $portfolioData['target'] }}%</strong>
                                            @if($portfolioData['deviation'])
                                                <span class="text-muted" style="font-size: 0.7rem;">±{{ $portfolioData['deviation'] }}%</span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer --}}
                @php
                    try {
                        $totalShares = $tp->total_shares ?? 0;
                    } catch (\Error $e) {
                        $totalShares = 0;
                    }
                @endphp
                <div class="card-footer py-2 text-center" style="background: {{ $totalShares == 100 ? '#dcfce7' : '#fee2e2' }};">
                    @if($totalShares == 100)
                        <i class="fa fa-check-circle" style="color: #16a34a;"></i>
                        <span style="color: #16a34a;">Total: {{ $totalShares }}%</span>
                    @else
                        <i class="fa fa-exclamation-circle" style="color: #dc2626;"></i>
                        <span style="color: #dc2626;">Total: {{ $totalShares }}%</span>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif
