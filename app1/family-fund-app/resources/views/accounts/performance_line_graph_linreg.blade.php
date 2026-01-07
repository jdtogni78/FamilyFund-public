@if(!empty($api['linear_regression']['predictions']))
<div>
    <canvas id="accountPerfGraphLinReg"></canvas>
</div>
<div class="col-xs-12 mt-2">
    <ul class="small text-muted">
        <li><b>Predicted Value</b>: the predicted value of this account based on linear regression</li>
        <li><b>Conservative</b>: 80% of predicted value</li>
        <li><b>Aggressive</b>: 120% of predicted value</li>
    </ul>
</div>

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
    try {
        var api = {!! json_encode($api) !!};

        if (!api.linear_regression || !api.linear_regression.predictions || Object.keys(api.linear_regression.predictions).length === 0) {
            console.log('No linear regression data available');
            return;
        }

        const rawLabelsLinReg = Object.keys(api.linear_regression.predictions);
        const sparseLabelsLinReg = createSparseLabels(rawLabelsLinReg);
        const predictedData = Object.values(api.linear_regression.predictions);
        const conservativeData = predictedData.map(value => value * 0.8);
        const aggressiveData = predictedData.map(value => value * 1.2);

        const linRegDatasets = [{
            label: 'Conservative (80%)',
            data: conservativeData,
            backgroundColor: graphColors[2],
            borderColor: graphColors[2],
            borderWidth: 2,
            pointRadius: 2,
            pointHoverRadius: 4,
            tension: 0.1,
            fill: false,
            borderDash: [5, 5],
        },{
            label: 'Predicted Value',
            data: predictedData,
            backgroundColor: graphColors[0],
            borderColor: graphColors[0],
            borderWidth: 2,
            pointRadius: 2,
            pointHoverRadius: 4,
            tension: 0.1,
            fill: false,
        },{
            label: 'Aggressive (120%)',
            data: aggressiveData,
            backgroundColor: graphColors[1],
            borderColor: graphColors[1],
            borderWidth: 2,
            pointRadius: 2,
            pointHoverRadius: 4,
            tension: 0.1,
            fill: false,
            borderDash: [5, 5],
        }];

        new Chart(
            document.getElementById('accountPerfGraphLinReg'),
            {
                type: 'line',
                data: {
                    labels: sparseLabelsLinReg,
                    datasets: linRegDatasets
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
                            ticks: {
                                callback: function(value) {
                                    return formatCurrencyShort(value);
                                },
                                color: chartTheme.fontColor,
                                font: {
                                    size: 12,
                                    weight: '500',
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)',
                            }
                        },
                        x: {
                            ticks: {
                                color: chartTheme.fontColor,
                                font: {
                                    size: 11,
                                },
                                maxRotation: 45,
                                minRotation: 0,
                            },
                            grid: {
                                display: false,
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: chartTheme.fontColor,
                                font: {
                                    size: 13,
                                    weight: '600',
                                },
                                usePointStyle: true,
                                padding: 15,
                            }
                        },
                        datalabels: {
                            display: false,
                        },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    return rawLabelsLinReg[context[0].dataIndex];
                                },
                                label: function(context) {
                                    return context.dataset.label + ': ' + formatCurrency(context.raw, 2);
                                }
                            }
                        }
                    }
                },
            }
        );
    } catch (e) {
        console.error('Error creating linear regression chart:', e);
    }
});
</script>
@endpush
@else
<div class="text-center text-muted py-4">
    <i class="fa fa-chart-line fa-2x mb-2" style="color: #cbd5e1;"></i>
    <p>Not enough data for forecast</p>
</div>
@endif
