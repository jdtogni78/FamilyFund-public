<div>
    <canvas id="tradePortfolioGroupGraph{{ $tradePortfolio->id }}"></canvas>
</div>

@push('scripts')
    <script type="text/javascript">
        var api_groups = {!! json_encode($tradePortfolio->groups) !!};
        var assets_labels = Object.keys(api_groups);
        var assets_shares = Object.values(api_groups);

        var myChart = new Chart(
            document.getElementById('tradePortfolioGroupGraph{{ $tradePortfolio->id }}'),
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
            }  );
    </script>
@endpush
