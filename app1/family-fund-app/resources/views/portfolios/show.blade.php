<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('portfolios.index') }}">Portfolios</a>
        </li>
        <li class="breadcrumb-item active">{{ $portfolio->display_name ?? $portfolio->source }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-briefcase me-2"></i>
                                <strong>{{ $portfolio->display_name ?? $portfolio->source }}</strong>
                                @if($portfolio->display_name)
                                    <code class="ms-2 small">{{ $portfolio->source }}</code>
                                @endif
                                @if($portfolio->fund)
                                    <span class="text-body-secondary ms-2">
                                        (<a href="{{ route('funds.show', $portfolio->fund_id) }}">{{ $portfolio->fund->name }}</a>)
                                    </span>
                                @endif
                            </div>
                            <div>
                                @include('portfolios.actions', ['portfolio' => $portfolio])
                                <a href="{{ route('portfolios.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @include('portfolios.show_fields')
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance History Chart (2nd card) -->
            @php
                $totalBalanceCount = $portfolio->portfolioBalances()->count();
                $portfolioBalances = $portfolio->portfolioBalances()
                    ->orderBy('start_dt', 'desc')
                    ->limit(50)
                    ->get();
                // Prepare chart data (chronological order, sampled for performance)
                $allBalances = $portfolio->portfolioBalances()
                    ->orderBy('start_dt', 'asc')
                    ->get();
                // Sample to ~12 points if too many records
                $maxPoints = 12;
                if ($allBalances->count() > $maxPoints) {
                    $step = ceil($allBalances->count() / $maxPoints);
                    $chartBalances = $allBalances->filter(fn($item, $key) => $key % $step === 0 || $key === $allBalances->count() - 1);
                } else {
                    $chartBalances = $allBalances;
                }
                $chartLabels = $chartBalances->pluck('start_dt')->map(fn($d) => $d->format('M Y'))->toArray();
                $chartValues = $chartBalances->pluck('balance')->map(fn($v) => (float)$v)->toArray();
            @endphp
            @if($portfolioBalances->count() > 0)
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white;">
                            <strong><i class="fa fa-chart-line me-2"></i>Balance History</strong>
                            <a class="btn btn-sm btn-outline-light" data-bs-toggle="collapse" href="#collapseBalanceChart"
                               role="button" aria-expanded="true" aria-controls="collapseBalanceChart">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseBalanceChart">
                            <div class="card-body">
                                <div style="height: 300px;">
                                    <canvas id="balanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Balance Validation Section -->
            @php
                $asOf = request()->get('as_of', now()->format('Y-m-d'));
                $validation = $portfolio->valueAsOf($asOf, true);
            @endphp
            @if($validation['has_set_balance'])
            <div class="row">
                <div class="col-lg-12">
                    <div class="card {{ $validation['is_valid'] ? '' : 'border-warning' }}">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: {{ $validation['is_valid'] ? '#f0fdfa' : '#fef3c7' }};">
                            <div>
                                <i class="fa fa-balance-scale me-2"></i>
                                <strong>Balance Validation</strong>
                                @if($validation['is_valid'])
                                    <span class="badge bg-success ms-2">Valid</span>
                                @else
                                    <span class="badge bg-warning text-dark ms-2">Mismatch</span>
                                @endif
                                <span class="text-body-secondary ms-2 small">as of {{ $asOf }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="text-body-secondary small">Set Balance</label>
                                    <div class="fw-bold text-primary">${{ number_format($validation['set_balance'], 2) }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-body-secondary small">Calculated (from assets)</label>
                                    <div class="fw-bold">${{ number_format($validation['calculated'], 2) }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="text-body-secondary small">Difference</label>
                                    @php
                                        $diffClass = abs($validation['percent_diff']) < 1 ? 'text-success' : (abs($validation['percent_diff']) < 5 ? 'text-warning' : 'text-danger');
                                    @endphp
                                    <div class="fw-bold {{ $diffClass }}">
                                        ${{ number_format($validation['difference'], 2) }}
                                        ({{ number_format($validation['percent_diff'], 2) }}%)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Portfolio Assets Section -->
            @php
                $portfolioAssets = $portfolio->portfolioAssets()
                    ->where('start_dt', '<=', $asOf)
                    ->where('end_dt', '>=', $asOf)
                    ->with('asset')
                    ->orderBy('position', 'desc')
                    ->get();
            @endphp
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-coins me-2"></i>
                                <strong>Assets</strong>
                                <span class="badge bg-primary ms-2">{{ $portfolioAssets->count() }}</span>
                                <span class="text-body-secondary ms-2 small">as of {{ $asOf }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            @if($portfolioAssets->count() > 0)
                                <div class="table-responsive-sm">
                                    <table class="table table-striped table-sm" id="portfolio-assets-table">
                                        <thead>
                                            <tr>
                                                <th>Symbol</th>
                                                <th>Type</th>
                                                <th class="text-end">Position</th>
                                                <th class="text-end">Price</th>
                                                <th class="text-end">Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @php $totalValue = 0; @endphp
                                        @foreach($portfolioAssets as $pa)
                                            @php
                                                $asset = $pa->asset;
                                                $priceRecord = $asset ? $asset->priceAsOf($asOf)?->first() : null;
                                                $price = $priceRecord?->price ?? 0;
                                                $value = $pa->position * $price;
                                                $totalValue += $value;
                                            @endphp
                                            <tr>
                                                <td>
                                                    @if($asset)
                                                        <a href="{{ route('assets.show', $asset->id) }}"><strong>{{ $asset->name }}</strong></a>
                                                    @else
                                                        <strong>N/A</strong>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ $asset->type ?? 'N/A' }}</span>
                                                </td>
                                                <td class="text-end">{{ number_format($pa->position, 4) }}</td>
                                                <td class="text-end">${{ number_format($price, 2) }}</td>
                                                <td class="text-end">${{ number_format($value, 2) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-dark">
                                                <th colspan="4" class="text-end">Total Value:</th>
                                                <th class="text-end">${{ number_format($totalValue, 2) }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <p class="text-body-secondary mb-0">No assets in this portfolio as of {{ $asOf }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance Records Table -->
            @if($portfolioBalances->count() > 0)
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white;">
                            <div>
                                <i class="fa fa-history me-2"></i>
                                <strong>Balance Records</strong>
                                <span class="badge bg-light text-dark ms-2">{{ $totalBalanceCount }}</span>
                            </div>
                            <a class="btn btn-sm btn-outline-light" data-bs-toggle="collapse" href="#collapseBalanceTable"
                               role="button" aria-expanded="false" aria-controls="collapseBalanceTable">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse" id="collapseBalanceTable">
                            <div class="card-body">
                                <div class="table-responsive-sm">
                                    <table class="table table-striped table-sm" id="portfolio-balances-table">
                                        <thead>
                                            <tr>
                                                <th class="text-end">Balance</th>
                                                <th>Start Date</th>
                                                <th>End Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($portfolioBalances as $bal)
                                            <tr>
                                                <td class="text-end fw-bold" data-order="{{ $bal->balance }}">${{ number_format($bal->balance, 2) }}</td>
                                                <td data-order="{{ $bal->start_dt->format('Y-m-d') }}">{{ $bal->start_dt->format('Y-m-d') }}</td>
                                                <td data-order="{{ $bal->end_dt->format('Y-m-d') }}">
                                                    @if($bal->end_dt->format('Y') === '9999')
                                                        <span class="badge bg-success">Current</span>
                                                    @else
                                                        {{ $bal->end_dt->format('Y-m-d') }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($totalBalanceCount > 50)
                                    <p class="text-muted small mt-2">Showing 50 of {{ $totalBalanceCount }} records</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @push('scripts')
            <script>
            $(document).ready(function() {
                $('#portfolio-balances-table').DataTable({
                    order: [[1, 'desc']],
                    pageLength: 25,
                    paging: true,
                    searching: true,
                    info: true
                });

                // Balance History Chart
                const ctx = document.getElementById('balanceChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($chartLabels),
                        datasets: [{
                            label: 'Balance',
                            data: @json($chartValues),
                            borderColor: '#0d9488',
                            backgroundColor: 'rgba(13, 148, 136, 0.15)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#0d9488'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return '$' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    maxTicksLimit: 6,
                                    maxRotation: 0
                                },
                                grid: { display: false }
                            },
                            y: {
                                ticks: {
                                    callback: function(value) {
                                        if (value >= 1000000) {
                                            return '$' + (value / 1000000).toFixed(1) + 'M';
                                        }
                                        return '$' + (value / 1000).toFixed(0) + 'K';
                                    }
                                }
                            }
                        }
                    }
                });
            });
            </script>
            @endpush
            @endif

            <!-- Trade Portfolios Section -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-chart-pie me-2"></i>
                                <strong>Trade Portfolios</strong>
                                <span class="badge bg-primary ms-2">{{ $portfolio->tradePortfolios()->count() }}</span>
                            </div>
                            <a href="{{ route('tradePortfolios.create') }}?portfolio_id={{ $portfolio->id }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus me-1"></i> New Trade Portfolio
                            </a>
                        </div>
                        <div class="card-body">
                            @php($tradePortfolios = $portfolio->tradePortfolios()->get()->sortByDesc('end_dt'))
                            @include('trade_portfolios.table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
