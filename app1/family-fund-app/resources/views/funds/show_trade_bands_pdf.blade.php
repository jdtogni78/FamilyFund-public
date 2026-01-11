@extends('layouts.pdf_modern')

@section('report-type', 'Trading Bands Report')

@section('content')
    @php
        // Build list of symbols that are in at least one trade portfolio
        $portfolioSymbols = collect($api['tradePortfolios'] ?? [])
            ->flatMap(fn($tp) => collect($tp['items'] ?? [])->pluck('symbol'))
            ->unique()
            ->toArray();
    @endphp

    <!-- Fund Summary (compact) -->
    <div class="summary-box" style="padding: 15px 20px; margin-bottom: 20px;">
        <h2 style="margin-bottom: 10px;">{{ $api['name'] }}</h2>
        <div style="display: flex; gap: 30px; font-size: 14px;">
            <span><strong>Total Value:</strong> ${{ number_format($api['summary']['value'], 2) }}</span>
            <span><strong>Share Price:</strong> ${{ number_format($api['summary']['share_value'], 4) }}</span>
            <span><strong>Trade Portfolios:</strong> {{ count($api['tradePortfolios']) }}</span>
        </div>
    </div>

    <!-- Current Allocation Status -->
    @if(!empty($api['allocation_status']['symbols']))
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-header-title">
                    <img src="{{ public_path('images/icons/tasks.svg') }}" class="header-icon">Current Allocation Status
                    <span style="font-weight: normal; font-size: 11px; opacity: 0.8; margin-left: 10px;">
                        (as of {{ $api['allocation_status']['as_of_date'] ?? 'N/A' }})
                    </span>
                </h4>
            </div>
            <div class="card-body" style="padding: 0;">
                <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 11px; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f1f5f9;">
                            <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #e2e8f0;">Symbol</th>
                            <th style="padding: 8px 10px; text-align: left; border-bottom: 1px solid #e2e8f0;">Type</th>
                            <th style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #e2e8f0;">Target</th>
                            <th style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #e2e8f0;">Range</th>
                            <th style="padding: 8px 10px; text-align: right; border-bottom: 1px solid #e2e8f0;">Current</th>
                            <th style="padding: 8px 10px; text-align: center; border-bottom: 1px solid #e2e8f0; width: 60px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rowIdx = 0; @endphp
                        @foreach($api['allocation_status']['symbols'] as $item)
                            <tr style="background: {{ $rowIdx % 2 === 0 ? '#ffffff' : '#f8fafc' }}; border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 6px 10px; font-weight: 600;">{{ $item['symbol'] }}</td>
                                <td style="padding: 6px 10px; color: #64748b;">{{ $item['type'] }}</td>
                                <td style="padding: 6px 10px; text-align: right;">{{ number_format($item['target_pct'], 1) }}%</td>
                                <td style="padding: 6px 10px; text-align: right; color: #64748b;">
                                    {{ number_format($item['min_pct'], 1) }} - {{ number_format($item['max_pct'], 1) }}%
                                </td>
                                <td style="padding: 6px 10px; text-align: right; font-weight: 700; color: {{ $item['status'] === 'ok' ? '#16a34a' : '#dc2626' }};">
                                    {{ number_format($item['current_pct'], 2) }}%
                                </td>
                                <td style="padding: 6px 10px; text-align: center;">
                                    @if($item['status'] === 'ok')
                                        <span style="background: #16a34a; color: white; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 600;">OK</span>
                                    @elseif($item['status'] === 'under')
                                        <span style="background: #dc2626; color: white; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 600;">UNDER</span>
                                    @else
                                        <span style="background: #d97706; color: white; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 600;">OVER</span>
                                    @endif
                                </td>
                            </tr>
                            @php $rowIdx++; @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Trade Portfolios Comparison -->
    @if(count($api['tradePortfolios']) > 0)
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-header-title">
                    <img src="{{ public_path('images/icons/columns.svg') }}" class="header-icon">Trade Portfolios Comparison
                </h4>
            </div>
            <div class="card-body">
                @include('trade_portfolios.inner_show_alt_pdf')
            </div>
        </div>
    @endif

    <!-- Trading Bands by Symbol -->
    <h3 class="section-title">Trading Bands Analysis</h3>

    @foreach ($api['asset_monthly_bands'] as $symbol => $data)
        @if ($symbol != 'SP500' && $symbol != 'CASH' && in_array($symbol, $portfolioSymbols))
            @php
                $portfolioInfo = [];
                foreach ($api['tradePortfolios'] as $tp) {
                    foreach ($tp['items'] as $item) {
                        if ($item['symbol'] == $symbol) {
                            $portfolioInfo[] = [
                                'start_dt' => substr($tp['start_dt'] ?? '', 0, 10),
                                'end_dt' => substr($tp['end_dt'] ?? '', 0, 10),
                                'target_share' => $item['target_share'],
                                'deviation_trigger' => $item['deviation_trigger'],
                                'target_value' => $api['summary']['value'] * $item['target_share'],
                            ];
                        }
                    }
                }
            @endphp

            @if (count($portfolioInfo) > 0)
                <div class="card mb-4 avoid-break">
                    <div class="card-header">
                        <h4 class="card-header-title"><img src="{{ public_path('images/icons/chart-line.svg') }}" class="header-icon">{{ $symbol }}</h4>
                        @if (count($portfolioInfo) > 0)
                            <span class="badge badge-primary">
                                Target: {{ number_format($portfolioInfo[0]['target_share'] * 100, 1) }}%
                            </span>
                        @endif
                    </div>
                    <div class="card-body">
                        <!-- Portfolio Configuration Table -->
                        <div class="table-container mb-4">
                            <table class="table table-compact">
                                <thead>
                                    <tr>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th class="col-number">Target %</th>
                                        <th class="col-number">Deviation Trigger</th>
                                        <th class="col-number">Target Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($portfolioInfo as $info)
                                        <tr>
                                            <td>{{ $info['start_dt'] }}</td>
                                            <td>{{ $info['end_dt'] }}</td>
                                            <td class="col-number">{{ number_format($info['target_share'] * 100, 1) }}%</td>
                                            <td class="col-number">{{ number_format($info['deviation_trigger'] * 100, 1) }}%</td>
                                            <td class="col-number">${{ number_format($info['target_value'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Trading Bands Chart -->
                        <div class="mb-4">
                            <h5 class="text-sm font-semibold text-muted mb-2">Trading Bands</h5>
                            <div class="chart-container">
                                <img src="{{ $files['trade_bands_' . $symbol . '.png'] }}" alt="{{ $symbol }} Trading Bands"/>
                            </div>
                            <div class="text-xs text-muted mt-2">
                                Shows actual value vs target range (target ± deviation trigger)
                            </div>
                        </div>

                        <!-- Asset Positions Chart -->
                        <div>
                            <h5 class="text-sm font-semibold text-muted mb-2">Share Positions</h5>
                            <div class="chart-container">
                                <img src="{{ $files['asset_positions_' . $symbol . '.png'] }}" alt="{{ $symbol }} Asset Positions"/>
                            </div>
                            <div class="text-xs text-muted mt-2">
                                Historical share count over time
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @endforeach
@endsection
