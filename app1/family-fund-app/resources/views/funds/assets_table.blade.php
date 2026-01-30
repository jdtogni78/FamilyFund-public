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

    // Liability asset types (values should be negative)
    $liabilityTypes = ['MORTGAGE', 'LOAN', 'CREDIT_CARD'];
    // Liability portfolio categories
    $liabilityCategories = ['liability'];

    // Aggregate assets from ALL portfolios and calculate derived cash
    $aggregatedAssets = [];
    $derivedCashTotal = 0;
    $portfoliosToProcess = isset($api['portfolios']) ? $api['portfolios'] : [$api['portfolio']];
    foreach ($portfoliosToProcess as $port) {
        $portTotalValue = floatval(str_replace(['$', ','], '', $port['total_value'] ?? 0));
        $portCategory = $port['category'] ?? '';
        $isLiabilityPortfolio = in_array($portCategory, $liabilityCategories);
        $assetsSum = 0;

        foreach ($port['assets'] ?? [] as $asset) {
            $name = $asset['name'];
            $assetType = $asset['type'] ?? '';
            $isLiability = in_array($assetType, $liabilityTypes);
            $rawValue = floatval($asset['value'] ?? 0);
            $assetsSum += $rawValue;

            if (!isset($aggregatedAssets[$name])) {
                $aggregatedAssets[$name] = $asset;
                // Convert values to floats for aggregation
                $aggregatedAssets[$name]['position'] = floatval($asset['position'] ?? 0);
                // Make liability values negative
                $aggregatedAssets[$name]['value'] = $isLiability ? -abs($rawValue) : $rawValue;
                $aggregatedAssets[$name]['is_liability'] = $isLiability;
            } else {
                // Aggregate position and value
                $aggregatedAssets[$name]['position'] += floatval($asset['position'] ?? 0);
                $aggregatedAssets[$name]['value'] += $isLiability ? -abs($rawValue) : $rawValue;
            }
        }

        // Calculate derived cash for non-liability portfolios
        if (!$isLiabilityPortfolio && $portTotalValue > 0) {
            $portDerivedCash = $portTotalValue - $assetsSum;
            if ($portDerivedCash > 100) {
                $derivedCashTotal += $portDerivedCash;
            }
        }
    }

    // Add derived cash as a synthetic asset if significant
    if ($derivedCashTotal > 100) {
        $aggregatedAssets['Cash (derived)'] = [
            'name' => 'Cash (derived)',
            'type' => 'CSH',
            'group' => 'Stability',
            'position' => $derivedCashTotal,
            'price' => 1.0,
            'value' => $derivedCashTotal,
            'is_liability' => false,
            'is_derived' => true,
        ];
    }

    // Sort by absolute value descending
    uasort($aggregatedAssets, function($a, $b) {
        return abs($b['value'] ?? 0) <=> abs($a['value'] ?? 0);
    });
@endphp
<div class="table-responsive-sm">
    <table class="table table-striped" id="fund-assets-table">
        <thead>
            <tr>
                <th scope="col">Asset</th>
                <th scope="col">Type</th>
                <th scope="col">Group</th>
                <th scope="col">Position</th>
                <th scope="col">Price</th>
                <th scope="col">Market Value</th>
                <th scope="col">%</th>
                <th scope="col">Target</th>
                <th scope="col">Band</th>
            </tr>
        </thead>
        <tbody>
        @foreach($aggregatedAssets as $asset)
            @php $isDerived = $asset['is_derived'] ?? false; @endphp
            <tr class="{{ $isDerived ? 'table-info' : '' }}">
                <th scope="row">
                    @if(isset($asset['id']))
                        <a href="{{ route('assets.show', $asset['id']) }}">{{ $asset['name'] }}</a>
                    @elseif($isDerived)
                        <em class="text-info">{{ $asset['name'] }}</em>
                        <i class="fa fa-calculator ms-1 text-muted" title="Calculated from portfolio balance minus tracked assets"></i>
                    @else
                        {{ $asset['name'] }}
                    @endif
                </th>
                <td>
                    @php
                        $typeColors = [
                            'CSH' => ['bg' => '#dbeafe', 'border' => '#2563eb', 'text' => '#1d4ed8', 'label' => 'Cash'],
                            'STK' => ['bg' => '#dcfce7', 'border' => '#16a34a', 'text' => '#15803d', 'label' => 'Stock'],
                            'CRYPTO' => ['bg' => '#fef3c7', 'border' => '#d97706', 'text' => '#b45309', 'label' => 'Crypto'],
                            'FUND' => ['bg' => '#e0e7ff', 'border' => '#4f46e5', 'text' => '#4338ca', 'label' => 'Fund'],
                            'RE' => ['bg' => '#ccfbf1', 'border' => '#0d9488', 'text' => '#0f766e', 'label' => 'Real Estate'],
                            'VEHICLE' => ['bg' => '#e0f2fe', 'border' => '#0284c7', 'text' => '#0369a1', 'label' => 'Vehicle'],
                            'MORTGAGE' => ['bg' => '#fee2e2', 'border' => '#dc2626', 'text' => '#b91c1c', 'label' => 'Mortgage'],
                            'BOND' => ['bg' => '#fae8ff', 'border' => '#c026d3', 'text' => '#a21caf', 'label' => 'Bond'],
                        ];
                        $colors = $typeColors[$asset['type']] ?? ['bg' => '#f3e8ff', 'border' => '#9333ea', 'text' => '#7e22ce', 'label' => $asset['type']];
                    @endphp
                    <span class="badge" style="background: {{ $colors['bg'] }}; color: {{ $colors['text'] }}; border: 1px solid {{ $colors['border'] }}; font-size: 0.75rem; padding: 0.25em 0.5em;">
                        {{ $colors['label'] }}
                    </span>
                </td>
                <td>
                    @php
                        $groupName = $asset['group'] ?? 'Unknown';
                        $groupColor = \App\Support\UIColors::byIndex(crc32($groupName));
                    @endphp
                    <span class="badge" style="background: {{ $groupColor }}; color: white;">
                        {{ $groupName }}
                    </span>
                </td>
                <td data-order="{{ $asset['position'] }}">{{ number_format($asset['position'], 6) }}</td>
                <td data-order="{{ $asset['price'] ?? 0 }}">@isset($asset['price'])
                        ${{ number_format($asset['price'], 2) }}
                    @else
                        <span class="text-danger">N/A</span>
                    @endisset</td>
                @php
                    $isLiabilityAsset = $asset['is_liability'] ?? in_array($asset['type'] ?? '', ['MORTGAGE', 'LOAN', 'CREDIT_CARD']);
                    $valueClass = $isLiabilityAsset ? 'text-danger' : '';
                @endphp
                <td data-order="{{ $asset['value'] ?? 0 }}" class="{{ $valueClass }}">@isset($asset['value'])
                        ${{ number_format($asset['value'], 2) }}
                    @else
                        <span class="text-danger">N/A</span>
                    @endisset</td>
                <td data-order="{{ isset($asset['value']) ? ($asset['value'] / $api['summary']['value']) * 100.0 : 0 }}">@isset($asset['value'])
                        {{ number_format(($asset['value'] / $api['summary']['value']) * 100.0, 2) }}%
                    @else
                        <span class="text-danger">N/A</span>
                    @endisset</td>
                @php
                    $symbol = $asset['name'];
                    $targetInfo = $tpTargets[$symbol] ?? null;
                @endphp
                <td data-order="{{ $targetInfo ? $targetInfo['target'] * 100 : 0 }}">
                    @if($targetInfo)
                        {{ number_format($targetInfo['target'] * 100, 1) }}%
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    @if($targetInfo)
                        @php
                            $lower = ($targetInfo['target'] - $targetInfo['deviation']) * 100;
                            $upper = ($targetInfo['target'] + $targetInfo['deviation']) * 100;
                        @endphp
                        <span class="text-muted small">{{ number_format($lower, 1) }}% - {{ number_format($upper, 1) }}%</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr class="table-total-row">
                <th scope="row">Total</th>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>${{ number_format($api['summary']['value'], 2) }}</td>
                <td>100%</td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#fund-assets-table').DataTable({
        order: [[5, 'desc']], // Sort by Market Value descending
        pageLength: 25,
        paging: false,
        searching: false,
        info: false
    });
});
</script>
@endpush
