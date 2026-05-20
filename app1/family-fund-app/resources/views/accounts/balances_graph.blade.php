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

                // Build net balance history from bucket-specific running balances.
                const balances = {};
                const borrowedBalances = {};
                let ownShares = 0;
                let borrowedShares = 0;
                let hasBorrowedShares = false;
                let lastKey = '';

                for (const transaction of transactions) {
                    if (!transaction.balance) {
                        continue;
                    }

                    const dateKey = transaction.timestamp.substr(0, 10);
                    const bucket = transaction.balance.type || 'OWN';
                    const shares = Number(transaction.balance.shares || 0);

                    if (bucket === 'BOR') {
                        borrowedShares = shares;
                        hasBorrowedShares = hasBorrowedShares || borrowedShares > 0;
                    } else if (bucket === 'OWN') {
                        ownShares = shares;
                    }

                    balances[dateKey] = ownShares - borrowedShares;
                    borrowedBalances[dateKey] = borrowedShares;
                    lastKey = dateKey;
                }

                // Extend to current date
                if (api.as_of && lastKey) {
                    balances[api.as_of] = balances[lastKey];
                    borrowedBalances[api.as_of] = borrowedBalances[lastKey] || 0;
                }

                const sortedDates = Object.keys(balances).sort();
                if (sortedDates.length === 0) {
                    console.log('No balance data');
                    $('#balancesGraph').hide();
                    $('#balancesGraphNoData').show();
                    return;
                }

                const balanceValues = sortedDates.map(d => balances[d]);
                const borrowedValues = sortedDates.map(d => borrowedBalances[d] || 0);
                const sparseLabels = createSparseLabels(sortedDates, 24);

                const config = {
                    type: 'line',
                    data: {
                        labels: sparseLabels,
                        datasets: [{
                            label: 'Net Shares',
                            data: balanceValues,
                            fill: true,
                            backgroundColor: 'rgba(13, 148, 136, 0.15)',
                            borderColor: '#0d9488',
                            borderWidth: 2,
                            stepped: true,
                            pointRadius: sortedDates.length > 30 ? 0 : 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#0d9488',
                        }].concat(hasBorrowedShares ? [{
                            label: 'Borrowed Shares',
                            data: borrowedValues,
                            fill: false,
                            borderColor: '#dc2626',
                            borderDash: [6, 4],
                            borderWidth: 2,
                            stepped: true,
                            pointRadius: sortedDates.length > 30 ? 0 : 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#dc2626',
                        }] : [])
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { display: hasBorrowedShares },
                            tooltip: {
                                callbacks: {
                                    title: function(items) {
                                        return sortedDates[items[0].dataIndex];
                                    },
                                    label: function(context) {
                                        const values = context.dataset.data;
                                        const idx = context.dataIndex;
                                        const change = idx === 0 ? 0 : values[idx] - values[idx - 1];
                                        const lines = [context.dataset.label + ': ' + formatNumber(context.raw, 4)];
                                        if (change !== 0) {
                                            const sign = change > 0 ? '+' : '';
                                            lines.push('Change: ' + sign + formatNumber(change, 4));
                                        }
                                        return lines;
                                    }
                                }
                            },
                            datalabels: {
                                display: function(context) {
                                    // Only show labels at step changes (where value differs from previous)
                                    const idx = context.dataIndex;
                                    if (idx === 0) return true;
                                    return context.dataset.data[idx] !== context.dataset.data[idx - 1];
                                },
                                anchor: 'end',
                                align: 'top',
                                offset: 4,
                                color: '#0f766e',
                                font: { size: 11, weight: 'bold' },
                                formatter: function(value) {
                                    return formatNumber(value, 2);
                                }
                            }
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
                                beginAtZero: false,
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
