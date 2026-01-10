@php
    // Get current trade portfolio (the one that includes today's date)
    $currentTP = null;
    $tpTargets = [];
    if (isset($api['tradePortfolios'])) {
        $today = now()->format('Y-m-d');
        foreach ($api['tradePortfolios'] as $tp) {
            if ($tp->start_dt <= $today && ($tp->end_dt >= $today || $tp->end_dt === null)) {
                $currentTP = $tp;
                break;
            }
        }
        // Build lookup map: symbol => [target_share, deviation_trigger]
        if ($currentTP && isset($currentTP->items)) {
            foreach ($currentTP->items as $item) {
                $symbol = is_array($item) ? $item['symbol'] : $item->symbol;
                $tpTargets[$symbol] = [
                    'target' => is_array($item) ? $item['target_share'] : $item->target_share,
                    'deviation' => is_array($item) ? $item['deviation_trigger'] : $item->deviation_trigger,
                ];
            }
        }
    }
@endphp
@if(isset($api['portfolio']['assets']) && count($api['portfolio']['assets']) > 0)
<table style="width: 100%;">
    <thead>
        <tr>
            <th>Asset</th>
            <th>Type</th>
            <th class="col-number">Position</th>
            <th class="col-number">Price</th>
            <th class="col-number">Market Value</th>
            <th class="col-number">%</th>
            <th class="col-number">Target</th>
            <th class="col-number">Band</th>
        </tr>
    </thead>
    <tbody>
    @foreach($api['portfolio']['assets'] as $asset)
        @php
            $symbol = $asset['name'];
            $targetInfo = $tpTargets[$symbol] ?? null;
            $currentPct = isset($asset['value']) ? ($asset['value'] / $api['summary']['value']) * 100.0 : 0;
            $inBand = true;
            if ($targetInfo) {
                $lower = ($targetInfo['target'] - $targetInfo['deviation']) * 100;
                $upper = ($targetInfo['target'] + $targetInfo['deviation']) * 100;
                $inBand = $currentPct >= $lower && $currentPct <= $upper;
            }
        @endphp
        <tr>
            <td><strong>{{ $asset['name'] }}</strong></td>
            <td>{{ $asset['type'] ?? '-' }}</td>
            <td class="col-number">{{ number_format($asset['position'] ?? 0, 6) }}</td>
            <td class="col-number">
                @isset($asset['price'])
                    ${{ number_format($asset['price'], 2) }}
                @else
                    <span class="text-muted">N/A</span>
                @endisset
            </td>
            <td class="col-number">
                @isset($asset['value'])
                    ${{ number_format($asset['value'], 2) }}
                @else
                    <span class="text-muted">N/A</span>
                @endisset
            </td>
            <td class="col-number" style="{{ !$inBand && $targetInfo ? 'color: #dc2626; font-weight: 600;' : '' }}">
                @isset($asset['value'])
                    {{ number_format($currentPct, 1) }}%
                @else
                    <span class="text-muted">-</span>
                @endisset
            </td>
            <td class="col-number">
                @if($targetInfo)
                    {{ number_format($targetInfo['target'] * 100, 1) }}%
                @else
                    -
                @endif
            </td>
            <td class="col-number">
                @if($targetInfo)
                    {{ number_format($lower, 1) }}% - {{ number_format($upper, 1) }}%
                @else
                    -
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr style="background: #f1f5f9; font-weight: 600;">
            <td colspan="4">Total</td>
            <td class="col-number">${{ number_format($api['summary']['value'], 2) }}</td>
            <td class="col-number">100%</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>
@else
<div class="text-muted" style="padding: 20px; text-align: center; background: #f8fafc; border-radius: 6px;">
    No assets data available.
</div>
@endif
