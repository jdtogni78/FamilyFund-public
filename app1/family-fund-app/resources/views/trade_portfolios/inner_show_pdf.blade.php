@php
    $tradePortfolioItems = $tradePortfolio['items'];

    // Group items by their group field
    $groupedItems = collect($tradePortfolioItems)->groupBy('group');

    // Get cash asset's group for proper display
    $cashAsset = \App\Models\AssetExt::getCashAsset();
    $cashGroup = $cashAsset->display_group ?? 'Stability';
    $cashPct = $tradePortfolio->cash_target * 100;

    // Group colors
    $groupColors = [
        'Growth' => ['bg' => '#dcfce7', 'border' => '#16a34a', 'text' => '#15803d'],
        'Stability' => ['bg' => '#dbeafe', 'border' => '#2563eb', 'text' => '#1d4ed8'],
        'Crypto' => ['bg' => '#fef3c7', 'border' => '#d97706', 'text' => '#b45309'],
        'default' => ['bg' => '#f1f5f9', 'border' => '#64748b', 'text' => '#475569'],
    ];

    $groupCount = count($tradePortfolio->groups);
@endphp

{{-- Compact Header --}}
<table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 12px;">
    <tr>
        <td class="section-header-cell" style="border-radius: 6px 6px 0 0;">
            <img src="{{ public_path('images/icons/columns.svg') }}" style="width: 14px; height: 14px; vertical-align: text-bottom; margin-right: 6px;">
            <span class="section-header-text">
                Trade Portfolio {{ $tradePortfolio->id }}
            </span>
            <span style="background: #14b8a6; color: #ffffff; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px;">
                {{ $api['portfolio']['source'] ?? 'N/A' }}
            </span>
        </td>
    </tr>
    <tr>
        <td style="padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-top: none;">
            <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="18%" style="text-align: center; border-right: 1px solid #e2e8f0; padding: 4px;">
                        <div style="font-size: 10px; color: #64748b; text-transform: uppercase;">Period</div>
                        <div style="font-size: 11px; font-weight: 600;">{{ \Carbon\Carbon::parse($tradePortfolio->start_dt)->format('M j, Y') }} - {{ \Carbon\Carbon::parse($tradePortfolio->end_dt)->format('M j, Y') }}</div>
                    </td>
                    <td width="10%" style="text-align: center; border-right: 1px solid #e2e8f0; padding: 4px;">
                        <div style="font-size: 10px; color: #64748b; text-transform: uppercase;">Cash</div>
                        <div style="font-size: 12px; font-weight: 700; color: #0d9488;">{{ $tradePortfolio->cash_target * 100 }}%</div>
                    </td>
                    <td width="10%" style="text-align: center; border-right: 1px solid #e2e8f0; padding: 4px;">
                        <div style="font-size: 10px; color: #64748b; text-transform: uppercase;">Reserve</div>
                        <div style="font-size: 12px; font-weight: 600;">{{ $tradePortfolio->cash_reserve_target * 100 }}%</div>
                    </td>
                    <td width="12%" style="text-align: center; border-right: 1px solid #e2e8f0; padding: 4px;">
                        <div style="font-size: 10px; color: #64748b; text-transform: uppercase;">Min Order</div>
                        <div style="font-size: 12px; font-weight: 600;">${{ number_format($tradePortfolio->minimum_order, 0) }}</div>
                    </td>
                    <td width="10%" style="text-align: center; border-right: 1px solid #e2e8f0; padding: 4px;">
                        <div style="font-size: 10px; color: #64748b; text-transform: uppercase;">Max Order</div>
                        <div style="font-size: 12px; font-weight: 600;">{{ $tradePortfolio->max_single_order * 100 }}%</div>
                    </td>
                    <td width="10%" style="text-align: center; border-right: 1px solid #e2e8f0; padding: 4px;">
                        <div style="font-size: 10px; color: #64748b; text-transform: uppercase;">Rebalance</div>
                        <div style="font-size: 12px; font-weight: 600;">{{ $tradePortfolio->rebalance_period }}d</div>
                    </td>
                    <td width="14%" style="text-align: center; border-right: 1px solid #e2e8f0; padding: 4px;">
                        <div style="font-size: 10px; color: #64748b; text-transform: uppercase;">Account</div>
                        <div style="font-size: 12px; font-weight: 600;">{{ $tradePortfolio->account_name }}</div>
                    </td>
                    <td width="10%" style="text-align: center; padding: 4px;">
                        <div style="font-size: 10px; color: #64748b; text-transform: uppercase;">Total</div>
                        @if($tradePortfolio->total_shares == 100)
                            <div style="font-size: 12px; font-weight: 700; color: #16a34a;">&#10003; {{ $tradePortfolio->total_shares }}%</div>
                        @else
                            <div style="font-size: 12px; font-weight: 700; color: #dc2626;">&#10007; {{ $tradePortfolio->total_shares }}%</div>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- Portfolio Allocation Header --}}
<div style="font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 10px; padding-left: 4px;">
    Portfolio Allocation
</div>

{{-- Groups Side by Side --}}
<table width="100%" cellspacing="8" cellpadding="0" style="margin-bottom: 16px;">
    <tr>
        @foreach($tradePortfolio->groups as $group => $targetPct)
            @php
                $colors = $groupColors[$group] ?? $groupColors['default'];
                $items = $groupedItems->get($group, collect());
                $actualPct = $items->sum('target_share') * 100;
                // Add cash to the group it belongs to
                $groupHasCash = ($group === $cashGroup && $cashPct > 0);
                if ($groupHasCash) {
                    $actualPct += $cashPct;
                }
                $isMatch = abs($actualPct - $targetPct) < 0.01;
                $colWidth = $groupCount <= 2 ? '50%' : ($groupCount == 3 ? '33%' : '25%');
            @endphp
            <td width="{{ $colWidth }}" valign="top" style="padding: 0;">
                {{-- Group Header --}}
                <table width="100%" cellspacing="0" cellpadding="8" style="background: {{ $colors['bg'] }}; border-left: 4px solid {{ $colors['border'] }};">
                    <tr>
                        <td style="font-weight: 700; color: {{ $colors['text'] }}; font-size: 12px;">{{ $group }}</td>
                        <td style="text-align: right;">
                            <span style="background: {{ $colors['border'] }}; color: #ffffff; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">{{ $targetPct }}%</span>
                            @if($isMatch)
                                <span style="color: #16a34a; font-weight: 700; margin-left: 4px;">&#10003;</span>
                            @else
                                <span style="background: #dc2626; color: #ffffff; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 4px;">{{ number_format($actualPct, 1) }}%</span>
                            @endif
                        </td>
                    </tr>
                </table>

                {{-- Group Items Table --}}
                <table width="100%" cellspacing="0" cellpadding="6" style="border: 1px solid #e2e8f0; border-top: none; font-size: 11px;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="text-align: left; font-weight: 600; color: #475569;">Symbol</th>
                            <th style="text-align: right; font-weight: 600; color: #475569;">Target</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td style="font-weight: 600;">{{ $item->symbol }}</td>
                                <td style="text-align: right;">{{ $item->target_share * 100 }}% <span style="color: #94a3b8; font-size: 9px;">±{{ $item->deviation_trigger * 100 }}%</span></td>
                            </tr>
                        @empty
                            @if(!$groupHasCash)
                            <tr>
                                <td colspan="2" style="text-align: center; color: #94a3b8; padding: 8px;">No items</td>
                            </tr>
                            @endif
                        @endforelse
                        @if($groupHasCash)
                            <tr style="background: #f0f9ff;">
                                <td style="font-weight: 600; color: #0d9488;">CASH</td>
                                <td style="text-align: right;">{{ $cashPct }}%</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </td>
        @endforeach
    </tr>
</table>
