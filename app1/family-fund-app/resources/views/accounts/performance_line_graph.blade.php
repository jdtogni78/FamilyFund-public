<div>
    <canvas id="perfGraphMonthly"></canvas>
</div>
<div class="mt-3">
    <small class="text-muted">
        <span class="me-3"><i class="fa fa-circle me-1" style="color: #2563eb;"></i>Account Value</span>
        @if($addSP500)
        <span class="me-3"><i class="fa fa-circle me-1" style="color: #dc2626;"></i>S&P 500 (if invested same amounts)</span>
        @endif
        <span><i class="fa fa-circle me-1" style="color: #16a34a;"></i>Cash (no growth)</span>
    </small>
</div>

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            try {
                const api = {!! json_encode($api) !!};
                const monthlyPerf = api.monthly_performance || {};
                const allLabels = Object.keys(monthlyPerf);

                if (allLabels.length === 0) {
                    console.log('No monthly performance data');
                    return;
                }

                const sparseLabels = createSparseLabels(allLabels, 24);
                const values = Object.values(monthlyPerf).map(e => e.value);

                const datasets = [{
                    label: 'Account Value',
                    data: values,
                    backgroundColor: graphColors[0],
                    borderColor: graphColors[0],
                    borderWidth: 2,
                    pointRadius: allLabels.length > 50 ? 0 : 2,
                    pointHoverRadius: 4,
                    tension: 0.1,
                    fill: false,
                }];

                const addSP500 = {!! json_encode($addSP500) !!};
                if (addSP500 && api.sp500_monthly_performance) {
                    const sp500Values = Object.values(api.sp500_monthly_performance).map(e => e.value);
                    datasets.push({
                        label: 'S&P 500',
                        data: sp500Values,
                        backgroundColor: graphColors[1],
                        borderColor: graphColors[1],
                        borderWidth: 2,
                        pointRadius: allLabels.length > 50 ? 0 : 2,
                        pointHoverRadius: 4,
                        tension: 0.1,
                        fill: false,
                    });
                }

                if (api.cash) {
                    const cashValues = Object.values(api.cash).map(e => e.value);
                    datasets.push({
                        label: 'Cash',
                        data: cashValues,
                        backgroundColor: graphColors[2],
                        borderColor: graphColors[2],
                        borderWidth: 2,
                        pointRadius: allLabels.length > 50 ? 0 : 2,
                        pointHoverRadius: 4,
                        tension: 0.1,
                        fill: false,
                        borderDash: [5, 5],
                    });
                }

                const config = {
                    type: 'line',
                    data: {
                        labels: sparseLabels,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: { usePointStyle: true, padding: 15 }
                            },
                            tooltip: {
                                callbacks: {
                                    title: function(items) {
                                        return allLabels[items[0].dataIndex];
                                    },
                                    label: function(context) {
                                        return context.dataset.label + ': ' + formatCurrency(context.raw, 2);
                                    }
                                }
                            },
                            datalabels: { display: false }
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
                                grid: { display: false },
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 0,
                                }
                            }
                        }
                    }
                };

                new Chart(document.getElementById('perfGraphMonthly'), config);
            } catch (e) {
                console.error('Error creating monthly chart:', e);
            }
        });
    </script>
@endpush
