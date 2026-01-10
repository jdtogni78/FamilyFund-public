@php
    // Support both $tradePortfolios (index page) and $api['tradePortfolios'] (fund page)
    $portfolioCollection = $tradePortfolios ?? ($api['tradePortfolios'] ?? collect());

    // Get current assets if available (fund page)
    $currentAssets = [];
    if (isset($api['portfolio']['assets']) && isset($api['portfolio']['total_value'])) {
        $totalValue = floatval(str_replace(['$', ','], '', $api['portfolio']['total_value']));
        if ($totalValue > 0) {
            foreach ($api['portfolio']['assets'] as $asset) {
                $value = floatval(str_replace(['$', ','], '', $asset['value'] ?? '0'));
                // Normalize CASH to Cash for consistent matching
                $symbol = strtoupper($asset['name']) === 'CASH' ? 'Cash' : $asset['name'];
                $currentAssets[] = [
                    'symbol' => $symbol,
                    'percent' => ($value / $totalValue) * 100,
                ];
            }
        }
    }
@endphp

@if($portfolioCollection->count() >= 1)
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: #ffffff;">
        <strong><i class="fa fa-chart-bar" style="margin-right: 8px;"></i>Portfolio Allocations Comparison</strong>
        <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapsePortfolioAllocations"
           role="button" aria-expanded="true" aria-controls="collapsePortfolioAllocations">
            <i class="fa fa-chevron-down"></i>
        </a>
    </div>
    <div class="collapse show" id="collapsePortfolioAllocations">
        <div class="card-body">
            <div style="position: relative; z-index: 1;">
                <canvas id="portfolioStackedBar" style="display: block !important; visibility: visible !important;"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
    try {
        const portfolios = {!! json_encode($portfolioCollection->map(function($tp) {
            $items = $tp->tradePortfolioItems ?? $tp->items ?? collect();
            return [
                'id' => $tp->id,
                'name' => '#' . $tp->id . ': ' . $tp->start_dt->format('Y-m-d') . ' to ' . $tp->end_dt->format('Y-m-d'),
                'items' => $items->map(function($item) {
                    return [
                        'symbol' => $item->symbol,
                        'target_share' => $item->target_share,
                        'deviation_trigger' => $item->deviation_trigger ?? 0
                    ];
                }),
                'cash_target' => $tp->cash_target
            ];
        })) !!};

        const currentAssets = {!! json_encode($currentAssets) !!};

        if (portfolios.length === 0) {
            console.log('No portfolios to display');
            return;
        }

        // Get all unique symbols across all portfolios (maintain order)
        const allSymbols = [];
        portfolios.forEach(p => {
            p.items.forEach(item => {
                if (!allSymbols.includes(item.symbol)) {
                    allSymbols.push(item.symbol);
                }
            });
        });
        // Also add symbols from current assets (case-insensitive check)
        currentAssets.forEach(a => {
            // Skip Cash variants - we'll add it at the end
            if (a.symbol.toUpperCase() === 'CASH') return;
            // Check if symbol already exists (case-insensitive)
            const exists = allSymbols.some(s => s.toUpperCase() === a.symbol.toUpperCase());
            if (!exists) {
                allSymbols.push(a.symbol);
            }
        });
        allSymbols.push('Cash');

        // Build lookup for deviation triggers: deviationTriggers[symbol][portfolioIndex]
        const deviationTriggers = {};
        allSymbols.forEach(symbol => {
            const triggers = portfolios.map(p => {
                if (symbol === 'Cash') return 0;
                const item = p.items.find(i => i.symbol === symbol);
                return item ? (item.deviation_trigger * 100) : 0;
            });
            // Current assets don't have deviation triggers
            if (currentAssets.length > 0) {
                triggers.push(0);
            }
            deviationTriggers[symbol] = triggers;
        });

        // Create datasets - one per symbol
        const datasets = allSymbols.map((symbol, index) => {
            // Data for each trade portfolio
            const data = portfolios.map(p => {
                if (symbol === 'Cash') {
                    return p.cash_target * 100;
                }
                const item = p.items.find(i => i.symbol === symbol);
                return item ? item.target_share * 100 : 0;
            });

            // Add current assets data if available (case-insensitive match)
            if (currentAssets.length > 0) {
                const symbolUpper = symbol.toUpperCase();
                const asset = currentAssets.find(a => a.symbol.toUpperCase() === symbolUpper);
                data.push(asset ? asset.percent : 0);
            }

            return {
                label: symbol,
                data: data,
                backgroundColor: graphColors[index % graphColors.length],
                borderColor: '#ffffff',
                borderWidth: 1,
            };
        });

        // Portfolio names as labels
        const labels = portfolios.map(p => p.name);
        if (currentAssets.length > 0) {
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
                            font: { size: 11, weight: 'bold' },
                            padding: 8
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.raw;
                                if (value === 0) return null;
                                const symbol = context.dataset.label;
                                let label = symbol + ': ' + value.toFixed(1) + '%';
                                // Add deviation trigger
                                const devTrigger = deviationTriggers[symbol]?.[context.dataIndex];
                                if (devTrigger && devTrigger > 0) {
                                    label += ' (±' + devTrigger.toFixed(1) + '%)';
                                }
                                return label;
                            }
                        }
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: function(context) {
                            const value = context.dataset.data[context.dataIndex];
                            // Smaller font for small segments
                            return { size: value < 8 ? 9 : 11, weight: 'bold' };
                        },
                        textShadowColor: 'rgba(0,0,0,0.5)',
                        textShadowBlur: 3,
                        formatter: function(value, context) {
                            // Hide if too small to display
                            if (value < 3) return '';

                            const symbol = context.dataset.label;
                            const portfolioIndex = context.dataIndex;

                            // For small segments (3-8%), show symbol + percentage (no deviation)
                            if (value < 8) {
                                return symbol + ' ' + value.toFixed(1) + '%';
                            }

                            // For larger segments, show full details with deviation
                            let label = symbol + ' ' + value.toFixed(1) + '%';
                            const devTrigger = deviationTriggers[symbol]?.[portfolioIndex];
                            if (devTrigger && devTrigger > 0) {
                                label += ' ±' + devTrigger.toFixed(0) + '%';
                            }
                            return label;
                        },
                        anchor: 'center',
                        align: 'center'
                    }
                }
            }
        };

        new Chart(document.getElementById('portfolioStackedBar'), config);
    } catch (e) {
        console.error('Error creating stacked bar chart:', e);
    }
});
</script>
@endpush
@endif
