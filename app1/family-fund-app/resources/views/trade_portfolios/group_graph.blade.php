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

<div style="position: relative; z-index: 1;">
    <canvas id="tradePortfolioGroupGraph{{ $tradePortfolio->id }}" style="display: block !important; visibility: visible !important;"></canvas>
</div>

@push('scripts')
    <script type="text/javascript">
    $(document).ready(function() {
        try {
            var groupData = {!! json_encode($groupData) !!};
            var labels = Object.keys(groupData);
            var data = Object.values(groupData);

            createDoughnutChart('tradePortfolioGroupGraph{{ $tradePortfolio->id }}', labels, data, {
                legendPosition: 'top',
                tooltipFormatter: function(context) {
                    const value = context.raw;
                    return context.label + ': ' + value.toFixed(1) + '%';
                }
            });
        } catch (e) {
            console.error('Error creating trade portfolio group chart:', e);
        }
    });
    </script>
@endpush
