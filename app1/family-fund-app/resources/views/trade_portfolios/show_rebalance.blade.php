<x-app-layout>

@section('content')
    <script type="text/javascript">
        var api = {!! json_encode($api) !!};
    </script>
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('tradePortfolios.index') }}">Trade Portfolios</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('tradePortfolios.show', $api['tradePortfolio']->id) }}">{{ $api['tradePortfolio']->portfolio->fund->name ?? 'Portfolio' }}</a>
        </li>
        <li class="breadcrumb-item active">Rebalance Analysis</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')

            <!-- Report Header -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="fa fa-chart-line mr-2"></i>
                                Portfolio Rebalance Analysis
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Portfolio:</strong> {{ $api['tradePortfolio']->portfolio->fund->name ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>Trade Portfolio:</strong> #{{ $api['tradePortfolio']->id }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Analysis Period:</strong></p>
                                    <p class="mb-1">
                                        {{ $api['asOf']->format('M d, Y') }} -
                                        {{ array_key_last($api['rebalance']) ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Trade Portfolio Period:</strong></p>
                                    <p class="mb-1">
                                        {{ \Carbon\Carbon::parse($api['tradePortfolio']->start_dt)->format('M d, Y') }} -
                                        {{ \Carbon\Carbon::parse($api['tradePortfolio']->end_dt)->format('M d, Y') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Statistics -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="fa fa-table mr-2"></i>
                            <strong>Asset Allocation Targets</strong>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Symbol</th>
                                            <th>Type</th>
                                            <th class="text-right">Target %</th>
                                            <th class="text-right">Deviation Trigger</th>
                                            <th class="text-right">Min %</th>
                                            <th class="text-right">Max %</th>
                                            @php
                                                $lastDate = array_key_last($api['rebalance']);
                                                $lastData = $lastDate ? $api['rebalance'][$lastDate] : null;
                                            @endphp
                                            <th class="text-right">Current %</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($api['tradePortfolio']->tradePortfolioItems()->get() as $item)
                                            @php
                                                $currentPerc = $lastData && isset($lastData[$item->symbol])
                                                    ? $lastData[$item->symbol]['perc'] * 100
                                                    : null;
                                                $minPerc = ($item->target_share - $item->deviation_trigger) * 100;
                                                $maxPerc = ($item->target_share + $item->deviation_trigger) * 100;
                                                $isWithinBounds = $currentPerc !== null && $currentPerc >= $minPerc && $currentPerc <= $maxPerc;
                                            @endphp
                                            <tr>
                                                <td><strong>{{ $item->symbol }}</strong></td>
                                                <td>{{ $item->type }}</td>
                                                <td class="text-right">{{ number_format($item->target_share * 100, 1) }}%</td>
                                                <td class="text-right">± {{ number_format($item->deviation_trigger * 100, 1) }}%</td>
                                                <td class="text-right text-muted">{{ number_format($minPerc, 1) }}%</td>
                                                <td class="text-right text-muted">{{ number_format($maxPerc, 1) }}%</td>
                                                <td class="text-right {{ $currentPerc !== null ? ($isWithinBounds ? 'text-success' : 'text-danger font-weight-bold') : '' }}">
                                                    {{ $currentPerc !== null ? number_format($currentPerc, 2) . '%' : 'N/A' }}
                                                </td>
                                                <td class="text-center">
                                                    @if($currentPerc !== null)
                                                        @if($isWithinBounds)
                                                            <span class="badge badge-success">OK</span>
                                                        @elseif($currentPerc < $minPerc)
                                                            <span class="badge badge-danger">Under</span>
                                                        @else
                                                            <span class="badge badge-warning">Over</span>
                                                        @endif
                                                    @else
                                                        <span class="badge badge-secondary">N/A</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Individual Asset Charts -->
            @foreach($api['tradePortfolio']->tradePortfolioItems()->get() as $item)
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fa fa-chart-area mr-2"></i>
                                    <strong>{{ $item->symbol }}</strong>
                                    <span class="text-muted ml-2">
                                        (Target: {{ number_format($item->target_share * 100, 1) }}% ± {{ number_format($item->deviation_trigger * 100, 1) }}%)
                                    </span>
                                </div>
                                <span class="badge badge-info">{{ $item->type }}</span>
                            </div>
                            <div class="card-body">
                                @include("trade_portfolio_items.rebalance_line_graph")
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Collapsible Portfolio Assets Table -->
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header" id="heading{{ $item->id }}">
                                <a class="btn btn-link text-muted" data-toggle="collapse" href="#collapse{{ $item->id }}"
                                   role="button" aria-expanded="false" aria-controls="collapse{{ $item->id }}">
                                    <i class="fa fa-chevron-down mr-2"></i>
                                    Portfolio Asset History for {{ $item->symbol }}
                                </a>
                            </div>
                            <div class="collapse" id="collapse{{ $item->id }}">
                                <div class="card-body">
                                    @php($portfolioAssets = $api['portfolioAssets']->filter(fn ($pa) => $pa->asset()->first()->name == $item->symbol))
                                    @include('portfolio_assets.table')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
