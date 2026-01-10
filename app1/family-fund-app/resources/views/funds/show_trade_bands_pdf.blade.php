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
