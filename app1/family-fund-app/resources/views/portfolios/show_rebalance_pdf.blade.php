@extends('layouts.pdf_modern')

@section('report-type', 'Portfolio Rebalance Analysis')
@section('report-period', $api['asOf']->format('M d, Y') . ' - ' . $api['endDate']->format('M d, Y'))

@section('content')
    @php
        $portfolio = $api['portfolio'];
        $rebalance = $api['rebalance'];
        $lastDate = array_key_last($rebalance);
        $lastData = $lastDate ? $rebalance[$lastDate] : null;
    @endphp

    <!-- Portfolio Header -->
    <table width="100%" cellspacing="0" cellpadding="0" style="border: 2px solid #1e40af; margin-bottom: 16px;">
        <tr>
            <td style="padding: 12px; background-color: #1e40af;">
                <h2 style="margin: 0; color: #ffffff; font-size: 20px;">{{ $portfolio->fund->name ?? 'Portfolio #'.$portfolio->id }}</h2>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px; background-color: #f8fafc;">
                <table width="100%" cellspacing="0" cellpadding="8">
                    <tr>
                        <td width="33%" align="center" style="border-right: 1px solid #e2e8f0;">
                            <div style="font-size: 20px; font-weight: 700; color: #1e40af;">{{ $api['tradePortfolios']->count() }}</div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-top: 4px;">Trade Portfolios</div>
                        </td>
                        <td width="33%" align="center" style="border-right: 1px solid #e2e8f0;">
                            <div style="font-size: 20px; font-weight: 700; color: #1e40af;">{{ count($api['symbols']) }}</div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-top: 4px;">Assets Tracked</div>
                        </td>
                        <td width="33%" align="center">
                            <div style="font-size: 20px; font-weight: 700; color: #1e40af;">{{ count($rebalance) }}</div>
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-top: 4px;">Days Analyzed</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if($api['tradePortfolios']->isEmpty())
        <div style="padding: 20px; background: #fef3c7; border: 1px solid #d97706; border-radius: 6px; text-align: center;">
            <strong style="color: #d97706;">No trade portfolios found for the specified date range.</strong>
        </div>
    @else
        <!-- Trade Portfolio Timeline -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-header-title">Trade Portfolio Timeline</h4>
            </div>
            <div class="card-body">
                <table class="table-compact" width="100%">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Period</th>
                            @foreach($api['symbols'] as $symbolInfo)
                                <th class="text-center">{{ $symbolInfo['symbol'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($api['tradePortfolios'] as $tp)
                            <tr>
                                <td>#{{ $tp->id }}</td>
                                <td style="font-size: 11px;">
                                    {{ \Carbon\Carbon::parse($tp->start_dt)->format('M d, Y') }} -
                                    {{ \Carbon\Carbon::parse($tp->end_dt)->format('M d, Y') }}
                                </td>
                                @foreach($api['symbols'] as $symbolInfo)
                                    @php
                                        $item = $tp->tradePortfolioItems->firstWhere('symbol', $symbolInfo['symbol']);
                                    @endphp
                                    <td class="text-center" style="font-size: 11px;">
                                        @if($item)
                                            {{ number_format($item->target_share * 100, 1) }}%
                                            <span style="color: #64748b;">(± {{ number_format($item->deviation_trigger * 100, 1) }})</span>
                                        @else
                                            <span style="color: #94a3b8;">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Current Allocation Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-header-title">Current Allocation Status</h4>
                <span class="badge badge-secondary" style="float: right;">as of {{ $lastDate ?? 'N/A' }}</span>
            </div>
            <div class="card-body">
                <table width="100%">
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Type</th>
                            <th class="col-number">Target</th>
                            <th class="col-number">Deviation</th>
                            <th class="col-number">Min</th>
                            <th class="col-number">Max</th>
                            <th class="col-number">Current</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
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
                                    <td class="col-number">{{ number_format($targetPerc, 1) }}%</td>
                                    <td class="col-number">± {{ number_format(($currentData['max'] - $currentData['target']) * 100, 1) }}%</td>
                                    <td class="col-number" style="color: #64748b;">{{ number_format($minPerc, 1) }}%</td>
                                    <td class="col-number" style="color: #64748b;">{{ number_format($maxPerc, 1) }}%</td>
                                    <td class="col-number" style="color: {{ $isWithinBounds ? '#16a34a' : '#dc2626' }}; font-weight: 700;">
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

        <!-- Individual Asset Charts (2 per page) -->
        @php $chartIndex = 0; @endphp
        @foreach($api['symbols'] as $symbolInfo)
            @php
                $symbol = $symbolInfo['symbol'];
                $slug = \Str::slug($symbol);
                $chartFile = "rebalance_{$slug}.png";
            @endphp
            @if(isset($files[$chartFile]))
                @if($chartIndex % 2 === 0)
                    <div class="page-break"></div>
                @endif
                @php $chartIndex++; @endphp
                <div class="card mb-3">
                    <div class="card-header">
                        <h4 class="card-header-title">{{ $symbol }}</h4>
                        <span class="badge badge-primary" style="float: right;">{{ $symbolInfo['type'] }}</span>
                    </div>
                    <div class="card-body">
                        @php
                            $currentData = $lastData && isset($lastData[$symbol]) ? $lastData[$symbol] : null;
                        @endphp
                        @if($currentData)
                            @php $diff = ($currentData['perc'] - $currentData['target']) * 100; @endphp
                            <div style="margin-bottom: 6px; font-size: 11px;">
                                <span style="margin-right: 12px;"><strong>Target:</strong> {{ number_format($currentData['target'] * 100, 1) }}%</span>
                                <span style="margin-right: 12px; color: {{ ($currentData['perc'] >= $currentData['min'] && $currentData['perc'] <= $currentData['max']) ? '#16a34a' : '#dc2626' }}; font-weight: 700;">
                                    <strong>Current:</strong> {{ number_format($currentData['perc'] * 100, 2) }}%
                                </span>
                                <span style="margin-right: 12px;"><strong>Range:</strong> {{ number_format($currentData['min'] * 100, 1) }}% - {{ number_format($currentData['max'] * 100, 1) }}%</span>
                                <span style="color: {{ abs($diff) < ($currentData['max'] - $currentData['target']) * 100 ? '#16a34a' : '#dc2626' }};">
                                    <strong>Dev:</strong> {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2) }}%
                                </span>
                            </div>
                        @endif
                        <div class="chart-container" style="max-height: 280px;">
                            <img src="{{ $files[$chartFile] }}" alt="{{ $symbol }} Rebalance Chart" style="max-height: 260px;"/>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endif
@endsection
