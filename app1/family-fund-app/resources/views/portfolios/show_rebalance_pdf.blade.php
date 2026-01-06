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
        <!-- Trade Portfolio Timeline (split into chunks if many symbols) -->
        @php
            $maxSymbolsPerTable = 6;
            $symbolChunks = array_chunk($api['symbols']->toArray(), $maxSymbolsPerTable);
        @endphp
        @foreach($symbolChunks as $chunkIdx => $symbolChunk)
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-header-title">Trade Portfolio Timeline @if(count($symbolChunks) > 1)<span style="font-weight: normal; font-size: 12px; color: #64748b;">({{ $chunkIdx + 1 }}/{{ count($symbolChunks) }})</span>@endif</h4>
                </div>
                <div class="card-body" style="padding: 0;">
                    <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 10px; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #1e40af; color: white;">
                                <th style="padding: 6px 8px; text-align: left; border-right: 1px solid rgba(255,255,255,0.2);">ID</th>
                                <th style="padding: 6px 8px; text-align: left; border-right: 1px solid rgba(255,255,255,0.2);">Period</th>
                                @foreach($symbolChunk as $symbolInfo)
                                    <th style="padding: 6px 4px; text-align: center; border-right: 1px solid rgba(255,255,255,0.2);">{{ $symbolInfo['symbol'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($api['tradePortfolios'] as $idx => $tp)
                                <tr style="background: {{ $idx % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 5px 8px; border-right: 1px solid #e2e8f0; font-weight: 600;">#{{ $tp->id }}</td>
                                    <td style="padding: 5px 8px; border-right: 1px solid #e2e8f0; white-space: nowrap;">
                                        {{ \Carbon\Carbon::parse($tp->start_dt)->format('m/d/y') }} - {{ \Carbon\Carbon::parse($tp->end_dt)->format('m/d/y') }}
                                    </td>
                                    @foreach($symbolChunk as $symbolInfo)
                                        @php
                                            $item = $tp->tradePortfolioItems->firstWhere('symbol', $symbolInfo['symbol']);
                                        @endphp
                                        <td style="padding: 5px 4px; text-align: center; border-right: 1px solid #e2e8f0;">
                                            @if($item)
                                                <strong>{{ number_format($item->target_share * 100, 0) }}%</strong><span style="color: #64748b;">±{{ number_format($item->deviation_trigger * 100, 0) }}%</span>
                                            @else
                                                <span style="color: #cbd5e1;">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <!-- Current Allocation Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-header-title">Current Allocation Status <span style="font-weight: normal; font-size: 12px; color: #64748b;">(as of {{ $lastDate ?? 'N/A' }})</span></h4>
            </div>
            <div class="card-body" style="padding: 0;">
                <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #1e40af; color: white;">
                            <th style="padding: 8px; text-align: left;">Symbol</th>
                            <th style="padding: 8px; text-align: left;">Type</th>
                            <th style="padding: 8px; text-align: right;">Target</th>
                            <th style="padding: 8px; text-align: right;">Range</th>
                            <th style="padding: 8px; text-align: right;">Current</th>
                            <th style="padding: 8px; text-align: center; width: 60px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rowIdx = 0; @endphp
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
                                <tr style="background: {{ $rowIdx % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom: 1px solid #e2e8f0;">
                                    <td style="padding: 6px 8px; font-weight: 600;">{{ $symbol }}</td>
                                    <td style="padding: 6px 8px; color: #64748b;">{{ $symbolInfo['type'] }}</td>
                                    <td style="padding: 6px 8px; text-align: right;">{{ number_format($targetPerc, 1) }}%</td>
                                    <td style="padding: 6px 8px; text-align: right; color: #64748b;">{{ number_format($minPerc, 1) }} - {{ number_format($maxPerc, 1) }}%</td>
                                    <td style="padding: 6px 8px; text-align: right; font-weight: 700; color: {{ $isWithinBounds ? '#16a34a' : '#dc2626' }};">
                                        {{ number_format($currentPerc, 2) }}%
                                    </td>
                                    <td style="padding: 6px 8px; text-align: center;">
                                        @if($isWithinBounds)
                                            <span style="background: #16a34a; color: white; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 600;">OK</span>
                                        @elseif($currentPerc < $minPerc)
                                            <span style="background: #dc2626; color: white; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 600;">UNDER</span>
                                        @else
                                            <span style="background: #d97706; color: white; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 600;">OVER</span>
                                        @endif
                                    </td>
                                </tr>
                                @php $rowIdx++; @endphp
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stacked Overview Chart -->
        @if(isset($files['rebalance_stacked.png']))
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-header-title">Portfolio Allocation Overview (Stacked)</h4>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <img src="{{ $files['rebalance_stacked.png'] }}" alt="Stacked Allocation Chart" style="width: 100%;"/>
                    </div>
                    <div style="margin-top: 6px; font-size: 10px; color: #64748b;">
                        Shows all asset allocations stacked together over time. Total should sum to ~100%.
                    </div>
                </div>
            </div>
        @endif

        <!-- Individual Asset Charts (3 per page) -->
        @php $chartIndex = 0; @endphp
        @foreach($api['symbols'] as $symbolInfo)
            @php
                $symbol = $symbolInfo['symbol'];
                $slug = \Str::slug($symbol);
                $chartFile = "rebalance_{$slug}.png";
            @endphp
            @if(isset($files[$chartFile]))
                @if($chartIndex % 3 === 0)
                    <div class="page-break"></div>
                @endif
                @php $chartIndex++; @endphp
                <div class="card mb-2">
                    <div class="card-header">
                        <h4 class="card-header-title">{{ $symbol }}</h4>
                        <span class="badge badge-primary" style="float: right;">{{ $symbolInfo['type'] }}</span>
                    </div>
                    <div class="card-body">
                        @php
                            // Find the last available data for this symbol
                            $currentData = null;
                            foreach (array_reverse($rebalance, true) as $date => $dayData) {
                                if (isset($dayData[$symbol])) {
                                    $currentData = $dayData[$symbol];
                                    break;
                                }
                            }
                        @endphp
                        @if($currentData)
                            @php $diff = ($currentData['perc'] - $currentData['target']) * 100; @endphp
                            <div style="margin-bottom: 6px; font-size: 11px;">
                                <strong>Target:</strong> {{ number_format($currentData['target'] * 100, 1) }}% |
                                <span style="color: {{ ($currentData['perc'] >= $currentData['min'] && $currentData['perc'] <= $currentData['max']) ? '#16a34a' : '#dc2626' }}; font-weight: 700;">
                                    Current: {{ number_format($currentData['perc'] * 100, 2) }}%
                                </span> |
                                Range: {{ number_format($currentData['min'] * 100, 1) }}-{{ number_format($currentData['max'] * 100, 1) }}% |
                                <span style="color: {{ abs($diff) < ($currentData['max'] - $currentData['target']) * 100 ? '#16a34a' : '#dc2626' }};">
                                    Dev: {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2) }}%
                                </span>
                            </div>
                        @endif
                        <div class="chart-container">
                            <img src="{{ $files[$chartFile] }}" alt="{{ $symbol }} Rebalance Chart"/>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endif
@endsection
