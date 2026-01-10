@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
            try {
                const api = {!! json_encode($api) !!};
                const transactions = api.transactions || [];

                if (transactions.length === 0) {
                    console.log('No transactions data');
                    $('#balancesGraph').hide();
                    $('#balancesGraphNoData').show();
                    return;
                }

                // Build balance history from transactions
                const allLabels = transactions.map(t => t.timestamp.substr(0, 10));
                const allBalances = transactions.map(t => t.balance?.shares || 0);

                // Get unique sorted dates and max balance for each date
                const balances = {};
                let lastKey = '';
                for (let i = 0; i < allLabels.length; i++) {
                    balances[allLabels[i]] = Math.max(balances[allLabels[i]] || 0, allBalances[i]);
                    lastKey = allLabels[i];
                }
                // Extend to current date
                if (api.as_of && lastKey) {
                    balances[api.as_of] = balances[lastKey];
                }

                const sortedDates = Object.keys(balances).sort();
                if (sortedDates.length === 0) {
                    console.log('No balance data');
                    $('#balancesGraph').hide();
                    $('#balancesGraphNoData').show();
                    return;
                }

                const balanceValues = sortedDates.map(d => balances[d]);
                const sparseLabels = createSparseLabels(sortedDates, 24);

                // Calculate changes for tooltip
                const balanceChanges = balanceValues.map((val, idx) => {
                    if (idx === 0) return 0;
                    return val - balanceValues[idx - 1];
                });

                const config = {
                    type: 'line',
                    data: {
                        labels: sparseLabels,
                        datasets: [{
                            label: 'Share Balance',
                            data: balanceValues,
                            fill: true,
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            borderColor: chartTheme.primary,
                            borderWidth: 2,
                            stepped: true,
                            pointRadius: sortedDates.length > 50 ? 0 : 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: chartTheme.primary,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: function(items) {
                                        return sortedDates[items[0].dataIndex];
                                    },
                                    label: function(context) {
                                        const idx = context.dataIndex;
                                        const change = balanceChanges[idx];
                                        const lines = ['Shares: ' + formatNumber(context.raw, 4)];
                                        if (change !== 0) {
                                            const sign = change > 0 ? '+' : '';
                                            lines.push('Change: ' + sign + formatNumber(change, 4));
                                        }
                                        return lines;
                                    }
                                }
                            },
                            datalabels: { display: false }
                        },
                        scales: {
                            x: {
                                type: 'category',
                                grid: { display: false },
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 0,
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return formatNumber(value, 2);
                                    }
                                },
                                grid: { color: 'rgba(0,0,0,0.05)' }
                            }
                        }
                    }
                };

                new Chart(document.getElementById('balancesGraph'), config);
            } catch (e) {
                console.error('Error creating balances chart:', e);
                $('#balancesGraph').hide();
                $('#balancesGraphNoData').show();
            }
        });
    </script>
@endpush
