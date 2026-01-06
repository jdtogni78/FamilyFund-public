<div>
    <canvas id="accountGraph"></canvas>
</div>

@push('scripts')
<script type="text/javascript">
var api = {!! json_encode($api) !!};

// Calculate total shares for percentage calculation
const totalShares = api.summary.total_shares;
const unallocatedShares = api.summary.unallocated_shares;

// Prepare data with percentages
let accountData = api.balances.map(function(e) {
    return {
        label: e.nickname,
        shares: e.shares,
        percent: (e.shares / totalShares) * 100
    };
});

// Group small accounts (<3%) into "Others"
const threshold = 3;
let othersShares = 0;
let othersCount = 0;
let filteredData = [];

accountData.forEach(function(item) {
    if (item.percent < threshold) {
        othersShares += item.shares;
        othersCount++;
    } else {
        filteredData.push(item);
    }
});

// Add "Others" if there are small accounts
if (othersCount > 0) {
    filteredData.push({
        label: 'Others (' + othersCount + ' accounts)',
        shares: othersShares,
        percent: (othersShares / totalShares) * 100
    });
}

// Add unallocated shares
if (unallocatedShares > 0) {
    filteredData.push({
        label: 'Unallocated',
        shares: unallocatedShares,
        percent: (unallocatedShares / totalShares) * 100
    });
}

const _labels = filteredData.map(function(e) { return e.label; });
const _shares = filteredData.map(function(e) { return e.shares; });
const _percents = filteredData.map(function(e) { return e.percent; });

const acct_config = {
    type: 'doughnut',
    data: {
        labels: _labels,
        datasets: [{
            data: _shares,
            backgroundColor: graphColors.slice(0, _labels.length),
            borderColor: '#ffffff',
            borderWidth: 2,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    color: '#000000',
                    font: {
                        size: 14,
                        weight: 'bold',
                    },
                    padding: 10,
                }
            },
            datalabels: {
                color: '#000000',
                font: {
                    size: 12,
                    weight: 'bold',
                },
                formatter: function(value, context) {
                    const percent = _percents[context.dataIndex];
                    if (percent < 5) return ''; // Hide labels on small slices
                    return percent.toFixed(1) + '%';
                },
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const percent = _percents[context.dataIndex];
                        return context.label + ': ' + formatNumber(context.raw, 2) + ' shares (' + percent.toFixed(2) + '%)';
                    }
                }
            }
        }
    },
};

var accountChart = new Chart(
    document.getElementById('accountGraph'),
    acct_config
);
</script>
@endpush
