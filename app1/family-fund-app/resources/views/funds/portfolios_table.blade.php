{{-- Reusable portfolios table partial --}}
{{-- Required: $portfolios (collection), $asOf (date string, optional) --}}
{{-- Optional: $showActions (bool), $compact (bool), $showFund (bool) --}}
@php
    $showActions = $showActions ?? true;
    $compact = $compact ?? false;
    $showFund = $showFund ?? false;
    $tableId = 'portfolios-table-' . Str::random(6);
    $totalValue = 0;
    $categoryColors = \App\Models\PortfolioExt::CATEGORY_COLORS;
    $categoryLabels = \App\Models\PortfolioExt::CATEGORY_LABELS;
    $typeColors = \App\Models\PortfolioExt::TYPE_COLORS;
    $typeLabels = \App\Models\PortfolioExt::TYPE_LABELS;
    $asOf = $asOf ?? now()->format('Y-m-d');

    // Build portfolio data with values
    $portfolioData = [];
    foreach ($portfolios as $portfolio) {
        // Get value from API data if available, otherwise calculate
        if (isset($portfolio['total_value'])) {
            $value = floatval(str_replace(['$', ','], '', $portfolio['total_value']));
            $portfolioData[] = [
                'portfolio' => (object) $portfolio,
                'value' => $value,
                'assets' => $portfolio['assets'] ?? [],
            ];
        } else {
            $value = $portfolio->valueAsOf($asOf);
            // Load assets from portfolio_assets when not from API
            $assets = [];
            $portfolioAssets = $portfolio->assetsAsOf($asOf);
            foreach ($portfolioAssets as $pa) {
                $asset = $pa->asset;
                if ($asset) {
                    $price = $asset->priceAsOf($asOf)->first()?->price ?? 0;
                    $assetValue = $pa->position * $price;
                    $assets[] = [
                        'name' => $asset->name,
                        'value' => $assetValue,
                    ];
                }
            }
            // Sort by value descending
            usort($assets, fn($a, $b) => $b['value'] <=> $a['value']);
            $portfolioData[] = [
                'portfolio' => $portfolio,
                'value' => $value,
                'assets' => $assets,
            ];
        }
        $totalValue += $value;
    }
@endphp

<div class="table-responsive-sm">
    <table class="table table-striped" id="{{ $tableId }}">
        <thead>
            <tr>
                @if(!$compact)<th>ID</th>@endif
                @if($showFund)<th>Fund</th>@endif
                <th>Portfolio</th>
                <th>Category</th>
                <th>Type</th>
                <th class="text-end">Value</th>
                <th class="text-end">%</th>
                @if(!$compact)<th>Assets</th>@endif
                @if($showActions)<th>Actions</th>@endif
            </tr>
        </thead>
        <tbody>
        @foreach($portfolioData as $data)
            @php
                $portfolio = $data['portfolio'];
                $value = $data['value'];
                $pct = $totalValue != 0 ? ($value / $totalValue) * 100 : 0;
                $isLiability = ($portfolio->category ?? null) === 'liability';

                // Get category/type from object or array
                $cat = is_array($portfolio) ? ($portfolio['category'] ?? null) : ($portfolio->category ?? null);
                $type = is_array($portfolio) ? ($portfolio['type'] ?? null) : ($portfolio->type ?? null);
                $displayName = is_array($portfolio) ? ($portfolio['display_name'] ?? null) : ($portfolio->display_name ?? null);
                $source = is_array($portfolio) ? ($portfolio['source'] ?? '') : ($portfolio->source ?? '');
                $id = is_array($portfolio) ? ($portfolio['id'] ?? 0) : ($portfolio->id ?? 0);
                $fund = is_array($portfolio) ? null : ($portfolio->fund ?? null);
            @endphp
            <tr>
                @if(!$compact)<td>{{ $id }}</td>@endif
                @if($showFund)
                <td>
                    @if($fund)
                        <a href="{{ route('funds.show', $fund->id) }}">{{ $fund->name }}</a>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                @endif
                <td>
                    @if($displayName)
                        <a href="{{ route('portfolios.show', $id) }}"><strong>{{ $displayName }}</strong></a>
                        <br><small class="text-muted">{{ $source }}</small>
                    @else
                        <a href="{{ route('portfolios.show', $id) }}"><code>{{ $source }}</code></a>
                    @endif
                </td>
                <td>
                    @if($cat)
                        <span class="badge" style="background: {{ $categoryColors[$cat] ?? '#6b7280' }}; color: white;">
                            {{ $categoryLabels[$cat] ?? ucfirst($cat) }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    @if($type)
                        <span class="badge" style="background: {{ $typeColors[$type] ?? '#6b7280' }}; color: white;">
                            {{ $typeLabels[$type] ?? ucfirst($type) }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="text-end" data-order="{{ $value }}">
                    <strong style="color: {{ $isLiability ? '#dc2626' : 'inherit' }};">
                        {{ $isLiability ? '-' : '' }}${{ number_format(abs($value), 2) }}
                    </strong>
                </td>
                <td class="text-end" data-order="{{ $pct }}">
                    {{ number_format(abs($pct), 1) }}%
                </td>
                @if(!$compact)
                <td>
                    @php
                        $assets = $data['assets'];
                        $assetColors = ['#0d9488', '#2563eb', '#7c3aed', '#db2777', '#ea580c'];
                        $topAssets = array_slice($assets, 0, 3);
                        $remainingAssets = array_slice($assets, 3);
                        $assetsToggleId = 'assets-' . $id . '-' . Str::random(4);
                    @endphp
                    @if(count($assets) > 0)
                        @foreach($topAssets as $idx => $asset)
                            <span class="badge me-1" style="background: {{ $assetColors[$idx % count($assetColors)] }}; color: white;"
                                  title="${{ number_format($asset['value'] ?? 0, 2) }}">
                                {{ $asset['name'] }}
                            </span>
                        @endforeach
                        @if(count($remainingAssets) > 0)
                            <span class="collapse" id="{{ $assetsToggleId }}">
                                @foreach($remainingAssets as $idx => $asset)
                                    <span class="badge me-1" style="background: {{ $assetColors[($idx + 3) % count($assetColors)] }}; color: white;"
                                          title="${{ number_format($asset['value'] ?? 0, 2) }}">
                                        {{ $asset['name'] }}
                                    </span>
                                @endforeach
                            </span>
                            <a href="#" class="assets-toggle text-muted small" data-target="{{ $assetsToggleId }}" style="text-decoration: none;">
                                <span class="expand-text">+{{ count($remainingAssets) }}</span>
                                <span class="collapse-text" style="display: none;">less</span>
                            </a>
                        @endif
                    @else
                        <span class="badge bg-secondary">-</span>
                    @endif
                </td>
                @endif
                @if($showActions)
                <td>
                    <div class="btn-group">
                        <a href="{{ route('portfolios.show', $id) }}"
                           class="btn btn-sm btn-ghost-success" title="View">
                            <i class="fa fa-eye"></i>
                        </a>
                        <a href="{{ route('portfolios.edit', $id) }}"
                           class="btn btn-sm btn-ghost-info" title="Edit">
                            <i class="fa fa-edit"></i>
                        </a>
                    </div>
                </td>
                @endif
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            @php
                $colspan = $compact ? 2 : 4;
                if ($showFund) $colspan++;
            @endphp
            <tr style="background: #f0fdfa; font-weight: bold;">
                <td colspan="{{ $colspan }}">Total</td>
                <td class="text-end">${{ number_format($totalValue, 2) }}</td>
                <td class="text-end">100%</td>
                @if(!$compact)<td></td>@endif
                @if($showActions)<td></td>@endif
            </tr>
        </tfoot>
    </table>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    @php
        $orderCol = $compact ? 3 : 4;
        if ($showFund) $orderCol++;
    @endphp
    $('#{{ $tableId }}').DataTable({
        order: [[{{ $orderCol }}, 'desc']],
        paging: true,
        pageLength: 10,
        searching: true,
        info: true
    });

    // Assets expand/collapse toggle
    $('.assets-toggle').on('click', function(e) {
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
.assets-toggle { cursor: pointer; }
.assets-toggle:hover { text-decoration: underline !important; }
.collapse.show { display: inline !important; }
</style>
@endpush
