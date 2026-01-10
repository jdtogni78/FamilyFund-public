<div>
    <canvas id="perfGraph"></canvas>
    <div id="perfGraphNoData" class="text-center text-muted py-5" style="display: none;">
        <i class="fa fa-chart-bar fa-3x mb-3" style="color: #cbd5e1;"></i>
        <p>No yearly performance data available</p>
    </div>
</div>

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            try {
                const api = {!! json_encode($api) !!};
                const yearlyPerf = api.yearly_performance || {};
                const rawLabels = Object.keys(yearlyPerf);

                // Extract end date from range labels like "2024-01-01 to 2025-01-01"
                const labels = rawLabels.map(label => {
                    const parts = label.split(' to ');
                    const endDate = parts.length > 1 ? parts[1] : label;
                    // Format as "Jan 2025"
                    const date = new Date(endDate);
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                });

                if (labels.length === 0) {
                    console.log('No yearly performance data');
                    $('#perfGraph').hide();
                    $('#perfGraphNoData').show();
                    return;
                }

                const values = Object.values(yearlyPerf).map(e => e.value);
                const performances = Object.values(yearlyPerf).map(e => parseFloat(e.performance));

                // Color bars based on performance (green positive, red negative)
                const backgroundColors = performances.map((perf, i) => {
                    if (i === labels.length - 1) return chartTheme.primary; // Highlight latest
                    return perf >= 0 ? chartTheme.success : chartTheme.danger;
                });

                const config = {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Value',
                            data: values,
                            backgroundColor: backgroundColors,
                            borderColor: backgroundColors,
                            borderWidth: 1,
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        layout: {
                            padding: { top: 20 }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = formatCurrency(context.raw, 2);
                                        const perf = performances[context.dataIndex];
                                        const perfStr = (perf >= 0 ? '+' : '') + perf.toFixed(2) + '%';
                                        return [value, 'Performance: ' + perfStr];
                                    }
                                }
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                color: chartTheme.fontColor,
                                font: { weight: 'bold', size: 11 },
                                formatter: function(value) {
                                    return formatCurrency(value, 0);
                                },
                                display: function(context) {
                                    return context.dataset.data.length <= 12;
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return formatCurrency(value, 0);
                                    }
                                },
                                grid: { color: 'rgba(0,0,0,0.05)' }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                };

                new Chart(document.getElementById('perfGraph'), config);
            } catch (e) {
                console.error('Error creating yearly chart:', e);
                $('#perfGraph').hide();
                $('#perfGraphNoData').show();
            }
        });
    </script>
@endpush
