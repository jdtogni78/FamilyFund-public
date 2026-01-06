@php
    // Support both $tradePortfolios (index page) and $api['tradePortfolios'] (fund page)
    $portfolioCollection = $tradePortfolios ?? ($api['tradePortfolios'] ?? collect());
@endphp

@if($portfolioCollection->count() > 1)
<div class="card mb-4">
    <div class="card-header">
        <strong><i class="fa fa-chart-bar" style="margin-right: 8px;"></i>Portfolio Allocations Comparison</strong>
    </div>
    <div class="card-body">
        <canvas id="portfolioStackedBar"></canvas>
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
                'name' => ($tp->account_name ?? 'Portfolio') . ' [' . $tp->start_dt->format('Y-m-d') . ']',
                'items' => $items->map(function($item) {
                    return [
                        'symbol' => $item->symbol,
                        'target_share' => $item->target_share
                    ];
                }),
                'cash_target' => $tp->cash_target
            ];
        })) !!};

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
        allSymbols.push('Cash');

        // Create datasets - one per symbol
        const datasets = allSymbols.map((symbol, index) => {
            return {
                label: symbol,
                data: portfolios.map(p => {
                    if (symbol === 'Cash') {
                        return p.cash_target * 100;
                    }
                    const item = p.items.find(i => i.symbol === symbol);
                    return item ? item.target_share * 100 : 0;
                }),
                backgroundColor: graphColors[index % graphColors.length],
                borderColor: '#ffffff',
                borderWidth: 1,
            };
        });

        // Portfolio names as labels
        const labels = portfolios.map(p => p.name);

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
                                let label = context.dataset.label + ': ' + value.toFixed(1) + '%';
                                // Add deviation from previous portfolio
                                if (context.dataIndex > 0) {
                                    const prevValue = context.dataset.data[context.dataIndex - 1];
                                    const diff = value - prevValue;
                                    if (diff !== 0) {
                                        const sign = diff > 0 ? '+' : '';
                                        label += ' (' + sign + diff.toFixed(1) + '%)';
                                    }
                                }
                                return label;
                            }
                        }
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: { size: 10, weight: 'bold' },
                        textShadowColor: 'rgba(0,0,0,0.5)',
                        textShadowBlur: 3,
                        formatter: function(value, context) {
                            if (value < 8) return '';
                            const symbol = context.dataset.label;
                            const portfolioIndex = context.dataIndex;
                            let label = symbol + ' ' + value.toFixed(0) + '%';

                            // Add deviation from previous portfolio
                            if (portfolioIndex > 0) {
                                const prevValue = context.dataset.data[portfolioIndex - 1];
                                const diff = value - prevValue;
                                if (diff !== 0) {
                                    const sign = diff > 0 ? '+' : '';
                                    label += ' (' + sign + diff.toFixed(0) + ')';
                                }
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
