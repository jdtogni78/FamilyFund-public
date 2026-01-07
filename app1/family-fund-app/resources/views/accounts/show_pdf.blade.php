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
            <td style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 24px 20px; border-radius: 8px;">
                <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="70%">
                            <div style="font-size: 28px; font-weight: 800; color: #ffffff; margin-bottom: 4px;">{{ $account->nickname }}</div>
                            <div style="font-size: 13px; color: #bfdbfe;">{{ $account->fund->name }} &bull; {{ $account->user->name }}</div>
                        </td>
                        <td width="30%" align="right">
                            <div style="font-size: 32px; font-weight: 800; color: #ffffff;">${{ number_format($marketValue, 0) }}</div>
                            <div style="font-size: 12px; color: #bfdbfe; text-transform: uppercase;">Total Value</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Key Metrics Row --}}
    <table width="100%" cellspacing="8" cellpadding="0" style="margin-bottom: 20px;">
        <tr>
            <td width="25%" style="background: #f8fafc; border-radius: 8px; padding: 16px; text-align: center; border: 1px solid #e2e8f0;">
                <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Shares Owned</div>
                <div style="font-size: 22px; font-weight: 700; color: #1e293b;">{{ number_format($shares, 2) }}</div>
            </td>
            <td width="25%" style="background: #f8fafc; border-radius: 8px; padding: 16px; text-align: center; border: 1px solid #e2e8f0;">
                <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Share Price</div>
                <div style="font-size: 22px; font-weight: 700; color: #1e293b;">${{ number_format($sharePrice, 2) }}</div>
            </td>
            <td width="25%" style="background: {{ $matchingAvailable > 0 ? '#dcfce7' : '#f8fafc' }}; border-radius: 8px; padding: 16px; text-align: center; border: 1px solid {{ $matchingAvailable > 0 ? '#16a34a' : '#e2e8f0' }};">
                <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Matching Available</div>
                <div style="font-size: 22px; font-weight: 700; color: {{ $matchingAvailable > 0 ? '#16a34a' : '#94a3b8' }};">${{ number_format($matchingAvailable, 0) }}</div>
            </td>
            <td width="25%" style="background: #f8fafc; border-radius: 8px; padding: 16px; text-align: center; border: 1px solid #e2e8f0;">
                <div style="font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Goals Status</div>
                <div style="font-size: 22px; font-weight: 700; color: {{ $healthPct >= 50 ? '#16a34a' : '#d97706' }};">{{ $onTrackGoals }}/{{ $goalsCount }}</div>
                <div style="font-size: 10px; color: #64748b;">on track</div>
            </td>
        </tr>
    </table>

    {{-- Disbursement Eligibility Section --}}
    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 20px; border: 2px solid #d97706; border-radius: 8px; overflow: hidden;">
        <tr>
            <td style="background: #d97706; padding: 10px 16px;">
                <span style="color: #ffffff; font-weight: 700; font-size: 13px;">DISBURSEMENT ELIGIBILITY ({{ $disbYear }})</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px; background: #fffbeb;">
                <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="33%" align="center" style="border-right: 1px solid #fcd34d; padding: 8px;">
                            <div style="font-size: 11px; text-transform: uppercase; color: #92400e; margin-bottom: 4px;">Eligible Value</div>
                            <div style="font-size: 24px; font-weight: 700; color: #1e293b;">${{ number_format($disbValue, 0) }}</div>
                        </td>
                        <td width="33%" align="center" style="border-right: 1px solid #fcd34d; padding: 8px;">
                            <div style="font-size: 11px; text-transform: uppercase; color: #92400e; margin-bottom: 4px;">YTD Performance</div>
                            <div style="font-size: 24px; font-weight: 700; color: {{ $disbPerformance >= 0 ? '#16a34a' : '#dc2626' }};">
                                {{ $disbPerformance >= 0 ? '+' : '' }}{{ number_format($disbPerformance, 1) }}%
                            </div>
                        </td>
                        <td width="33%" align="center" style="padding: 8px;">
                            <div style="font-size: 11px; text-transform: uppercase; color: #92400e; margin-bottom: 4px;">Annual Cap</div>
                            <div style="font-size: 24px; font-weight: 700; color: #d97706;">{{ number_format($disbLimit, 0) }}%</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Goals Progress Section --}}
    @if($goalsCount > 0)
    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <tr>
            <td style="background: #1e293b; padding: 10px 16px;">
                <span style="color: #ffffff; font-weight: 700; font-size: 13px;">GOALS PROGRESS</span>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px; background: #ffffff;">
                @foreach($account->goals as $goal)
                    @php
                        $currentPct = $goal->progress['current']['completed_pct'] ?? 0;
                        $expectedPct = $goal->progress['expected']['completed_pct'] ?? 0;
                        $currentValue = $goal->progress['current']['value'] ?? 0;
                        $expectedValue = $goal->progress['expected']['value'] ?? 0;
                        $targetValue = $goal->progress['current']['final_value'] ?? $goal->target_amount;
                        $diff = $currentValue - $expectedValue;
                        $isOnTrack = $diff >= 0;
                        $progressColor = $isOnTrack ? '#16a34a' : '#d97706';
                    @endphp
                    <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: {{ $loop->last ? '0' : '16px' }};">
                        <tr>
                            <td>
                                <table width="100%" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td width="50%">
                                            <div style="font-weight: 700; color: #1e40af; font-size: 14px;">{{ $goal->name }}</div>
                                            <div style="font-size: 11px; color: #64748b;">Target: ${{ number_format($targetValue, 0) }} by {{ $goal->end_dt->format('M Y') }}</div>
                                        </td>
                                        <td width="25%" align="center">
                                            <span style="font-size: 20px; font-weight: 700; color: {{ $progressColor }};">{{ number_format($currentPct, 0) }}%</span>
                                        </td>
                                        <td width="25%" align="right">
                                            <span style="background: {{ $isOnTrack ? '#dcfce7' : '#fef2f2' }}; color: {{ $isOnTrack ? '#16a34a' : '#dc2626' }}; padding: 4px 10px; border-radius: 4px; font-weight: 600; font-size: 11px;">
                                                ${{ number_format(abs($diff), 0) }} {{ $isOnTrack ? 'ahead' : 'behind' }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                                {{-- Progress Bar --}}
                                <div style="background: #e2e8f0; border-radius: 4px; height: 8px; margin-top: 8px; overflow: hidden;">
                                    <div style="background: {{ $progressColor }}; height: 100%; width: {{ min(100, $currentPct) }}%; border-radius: 4px;"></div>
                                </div>
                            </td>
                        </tr>
                    </table>
                @endforeach
            </td>
        </tr>
    </table>
    @endif

    {{-- Performance Overview (Mini Charts Side by Side) --}}
    <table width="100%" cellspacing="8" cellpadding="0" style="margin-bottom: 0;">
        <tr>
            <td width="50%" valign="top" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                <div style="background: #1e293b; padding: 10px 16px;">
                    <span style="color: #ffffff; font-weight: 700; font-size: 12px;">MONTHLY PERFORMANCE</span>
                </div>
                <div style="padding: 12px;">
                    <img src="{{ $files['monthly_performance.png'] }}" alt="Monthly Performance" style="width: 100%;"/>
                </div>
            </td>
            <td width="50%" valign="top" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                <div style="background: #1e293b; padding: 10px 16px;">
                    <span style="color: #ffffff; font-weight: 700; font-size: 12px;">YEARLY PERFORMANCE</span>
                </div>
                <div style="padding: 12px;">
                    <img src="{{ $files['yearly_performance.png'] }}" alt="Yearly Performance" style="width: 100%;"/>
                </div>
            </td>
        </tr>
    </table>

    {{-- ============================================== --}}
    {{-- DETAILED GOALS - Page 2 --}}
    {{-- ============================================== --}}
    @if($goalsCount > 0)
        <div class="page-break"></div>
        <h3 class="section-title">Detailed Goals Analysis</h3>

        @foreach($account->goals as $goal)
            <div class="goal-item avoid-break mb-4">
                <div class="goal-header">
                    <span class="goal-name">{{ $goal->name }}</span>
                    @php
                        $currentPct = $goal->progress['current']['completed_pct'] ?? 0;
                        $expectedPct = $goal->progress['expected']['completed_pct'] ?? 0;
                    @endphp
                    <span class="badge {{ $currentPct >= $expectedPct ? 'badge-success' : 'badge-warning' }}">
                        {{ number_format($currentPct, 1) }}% Complete
                    </span>
                </div>
                <div class="chart-container mt-3">
                    <img src="{{ $files['goals_progress_' . $goal->id . '.png'] }}" alt="{{ $goal->name }} Progress"/>
                </div>
                <div class="mt-3">
                    @include('goals.progress_details_pdf')
                </div>
            </div>
        @endforeach
    @endif

    {{-- ============================================== --}}
    {{-- CHARTS & ANALYSIS - Page 3 --}}
    {{-- ============================================== --}}
    <div class="page-break"></div>
    <h3 class="section-title">Investment Analysis</h3>

    {{-- Shares Holdings --}}
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title">Shares Holdings Over Time</h4>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <img src="{{ $files['shares.png'] }}" alt="Shares Holdings"/>
            </div>
        </div>
    </div>

    {{-- Portfolio Comparison --}}
    @if(isset($files['portfolio_comparison.png']))
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-header-title">Portfolio Allocations</h4>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <img src="{{ $files['portfolio_comparison.png'] }}" alt="Portfolio Comparison" style="width: 100%;"/>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================== --}}
    {{-- PERFORMANCE DATA - Page 4 --}}
    {{-- ============================================== --}}
    <div class="page-break"></div>
    <h3 class="section-title">Performance Data</h3>

    <table width="100%" cellspacing="8" cellpadding="0" style="margin-bottom: 16px;">
        <tr>
            <td width="50%" valign="top" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                <div style="background: #1e293b; padding: 10px 16px;">
                    <span style="color: #ffffff; font-weight: 700; font-size: 12px;">YEARLY PERFORMANCE</span>
                </div>
                <div style="padding: 12px;">
                    @php ($performance_key = 'yearly_performance')
                    @include('accounts.performance_table_pdf')
                </div>
            </td>
            <td width="50%" valign="top" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                <div style="background: #1e293b; padding: 10px 16px;">
                    <span style="color: #ffffff; font-weight: 700; font-size: 12px;">MONTHLY PERFORMANCE (Recent)</span>
                </div>
                <div style="padding: 12px;">
                    @php ($performance_key = 'monthly_performance')
                    @include('accounts.performance_table_pdf')
                </div>
            </td>
        </tr>
    </table>

    {{-- ============================================== --}}
    {{-- TRANSACTIONS - Page 5 --}}
    {{-- ============================================== --}}
    <div class="page-break"></div>
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-header-title">Transaction History</h4>
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
        <table width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 16px; border: 2px solid #16a34a; border-radius: 8px; overflow: hidden;">
            <tr>
                <td style="background: #16a34a; padding: 12px 16px;">
                    <table width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td>
                                <span style="color: #ffffff; font-weight: 700; font-size: 14px;">MATCHING CONTRIBUTION RULES</span>
                            </td>
                            <td align="right">
                                <span style="background: #ffffff; color: #16a34a; padding: 4px 12px; border-radius: 4px; font-weight: 700; font-size: 13px;">
                                    ${{ number_format($matchingAvailable, 2) }} Available
                                </span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="padding: 16px; background: #f0fdf4;">
                    @include('accounts.matching_rules_table_pdf')
                </td>
            </tr>
        </table>
    @endif

@endsection
