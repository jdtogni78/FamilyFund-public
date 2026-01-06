<div>
    <canvas id="tradePortfolioGraph{{ $tradePortfolio->id }}"></canvas>
</div>

@push('scripts')
    <script type="text/javascript">
    (function() {
        var items = {!! json_encode($tradePortfolio->tradePortfolioItems) !!};
        var assets_labels = items.map(function(e) {return e.symbol;});
        var assets_shares = items.map(function(e) {return e.target_share * 100.0;});

        assets_labels.push('Cash');
        assets_shares.push({{ $tradePortfolio->cash_target * 100.0 }});

        new Chart(
            document.getElementById('tradePortfolioGraph{{ $tradePortfolio->id }}'),
            {
                type: 'doughnut',
                data: {
                    labels: assets_labels,
                    datasets: [{
                        data: assets_shares,
                        backgroundColor: graphColors,
                        hoverOffset: 3
                    }]
                },
            });
    })();
    </script>
@endpush
