<div>
    <canvas id="perfGraph"></canvas>
</div>

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            try {
                const api = {!! json_encode($api) !!};
                const yearlyPerf = api.yearly_performance || {};
                const labels = Object.keys(yearlyPerf);

                if (labels.length === 0) {
                    console.log('No yearly performance data');
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
            }
        });
    </script>
@endpush
