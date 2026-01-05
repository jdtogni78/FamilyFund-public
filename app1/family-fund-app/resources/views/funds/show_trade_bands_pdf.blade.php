@extends('layouts.pdf_modern')

@section('report-type', 'Trading Bands Report')

@section('content')
    <!-- Fund Summary -->
    <div class="summary-box">
        <h2>{{ $api['name'] }}</h2>
        <div class="stat-grid">
            <div class="stat-card" style="background: rgba(255,255,255,0.1); border: none;">
                <div class="stat-value">${{ number_format($api['summary']['value'], 2) }}</div>
                <div class="stat-label">Total Value</div>
            </div>
            <div class="stat-card" style="background: rgba(255,255,255,0.1); border: none;">
                <div class="stat-value">${{ number_format($api['summary']['share_value'], 4) }}</div>
                <div class="stat-label">Share Price</div>
            </div>
            <div class="stat-card" style="background: rgba(255,255,255,0.1); border: none;">
                <div class="stat-value">{{ count($api['tradePortfolios']) }}</div>
                <div class="stat-label">Trade Portfolios</div>
            </div>
        </div>
    </div>

    <!-- Fund Details Card -->
    <div class="card mb-5">
        <div class="card-header">
            <h4 class="card-header-title">Fund Details</h4>
        </div>
        <div class="card-body">
            @include('funds.show_fields_pdf')
        </div>
    </div>

    <!-- Trading Bands by Symbol -->
    <h3 class="section-title">Trading Bands Analysis</h3>

    @foreach ($api['asset_monthly_bands'] as $symbol => $data)
        @if ($symbol != 'SP500' && $symbol != 'CASH')
            @php
                $hasPortfolioItem = false;
                $portfolioInfo = [];
                foreach ($api['tradePortfolios'] as $tp) {
                    foreach ($tp['items'] as $item) {
                        if ($item['symbol'] == $symbol) {
                            $hasPortfolioItem = true;
                            $portfolioInfo[] = [
                                'start_dt' => $tp['start_dt'],
                                'end_dt' => $tp['end_dt'],
                                'target_share' => $item['target_share'],
                                'deviation_trigger' => $item['deviation_trigger'],
                                'target_value' => $api['summary']['value'] * $item['target_share'],
                            ];
                        }
                    }
                }
            @endphp

            @if ($hasPortfolioItem)
                <div class="card mb-4 avoid-break">
                    <div class="card-header">
                        <h4 class="card-header-title">{{ $symbol }}</h4>
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
                                Shows actual value vs target range (target +/- deviation trigger)
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
