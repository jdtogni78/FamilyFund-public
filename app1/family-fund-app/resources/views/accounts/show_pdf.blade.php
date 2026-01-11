@extends('layouts.pdf_modern')

@section('report-type', 'Account Quarterly Report')

@section('content')
    @php
        $account = $api['account'];
        $shares = $account->balances['OWN']->shares ?? 0;
        $marketValue = $account->balances['OWN']->market_value ?? 0;
        $sharePrice = $shares > 0 ? $marketValue / $shares : 0;
        $matchingAvailable = $api['matching_available'] ?? 0;
        $goalsCount = count($account->goals);

        // Disbursement data
        $disb = $api['disbursable'] ?? [];
        $disbPerformance = floatval($disb['performance'] ?? 0);
        $disbLimit = floatval($disb['limit'] ?? 0);
        $disbValue = floatval($disb['value'] ?? 0);
        $disbYear = $disb['year'] ?? date('Y');

        // Calculate year growth from yearly_performance (prev year + current year)
        $yearlyPerf = $api['yearly_performance'] ?? [];
        $currentYear = date('Y');
        $prevYear = $currentYear - 1;

        // Find current year (YTD) and previous year growth
        $currentYearGrowth = 0;
        $prevYearGrowth = 0;
        $hasCurrentYear = false;
        $hasPrevYear = false;

        foreach ($yearlyPerf as $yearKey => $data) {
            $year = substr($yearKey, 0, 4);
            if ($year == $currentYear) {
                $currentYearGrowth = $data['performance'] ?? 0;
                $hasCurrentYear = true;
            } elseif ($year == $prevYear) {
                $prevYearGrowth = $data['performance'] ?? 0;
                $hasPrevYear = true;
            }
        }

        // All-time performance - calculate true return: (current value - total invested) / total invested
        // This is more accurate for accounts than compounding share price returns (which doesn't account for deposits at different times)
        $totalInvested = 0;
        foreach ($api['transactions'] ?? [] as $trans) {
            if ($trans->value > 0) {
                $totalInvested += $trans->value;
            }
        }
        $allTimeGrowth = $totalInvested > 0 ? (($marketValue - $totalInvested) / $totalInvested) * 100 : 0;

        // Calculate overall account health
        $onTrackGoals = 0;
        foreach ($account->goals as $goal) {
            $currentVal = $goal->progress['current']['value'] ?? 0;
            $expectedVal = $goal->progress['expected']['value'] ?? 0;
            if ($currentVal >= $expectedVal) $onTrackGoals++;
        }
        $healthPct = $goalsCount > 0 ? ($onTrackGoals / $goalsCount) * 100 : 100;
    @endphp

    {{-- ============================================== --}}
    {{-- EXECUTIVE SUMMARY - Page 1 --}}
    {{-- ============================================== --}}

    {{-- Hero Header --}}
    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 20px;">
        <tr>
            <td class="hero-header-cell">
                <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="65%">
                            <div style="font-size: 36px; font-weight: 800; color: #0f766e; margin-bottom: 6px;">{{ $account->nickname }}</div>
                            <div style="font-size: 16px; color: #0d9488;">{{ $account->fund->name }} &bull; {{ $account->user->name }}</div>
                        </td>
                        <td width="35%" align="right">
                            <div style="font-size: 42px; font-weight: 800; color: #0f766e;">${{ number_format($marketValue, 0) }}</div>
                            <div style="font-size: 14px; color: #0d9488; text-transform: uppercase;">Total Value</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Key Metrics Row (7 columns like web) --}}
    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 0; background: #f0fdfa; border-radius: 8px 8px 0 0; padding: 12px;">
        <tr>
            <td width="14%" style="padding: 10px 6px; text-align: center; border-right: 1px solid #99f6e4;">
                <div style="font-size: 18px; font-weight: 700; color: #0d9488;">${{ number_format($marketValue, 0) }}</div>
                <div style="font-size: 9px; text-transform: uppercase; color: #64748b; margin-top: 2px;">Market Value</div>
            </td>
            <td width="14%" style="padding: 10px 6px; text-align: center; border-right: 1px solid #99f6e4;">
                <div style="font-size: 18px; font-weight: 700; color: #0d9488;">{{ number_format($shares, 2) }}</div>
                <div style="font-size: 9px; text-transform: uppercase; color: #64748b; margin-top: 2px;">Shares</div>
            </td>
            <td width="14%" style="padding: 10px 6px; text-align: center; border-right: 1px solid #99f6e4;">
                <div style="font-size: 18px; font-weight: 700; color: #0d9488;">${{ number_format($sharePrice, 2) }}</div>
                <div style="font-size: 9px; text-transform: uppercase; color: #64748b; margin-top: 2px;">Share Price</div>
            </td>
            @if($account->disbursement_cap !== 0.0)
            <td width="14%" style="padding: 10px 6px; text-align: center; border-right: 1px solid #99f6e4;">
                <div style="font-size: 18px; font-weight: 700; color: #059669;">${{ number_format($disbValue, 0) }}</div>
                <div style="font-size: 9px; text-transform: uppercase; color: #64748b; margin-top: 2px;">Eligible Disbursement</div>
            </td>
            @endif
            @if($matchingAvailable > 0)
            <td width="14%" style="padding: 10px 6px; text-align: center; border-right: 1px solid #99f6e4;">
                <div style="font-size: 18px; font-weight: 700; color: #16a34a;">${{ number_format($matchingAvailable, 0) }}</div>
                <div style="font-size: 9px; text-transform: uppercase; color: #64748b; margin-top: 2px;">Matching Available</div>
            </td>
            @endif
            @if($hasPrevYear)
            <td width="12%" style="padding: 10px 6px; text-align: center; border-right: 1px solid #99f6e4;">
                <div style="font-size: 18px; font-weight: 700; color: {{ $prevYearGrowth >= 0 ? '#2563eb' : '#dc2626' }};">{{ $prevYearGrowth >= 0 ? '+' : '' }}{{ number_format($prevYearGrowth, 1) }}%</div>
                <div style="font-size: 9px; text-transform: uppercase; color: #64748b; margin-top: 2px;">{{ $prevYear }} Growth</div>
            </td>
            @endif
            @if($hasCurrentYear)
            <td width="12%" style="padding: 10px 6px; text-align: center; border-right: 1px solid #99f6e4;">
                <div style="font-size: 18px; font-weight: 700; color: {{ $currentYearGrowth >= 0 ? '#2563eb' : '#dc2626' }};">{{ $currentYearGrowth >= 0 ? '+' : '' }}{{ number_format($currentYearGrowth, 1) }}%</div>
                <div style="font-size: 9px; text-transform: uppercase; color: #64748b; margin-top: 2px;">{{ $currentYear }} YTD</div>
            </td>
            @endif
            @if(!empty($yearlyPerf))
            <td width="12%" style="padding: 10px 6px; text-align: center;">
                <div style="font-size: 18px; font-weight: 700; color: {{ $allTimeGrowth >= 0 ? '#2563eb' : '#dc2626' }};">{{ $allTimeGrowth >= 0 ? '+' : '' }}{{ number_format($allTimeGrowth, 1) }}%</div>
                <div style="font-size: 9px; text-transform: uppercase; color: #64748b; margin-top: 2px;">All-Time</div>
            </td>
            @endif
        </tr>
    </table>

    {{-- Goals Summary (like web header) --}}
    @if($goalsCount > 0)
    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 20px; background: #ffffff; border: 1px solid #99f6e4; border-top: none; border-radius: 0 0 8px 8px; padding: 12px;">
        <tr>
            <td style="padding: 8px 12px;">
                <div style="font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 600; margin-bottom: 8px;">Goals Summary</div>
                @foreach($account->goals as $goal)
                    @php
                        $currentPct = $goal->progress['current']['completed_pct'] ?? 0;
                        $expectedPct = $goal->progress['expected']['completed_pct'] ?? 0;
                        $currentValue = $goal->progress['current']['value'] ?? 0;
                        $expectedValue = $goal->progress['expected']['value'] ?? 0;
                        $diff = $currentValue - $expectedValue;
                        $isOnTrack = $diff >= 0;

                        // Calculate time ahead/behind
                        $period = $goal->progress['period'] ?? [0, 1, 0];
                        $totalDays = $period[1] ?? 1;
                        $pctDiff = abs($currentPct - $expectedPct);
                        $timeAheadDays = ($pctDiff / 100) * $totalDays;
                        if ($timeAheadDays >= 365) {
                            $timeAheadYears = $timeAheadDays / 365;
                            $timeAheadStr = number_format($timeAheadYears, 1) . ' yr' . ($timeAheadYears >= 1.5 ? 's' : '');
                        } elseif ($timeAheadDays >= 30) {
                            $timeAheadMonths = round($timeAheadDays / 30);
                            $timeAheadStr = $timeAheadMonths . ' mo' . ($timeAheadMonths != 1 ? 's' : '');
                        } else {
                            $timeAheadWeeks = max(1, round($timeAheadDays / 7));
                            $timeAheadStr = $timeAheadWeeks . ' wk' . ($timeAheadWeeks != 1 ? 's' : '');
                        }
                    @endphp
                    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: {{ $loop->last ? '0' : '6px' }}; {{ !$loop->last ? 'border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;' : '' }}">
                        <tr>
                            <td width="40%" style="color: #0d9488; font-weight: 600; font-size: 12px;">{{ $goal->name }}</td>
                            <td width="20%" align="right" style="font-size: 12px;">
                                <span style="font-weight: 700; color: {{ $isOnTrack ? '#16a34a' : '#d97706' }};">{{ number_format($currentPct, 1) }}%</span>
                                <span style="color: #64748b;"> complete</span>
                            </td>
                            <td width="40%" style="text-align: right;">
                                <span style="background: {{ $isOnTrack ? '#dcfce7' : '#fef2f2' }}; color: {{ $isOnTrack ? '#16a34a' : '#dc2626' }}; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 10px;">
                                    ${{ number_format(abs($diff), 0) }} or {{ $timeAheadStr }} {{ $isOnTrack ? 'ahead' : 'behind' }}
                                </span>
                            </td>
                        </tr>
                    </table>
                @endforeach
            </td>
        </tr>
    </table>
    @else
    <div style="margin-bottom: 20px;"></div>
    @endif

    {{-- Disbursement Eligibility Section --}}
    @if($account->disbursement_cap !== 0.0)
    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <tr>
            <td class="section-header-cell">
                <img src="{{ public_path('images/icons/money-bill.svg') }}" class="header-icon"><span class="section-header-text">DISBURSEMENT ELIGIBILITY ({{ $disbYear }})</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px; background: #ffffff;">
                <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="33%" align="center" style="border-right: 1px solid #e2e8f0; padding: 8px;">
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Eligible Value</div>
                            <div style="font-size: 24px; font-weight: 700; color: #059669;">${{ number_format($disbValue, 0) }}</div>
                            <div style="font-size: 10px; color: #64748b; margin-top: 4px;">{{ number_format($disbLimit, 0) }}% of ${{ number_format($marketValue, 0) }}</div>
                        </td>
                        <td width="33%" align="center" style="border-right: 1px solid #e2e8f0; padding: 8px;">
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">YTD Performance</div>
                            <div style="font-size: 24px; font-weight: 700; color: {{ $disbPerformance >= 0 ? '#16a34a' : '#dc2626' }};">
                                {{ $disbPerformance >= 0 ? '+' : '' }}{{ number_format($disbPerformance, 1) }}%
                            </div>
                        </td>
                        <td width="33%" align="center" style="padding: 8px;">
                            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Annual Cap</div>
                            <div style="font-size: 24px; font-weight: 700; color: #1e293b;">{{ number_format($disbLimit, 0) }}%</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    @endif

    {{-- Goals Progress Section (with details) --}}
    @if($goalsCount > 0)
    @foreach($account->goals as $goal)
    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; page-break-inside: avoid;">
        <tr>
            <td class="section-header-cell">
                <img src="{{ public_path('images/icons/bullseye.svg') }}" class="header-icon"><span class="section-header-text">{{ $goal->name }} - PROGRESS</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px; background: #ffffff;">
                {{-- Summary row with progress bar and $ ahead badge --}}
                @include('goals.progress_summary', ['goal' => $goal, 'format' => 'pdf'])

                {{-- Detailed info: Expected/Current boxes, time progress, On Track badge --}}
                <div style="margin-top: 12px;">
                    @include('goals.progress_details_unified', ['goal' => $goal, 'format' => 'pdf'])
                </div>
            </td>
        </tr>
    </table>
    @endforeach
    @endif

    {{-- Monthly Value Chart --}}
    <table width="100%" cellspacing="0" cellpadding="0" class="keep-together" style="margin-bottom: 16px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <tr>
            <td class="section-header-cell keep-with-next">
                <img src="{{ public_path('images/icons/chart-line.svg') }}" class="header-icon"><span class="section-header-text">MONTHLY VALUE</span>
            </td>
        </tr>
        @if(isset($files['monthly_performance.png']) && file_exists($files['monthly_performance.png']))
        <tr>
            <td style="padding: 12px;">
                <img src="{{ $files['monthly_performance.png'] }}" alt="Monthly Value" style="width: 100%;"/>
            </td>
        </tr>
        @else
        <tr>
            <td style="padding: 12px; color: #64748b; font-size: 11px;">
                Monthly value chart not available
            </td>
        </tr>
        @endif
    </table>

    {{-- Yearly Value Chart --}}
    <table width="100%" cellspacing="0" cellpadding="0" class="keep-together" style="margin-bottom: 16px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <tr>
            <td class="section-header-cell keep-with-next">
                <img src="{{ public_path('images/icons/chart-bar.svg') }}" class="header-icon"><span class="section-header-text">YEARLY VALUE</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px;">
                <img src="{{ $files['yearly_performance.png'] }}" alt="Yearly Value" style="width: 100%;"/>
            </td>
        </tr>
    </table>

    {{-- Linear Regression Forecast (only show if account has goals) --}}
    @if(!empty($api['linear_regression']['predictions']) && $goalsCount > 0)
        {{-- Value Comparison Boxes --}}
        @if(!empty($api['linear_regression']['comparison']))
            @php
                $comp = $api['linear_regression']['comparison'];
                $isAhead = $comp['is_ahead'];
            @endphp
            <table width="100%" cellspacing="8" cellpadding="0" style="margin-bottom: 16px;">
                <tr>
                    <td colspan="3" style="padding-bottom: 8px;">
                        <span style="color: #64748b; font-size: 11px; text-transform: uppercase; font-weight: 600;">
                            <i class="fa fa-balance-scale"></i> Value Comparison
                        </span>
                    </td>
                </tr>
                <tr>
                    {{-- Starting Box --}}
                    <td width="33%" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 10px; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Starting ({{ $comp['starting']['date'] }})</div>
                        <div style="font-size: 20px; font-weight: 700; color: #1e293b;">${{ number_format($comp['starting']['value'], 0) }}</div>
                        <div style="font-size: 10px; color: #64748b;">${{ number_format($comp['starting']['yield_per_year'], 0) }}/yr yield</div>
                    </td>
                    {{-- Expected Box --}}
                    <td width="33%" style="background: #fef9c3; border: 1px solid #fcd34d; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 10px; text-transform: uppercase; color: #92400e; margin-bottom: 4px;">Expected ({{ $comp['expected']['date'] }})</div>
                        <div style="font-size: 20px; font-weight: 700; color: #d97706;">${{ number_format($comp['expected']['value'], 0) }}</div>
                        <div style="font-size: 10px; color: #92400e;">${{ number_format($comp['expected']['yield_per_year'], 0) }}/yr yield</div>
                    </td>
                    {{-- Current Box --}}
                    <td width="33%" style="background: {{ $isAhead ? '#dcfce7' : '#fef2f2' }}; border: 1px solid {{ $isAhead ? '#16a34a' : '#dc2626' }}; border-radius: 8px; padding: 12px; text-align: center;">
                        <div style="font-size: 10px; text-transform: uppercase; color: {{ $isAhead ? '#166534' : '#991b1b' }}; margin-bottom: 4px;">Current ({{ $comp['current']['date'] }})</div>
                        <div style="font-size: 20px; font-weight: 700; color: {{ $isAhead ? '#16a34a' : '#dc2626' }};">${{ number_format($comp['current']['value'], 0) }}</div>
                        <div style="font-size: 10px; color: {{ $isAhead ? '#166534' : '#991b1b' }};">${{ number_format($comp['current']['yield_per_year'], 0) }}/yr yield</div>
                        <div style="margin-top: 6px;">
                            <span style="background: {{ $isAhead ? '#16a34a' : '#dc2626' }}; color: white; padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 10px;">
                                ${{ number_format(abs($comp['diff']), 0) }} {{ $isAhead ? 'ahead' : 'behind' }}
                            </span>
                        </div>
                    </td>
                </tr>
            </table>
        @endif

        {{-- Forecast Chart --}}
        <table width="100%" cellspacing="0" cellpadding="0" class="keep-together" style="margin-bottom: 16px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
            <tr>
                <td class="section-header-cell keep-with-next">
                    <img src="{{ public_path('images/icons/chart-area.svg') }}" class="header-icon"><span class="section-header-text">10-YEAR FORECAST (LINEAR REGRESSION)</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 12px;">
                    @if(isset($files['linear_regression.png']))
                        <img src="{{ $files['linear_regression.png'] }}" alt="Linear Regression Forecast" style="width: 100%;"/>
                    @else
                        <div style="padding: 20px; text-align: center; color: #64748b;">Chart not available</div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Projection Table --}}
        <table width="100%" cellspacing="0" cellpadding="0" class="keep-together" style="margin-bottom: 16px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
            <tr>
                <td class="section-header-cell keep-with-next">
                    <img src="{{ public_path('images/icons/table.svg') }}" class="header-icon"><span class="section-header-text">PROJECTION TABLE</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 12px;">
                    <table width="100%" cellspacing="0" cellpadding="4" style="font-size: 11px;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="text-align: left; border-bottom: 1px solid #e2e8f0; padding: 8px;">Year</th>
                                <th style="text-align: right; border-bottom: 1px solid #e2e8f0; padding: 8px;">Conservative</th>
                                <th style="text-align: right; border-bottom: 1px solid #e2e8f0; padding: 8px;">Predicted</th>
                                <th style="text-align: right; border-bottom: 1px solid #e2e8f0; padding: 8px;">Aggressive</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($api['linear_regression']['predictions'] as $year => $value)
                            <tr style="background: {{ $loop->even ? '#f8fafc' : '#ffffff' }};">
                                <td style="padding: 6px 8px;">{{ substr($year, 0, 4) }}</td>
                                <td style="text-align: right; padding: 6px 8px;">${{ number_format($value * 0.8, 0) }}</td>
                                <td style="text-align: right; padding: 6px 8px; font-weight: 600;">${{ number_format($value, 0) }}</td>
                                <td style="text-align: right; padding: 6px 8px;">${{ number_format($value * 1.2, 0) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    @endif

    {{-- ============================================== --}}
    {{-- CHARTS & ANALYSIS - Page 3 --}}
    {{-- ============================================== --}}
    <div class="page-break"></div>
    <h3 class="section-title">Investment Analysis</h3>

    {{-- Shares Holdings --}}
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title"><img src="{{ public_path('images/icons/coins.svg') }}" class="header-icon">Shares Holdings Over Time</h4>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <img src="{{ $files['shares.png'] }}" alt="Shares Holdings"/>
            </div>
        </div>
    </div>


    {{-- ============================================== --}}
    {{-- PERFORMANCE DATA - Page 4 --}}
    {{-- ============================================== --}}
    <div class="page-break"></div>
    <h3 class="section-title">Performance Data</h3>

    {{-- Yearly Performance Table --}}
    <table width="100%" cellspacing="0" cellpadding="0" class="keep-together" style="margin-bottom: 16px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <tr>
            <td class="section-header-cell keep-with-next">
                <img src="{{ public_path('images/icons/chart-bar.svg') }}" class="header-icon"><span class="section-header-text">YEARLY PERFORMANCE</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px;">
                @php ($performance_key = 'yearly_performance')
                @include('accounts.performance_table_pdf')
            </td>
        </tr>
    </table>

    {{-- Monthly Performance Table --}}
    <table width="100%" cellspacing="0" cellpadding="0" class="keep-together" style="margin-bottom: 16px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <tr>
            <td class="section-header-cell keep-with-next">
                <img src="{{ public_path('images/icons/chart-line.svg') }}" class="header-icon"><span class="section-header-text">MONTHLY PERFORMANCE (Recent)</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px;">
                @php ($performance_key = 'monthly_performance')
                @include('accounts.performance_table_pdf')
            </td>
        </tr>
    </table>

    {{-- ============================================== --}}
    {{-- TRANSACTIONS --}}
    {{-- ============================================== --}}
    <div class="page-break"></div>
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title"><img src="{{ public_path('images/icons/table.svg') }}" class="header-icon">Transaction History</h4>
        </div>
        <div class="card-body">
            @include('accounts.transactions_table_pdf')
        </div>
    </div>

    {{-- ============================================== --}}
    {{-- MATCHING RULES - Page 6 (if applicable) --}}
    {{-- ============================================== --}}
    @if(!empty($api['matching_rules']))
        <div class="page-break"></div>
        <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 16px; border: 2px solid #0d9488; border-radius: 8px; overflow: hidden;">
            <tr>
                <td style="background: #f0fdf4; padding: 12px 16px; border-bottom: 1px solid #99f6e4;">
                    <table width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td>
                                <img src="{{ public_path('images/icons/hand-holding-usd.svg') }}" style="width:16px;height:16px;margin-right:8px;vertical-align:middle;"><span style="color: #0f766e; font-weight: 700; font-size: 14px;">Matching Rules</span>
                            </td>
                            <td align="right">
                                <span style="background: #10b981; color: #ffffff; padding: 4px 12px; border-radius: 4px; font-weight: 700; font-size: 13px;">
                                    ${{ number_format($matchingAvailable, 0) }} AVAILABLE
                                </span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="padding: 16px; background: #ffffff;">
                    @include('accounts.matching_rules_table_pdf')
                </td>
            </tr>
        </table>
    @endif

@endsection
