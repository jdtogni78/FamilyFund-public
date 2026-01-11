@php
    // Support both $tradePortfolios (index page) and $api['tradePortfolios'] (fund page)
    $portfolioCollection = $tradePortfolios ?? ($api['tradePortfolios'] ?? collect());

    // Group colors matching the updated color scheme
    $groupColors = [
        'Growth' => '#16a34a',
        'Stability' => '#2563eb',
        'Crypto' => '#d97706',
        'Other' => '#64748b',
    ];

    // Get cash asset's group
    $cashAsset = \App\Models\AssetExt::getCashAsset();
    $cashGroup = $cashAsset->display_group ?? 'Stability';

    // Build group data for each portfolio
    $portfolioGroupData = [];
    foreach ($portfolioCollection as $tp) {
        $items = $tp->tradePortfolioItems ?? $tp->items ?? collect();
        $groups = ['Growth' => 0, 'Stability' => 0, 'Crypto' => 0, 'Other' => 0];

        foreach ($items as $item) {
            $group = $item->group ?? 'Other';
            if (!isset($groups[$group])) {
                $groups['Other'] += $item->target_share * 100;
            } else {
                $groups[$group] += $item->target_share * 100;
            }
        }

        // Add cash to its group
        $cashPct = $tp->cash_target * 100;
        if (isset($groups[$cashGroup])) {
            $groups[$cashGroup] += $cashPct;
        } else {
            $groups['Other'] += $cashPct;
        }

        $portfolioGroupData[] = [
            'id' => $tp->id,
            'name' => '#' . $tp->id . ': ' . $tp->start_dt->format('Y-m-d') . ' to ' . $tp->end_dt->format('Y-m-d'),
            'groups' => $groups,
            'targets' => $tp->groups ?? [], // Target allocations if defined
        ];
    }

    // Get current assets grouped if available
    $currentGroups = ['Growth' => 0, 'Stability' => 0, 'Crypto' => 0, 'Other' => 0];
    $hasCurrentAssets = false;
    if (isset($api['portfolio']['assets']) && isset($api['portfolio']['total_value'])) {
        $totalValue = floatval(str_replace(['$', ','], '', $api['portfolio']['total_value']));
        if ($totalValue > 0) {
            $hasCurrentAssets = true;
            foreach ($api['portfolio']['assets'] as $asset) {
                $value = floatval(str_replace(['$', ','], '', $asset['value'] ?? '0'));
                $pct = ($value / $totalValue) * 100;
                $group = $asset['group'] ?? 'Other';
                if (!isset($currentGroups[$group])) {
                    $currentGroups['Other'] += $pct;
                } else {
                    $currentGroups[$group] += $pct;
                }
            }
        }
    }
@endphp

@if($portfolioCollection->count() >= 1)
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: #ffffff;">
        <strong><i class="fa fa-layer-group" style="margin-right: 8px;"></i>Portfolio Allocations by Group</strong>
        <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseGroupAllocations"
           role="button" aria-expanded="true" aria-controls="collapseGroupAllocations">
            <i class="fa fa-chevron-down"></i>
        </a>
    </div>
    <div class="collapse show" id="collapseGroupAllocations">
        <div class="card-body">
            <div style="position: relative; z-index: 1;">
                <canvas id="portfolioGroupsStackedBar" style="display: block !important; visibility: visible !important;"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
    try {
        const portfolioData = {!! json_encode($portfolioGroupData) !!};
        const currentGroups = {!! json_encode($currentGroups) !!};
        const hasCurrentAssets = {!! json_encode($hasCurrentAssets) !!};

        const groupColors = {
            'Growth': '#16a34a',
            'Stability': '#2563eb',
            'Crypto': '#d97706',
            'Other': '#64748b',
        };

        const groupOrder = ['Growth', 'Stability', 'Crypto', 'Other'];

        if (portfolioData.length === 0) {
            console.log('No portfolios to display');
            return;
        }

        // Build datasets - one per group
        const datasets = groupOrder.map(group => {
            const data = portfolioData.map(p => p.groups[group] || 0);

            // Add current assets data if available
            if (hasCurrentAssets) {
                data.push(currentGroups[group] || 0);
            }

            return {
                label: group,
                data: data,
                backgroundColor: groupColors[group],
                borderColor: '#ffffff',
                borderWidth: 2,
            };
        }).filter(ds => ds.data.some(v => v > 0)); // Only show groups with data

        // Portfolio names as labels
        const labels = portfolioData.map(p => p.name);
        if (hasCurrentAssets) {
            labels.push('Current');
        }

        const config = {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    x: {
                        stacked: true,
                        grid: { display: false }
                    },
                    y: {
                        stacked: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: chartTheme.fontColor,
                            font: { size: 12, weight: 'bold' },
                            padding: 12,
                            usePointStyle: true,
                            pointStyle: 'rectRounded'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                if (value === 0) return null;
                                const group = context.dataset.label;
                                // Show target if available
                                const portfolioIndex = context.dataIndex;
                                if (portfolioIndex < portfolioData.length) {
                                    const targetPct = portfolioData[portfolioIndex].targets[group];
                                    if (targetPct !== undefined) {
                                        return group + ': ' + value.toFixed(1) + '% (target: ' + targetPct + '%)';
                                    }
                                }
                                return group + ': ' + value.toFixed(1) + '%';
                            }
                        }
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: { size: 11, weight: 'bold' },
                        textShadowColor: 'rgba(0,0,0,0.5)',
                        textShadowBlur: 3,
                        formatter: function(value, context) {
                            if (value < 8) return '';
                            return value.toFixed(0) + '%';
                        },
                        anchor: 'center',
                        align: 'center'
                    }
                }
            }
        };

        new Chart(document.getElementById('portfolioGroupsStackedBar'), config);
    } catch (e) {
        console.error('Error creating groups stacked bar chart:', e);
    }
});
</script>
@endpush
@endif
