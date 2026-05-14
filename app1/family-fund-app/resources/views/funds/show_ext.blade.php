<x-app-layout>

@section('content')
    <ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('funds.index') }}">Funds</a>
        </li>
        <li class="breadcrumb-item active">{{ $api['name'] }}</li>
    </ol>
    <div class="container-fluid">
        <div class="animated fadeIn">
            @include('coreui-templates.common.errors')

            {{-- Data Staleness Warning Banner --}}
            @if(isset($api['data_staleness']) && $api['data_staleness']['is_stale'])
            <style>
                #data-staleness-warning { border: 2px solid #f59e0b; background: #fffbeb; }
                #data-staleness-warning strong { color: #d97706; }
                #data-staleness-warning span.warning-text { color: #78350f; }
                .dark #data-staleness-warning { background: #451a03; }
                .dark #data-staleness-warning strong { color: #fbbf24; }
                .dark #data-staleness-warning span.warning-text { color: #fef3c7; }
                .dark #data-staleness-warning i { color: #fbbf24; }
            </style>
            <div id="data-staleness-warning" class="alert d-flex align-items-center mb-4" role="alert">
                <i class="fa fa-exclamation-triangle me-3" style="font-size: 1.5rem; color: #d97706;"></i>
                <div class="flex-grow-1">
                    <strong>Data Warning:</strong>
                    <span class="warning-text">{{ $api['data_staleness']['message'] ?? 'Portfolio data may be stale' }}</span>
                </div>
                <span class="badge" style="background: #f59e0b; color: #fff; font-size: 0.75rem; padding: 6px 12px;">
                    {{ $api['data_staleness']['trading_days_stale'] }} TRADING DAY{{ $api['data_staleness']['trading_days_stale'] > 1 ? 'S' : '' }} DELAYED
                </span>
            </div>
            @endif

            {{-- Fund Highlights Card --}}
            @php
                $totalValueRaw = $api['portfolio']['total_value'] ?? 0;
                // Remove $ and commas if it's a string, then format properly
                if (is_string($totalValueRaw)) {
                    $totalValueRaw = floatval(str_replace(['$', ','], '', $totalValueRaw));
                }
                $totalValue = '$' . number_format($totalValueRaw, 0);
                $sharePrice = $api['summary']['share_value'] ?? 0;
                $accountsCount = count($api['balances'] ?? []);

                // Calculate true all-time return for fund: (current value - total deposits) / total deposits
                // This is the correct formula (not compounded yearly returns)
                $totalDeposits = 0;
                foreach ($api['transactions'] ?? [] as $trans) {
                    if ($trans->value > 0) {
                        $totalDeposits += $trans->value;
                    }
                }
                $allTimeReturn = $totalDeposits > 0 ? (($totalValueRaw - $totalDeposits) / $totalDeposits) * 100 : 0;
            @endphp
            <div class="row mb-4" id="section-details">
                <div class="col">
                    <div class="card" style="border: 2px solid #0d9488;">
                        {{-- Header --}}
                        <div class="card-header card-header-dark d-flex justify-content-between align-items-center flex-wrap py-3" style="gap: 8px;">
                            <div class="d-flex align-items-center">
                                <h4 class="mb-0" style="font-weight: 700;">{{ $api['name'] }}</h4>
                                @isset($api['admin'])
                                    <span class="badge badge-warning ms-2">ADMIN</span>
                                @endisset
                            </div>
                            @php
                                $activeTradePortfolio = $api['tradePortfolios']->first(function($tp) {
                                    return \Carbon\Carbon::parse($tp->end_dt)->isAfter(\Carbon\Carbon::today());
                                });
                            @endphp
                            <div class="d-flex flex-wrap" style="gap: 4px;">
                                <a href="{{ route('funds.index') }}" class="btn btn-sm btn-primary">Back</a>
                                <a href="{{ route('funds.overview', $api['id']) }}" class="btn btn-sm btn-primary" title="Overview">
                                    <i class="fa fa-chart-area"></i>
                                </a>
                                @if($activeTradePortfolio)
                                <a href="{{ route('tradePortfolios.rebalance', [$activeTradePortfolio->id]) }}" class="btn btn-sm btn-warning" title="Edit Allocations">
                                    <i class="fa fa-balance-scale"></i>
                                </a>
                                @endif
                                <a href="/funds/{{ $api['id'] }}/trade_bands" class="btn btn-sm btn-primary" title="Trading Bands">
                                    <i class="fa fa-chart-bar"></i>
                                </a>
                                @isset($api['admin'])
                                    <a href="/funds/{{ $api['id'] }}/as_of/{{ $asOf }}?admin=0" class="btn btn-sm btn-primary" title="Switch to User View">
                                        <i class="fa fa-user"></i>
                                    </a>
                                @else
                                    @if(in_array(Auth::user()->email ?? '', ['admin@dev.familyfund.local', 'claude@test.local']))
                                        <a href="/funds/{{ $api['id'] }}/as_of/{{ $asOf }}" class="btn btn-sm btn-warning" title="Switch to Admin View">
                                            <i class="fa fa-user-shield"></i>
                                        </a>
                                    @endif
                                @endisset
                                <a href="/funds/{{ $api['id'] }}/pdf_as_of/{{ $asOf }}{{ isset($api['admin']) ? '' : '?admin=0' }}" class="btn btn-sm btn-primary" target="_blank" title="Download PDF">
                                    <i class="fa fa-file-pdf"></i>
                                </a>
                            </div>
                        </div>

                        {{-- Stats Row --}}
                        <div class="card-body py-3" style="background: #f0fdfa;">
                            <div class="row text-center">
                                <div class="col mb-3 mb-md-0" style="border-right: 1px solid #99f6e4;">
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #0d9488;">{{ $totalValue }}</div>
                                    <div class="text-muted text-uppercase small">Total Value</div>
                                </div>
                                <div class="col mb-3 mb-md-0" style="border-right: 1px solid #99f6e4;">
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #0d9488;">${{ number_format($sharePrice, 2) }}</div>
                                    <div class="text-muted text-uppercase small">Share Price</div>
                                </div>
                                @include('partials.highlights_growth', ['yearlyPerf' => $api['yearly_performance'] ?? [], 'allTimeOverride' => $allTimeReturn, 'showBorder' => isset($api['admin']) && $accountsCount > 0])
                                @if(isset($api['admin']) && $accountsCount > 0)
                                <div class="col" style="background: #fffbeb; border-radius: 6px; padding: 8px; margin: -8px 0;">
                                    <div style="font-size: 1.75rem; font-weight: 700; color: #d97706;">{{ $accountsCount }}</div>
                                    <div class="text-uppercase small" style="color: #d97706;">
                                        Accounts <span class="badge badge-warning">ADMIN</span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Admin: Allocated/Unallocated Visual (only show if there are user accounts) --}}
                        @if(isset($api['admin']) && $accountsCount > 0)
                        @php
                            $allocatedPct = $api['summary']['allocated_shares_percent'] ?? 0;
                            $unallocatedPct = $api['summary']['unallocated_shares_percent'] ?? (100 - $allocatedPct);
                            $allocatedShares = ($api['summary']['shares'] ?? 0) * $allocatedPct / 100;
                            $unallocatedShares = ($api['summary']['shares'] ?? 0) - $allocatedShares;
                            $allocatedValueCalc = $allocatedShares * ($api['summary']['share_value'] ?? 0);
                            $unallocatedValueCalc = $unallocatedShares * ($api['summary']['share_value'] ?? 0);
                        @endphp
                        <div class="card-body py-3" style="background: #fffbeb; border-top: 1px solid #99f6e4;">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge badge-warning">ADMIN</span>
                                <strong class="text-muted small ms-2">Share Allocation</strong>
                            </div>
                            {{-- Progress Bar --}}
                            <div class="d-flex mb-3" style="border-radius: 6px; overflow: hidden;">
                                <div style="width: {{ $allocatedPct }}%; background-color: #22c55e; padding: 8px 0; text-align: center; color: #ffffff; font-weight: 700; font-size: 13px;">
                                    {{ number_format($allocatedPct, 1) }}%
                                </div>
                                <div style="width: {{ $unallocatedPct }}%; background-color: #d97706; padding: 8px 0; text-align: center; color: #ffffff; font-weight: 700; font-size: 13px;">
                                    {{ number_format($unallocatedPct, 1) }}%
                                </div>
                            </div>
                            {{-- Allocated / Unallocated Boxes --}}
                            <div class="row">
                                <div class="col-6">
                                    <div style="background-color: #22c55e; padding: 12px 16px; border-radius: 6px; color: #ffffff;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <span style="font-size: 14px; font-weight: 700;">Allocated</span>
                                                <span style="background-color: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-left: 6px;">{{ number_format($allocatedPct, 1) }}%</span>
                                            </div>
                                            <div style="text-align: right;">
                                                <span style="font-size: 11px; opacity: 0.9;">{{ number_format($allocatedShares, 2) }} shares</span><br>
                                                <span style="font-size: 18px; font-weight: 700;">${{ number_format($allocatedValueCalc, 0) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div style="background-color: #d97706; padding: 12px 16px; border-radius: 6px; color: #ffffff;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <span style="font-size: 14px; font-weight: 700;">Unallocated</span>
                                                <span style="background-color: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-left: 6px;">{{ number_format($unallocatedPct, 1) }}%</span>
                                            </div>
                                            <div style="text-align: right;">
                                                <span style="font-size: 11px; opacity: 0.9;">{{ number_format($unallocatedShares, 2) }} shares</span><br>
                                                <span style="font-size: 18px; font-weight: 700;">${{ number_format($unallocatedValueCalc, 0) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Fund Details --}}
                        <div class="card-body pt-0 pb-3" style="background: #ffffff; border-top: 1px solid #99f6e4;">
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>As of:</strong> {{ $asOf }}</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Source:</strong> {{ $api['portfolio']['source'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Withdrawal Rule Retirement Goal Card --}}
            @if(isset($api['withdrawal_goal']))
                @include('funds.withdrawal_goal_card', ['withdrawalGoal' => $api['withdrawal_goal'], 'fund' => $fund ?? (object)['id' => $api['id']]])
            @elseif(isset($api['admin']))
                {{-- Show setup prompt for admins --}}
                <div class="row mb-4" id="section-withdrawal-goal">
                    <div class="col">
                        <div class="card" style="border: 2px dashed #9ca3af;">
                            <div class="card-body text-center py-4">
                                <i class="fa fa-bullseye fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Set Up Withdrawal Rule Retirement Goal</h5>
                                <p class="text-muted small mb-3">Track progress toward your retirement target based on the withdrawal rule.</p>
                                <a href="{{ route('funds.withdrawal_goal.edit', $api['id']) }}" class="btn btn-outline-primary">
                                    <i class="fa fa-plus me-1"></i> Configure Goal
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @php
                $hasTradePortfolios = $api['tradePortfolios']->count() > 0;
                $hasForecastData = !empty($api['linear_regression']['predictions']);
                $hasMultiplePortfolios = count($api['portfolios'] ?? []) > 1;

                // Calculate category totals with portfolio details
                $categoryTotals = [];
                $categoryColors = \App\Models\PortfolioExt::CATEGORY_COLORS;
                $categoryLabels = \App\Models\PortfolioExt::CATEGORY_LABELS;
                foreach ($api['portfolios'] ?? [] as $port) {
                    $cat = $port['category'] ?? 'unknown';
                    $portValue = floatval(str_replace(['$', ','], '', $port['total_value'] ?? 0));
                    $portName = $port['display_name'] ?? $port['source'] ?? 'Unknown';
                    $portId = $port['id'] ?? null;
                    if (!isset($categoryTotals[$cat])) {
                        $categoryTotals[$cat] = ['value' => 0, 'count' => 0, 'portfolios' => []];
                    }
                    $categoryTotals[$cat]['value'] += $portValue;
                    $categoryTotals[$cat]['count']++;
                    $categoryTotals[$cat]['portfolios'][] = ['id' => $portId, 'name' => $portName, 'value' => $portValue];
                }
                // Sort portfolios by value descending within each category
                foreach ($categoryTotals as &$catData) {
                    usort($catData['portfolios'], fn($a, $b) => abs($b['value']) <=> abs($a['value']));
                }
                $hasCategoryData = count($categoryTotals) > 1 || (count($categoryTotals) == 1 && !isset($categoryTotals['unknown']));

                // Calculate portfolio type totals with portfolio details
                $typeTotals = [];
                $typeColors = \App\Models\PortfolioExt::TYPE_COLORS;
                $typeLabels = \App\Models\PortfolioExt::TYPE_LABELS;
                foreach ($api['portfolios'] ?? [] as $port) {
                    $type = $port['type'] ?? 'unknown';
                    $portValue = floatval(str_replace(['$', ','], '', $port['total_value'] ?? 0));
                    $portName = $port['display_name'] ?? $port['source'] ?? 'Unknown';
                    $portId = $port['id'] ?? null;
                    if (!isset($typeTotals[$type])) {
                        $typeTotals[$type] = ['value' => 0, 'count' => 0, 'portfolios' => []];
                    }
                    $typeTotals[$type]['value'] += $portValue;
                    $typeTotals[$type]['count']++;
                    $typeTotals[$type]['portfolios'][] = ['id' => $portId, 'name' => $portName, 'value' => $portValue];
                }
                // Sort portfolios by value descending within each type
                foreach ($typeTotals as &$typeData) {
                    usort($typeData['portfolios'], fn($a, $b) => abs($b['value']) <=> abs($a['value']));
                }
                $hasTypeData = count($typeTotals) > 1 || (count($typeTotals) == 1 && !isset($typeTotals['unknown']));
            @endphp

            {{-- Category Summary (only if portfolios have categories) --}}
            @if($hasCategoryData && $hasMultiplePortfolios)
            <div class="row mb-4" id="section-category-summary">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white;">
                            <strong><i class="fa fa-layer-group me-2"></i>Category Summary</strong>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-light btn-sm expand-all-btn" data-section="category">
                                    <i class="fa fa-expand-alt me-1"></i><span class="d-none d-md-inline">Expand</span>
                                </button>
                                <button type="button" class="btn btn-outline-light btn-sm collapse-all-btn" data-section="category" style="display: none;">
                                    <i class="fa fa-compress-alt me-1"></i><span class="d-none d-md-inline">Collapse</span>
                                </button>
                            </div>
                        </div>
                        <div class="card-body py-3">
                            <div class="row">
                                @php
                                    $catOrder = ['retirement', 'taxable', 'education', 'cash', 'liability'];
                                    $sortedCategories = collect($categoryTotals)->sortBy(function($v, $k) use ($catOrder) {
                                        $pos = array_search($k, $catOrder);
                                        return $pos !== false ? $pos : 999;
                                    });
                                    $grandTotal = array_sum(array_column($categoryTotals, 'value'));
                                @endphp
                                @foreach($sortedCategories as $cat => $data)
                                    @php
                                        $color = $categoryColors[$cat] ?? '#6b7280';
                                        $label = $categoryLabels[$cat] ?? ucfirst($cat);
                                        $pct = $grandTotal > 0 ? ($data['value'] / $grandTotal) * 100 : 0;
                                        $isLiability = $cat === 'liability';
                                    @endphp
                                    <div class="col-md-{{ count($categoryTotals) <= 4 ? (12 / count($categoryTotals)) : 3 }} mb-2">
                                        <div class="p-3 rounded" style="background: {{ $color }}15; border-left: 4px solid {{ $color }};">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge" style="background: {{ $color }}; color: white;">{{ $label }}</span>
                                                    <span class="text-muted small ms-1">({{ $data['count'] }} portfolio{{ $data['count'] > 1 ? 's' : '' }})</span>
                                                </div>
                                                <span class="text-muted small">{{ number_format($pct, 1) }}%</span>
                                            </div>
                                            <div class="mt-2" style="font-size: 1.25rem; font-weight: 700; color: {{ $isLiability ? '#dc2626' : $color }};">
                                                {{ $isLiability ? '-' : '' }}${{ number_format(abs($data['value']), 0) }}
                                            </div>
                                            {{-- Show portfolios in this category --}}
                                            @php
                                                $allPortfolios = $data['portfolios'];
                                                $topPortfolios = array_slice($allPortfolios, 0, 3);
                                                $remainingPortfolios = array_slice($allPortfolios, 3);
                                                $catId = 'cat-' . Str::slug($cat);
                                            @endphp
                                            <table class="summary-table">
                                                @foreach($topPortfolios as $port)
                                                <tr>
                                                    <td>
                                                        @if($port['id'])
                                                            <a href="{{ route('portfolios.show', $port['id']) }}" style="color: inherit; text-decoration: none;" class="summary-link">{{ Str::limit($port['name'], 25) }}</a>
                                                        @else
                                                            {{ Str::limit($port['name'], 25) }}
                                                        @endif
                                                    </td>
                                                    <td style="color: {{ $isLiability ? '#dc2626' : $color }};">{{ $isLiability ? '-' : '' }}${{ number_format(abs($port['value']), 0) }}</td>
                                                </tr>
                                                @endforeach
                                                @if(count($remainingPortfolios) > 0)
                                                <tbody class="collapse" id="{{ $catId }}-more">
                                                    @foreach($remainingPortfolios as $port)
                                                    <tr>
                                                        <td>
                                                            @if($port['id'])
                                                                <a href="{{ route('portfolios.show', $port['id']) }}" style="color: inherit; text-decoration: none;" class="summary-link">{{ Str::limit($port['name'], 25) }}</a>
                                                            @else
                                                                {{ Str::limit($port['name'], 25) }}
                                                            @endif
                                                        </td>
                                                        <td style="color: {{ $isLiability ? '#dc2626' : $color }};">{{ $isLiability ? '-' : '' }}${{ number_format(abs($port['value']), 0) }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tr>
                                                    <td colspan="2">
                                                        <a href="#" class="expand-toggle" data-target="{{ $catId }}-more" style="color: {{ $color }}; text-decoration: none;">
                                                            <span class="expand-text">+{{ count($remainingPortfolios) }} more</span>
                                                            <span class="collapse-text" style="display: none;">show less</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                @endif
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if(isset($categoryTotals['liability']))
                            <div class="mt-3 pt-3" style="border-top: 1px solid #e5e7eb;">
                                @php
                                    $netWorth = $grandTotal;
                                    // Note: liability values should already be negative in the API
                                @endphp
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Net Worth</strong>
                                    <span style="font-size: 1.25rem; font-weight: 700; color: {{ $netWorth >= 0 ? '#0d9488' : '#dc2626' }};">
                                        ${{ number_format($netWorth, 0) }}
                                    </span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Portfolio Type Summary (only if portfolios have types) --}}
            @if($hasTypeData && $hasMultiplePortfolios)
            <div class="row mb-4" id="section-type-summary">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white;">
                            <strong><i class="fa fa-briefcase me-2"></i>Portfolio Type Summary</strong>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-light btn-sm expand-all-btn" data-section="type">
                                    <i class="fa fa-expand-alt me-1"></i><span class="d-none d-md-inline">Expand</span>
                                </button>
                                <button type="button" class="btn btn-outline-light btn-sm collapse-all-btn" data-section="type" style="display: none;">
                                    <i class="fa fa-compress-alt me-1"></i><span class="d-none d-md-inline">Collapse</span>
                                </button>
                            </div>
                        </div>
                        <div class="card-body py-3">
                            <div class="row">
                                @php
                                    // Sort types by value descending
                                    uasort($typeTotals, function($a, $b) {
                                        return abs($b['value']) <=> abs($a['value']);
                                    });
                                    $typeGrandTotal = array_sum(array_column($typeTotals, 'value'));
                                    $liabilityTypes = ['mortgage', 'loan', 'credit_card'];
                                @endphp
                                @foreach($typeTotals as $type => $data)
                                    @php
                                        $color = $typeColors[$type] ?? '#6b7280';
                                        $label = $typeLabels[$type] ?? ucfirst($type);
                                        $pct = $typeGrandTotal != 0 ? ($data['value'] / abs($typeGrandTotal)) * 100 : 0;
                                        $isLiability = in_array($type, $liabilityTypes);
                                    @endphp
                                    <div class="col-md-{{ count($typeTotals) <= 4 ? (12 / count($typeTotals)) : 3 }} mb-2">
                                        <div class="p-3 rounded" style="background: {{ $color }}15; border-left: 4px solid {{ $color }};">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge" style="background: {{ $color }}; color: white;">{{ $label }}</span>
                                                    <span class="text-muted small ms-1">({{ $data['count'] }} portfolio{{ $data['count'] > 1 ? 's' : '' }})</span>
                                                </div>
                                                <span class="text-muted small">{{ number_format(abs($pct), 1) }}%</span>
                                            </div>
                                            <div class="mt-2" style="font-size: 1.25rem; font-weight: 700; color: {{ $isLiability ? '#dc2626' : $color }};">
                                                {{ $isLiability ? '-' : '' }}${{ number_format(abs($data['value']), 0) }}
                                            </div>
                                            {{-- Show portfolios in this type --}}
                                            @php
                                                $allPortfolios = $data['portfolios'];
                                                $topPortfolios = array_slice($allPortfolios, 0, 3);
                                                $remainingPortfolios = array_slice($allPortfolios, 3);
                                                $typeId = 'type-' . Str::slug($type);
                                            @endphp
                                            <table class="summary-table">
                                                @foreach($topPortfolios as $port)
                                                <tr>
                                                    <td>
                                                        @if($port['id'])
                                                            <a href="{{ route('portfolios.show', $port['id']) }}" style="color: inherit; text-decoration: none;" class="summary-link">{{ Str::limit($port['name'], 25) }}</a>
                                                        @else
                                                            {{ Str::limit($port['name'], 25) }}
                                                        @endif
                                                    </td>
                                                    <td style="color: {{ $isLiability ? '#dc2626' : $color }};">{{ $isLiability ? '-' : '' }}${{ number_format(abs($port['value']), 0) }}</td>
                                                </tr>
                                                @endforeach
                                                @if(count($remainingPortfolios) > 0)
                                                <tbody class="collapse" id="{{ $typeId }}-more">
                                                    @foreach($remainingPortfolios as $port)
                                                    <tr>
                                                        <td>
                                                            @if($port['id'])
                                                                <a href="{{ route('portfolios.show', $port['id']) }}" style="color: inherit; text-decoration: none;" class="summary-link">{{ Str::limit($port['name'], 25) }}</a>
                                                            @else
                                                                {{ Str::limit($port['name'], 25) }}
                                                            @endif
                                                        </td>
                                                        <td style="color: {{ $isLiability ? '#dc2626' : $color }};">{{ $isLiability ? '-' : '' }}${{ number_format(abs($port['value']), 0) }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                                <tr>
                                                    <td colspan="2" class="text-end">
                                                        <a href="#" class="expand-toggle" data-target="{{ $typeId }}-more" style="color: {{ $color }}; text-decoration: none;">
                                                            <span class="expand-text">+{{ count($remainingPortfolios) }} more</span>
                                                            <span class="collapse-text" style="display: none;">show less</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                                @endif
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Group Summary (aggregate assets by display_group) --}}
            @php
                // Liability asset types (values should be negative)
                $liabilityAssetTypes = ['MORTGAGE', 'LOAN', 'CREDIT_CARD'];
                // Liability portfolio categories (for derived cash calculation)
                $liabilityCategories = ['liability'];

                // Aggregate assets from all portfolios by display_group
                $groupTotals = [];
                $derivedCashTotal = 0;
                $portfoliosToProcess = isset($api['portfolios']) ? $api['portfolios'] : [$api['portfolio']];
                foreach ($portfoliosToProcess as $port) {
                    // Calculate derived cash: Set Balance - Sum of Assets
                    $portTotalValue = floatval(str_replace(['$', ','], '', $port['total_value'] ?? 0));
                    $portCategory = $port['category'] ?? '';
                    $isLiabilityPortfolio = in_array($portCategory, $liabilityCategories);

                    $assetsSum = 0;
                    foreach ($port['assets'] ?? [] as $asset) {
                        $group = $asset['group'] ?? 'Other';
                        $assetType = $asset['type'] ?? '';
                        $isLiability = in_array($assetType, $liabilityAssetTypes);
                        $rawValue = floatval($asset['value'] ?? 0);
                        $assetValue = $isLiability ? -abs($rawValue) : $rawValue;
                        $assetsSum += abs($rawValue); // Sum absolute for comparison

                        if (!isset($groupTotals[$group])) {
                            $groupTotals[$group] = ['value' => 0, 'count' => 0, 'assets' => [], 'is_liability' => $isLiability];
                        }
                        // Store asset with ID and value for clickable links
                        $assetName = $asset['name'];
                        $assetId = $asset['id'] ?? null;
                        if (!isset($groupTotals[$group]['assets'][$assetName])) {
                            $groupTotals[$group]['assets'][$assetName] = ['value' => 0, 'id' => $assetId];
                            $groupTotals[$group]['count']++;
                        }
                        $groupTotals[$group]['assets'][$assetName]['value'] += $assetValue;
                        $groupTotals[$group]['value'] += $assetValue;
                        // If any asset in group is liability, mark group as liability
                        if ($isLiability) {
                            $groupTotals[$group]['is_liability'] = true;
                        }
                    }

                    // Calculate derived cash for non-liability portfolios
                    if (!$isLiabilityPortfolio && $portTotalValue > 0) {
                        $portDerivedCash = $portTotalValue - $assetsSum;
                        if ($portDerivedCash > 100) { // Only add if > $100 (avoid rounding noise)
                            $derivedCashTotal += $portDerivedCash;
                        }
                    }
                }

                // Add derived cash to Stability group if significant
                if ($derivedCashTotal > 100) {
                    if (!isset($groupTotals['Stability'])) {
                        $groupTotals['Stability'] = ['value' => 0, 'count' => 0, 'assets' => [], 'is_liability' => false];
                    }
                    $groupTotals['Stability']['value'] += $derivedCashTotal;
                    $groupTotals['Stability']['assets']['Cash (derived)'] = ['value' => $derivedCashTotal, 'id' => null];
                    $groupTotals['Stability']['count']++;
                }

                $hasGroupData = count($groupTotals) > 0;
                $groupGrandTotal = array_sum(array_column($groupTotals, 'value'));

                // Group colors - using standard scheme from stacked bar chart
                $groupColors = [
                    'Growth' => '#16a34a',
                    'Stability' => '#2563eb',
                    'Crypto' => '#d97706',
                    'Bonds' => '#9333ea',
                    'Real Estate' => '#0d9488',
                    'SP500' => '#059669',
                    'Vehicles' => '#64748b',
                    'Dividend' => '#0891b2',
                    'Other' => '#6b7280',
                ];

                // Sort groups by absolute value descending
                uasort($groupTotals, function($a, $b) {
                    return abs($b['value']) <=> abs($a['value']);
                });
            @endphp
            @if($hasGroupData)
            <div class="row mb-4" id="section-group-summary">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white;">
                            <strong><i class="fa fa-chart-pie me-2"></i>Group Summary (by Asset Type)</strong>
                            <div>
                                <button type="button" class="btn btn-outline-light btn-sm expand-all-btn me-1" data-section="group">
                                    <i class="fa fa-expand-alt me-1"></i><span class="d-none d-md-inline">Expand</span>
                                </button>
                                <button type="button" class="btn btn-outline-light btn-sm collapse-all-btn me-1" data-section="group" style="display: none;">
                                    <i class="fa fa-compress-alt me-1"></i><span class="d-none d-md-inline">Collapse</span>
                                </button>
                                <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-light me-1" title="Manage Asset Groups">
                                    <i class="fa fa-cog"></i>
                                </a>
                                <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseGroupSummary"
                                   role="button" aria-expanded="true" aria-controls="collapseGroupSummary">
                                    <i class="fa fa-chevron-down"></i>
                                </a>
                            </div>
                        </div>
                        <div class="collapse show" id="collapseGroupSummary">
                            <div class="card-body py-3">
                                <div class="row">
                                    @foreach($groupTotals as $group => $data)
                                        @php
                                            $isLiabilityGroup = $data['is_liability'] ?? false;
                                            $color = $isLiabilityGroup ? '#dc2626' : ($groupColors[$group] ?? \App\Support\UIColors::byIndex(crc32($group)));
                                            $pct = $groupGrandTotal != 0 ? ($data['value'] / abs($groupGrandTotal)) * 100 : 0;
                                        @endphp
                                        <div class="col-md-{{ count($groupTotals) <= 4 ? (12 / count($groupTotals)) : 3 }} mb-2">
                                            <div class="p-3 rounded" style="background: {{ $color }}15; border-left: 4px solid {{ $color }};">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <span class="badge" style="background: {{ $color }}; color: white;">{{ $group }}</span>
                                                        <span class="text-muted small ms-1">({{ $data['count'] }} asset{{ $data['count'] > 1 ? 's' : '' }})</span>
                                                    </div>
                                                    <span class="text-muted small">{{ number_format(abs($pct), 1) }}%</span>
                                                </div>
                                                <div class="mt-2" style="font-size: 1.25rem; font-weight: 700; color: {{ $color }};">
                                                    ${{ number_format($data['value'], 0) }}
                                                </div>
                                                {{-- Show top assets in this group --}}
                                                @php
                                                    // Sort by absolute value descending
                                                    uasort($data['assets'], fn($a, $b) => abs($b['value']) <=> abs($a['value']));
                                                    $allAssets = $data['assets'];
                                                    $topAssets = array_slice($allAssets, 0, 3, true);
                                                    $remainingAssets = array_slice($allAssets, 3, null, true);
                                                    $groupId = 'group-' . Str::slug($group);
                                                @endphp
                                                <table class="summary-table">
                                                    @foreach($topAssets as $assetName => $assetData)
                                                    <tr>
                                                        <td>
                                                            @if($assetData['id'])
                                                                <a href="{{ route('assets.show', $assetData['id']) }}" style="color: inherit; text-decoration: none;" class="summary-link">{{ Str::limit($assetName, 25) }}</a>
                                                            @else
                                                                {{ Str::limit($assetName, 25) }}
                                                            @endif
                                                        </td>
                                                        <td style="color: {{ $isLiabilityGroup ? '#dc2626' : $color }};">{{ $isLiabilityGroup ? '-' : '' }}${{ number_format(abs($assetData['value']), 0) }}</td>
                                                    </tr>
                                                    @endforeach
                                                    @if(count($remainingAssets) > 0)
                                                    <tbody class="collapse" id="{{ $groupId }}-more">
                                                        @foreach($remainingAssets as $assetName => $assetData)
                                                        <tr>
                                                            <td>
                                                                @if($assetData['id'])
                                                                    <a href="{{ route('assets.show', $assetData['id']) }}" style="color: inherit; text-decoration: none;" class="summary-link">{{ Str::limit($assetName, 25) }}</a>
                                                                @else
                                                                    {{ Str::limit($assetName, 25) }}
                                                                @endif
                                                            </td>
                                                            <td style="color: {{ $isLiabilityGroup ? '#dc2626' : $color }};">{{ $isLiabilityGroup ? '-' : '' }}${{ number_format(abs($assetData['value']), 0) }}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tr>
                                                        <td colspan="2">
                                                            <a href="#" class="expand-toggle" data-target="{{ $groupId }}-more" style="color: {{ $color }}; text-decoration: none;">
                                                                <span class="expand-text">+{{ count($remainingAssets) }} more</span>
                                                                <span class="collapse-text" style="display: none;">show less</span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    @endif
                                                </table>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Reusable Jump Bar --}}
            @include('partials.jump_bar', ['sections' => [
                ['id' => 'section-details', 'icon' => 'fa-info-circle', 'label' => 'Details'],
                ['id' => 'section-withdrawal-goal', 'icon' => 'fa-bullseye', 'label' => 'Withdrawal Goal', 'condition' => isset($api['withdrawal_goal']) || isset($api['admin'])],
                ['id' => 'section-category-summary', 'icon' => 'fa-layer-group', 'label' => 'Categories', 'condition' => $hasCategoryData && $hasMultiplePortfolios],
                ['id' => 'section-type-summary', 'icon' => 'fa-briefcase', 'label' => 'Types', 'condition' => $hasTypeData && $hasMultiplePortfolios],
                ['id' => 'section-group-summary', 'icon' => 'fa-chart-pie', 'label' => 'Groups', 'condition' => $hasGroupData],
                ['id' => 'section-brokerage-portfolios', 'icon' => 'fa-folder-open', 'label' => 'Portfolios', 'condition' => $hasMultiplePortfolios],
                ['id' => 'section-charts', 'icon' => 'fa-chart-line', 'label' => 'Charts'],
                ['id' => 'section-regression', 'icon' => 'fa-chart-area', 'label' => 'Forecast', 'condition' => $hasForecastData],
                ['id' => 'section-portfolios', 'icon' => 'fa-chart-bar', 'label' => 'Portfolios', 'condition' => $hasTradePortfolios],
                ['id' => 'section-allocation', 'icon' => 'fa-users', 'label' => 'Acct Alloc', 'condition' => isset($api['admin']) && $accountsCount > 0],
                ['id' => 'section-performance', 'icon' => 'fa-table', 'label' => 'Performance'],
                ['id' => 'section-trade-portfolios', 'icon' => 'fa-briefcase', 'label' => 'Trade Portfolios', 'condition' => $hasTradePortfolios],
                ['id' => 'section-assets-table', 'icon' => 'fa-coins', 'label' => 'Assets'],
                ['id' => 'section-transactions', 'icon' => 'fa-exchange-alt', 'label' => 'Transaction History', 'condition' => isset($api['admin'])],
                ['id' => 'section-accounts', 'icon' => 'fa-user-friends', 'label' => 'Accounts', 'condition' => isset($api['admin']) && $accountsCount > 0],
            ]])

            {{-- Portfolios Section (only show if multiple portfolios) --}}
            @if($hasMultiplePortfolios)
            <div class="row mb-4" id="section-brokerage-portfolios">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-folder-open mr-2"></i>Portfolios <span class="badge bg-light text-dark ms-2">{{ count($api['portfolios']) }}</span></strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapsePortfoliosList"
                               role="button" aria-expanded="true" aria-controls="collapsePortfoliosList">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapsePortfoliosList">
                            <div class="card-body">
                                @include('funds.portfolios_table', [
                                    'portfolios' => $api['portfolios'],
                                    'asOf' => $asOf,
                                    'showActions' => isset($api['admin']),
                                    'compact' => false
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Main Charts Row (Collapsible, start expanded) --}}
            <div class="row mb-4" id="section-charts">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-chart-line mr-2"></i>Monthly Value</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseMonthlyValue"
                               role="button" aria-expanded="true" aria-controls="collapseMonthlyValue">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseMonthlyValue">
                            <div class="card-body">
                                @php($addSP500 = true)
                                @include('funds.performance_line_graph')
                                @php($addSP500 = false)
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-chart-bar mr-2"></i>Yearly Value</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseYearlyValue"
                               role="button" aria-expanded="true" aria-controls="collapseYearlyValue">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseYearlyValue">
                            <div class="card-body">
                                @include('funds.performance_graph')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Forecast (Linear Regression) (Collapsible, start expanded) --}}
            @if($hasForecastData)
            <div class="row mb-4" id="section-regression">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-chart-area mr-2"></i>Forecast (Linear Regression)</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseForecast"
                               role="button" aria-expanded="true" aria-controls="collapseForecast">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseForecast">
                            <div class="card-body">
                                @include('funds.performance_line_graph_linreg')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-table mr-2"></i>Projection Table</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseProjection"
                               role="button" aria-expanded="true" aria-controls="collapseProjection">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseProjection">
                            <div class="card-body">
                                @include('funds.linreg_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Asset Performance by Group (Collapsible, start expanded) --}}
            @foreach($api['asset_monthly_performance'] as $group => $perf)
                <div class="row mb-4" id="section-group-{{ Str::slug($group) }}">
                    <div class="col">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                                <strong><i class="fa fa-layer-group mr-2"></i>Group {{ $group }} Performance</strong>
                                <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseGroup{{$group}}"
                                   role="button" aria-expanded="true" aria-controls="collapseGroup{{$group}}">
                                    <i class="fa fa-chevron-down"></i>
                                </a>
                            </div>
                            <div class="collapse show" id="collapseGroup{{$group}}">
                                <div class="card-body">
                                    @include('funds.performance_line_graph_assets')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Trade Portfolios Comparison by Group --}}
            @if($hasTradePortfolios)
            <div id="section-portfolios-groups">
                @include('trade_portfolios.stacked_bar_groups_graph')
            </div>

            {{-- Trade Portfolios Comparison by Symbol --}}
            <div id="section-portfolios">
                @include('trade_portfolios.stacked_bar_graph')
            </div>
            @endif

            {{-- Admin Accounts Allocation Chart (Collapsible, start expanded) --}}
            @if(isset($api['balances']) && isset($api['admin']) && $accountsCount > 0)
            <div class="row mb-4" id="section-allocation">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); color: #ffffff; border-bottom: 3px solid #b45309;">
                            <strong><i class="fa fa-users mr-2"></i>Accounts Allocation <span class="badge badge-warning">ADMIN</span></strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseAcctAlloc"
                               role="button" aria-expanded="true" aria-controls="collapseAcctAlloc">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseAcctAlloc">
                            <div class="card-body">
                                @include('funds.accounts_graph')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Performance Tables (Collapsible, start expanded) --}}
            <div class="row mb-4" id="section-performance">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-table mr-2"></i>Yearly Performance</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseYearlyPerf"
                               role="button" aria-expanded="true" aria-controls="collapseYearlyPerf">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseYearlyPerf">
                            <div class="card-body">
                                @php ($performance_key = 'yearly_performance')
                                @include('funds.performance_table')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-table mr-2"></i>Monthly Performance</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseMonthlyPerf"
                               role="button" aria-expanded="true" aria-controls="collapseMonthlyPerf">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseMonthlyPerf">
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                @php ($performance_key = 'monthly_performance')
                                @include('funds.performance_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Trade Portfolios Comparison (Collapsible, start expanded) --}}
            @if($hasTradePortfolios)
            <div class="row mb-4" id="section-portfolios-alt">
            <div class="col">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                    <strong><i class="fa fa-columns mr-2"></i>Trade Portfolios Comparison</strong>
                    <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseTradePortfoliosAlt"
                       role="button" aria-expanded="true" aria-controls="collapseTradePortfoliosAlt">
                        <i class="fa fa-chevron-down"></i>
                    </a>
                </div>
                <div class="collapse show" id="collapseTradePortfoliosAlt">
                    <div class="card-body">
                        @include('trade_portfolios.inner_show_alt')
                    </div>
                </div>
            </div>
            </div>
            </div>

            {{-- Trade Portfolios Details (Collapsible, start expanded) --}}
            <div class="row mb-4" id="section-trade-portfolios">
            <div class="col">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                    <strong><i class="fa fa-briefcase mr-2"></i>Trade Portfolios</strong>
                    <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseTradePortfolios"
                       role="button" aria-expanded="true" aria-controls="collapseTradePortfolios">
                        <i class="fa fa-chevron-down"></i>
                    </a>
                </div>
                <div class="collapse show" id="collapseTradePortfolios">
                    <div class="card-body">
                        @foreach($api['tradePortfolios'] as $tradePortfolio)
                            @php($extraTitle = '' . $tradePortfolio->id)
                            @php($tradePortfolioItems = $tradePortfolio->items)
                            @include('trade_portfolios.inner_show_compact')
                        @endforeach
                    </div>
                </div>
            </div>
            </div>
            </div>
            @endif

            {{-- Assets Table (Collapsible, start expanded) --}}
            <div class="row mb-4" id="section-assets-table">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #134e4a; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-coins mr-2"></i>Assets</strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseAssets"
                               role="button" aria-expanded="true" aria-controls="collapseAssets">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseAssets">
                            <div class="card-body">
                                @include('funds.assets_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transactions Table - Admin Only (Collapsible, start expanded) --}}
            @isset($api['admin'])
            <div class="row mb-4" id="section-transactions">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center" style="background: #b45309; color: white; position: relative; z-index: 10;">
                            <strong><i class="fa fa-exchange-alt mr-2"></i>Transaction History <span class="badge badge-warning ml-2">ADMIN</span></strong>
                            <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseTransactions"
                               role="button" aria-expanded="true" aria-controls="collapseTransactions">
                                <i class="fa fa-chevron-down"></i>
                            </a>
                        </div>
                        <div class="collapse show" id="collapseTransactions">
                            <div class="card-body">
                                @include('accounts.transactions_table')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endisset

            {{-- Accounts Table - Admin Only (Collapsible, start expanded) --}}
            @if(isset($api['admin']) && $accountsCount > 0)
                <div class="row mb-4" id="section-accounts">
                    <div class="col">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); color: #ffffff; border-bottom: 3px solid #b45309;">
                                <strong><i class="fa fa-user-friends mr-2"></i>Accounts <span class="badge badge-warning">ADMIN</span></strong>
                                <a class="btn btn-sm btn-outline-light" data-toggle="collapse" href="#collapseAccounts"
                                   role="button" aria-expanded="true" aria-controls="collapseAccounts">
                                    <i class="fa fa-chevron-down"></i>
                                </a>
                            </div>
                            <div class="collapse show" id="collapseAccounts">
                                <div class="card-body">
                                    @include('funds.accounts_table')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

@push('scripts')
<script>
$(document).ready(function() {
    // Expand/collapse toggle for individual items
    $('.expand-toggle').on('click', function(e) {
        e.preventDefault();
        var targetId = $(this).data('target');
        var $target = $('#' + targetId);
        var $expandText = $(this).find('.expand-text');
        var $collapseText = $(this).find('.collapse-text');

        if ($target.hasClass('show')) {
            $target.removeClass('show');
            $expandText.show();
            $collapseText.hide();
        } else {
            $target.addClass('show');
            $expandText.hide();
            $collapseText.show();
        }
    });

    // Expand all button
    $('.expand-all-btn').on('click', function() {
        var section = $(this).data('section');
        $('[id^="' + section + '-"]').addClass('show');
        $('[data-target^="' + section + '-"]').each(function() {
            $(this).find('.expand-text').hide();
            $(this).find('.collapse-text').show();
        });
        $(this).hide();
        $(this).siblings('.collapse-all-btn').show();
    });

    // Collapse all button
    $('.collapse-all-btn').on('click', function() {
        var section = $(this).data('section');
        $('[id^="' + section + '-"]').removeClass('show');
        $('[data-target^="' + section + '-"]').each(function() {
            $(this).find('.expand-text').show();
            $(this).find('.collapse-text').hide();
        });
        $(this).hide();
        $(this).siblings('.expand-all-btn').show();
    });
});
</script>
<style>
.expand-toggle { cursor: pointer; font-weight: 500; }
.expand-toggle:hover { text-decoration: underline !important; }
.collapse.show { display: inline !important; }
tbody.collapse { display: none; }
tbody.collapse.show { display: table-row-group !important; }
.summary-table { width: 100%; margin-top: 0.5rem; }
.summary-table td { padding: 2px 0; font-size: 0.8rem; }
.summary-table td:first-child { color: #6b7280; }
.summary-table td:last-child { text-align: right; font-weight: 500; }
.summary-link:hover { text-decoration: underline !important; }
</style>
@endpush
</x-app-layout>
