<div>
    <canvas id="rebalanceGraph{{ Str::slug($symbol) }}"></canvas>
</div>
<div class="col-xs-12 mt-2">
    <ul class="small text-muted">
        <li><b>Target</b>: Target allocation percentage (changes when trade portfolio changes)</li>
        <li><b>Actual</b>: Current allocation percentage based on portfolio value</li>
        <li><b>Shaded area</b>: Acceptable deviation range (target ± trigger)</li>
    </ul>
</div>

@push('scripts')
<script type="text/javascript">
(function() {
    const symbol = "{{ $symbol }}";
    const canvasId = "rebalanceGraph{{ Str::slug($symbol) }}";
    const perf = api['rebalance'];
    const rawLabels = Object.keys(perf);
    const sparseLabels = createSparseLabels(rawLabels);

    // Extract data for this symbol - min/max/target now vary per date
    const targetData = [];
    const minData = [];
    const maxData = [];
    const actualData = [];
    const tpChanges = []; // Track trade portfolio changes for annotations

    let lastTpId = null;
    let lastValue = { target: 0, min: 0, max: 0, perc: 0 };

    rawLabels.forEach((date, index) => {
        const dayData = perf[date];
        if (dayData && dayData[symbol]) {
            lastValue = {
                target: dayData[symbol].target,
                min: dayData[symbol].min,
                max: dayData[symbol].max,
                perc: dayData[symbol].perc
            };

            // Track trade portfolio changes
            if (dayData[symbol].trade_portfolio_id !== lastTpId) {
                if (lastTpId !== null) {
                    tpChanges.push({
                        index: index,
                        date: date,
                        newTpId: dayData[symbol].trade_portfolio_id,
                        newTarget: dayData[symbol].target
                    });
                }
                lastTpId = dayData[symbol].trade_portfolio_id;
            }
        }

        targetData.push(lastValue.target);
        minData.push(lastValue.min);
        maxData.push(lastValue.max);
        actualData.push(lastValue.perc);
    });

    // Plugin to shade the area between min and max (handles varying bounds)
    const shadedBand = {
        id: 'shadedBand_' + symbol,
        beforeDatasetsDraw: function(chart) {
            const { ctx, chartArea: { top, bottom, left, right }, scales: { x, y } } = chart;
            const minDataset = chart.getDatasetMeta(1);
            const maxDataset = chart.getDatasetMeta(2);

            if (!minDataset.data[0] || !maxDataset.data[0]) return;

            ctx.save();
            ctx.beginPath();
            ctx.fillStyle = 'rgba(22, 163, 74, 0.15)';

            // Draw path along min line
            ctx.moveTo(minDataset.data[0].x, minDataset.data[0].y);
            for (let i = 1; i < minDataset.data.length; i++) {
                if (minDataset.data[i]) {
                    ctx.lineTo(minDataset.data[i].x, minDataset.data[i].y);
                }
            }
            // Draw path back along max line (reversed)
            for (let i = maxDataset.data.length - 1; i >= 0; i--) {
                if (maxDataset.data[i]) {
                    ctx.lineTo(maxDataset.data[i].x, maxDataset.data[i].y);
                }
            }
            ctx.closePath();
            ctx.fill();
            ctx.restore();
        }
    };

    // Plugin to draw vertical lines where trade portfolio changes
    const tpChangeLines = {
        id: 'tpChangeLines_' + symbol,
        afterDatasetsDraw: function(chart) {
            if (tpChanges.length === 0) return;

            const { ctx, chartArea: { top, bottom }, scales: { x } } = chart;

            ctx.save();
            tpChanges.forEach(change => {
                const xPos = x.getPixelForValue(change.index);

                // Draw vertical dashed line
                ctx.beginPath();
                ctx.strokeStyle = 'rgba(100, 100, 100, 0.5)';
                ctx.lineWidth = 1;
                ctx.setLineDash([5, 5]);
                ctx.moveTo(xPos, top);
                ctx.lineTo(xPos, bottom);
                ctx.stroke();

                // Draw label
                ctx.fillStyle = 'rgba(100, 100, 100, 0.8)';
                ctx.font = '10px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('TP#' + change.newTpId, xPos, top - 5);
            });
            ctx.restore();
        }
    };

    // Calculate current target for legend label
    const currentTarget = targetData.length > 0 ? targetData[targetData.length - 1] : 0;

    // Calculate Y-axis bounds with padding for whitespace
    const allValues = [...targetData, ...minData, ...maxData, ...actualData].filter(v => v !== null && v !== undefined);
    const dataMin = Math.min(...allValues);
    const dataMax = Math.max(...allValues);
    const range = dataMax - dataMin;
    const padding = Math.max(range * 0.15, 0.02); // At least 15% padding or 2% absolute
    const yMin = Math.max(0, dataMin - padding);
    const yMax = dataMax + padding;

    const datasets = [
        {
            label: 'Target (' + (currentTarget * 100).toFixed(1) + '%)',
            data: targetData,
            borderColor: chartTheme.primary,
            backgroundColor: chartTheme.primary,
            borderWidth: 2,
            borderDash: [5, 5],
            pointRadius: 0,
            pointHoverRadius: 4,
            tension: 0,
            order: 2,
        },
        {
            label: 'Min Bound',
            data: minData,
            borderColor: 'rgba(22, 163, 74, 0.5)',
            backgroundColor: 'transparent',
            borderWidth: 1,
            borderDash: [2, 2],
            pointRadius: 0,
            tension: 0,
            order: 3,
        },
        {
            label: 'Max Bound',
            data: maxData,
            borderColor: 'rgba(22, 163, 74, 0.5)',
            backgroundColor: 'transparent',
            borderWidth: 1,
            borderDash: [2, 2],
            pointRadius: 0,
            tension: 0,
            order: 4,
        },
        {
            label: 'Actual Allocation',
            data: actualData,
            borderColor: chartTheme.danger,
            backgroundColor: chartTheme.danger,
            borderWidth: 2.5,
            pointRadius: 1,
            pointHoverRadius: 5,
            tension: 0.1,
            order: 1,
        },
    ];

    new Chart(
        document.getElementById(canvasId),
        {
            type: 'line',
            data: {
                labels: sparseLabels,
                datasets: datasets
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        suggestedMin: yMin,
                        suggestedMax: yMax,
                        ticks: {
                            callback: function(value) {
                                return (value * 100).toFixed(1) + '%';
                            },
                            color: chartTheme.fontColor,
                            font: { size: 12, weight: '500' }
                        },
                        grid: { color: 'rgba(0, 0, 0, 0.08)' },
                        title: {
                            display: true,
                            text: 'Allocation %',
                            color: chartTheme.fontColor,
                            font: { size: 12, weight: '600' }
                        }
                    },
                    x: {
                        ticks: {
                            color: chartTheme.fontColor,
                            font: { size: 11 },
                            maxRotation: 45,
                            minRotation: 0,
                        },
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: chartTheme.fontColor,
                            font: { size: 12, weight: '500' },
                            usePointStyle: true,
                            padding: 15,
                            filter: function(item) {
                                // Hide min/max from legend (shown as shaded area)
                                return item.text !== 'Min Bound' && item.text !== 'Max Bound';
                            }
                        }
                    },
                    datalabels: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                return rawLabels[context[0].dataIndex];
                            },
                            label: function(context) {
                                const value = (context.raw * 100).toFixed(2);
                                const idx = context.dataIndex;

                                if (context.dataset.label === 'Actual Allocation') {
                                    const target = targetData[idx];
                                    const diff = context.raw - target;
                                    const sign = diff >= 0 ? '+' : '';
                                    return context.dataset.label + ': ' + value + '% (' + sign + (diff * 100).toFixed(2) + '% from target)';
                                }
                                return context.dataset.label + ': ' + value + '%';
                            }
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 20  // Space for TP change labels
                    }
                }
            },
            plugins: [shadedBand, tpChangeLines]
        }
    );
})();
</script>
@endpush
