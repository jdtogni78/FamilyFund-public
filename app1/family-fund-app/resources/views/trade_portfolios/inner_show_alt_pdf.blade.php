@php
    $tradePortfolios = $api['tradePortfolios'] ?? collect();
@endphp

@if($tradePortfolios->count() > 0)
@php
    $groupColors = [
        'Growth' => ['bg' => '#dcfce7', 'border' => '#16a34a', 'text' => '#15803d'],
        'Stability' => ['bg' => '#dbeafe', 'border' => '#2563eb', 'text' => '#1d4ed8'],
        'Crypto' => ['bg' => '#fef3c7', 'border' => '#d97706', 'text' => '#b45309'],
        'default' => ['bg' => '#f1f5f9', 'border' => '#64748b', 'text' => '#475569'],
    ];

    $cashAsset = \App\Models\AssetExt::getCashAsset();
    $cashGroup = $cashAsset->display_group ?? 'Stability';

    // Build symbol map for comparison
    $symbolMap = [];
    $allGroups = [];
    $assetGroups = \App\Models\Asset::pluck('display_group', 'name')->toArray();

    foreach($tradePortfolios as $tp) {
        $items = $tp->tradePortfolioItems ?? $tp->items ?? collect();
        foreach($items as $item) {
            $symbol = $item->symbol;
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

    $portfolioCount = $tradePortfolios->count();
    $colWidth = floor(100 / $portfolioCount);
@endphp

{{-- Portfolio Comparison Table --}}
<table width="100%" cellspacing="8" cellpadding="0">
    <tr>
        @foreach($tradePortfolios as $tp)
            @php
                $asOfDate = $asOf ?? $api['asOf'] ?? $api['as_of'] ?? now()->format('Y-m-d');
            @endphp
            <td width="{{ $colWidth }}%" valign="top" style="padding: 0;">
                {{-- Portfolio Card --}}
                <table width="100%" cellspacing="0" cellpadding="0" style="border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
                    {{-- Header --}}
                    <tr>
                        <td style="padding: 8px 12px; background: #1e293b; color: #ffffff;">
                            <strong style="font-size: 12px;">Portfolio {{ $tp->id }}</strong>
                        </td>
                    </tr>

                    {{-- Period & Settings --}}
                    <tr>
                        <td style="padding: 8px 10px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: center;">
                            <div style="font-size: 10px; color: #64748b; margin-bottom: 6px;">
                                {{ \Carbon\Carbon::parse($tp->start_dt)->format('M j, Y') }} - {{ \Carbon\Carbon::parse($tp->end_dt)->format('M j, Y') }}
                            </div>
                            <div style="font-size: 9px;">
                                <span style="background: #2563eb; color: white; padding: 2px 4px; border-radius: 3px; margin-right: 2px;">Cash {{ $tp->cash_target * 100 }}%</span>
                                <span style="background: #64748b; color: white; padding: 2px 4px; border-radius: 3px; margin-right: 2px;">Reserve {{ $tp->cash_reserve_target * 100 }}%</span>
                                <span style="background: #059669; color: white; padding: 2px 4px; border-radius: 3px;">Min ${{ number_format($tp->minimum_order, 0) }}</span>
                            </div>
                        </td>
                    </tr>

                    {{-- Group Totals --}}
                    @php
                        try {
                            $tpGroups = $tp->groups ?? [];
                        } catch (\Error $e) {
                            $tpGroups = [];
                        }
                    @endphp
                    @if(!empty($tpGroups))
                    <tr>
                        <td style="padding: 8px 10px; text-align: center;">
                            @foreach($tpGroups as $group => $targetPct)
                                @php $colors = $groupColors[$group] ?? $groupColors['default']; @endphp
                                <span style="background: {{ $colors['bg'] }}; color: {{ $colors['text'] }}; border: 1px solid {{ $colors['border'] }}; padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; display: inline-block; margin: 2px;">
                                    {{ $group }}: {{ $targetPct }}%
                                </span>
                            @endforeach
                        </td>
                    </tr>
                    @endif

                    {{-- Stocks List --}}
                    <tr>
                        <td style="padding: 0;">
                            <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 10px;">
                                @php $currentGroup = null; @endphp
                                @foreach($symbolMap as $symbol => $data)
                                    @php
                                        $colors = $groupColors[$data['group']] ?? $groupColors['default'];
                                        $portfolioData = $data['portfolios'][$tp->id] ?? null;
                                        $isCash = $data['isCash'] ?? false;
                                    @endphp

                                    {{-- Group separator --}}
                                    @if($currentGroup !== $data['group'])
                                        @php $currentGroup = $data['group']; @endphp
                                        <tr>
                                            <td colspan="2" style="background: {{ $colors['bg'] }}; padding: 4px 10px; border-left: 3px solid {{ $colors['border'] }};">
                                                <strong style="color: {{ $colors['text'] }}; font-size: 9px;">{{ $data['group'] }}</strong>
                                            </td>
                                        </tr>
                                    @endif

                                    <tr @if($isCash) style="background: #f0f9ff;" @endif>
                                        <td style="padding: 4px 10px; border-bottom: 1px solid #f1f5f9;">
                                            @if($isCash)
                                                <span style="color: #2563eb;">&#9679;</span>
                                            @endif
                                            {{ $symbol }}
                                        </td>
                                        <td style="padding: 4px 10px; border-bottom: 1px solid #f1f5f9; text-align: right;">
                                            @if($portfolioData)
                                                <strong>{{ $portfolioData['target'] }}%</strong>
                                                @if($portfolioData['deviation'])
                                                    <span style="color: #94a3b8; font-size: 8px;">±{{ $portfolioData['deviation'] }}%</span>
                                                @endif
                                            @else
                                                <span style="color: #94a3b8;">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    @php
                        try {
                            $totalShares = $tp->total_shares ?? 0;
                        } catch (\Error $e) {
                            $totalShares = 0;
                        }
                    @endphp
                    <tr>
                        <td style="padding: 8px 10px; text-align: center; background: {{ $totalShares == 100 ? '#dcfce7' : '#fee2e2' }};">
                            @if($totalShares == 100)
                                <span style="color: #16a34a; font-weight: 700; font-size: 11px;">&#10003; Total: {{ $totalShares }}%</span>
                            @else
                                <span style="color: #dc2626; font-weight: 700; font-size: 11px;">&#10007; Total: {{ $totalShares }}%</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        @endforeach
    </tr>
</table>
@endif
