@php
    // Compute groups from items
    $groupData = [];
    foreach ($tradePortfolio->tradePortfolioItems as $item) {
        $asset = \App\Models\AssetExt::getAsset($item->symbol, $item->type);
        $group = $asset->display_group ?? 'Other';
        if (!isset($groupData[$group])) {
            $groupData[$group] = 0;
        }
        $groupData[$group] += $item->target_share * 100;
    }
    // Add cash
    $cashAsset = \App\Models\AssetExt::getCashAsset();
    $cashGroup = $cashAsset->display_group ?? 'Cash';
    if (!isset($groupData[$cashGroup])) {
        $groupData[$cashGroup] = 0;
    }
    $groupData[$cashGroup] += $tradePortfolio->cash_target * 100;
@endphp

<div>
    <canvas id="tradePortfolioGroupGraph{{ $tradePortfolio->id }}"></canvas>
</div>

@push('scripts')
    <script type="text/javascript">
    (function() {
        var groupData = {!! json_encode($groupData) !!};
        var assets_labels = Object.keys(groupData);
        var assets_shares = Object.values(groupData);

        new Chart(
            document.getElementById('tradePortfolioGroupGraph{{ $tradePortfolio->id }}'),
            {
                type: 'doughnut',
                data: {
                    labels: assets_labels,
                    datasets: [{
                        data: assets_shares,
                        backgroundColor: graphColors,
                        hoverOffset: 3
                    }]
                },
            });
    })();
    </script>
@endpush
