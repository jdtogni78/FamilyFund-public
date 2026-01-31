{{--
    Reusable Group Summary Cards Component

    Required variables:
    - $groups: array of groups, each with:
        - key: string - group identifier
        - label: string - display label
        - color: string - hex color (e.g., '#0d9488')
        - value: float - current/total value
        - count: int (optional) - item count, defaults to count of items
        - dollarChange: float (optional) - period change amount
        - percentChange: float (optional) - period change percent
        - items: array of items, each with:
            - id: int - for link
            - name: string - display name
            - value: float - item value
            - dollarChange: float (optional)
            - percentChange: float (optional)

    Optional variables:
    - $sectionId: string - unique section ID for expand/collapse (default: 'group')
    - $itemRoute: string - route name for item links (default: 'portfolios.show')
    - $showChanges: bool - whether to show $ and % changes (default: false)
    - $grandTotal: float - total for percent calculation (default: sum of group values)
    - $showNetWorth: bool - whether to show net worth summary row (default: false)
    - $liabilityKeys: array - keys that represent liabilities (default: ['liability'])
    - $maxVisible: int - max items to show before "show more" (default: 2)
--}}

@php
    $sectionId = $sectionId ?? 'group';
    $itemRoute = $itemRoute ?? 'portfolios.show';
    $showChanges = $showChanges ?? false;
    $showNetWorth = $showNetWorth ?? false;
    $liabilityKeys = $liabilityKeys ?? ['liability', 'mortgage', 'loan', 'credit_card'];
    $maxVisible = $maxVisible ?? 2;

    // Calculate totals for assets and liabilities separately for proper percentages
    $totalAssets = 0;
    $totalLiabilities = 0;
    foreach ($groups as $g) {
        $isLiab = in_array($g['key'], $liabilityKeys);
        if ($isLiab) {
            $totalLiabilities += abs($g['value']);
        } else {
            $totalAssets += $g['value'];
        }
    }
    $grandTotal = $grandTotal ?? ($totalAssets - $totalLiabilities);
@endphp

@if(empty($groups))
    <div class="text-center text-muted py-4">
        No data available.
    </div>
@else
    <div class="row">
        @foreach($groups as $group)
            @php
                $isLiability = in_array($group['key'], $liabilityKeys);
                $color = $isLiability ? '#dc2626' : ($group['color'] ?? '#6b7280');
                // Calculate % of total assets or total liabilities (not net worth)
                $pctBase = $isLiability ? $totalLiabilities : $totalAssets;
                $pct = $pctBase != 0 ? (abs($group['value']) / $pctBase) * 100 : 0;
                $groupId = $sectionId . '-' . Str::slug($group['key']);
                $items = $group['items'] ?? [];
                $itemCount = $group['count'] ?? count($items);
                $topItems = array_slice($items, 0, $maxVisible);
                $remainingItems = array_slice($items, $maxVisible);

                // Change colors (if showing changes)
                $hasChanges = $showChanges && isset($group['dollarChange']);
                $isPositive = ($group['dollarChange'] ?? 0) >= 0;
                $changeColor = $isPositive ? '#16a34a' : '#dc2626';
            @endphp
            <div class="col-md-{{ count($groups) <= 4 ? (12 / count($groups)) : 3 }} mb-2">
                <div class="p-3 rounded" style="background: {{ $color }}15; border-left: 4px solid {{ $color }};">
                    {{-- Group Header --}}
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge" style="background: {{ $color }}; color: white;">{{ $group['label'] }}</span>
                            <span class="text-muted small ml-1">({{ $itemCount }})</span>
                        </div>
                        <span class="text-muted small">{{ number_format(abs($pct), 1) }}%</span>
                    </div>

                    {{-- Group Total Value --}}
                    <div class="mt-2" style="font-size: 1.25rem; font-weight: 700; color: {{ $color }};">
                        {{ $isLiability ? '-' : '' }}${{ number_format(abs($group['value']), 0) }}
                    </div>

                    {{-- Group Change (if enabled) --}}
                    @if($hasChanges)
                        <div class="small" style="color: {{ $changeColor }};">
                            {{ $isPositive ? '+' : '' }}${{ number_format($group['dollarChange'], 0) }}
                            ({{ $isPositive ? '+' : '' }}{{ number_format($group['percentChange'], 1) }}%)
                        </div>
                    @endif

                    {{-- Items Table --}}
                    @if(count($items) > 0)
                        <table class="summary-table">
                            @foreach($topItems as $item)
                                @php
                                    $itemIsLiability = $isLiability;
                                    $itemHasChanges = $showChanges && isset($item['dollarChange']);
                                    $itemIsPositive = ($item['dollarChange'] ?? 0) >= 0;
                                    $itemChangeColor = $itemIsPositive ? '#16a34a' : '#dc2626';
                                @endphp
                                <tr>
                                    <td>
                                        @if($item['id'] ?? null)
                                            <a href="{{ route($itemRoute, $item['id']) }}" style="color: inherit; text-decoration: none;" class="summary-link">
                                                {{ Str::limit($item['name'], 25) }}
                                            </a>
                                        @else
                                            {{ Str::limit($item['name'], 25) }}
                                        @endif
                                    </td>
                                    <td style="color: {{ $itemIsLiability ? '#dc2626' : $color }}; text-align: right;">
                                        {{ $itemIsLiability ? '-' : '' }}${{ number_format(abs($item['value']), 0) }}
                                    </td>
                                </tr>
                                @if($itemHasChanges)
                                    <tr class="change-row">
                                        <td colspan="2" style="text-align: right; color: {{ $itemChangeColor }}; font-size: 0.7rem; padding-top: 0;">
                                            {{ $itemIsPositive ? '+' : '' }}${{ number_format($item['dollarChange'], 0) }}
                                            ({{ $itemIsPositive ? '+' : '' }}{{ number_format($item['percentChange'], 1) }}%)
                                        </td>
                                    </tr>
                                @endif
                            @endforeach

                            @if(count($remainingItems) > 0)
                                <tbody class="collapse" id="{{ $groupId }}-more">
                                    @foreach($remainingItems as $item)
                                        @php
                                            $itemIsLiability = $isLiability;
                                            $itemHasChanges = $showChanges && isset($item['dollarChange']);
                                            $itemIsPositive = ($item['dollarChange'] ?? 0) >= 0;
                                            $itemChangeColor = $itemIsPositive ? '#16a34a' : '#dc2626';
                                        @endphp
                                        <tr>
                                            <td>
                                                @if($item['id'] ?? null)
                                                    <a href="{{ route($itemRoute, $item['id']) }}" style="color: inherit; text-decoration: none;" class="summary-link">
                                                        {{ Str::limit($item['name'], 25) }}
                                                    </a>
                                                @else
                                                    {{ Str::limit($item['name'], 25) }}
                                                @endif
                                            </td>
                                            <td style="color: {{ $itemIsLiability ? '#dc2626' : $color }}; text-align: right;">
                                                {{ $itemIsLiability ? '-' : '' }}${{ number_format(abs($item['value']), 0) }}
                                            </td>
                                        </tr>
                                        @if($itemHasChanges)
                                            <tr class="change-row">
                                                <td colspan="2" style="text-align: right; color: {{ $itemChangeColor }}; font-size: 0.7rem; padding-top: 0;">
                                                    {{ $itemIsPositive ? '+' : '' }}${{ number_format($item['dollarChange'], 0) }}
                                                    ({{ $itemIsPositive ? '+' : '' }}{{ number_format($item['percentChange'], 1) }}%)
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                                <tr>
                                    <td colspan="2">
                                        <a href="#" class="expand-toggle" data-target="{{ $groupId }}-more" style="color: {{ $color }}; text-decoration: none;">
                                            <span class="expand-text">+{{ count($remainingItems) }} more</span>
                                            <span class="collapse-text" style="display: none;">show less</span>
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        </table>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Net Worth Summary (if enabled) --}}
    @if($showNetWorth)
        @php
            $totalValue = array_sum(array_column($groups, 'value'));
            $totalChange = $showChanges ? array_sum(array_column($groups, 'dollarChange')) : null;
            $totalStart = $totalChange !== null ? $totalValue - $totalChange : null;
            $totalPercent = ($totalStart !== null && $totalStart != 0) ? (($totalValue - $totalStart) / abs($totalStart)) * 100 : 0;
            $totalIsPositive = ($totalChange ?? 0) >= 0;
            $totalChangeColor = $totalIsPositive ? '#16a34a' : '#dc2626';
        @endphp
        <div class="row mt-3 pt-3" style="border-top: 2px solid #e5e7eb;">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong style="font-size: 1.1rem;">Net Worth</strong>
                    </div>
                    <div class="text-right">
                        <span style="font-size: 1.25rem; font-weight: 700; color: {{ $totalValue >= 0 ? '#0d9488' : '#dc2626' }};">
                            ${{ number_format($totalValue, 0) }}
                        </span>
                        @if($showChanges && $totalChange !== null)
                            <span style="font-size: 1rem; color: {{ $totalChangeColor }}; margin-left: 1rem;">
                                {{ $totalIsPositive ? '+' : '' }}${{ number_format($totalChange, 0) }}
                                ({{ $totalIsPositive ? '+' : '' }}{{ number_format($totalPercent, 1) }}%)
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif

@once
@push('scripts')
<script>
$(document).ready(function() {
    // Expand/collapse toggle for summary cards
    $(document).on('click', '.expand-toggle', function(e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        var $target = $('#' + targetId);
        var $expandText = $(this).find('.expand-text');
        var $collapseText = $(this).find('.collapse-text');

        if ($target.hasClass('show')) {
            $target.removeClass('show');
            $expandText.show();
            $collapseText.hide();
        } else {
            $target.addClass('show');
            $expandText.hide();
            $collapseText.show();
        }
    });
});
</script>
<style>
.summary-table { width: 100%; margin-top: 0.5rem; }
.summary-table td { padding: 2px 0; font-size: 0.8rem; }
.summary-table td:first-child { color: #6b7280; }
.summary-table .change-row td { padding-bottom: 4px; }
.summary-link:hover { text-decoration: underline !important; }
tbody.collapse { display: none; }
tbody.collapse.show { display: table-row-group !important; }
.expand-toggle { cursor: pointer; font-weight: 500; font-size: 0.8rem; }
.expand-toggle:hover { text-decoration: underline !important; }
</style>
@endpush
@endonce
