<x-app-layout>

@section('content')
    <script type="text/javascript">
        var api = {!! json_encode($api) !!};
    </script>
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('portfolios.index') }}">Portfolios</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('portfolios.show', $api['portfolio']->id) }}">{{ $api['portfolio']->fund->name ?? 'Portfolio #'.$api['portfolio']->id }}</a>
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
                                Portfolio Rebalance Analysis (Multi-Period)
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Portfolio:</strong> {{ $api['portfolio']->fund->name ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>Source:</strong> {{ $api['portfolio']->source ?? 'N/A' }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Analysis Period:</strong></p>
                                    <p class="mb-1">
                                        {{ $api['asOf']->format('M d, Y') }} -
                                        {{ $api['endDate']->format('M d, Y') }}
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Trade Portfolios Covered:</strong> {{ $api['tradePortfolios']->count() }}</p>
                                    @if($api['tradePortfolios']->count() > 0)
                                        <p class="mb-1 small text-muted">
                                            IDs: {{ $api['tradePortfolios']->pluck('id')->implode(', ') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($api['tradePortfolios']->isEmpty())
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle mr-2"></i>
                    No trade portfolios found for the specified date range.
                </div>
            @else
                <!-- Trade Portfolio Timeline -->
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-history mr-2"></i>
                                <strong>Trade Portfolio Timeline</strong>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Period</th>
                                                @foreach($api['symbols'] as $symbolInfo)
                                                    <th class="text-center">{{ $symbolInfo['symbol'] }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($api['tradePortfolios'] as $tp)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('tradePortfolios.show', $tp->id) }}">#{{ $tp->id }}</a>
                                                    </td>
                                                    <td class="small">
                                                        {{ \Carbon\Carbon::parse($tp->start_dt)->format('M d, Y') }} -
                                                        {{ \Carbon\Carbon::parse($tp->end_dt)->format('M d, Y') }}
                                                    </td>
                                                    @foreach($api['symbols'] as $symbolInfo)
                                                        @php
                                                            $item = $tp->tradePortfolioItems->firstWhere('symbol', $symbolInfo['symbol']);
                                                        @endphp
                                                        <td class="text-center small">
                                                            @if($item)
                                                                {{ number_format($item->target_share * 100, 1) }}%
                                                                <span class="text-muted">(± {{ number_format($item->deviation_trigger * 100, 1) }}%)</span>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Status Summary -->
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-table mr-2"></i>
                                <strong>Current Allocation Status</strong>
                                <span class="text-muted ml-2">(as of {{ array_key_last($api['rebalance']) ?? 'N/A' }})</span>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Symbol</th>
                                                <th>Type</th>
                                                <th class="text-right">Target %</th>
                                                <th class="text-right">Deviation</th>
                                                <th class="text-right">Min %</th>
                                                <th class="text-right">Max %</th>
                                                <th class="text-right">Current %</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $lastDate = array_key_last($api['rebalance']);
                                                $lastData = $lastDate ? $api['rebalance'][$lastDate] : null;
                                            @endphp
                                            @foreach($api['symbols'] as $symbolInfo)
                                                @php
                                                    $symbol = $symbolInfo['symbol'];
                                                    $currentData = $lastData && isset($lastData[$symbol]) ? $lastData[$symbol] : null;
                                                @endphp
                                                @if($currentData)
                                                @php
                                                    $currentPerc = $currentData['perc'] * 100;
                                                    $targetPerc = $currentData['target'] * 100;
                                                    $minPerc = $currentData['min'] * 100;
                                                    $maxPerc = $currentData['max'] * 100;
                                                    $isWithinBounds = $currentPerc >= $minPerc && $currentPerc <= $maxPerc;
                                                @endphp
                                                <tr>
                                                    <td><strong>{{ $symbol }}</strong></td>
                                                    <td>{{ $symbolInfo['type'] }}</td>
                                                    <td class="text-right">{{ number_format($targetPerc, 1) }}%</td>
                                                    <td class="text-right">± {{ number_format(($currentData['max'] - $currentData['target']) * 100, 1) }}%</td>
                                                    <td class="text-right text-muted">{{ number_format($minPerc, 1) }}%</td>
                                                    <td class="text-right text-muted">{{ number_format($maxPerc, 1) }}%</td>
                                                    <td class="text-right {{ $isWithinBounds ? 'text-success' : 'text-danger font-weight-bold' }}">
                                                        {{ number_format($currentPerc, 2) }}%
                                                    </td>
                                                    <td class="text-center">
                                                        @if($isWithinBounds)
                                                            <span class="badge badge-success">OK</span>
                                                        @elseif($currentPerc < $minPerc)
                                                            <span class="badge badge-danger">Under</span>
                                                        @else
                                                            <span class="badge badge-warning">Over</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Navigation -->
                <div class="row mb-3">
                    <div class="col-lg-12">
                        <div class="card" id="stockNav" style="position: sticky; top: 0; z-index: 100;">
                            <div class="card-body py-2">
                                <div class="d-flex flex-wrap align-items-center">
                                    <span class="mr-3 text-muted small">Jump to:</span>
                                    @foreach($api['symbols'] as $idx => $symbolInfo)
                                        <a href="#chart-{{ Str::slug($symbolInfo['symbol']) }}"
                                           class="btn btn-sm btn-outline-primary mr-2 mb-1 stock-nav-btn"
                                           data-symbol="{{ Str::slug($symbolInfo['symbol']) }}">
                                            {{ $symbolInfo['symbol'] }}
                                        </a>
                                    @endforeach
                                    <span class="ml-auto">
                                        <button type="button" class="btn btn-outline-secondary btn-sm mr-1" id="expandAllCharts" title="Expand All">
                                            <i class="fa fa-expand"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="collapseAllCharts" title="Collapse All">
                                            <i class="fa fa-compress"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Individual Asset Charts -->
                @foreach($api['symbols'] as $symbolInfo)
                    @php $symbol = $symbolInfo['symbol']; @endphp
                    <div class="row mb-4" id="chart-{{ Str::slug($symbol) }}">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center"
                                     style="cursor: pointer;"
                                     data-toggle="collapse"
                                     data-target="#chartCollapse{{ Str::slug($symbol) }}"
                                     aria-expanded="true"
                                     aria-controls="chartCollapse{{ Str::slug($symbol) }}">
                                    <div>
                                        <i class="fa fa-chevron-down mr-2 collapse-icon"></i>
                                        <i class="fa fa-chart-area mr-2"></i>
                                        <strong>{{ $symbol }}</strong>
                                        <span class="text-muted ml-2">(targets vary by trade portfolio period)</span>
                                    </div>
                                    <span class="badge badge-info">{{ $symbolInfo['type'] }}</span>
                                </div>
                                <div class="collapse show chart-collapse" id="chartCollapse{{ Str::slug($symbol) }}">
                                    <div class="card-body">
                                        @include("portfolios.partials.rebalance_chart", ['symbol' => $symbol, 'symbolInfo' => $symbolInfo])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Collapsible Portfolio Assets Table -->
                <div class="row mb-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <a class="btn btn-link text-muted" data-toggle="collapse" href="#collapseAssets"
                                   role="button" aria-expanded="false" aria-controls="collapseAssets">
                                    <i class="fa fa-chevron-down mr-2"></i>
                                    Portfolio Asset History
                                </a>
                            </div>
                            <div class="collapse" id="collapseAssets">
                                <div class="card-body">
                                    @php($portfolioAssets = $api['portfolioAssets'])
                                    @include('portfolio_assets.table')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

@push('scripts')
<script type="text/javascript">
$(document).ready(function() {
    // Smooth scroll for stock navigation
    $('.stock-nav-btn').click(function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        const $target = $(target);

        // Expand the chart if collapsed
        const collapseId = '#chartCollapse' + $(this).data('symbol');
        $(collapseId).collapse('show');

        // Scroll to chart with offset for sticky nav
        const navHeight = $('#stockNav').outerHeight() + 20;
        $('html, body').animate({
            scrollTop: $target.offset().top - navHeight
        }, 300);

        // Highlight active button
        $('.stock-nav-btn').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
    });

    // Expand all charts
    $('#expandAllCharts').click(function() {
        $('.chart-collapse').collapse('show');
    });

    // Collapse all charts
    $('#collapseAllCharts').click(function() {
        $('.chart-collapse').collapse('hide');
    });

    // Rotate chevron icon on collapse/expand
    $('.chart-collapse').on('show.bs.collapse', function() {
        $(this).prev('.card-header').find('.collapse-icon')
            .removeClass('fa-chevron-right').addClass('fa-chevron-down');
    });

    $('.chart-collapse').on('hide.bs.collapse', function() {
        $(this).prev('.card-header').find('.collapse-icon')
            .removeClass('fa-chevron-down').addClass('fa-chevron-right');
    });

    // Update active nav button on scroll
    $(window).scroll(function() {
        const navHeight = $('#stockNav').outerHeight() + 50;
        let currentSection = null;

        $('[id^="chart-"]').each(function() {
            const sectionTop = $(this).offset().top - navHeight;
            if ($(window).scrollTop() >= sectionTop) {
                currentSection = $(this).attr('id');
            }
        });

        if (currentSection) {
            $('.stock-nav-btn').removeClass('btn-primary').addClass('btn-outline-primary');
            $('.stock-nav-btn[href="#' + currentSection + '"]')
                .removeClass('btn-outline-primary').addClass('btn-primary');
        }
    });
});
</script>
@endpush
</x-app-layout>
